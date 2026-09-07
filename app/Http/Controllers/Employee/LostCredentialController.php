<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\InternalNote;
use App\Models\Supervisor;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LostCredentialController extends Controller
{
    private const AUTHORIZATION_KEY = 'lost_credentials.authorized_at';
    private const AUTHORIZATION_TTL_SECONDS = 600;
    private const QUARANTINE_DAYS = 14;

    public function create(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request, 'Le signalement d’identifiants perdus nécessite une authentification superviseur.');
        $request->session()->put(self::AUTHORIZATION_KEY, time());

        return view('employee.lost-credentials.create', [
            'supervisors' => Supervisor::with(['superadmin:id,name', 'holderAdmin:id,name'])
                ->orderBy('supervisor_number')
                ->get(),
            'users' => User::query()
                ->whereIn('global_role', ['superadmin', 'admin', 'moderator'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'email', 'global_role', 'quick_login_disabled']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless((int) $request->session()->get(self::AUTHORIZATION_KEY, 0) >= time() - self::AUTHORIZATION_TTL_SECONDS, 403, 'Une autorisation superviseur est requise avant le signalement.');

        $validated = $request->validate([
            'supervisor_ids' => ['nullable', 'array'],
            'supervisor_ids.*' => ['integer', 'distinct', 'exists:supervisors,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        if (empty($validated['supervisor_ids']) && empty($validated['user_ids'])) {
            return back()->withInput()->withErrors(['supervisor_ids' => 'Sélectionnez au moins un superviseur ou un compte salarié.']);
        }

        $endsAt = now()->addDays(self::QUARANTINE_DAYS);
        $temporarySupervisors = DB::transaction(function () use ($validated, $endsAt) {
            $temporary = collect();

            $supervisors = Supervisor::with(['superadmin', 'holderAdmin'])
                ->whereIn('id', $validated['supervisor_ids'] ?? [])
                ->lockForUpdate()
                ->get();

            foreach ($supervisors as $supervisor) {
                $supervisor->update(['is_active' => false, 'quarantined_until' => $endsAt]);
                $temporary->push($this->issueTemporarySupervisor($supervisor, $endsAt));
            }

            User::whereIn('id', $validated['user_ids'] ?? [])
                ->update(['quick_login_disabled' => true]);

            return $temporary;
        });

        foreach ($temporarySupervisors as $temporary) {
            $recipient = $temporary->holderAdmin ?? $temporary->superadmin;
            if (! $recipient) {
                continue;
            }

            $this->sendInternalNote(
                $request->user(),
                $recipient,
                'Identifiants superviseur temporaires',
                "Le superviseur #{$temporary->replacedSupervisor->supervisor_number} est en quarantaine jusqu’au {$temporary->temporary_expires_at->format('d/m/Y à H:i')}.\n\nVotre superviseur temporaire est : {$temporary->supervisor_number}\nPIN temporaire : {$temporary->plain_pin}\n\nCe code ne peut pas être modifié et sera désactivé automatiquement à la fin de la quarantaine.",
                $temporary->temporary_expires_at
            );
        }

        $usersWithLostQuickLogin = User::whereIn('id', $validated['user_ids'] ?? [])->get();
        if ($usersWithLostQuickLogin->isNotEmpty()) {
            $this->sendInternalNoteToUsers(
                $request->user(),
                $usersWithLostQuickLogin,
                'Connexion rapide désactivée',
                'Votre QR code de connexion rapide a été signalé comme perdu. Il est désactivé jusqu’à la réinitialisation de votre mot de passe.'
            );
        }

        $request->session()->forget(self::AUTHORIZATION_KEY);
        ActivityLogger::log('credentials.reported_lost', 'Signalement d’identifiants perdus traité.', null, null, [
            'supervisor_ids' => $validated['supervisor_ids'] ?? [],
            'user_ids' => $validated['user_ids'] ?? [],
            'quarantine_ends_at' => $endsAt->toDateTimeString(),
        ]);

        return redirect()->route('employee.lost-credentials.create')
            ->with('success', 'Signalement traité. Les superviseurs temporaires ont été communiqués aux détenteurs concernés.');
    }

    private function issueTemporarySupervisor(Supervisor $lostSupervisor, $endsAt): Supervisor
    {
        $baseNumber = '7' . $lostSupervisor->supervisor_number;
        $number = $baseNumber;
        $suffix = 1;
        while (Supervisor::where('supervisor_number', $number)->exists()) {
            $number = $baseNumber . '-' . $suffix++;
        }

        $pin = (string) random_int(1000, 999999);
        $temporary = Supervisor::create([
            'supervisor_number' => $number,
            'password' => Hash::make($pin),
            'is_active' => true,
            'is_temporary' => true,
            'replaces_supervisor_id' => $lostSupervisor->id,
            'temporary_expires_at' => $endsAt,
            'superadmin_id' => $lostSupervisor->superadmin_id,
            'holder_admin_id' => $lostSupervisor->holder_admin_id,
        ]);
        $temporary->setRelation('replacedSupervisor', $lostSupervisor);
        $temporary->setAttribute('plain_pin', $pin);

        return $temporary;
    }

    private function sendInternalNote(User $author, User $recipient, string $title, string $description, $expiresAt = null): void
    {
        $this->sendInternalNoteToUsers($author, collect([$recipient]), $title, $description, $expiresAt);
    }

    private function sendInternalNoteToUsers(User $author, $recipients, string $title, string $description, $expiresAt = null): void
    {
        $note = InternalNote::create([
            'author_id' => $author->id,
            'title' => $title,
            'description' => nl2br(e($description)),
            'display_location' => 'inbox',
            'background_color' => '#FEF3C7',
            'text_color' => '#78350F',
            'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
            'expires_at' => $expiresAt,
        ]);
        $note->recipients()->attach($recipients->pluck('id')->all());
    }
}
