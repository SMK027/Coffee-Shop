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
    private const EDIT_AUTHORIZATION_NOTE_KEY = 'internal_notes.edit_authorized_note_id';
    private const COMPOSE_AUTHORIZATION_TTL_SECONDS = 600;

    public function index(Request $request)
    {
        $notes = InternalNote::query()
            ->with(['author:id,name', 'attachments'])
            ->forUser($request->user())
            ->whereIn('display_location', ['employee_banner', 'inbox'])
            ->latest()
            ->paginate(20);

        $sentNotes = InternalNote::query()
            ->with('attachments')
            ->where('author_id', $request->user()->id)
            ->latest()
            ->get();

        return view('employee.internal-notes.index', compact('notes', 'sentNotes'));
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

        $validated = $this->validateNote($request);

        $note = DB::transaction(function () use ($request, $validated) {
            $note = InternalNote::create([
                'author_id'        => $request->user()->id,
                'title'            => $validated['title'],
                'description'      => $this->sanitizeDescription($validated['description']),
                'display_location' => $validated['display_location'],
                'target_role'      => $validated['display_location'] === 'global_banner' || $validated['audience'] === 'all' ? null : ($validated['target_role'] ?? null),
                'is_for_all'       => $validated['display_location'] !== 'global_banner' && $validated['audience'] === 'all',
                'background_color' => $validated['background_color'],
                'text_color'       => $validated['text_color'],
                'font_family'      => $validated['font_family'],
                'expires_at'       => $validated['expires_at'] ?? null,
            ]);

            if ($validated['display_location'] !== 'global_banner' && $validated['audience'] !== 'all') {
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
            ['emplacement' => $note->display_location, 'type_destinataire' => $note->is_for_all ? 'Tous' : $note->target_role, 'expire_le' => $note->expires_at?->format('d/m/Y H:i')]
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

    public function edit(Request $request, InternalNote $note)
    {
        abort_unless((int) $note->author_id === (int) $request->user()->id, 403);

        $this->requireSuperAdminOrSupervisor(
            $request,
            'La modification d’une note interne nécessite une authentification superviseur.'
        );

        $request->session()->put(self::EDIT_AUTHORIZATION_NOTE_KEY, $note->id);
        $request->session()->put(self::COMPOSE_AUTHORIZATION_KEY, time());

        return view('employee.internal-notes.create', [
            'note' => $note->load('recipients', 'attachments'),
            'users' => $this->activeEmployees(),
        ]);
    }

    public function update(Request $request, InternalNote $note)
    {
        abort_unless((int) $note->author_id === (int) $request->user()->id, 403);
        abort_unless((int) $request->session()->get(self::EDIT_AUTHORIZATION_NOTE_KEY) === (int) $note->id, 403, 'Une autorisation superviseur est requise avant la modification.');

        $validated = $this->validateNote($request);
        $newAttachments = $request->file('attachments', []);
        if ($note->attachments()->count() + count($newAttachments) > 3) {
            return back()->withInput()->withErrors([
                'attachments' => 'Une note ne peut pas contenir plus de 3 pièces jointes.',
            ]);
        }

        $this->applyNoteData($note, $validated);

        foreach ($newAttachments as $file) {
            $path = $file->store('internal-notes/' . $note->id, 'local');
            InternalNoteAttachment::create([
                'internal_note_id' => $note->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }

        $request->session()->forget([self::EDIT_AUTHORIZATION_NOTE_KEY, self::COMPOSE_AUTHORIZATION_KEY]);

        ActivityLogger::log('internal_note.updated', 'Note interne modifiée : ' . $note->title, 'internal_note', $note->id);

        return redirect()->route('employee.internal-notes.index')->with('success', 'Note interne modifiée.');
    }

    public function destroy(Request $request, InternalNote $note)
    {
        abort_unless((int) $note->author_id === (int) $request->user()->id, 403);
        $this->requireSuperAdminOrSupervisor($request, 'La suppression d’une note interne nécessite une authentification superviseur.');

        $title = $note->title;
        Storage::disk('local')->delete($note->attachments()->pluck('path')->all());
        $note->delete();

        ActivityLogger::log('internal_note.deleted', 'Note interne supprimée : ' . $title, 'internal_note', $note->id);

        return redirect()->route('employee.internal-notes.index')->with('success', 'Note interne supprimée.');
    }

    private function activeEmployees()
    {
        return User::query()
            ->whereIn('global_role', ['superadmin', 'admin', 'moderator'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'global_role']);
    }

    private function validateNote(Request $request): array
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:150'],
            'description'      => ['required', 'string', 'max:20000'],
            'display_location' => ['required', Rule::in(['global_banner', 'employee_banner', 'inbox'])],
            'audience'         => ['required', Rule::in(['all', 'targeted'])],
            'target_role'      => ['nullable', Rule::in(['superadmin', 'admin', 'moderator'])],
            'recipient_ids'    => ['nullable', 'array', 'max:100'],
            'recipient_ids.*'  => ['integer', 'distinct', 'exists:users,id'],
            'background_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'text_color'       => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'font_family'      => ['required', Rule::in(collect(array_keys(InternalNote::FONT_FAMILIES)))],
            'expires_at'       => ['nullable', 'date', 'after:now'],
            'attachments'      => ['nullable', 'array', 'max:3'],
            'attachments.*'    => ['file', 'max:5120'],
        ]);

        if ($validated['display_location'] !== 'global_banner'
            && $validated['audience'] !== 'all'
            && empty($validated['target_role'] ?? null)
            && empty($validated['recipient_ids'] ?? [])) {
            abort(422, 'Sélectionnez au moins un destinataire ou un type de salarié.');
        }

        return $validated;
    }

    private function applyNoteData(InternalNote $note, array $validated): void
    {
        $note->update([
            'title'            => $validated['title'],
            'description'      => $this->sanitizeDescription($validated['description']),
            'display_location' => $validated['display_location'],
            'target_role'      => $validated['display_location'] === 'global_banner' || $validated['audience'] === 'all' ? null : ($validated['target_role'] ?? null),
            'is_for_all'       => $validated['display_location'] !== 'global_banner' && $validated['audience'] === 'all',
            'background_color' => $validated['background_color'],
            'text_color'       => $validated['text_color'],
            'font_family'      => $validated['font_family'],
            'expires_at'       => $validated['expires_at'] ?? null,
        ]);

        $note->recipients()->sync($validated['display_location'] !== 'global_banner' && $validated['audience'] !== 'all'
            ? ($validated['recipient_ids'] ?? [])
            : []);
    }

    private function sanitizeDescription(string $description): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3>';
        $html = strip_tags($description, $allowed);

        return preg_replace('/<(\\/?)(p|br|strong|b|em|i|u|ul|ol|li|h2|h3)(?:\\s[^>]*)?>/i', '<$1$2>', $html) ?? '';
    }
}
