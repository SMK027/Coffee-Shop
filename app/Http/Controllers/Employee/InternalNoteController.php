<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\InternalNote;
use App\Models\InternalNoteAttachment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InternalNoteController extends Controller
{
    private const COMPOSE_AUTHORIZATION_KEY = 'internal_notes.compose_authorized_at';
    private const COMPOSE_AUTHORIZATION_TTL_SECONDS = 600;

    public function index(Request $request)
    {
        $notes = InternalNote::query()
            ->with(['author:id,name', 'attachments'])
            ->forUser($request->user())
            ->whereIn('display_location', ['employee_banner', 'inbox'])
            ->latest()
            ->paginate(20);

        return view('employee.internal-notes.index', compact('notes'));
    }

    public function create(Request $request)
    {
        $this->requireSuperAdminOrSupervisor(
            $request,
            'L’ouverture de l’éditeur de note interne nécessite une authentification superviseur.'
        );

        $request->session()->put(self::COMPOSE_AUTHORIZATION_KEY, time());

        return view('employee.internal-notes.create', [
            'users' => User::query()
                ->whereIn('global_role', ['superadmin', 'admin', 'moderator'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'global_role']),
        ]);
    }

    public function store(Request $request)
    {
        $authorizedAt = (int) $request->session()->get(self::COMPOSE_AUTHORIZATION_KEY, 0);
        abort_unless($authorizedAt >= time() - self::COMPOSE_AUTHORIZATION_TTL_SECONDS, 403, 'Une autorisation superviseur est requise avant l’envoi.');

        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:150'],
            'description'      => ['required', 'string', 'max:20000'],
            'display_location' => ['required', Rule::in(['global_banner', 'employee_banner', 'inbox'])],
            'target_role'      => ['nullable', Rule::in(['superadmin', 'admin', 'moderator'])],
            'recipient_ids'    => ['nullable', 'array', 'max:100'],
            'recipient_ids.*'  => ['integer', 'distinct', 'exists:users,id'],
            'background_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_color'       => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'font_family'      => ['required', Rule::in(['sans-serif', 'serif', 'monospace', 'cursive'])],
            'attachments'      => ['nullable', 'array', 'max:3'],
            'attachments.*'    => ['file', 'max:5120'],
        ]);

        if ($validated['display_location'] !== 'global_banner'
            && empty($validated['target_role'] ?? null)
            && empty($validated['recipient_ids'] ?? [])) {
            return back()->withInput()->withErrors([
                'recipient_ids' => 'Sélectionnez au moins un destinataire ou un type de salarié.',
            ]);
        }

        $note = DB::transaction(function () use ($request, $validated) {
            $note = InternalNote::create([
                'author_id'        => $request->user()->id,
                'title'            => $validated['title'],
                'description'      => $this->sanitizeDescription($validated['description']),
                'display_location' => $validated['display_location'],
                'target_role'      => $validated['display_location'] === 'global_banner' ? null : ($validated['target_role'] ?? null),
                'background_color' => $validated['background_color'],
                'text_color'       => $validated['text_color'],
                'font_family'      => $validated['font_family'],
            ]);

            if ($validated['display_location'] !== 'global_banner') {
                $note->recipients()->sync($validated['recipient_ids'] ?? []);
            }

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('internal-notes/' . $note->id, 'local');
                InternalNoteAttachment::create([
                    'internal_note_id' => $note->id,
                    'original_name'    => $file->getClientOriginalName(),
                    'path'             => $path,
                    'mime_type'        => $file->getMimeType() ?: 'application/octet-stream',
                    'size'             => $file->getSize(),
                ]);
            }

            return $note;
        });

        $request->session()->forget(self::COMPOSE_AUTHORIZATION_KEY);

        ActivityLogger::log(
            'internal_note.sent',
            'Note interne envoyée : ' . $note->title,
            'internal_note',
            $note->id,
            ['emplacement' => $note->display_location, 'type_destinataire' => $note->target_role]
        );

        return redirect()->route('employee.internal-notes.index')
            ->with('success', 'Note interne envoyée.');
    }

    public function download(Request $request, InternalNoteAttachment $attachment)
    {
        $attachment->loadMissing('note.recipients');
        abort_unless($attachment->note->isVisibleTo($request->user()), 403);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    private function sanitizeDescription(string $description): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3>';
        $html = strip_tags($description, $allowed);

        return preg_replace('/<(\\/?)(p|br|strong|b|em|i|u|ul|ol|li|h2|h3)(?:\\s[^>]*)?>/i', '<$1$2>', $html) ?? '';
    }
}
