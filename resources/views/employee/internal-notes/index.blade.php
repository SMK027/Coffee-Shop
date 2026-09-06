<x-employee-layout title="Messagerie interne">
    <x-slot name="headerActions">
        <a href="{{ route('employee.internal-notes.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Nouvelle note
        </a>
    </x-slot>

    <div class="max-w-4xl space-y-4">
        @forelse($notes as $note)
            <article class="border border-stone-200 rounded-lg overflow-hidden bg-white shadow-sm">
                <header class="px-5 py-4 border-b border-stone-100" style="background-color: {{ $note->background_color }}; color: {{ $note->text_color }}; font-family: {{ $note->font_family }};">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-semibold text-base">{{ $note->title }}</h2>
                        <span class="text-xs opacity-75">{{ $note->created_at->format('d/m/Y à H:i') }}</span>
                    </div>
                    <p class="text-xs opacity-75 mt-1">Par {{ $note->author->name }}</p>
                </header>
                <div class="px-5 py-4 text-sm text-stone-700 leading-relaxed rich-note-content">{!! $note->description !!}</div>
                @if($note->attachments->isNotEmpty())
                    <footer class="px-5 py-3 bg-stone-50 border-t border-stone-100">
                        <p class="text-xs font-medium text-stone-500 mb-2">Pièces jointes</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($note->attachments as $attachment)
                                <a href="{{ route('employee.internal-notes.attachments.download', $attachment) }}" class="text-xs text-amber-700 hover:text-amber-900 underline">
                                    {{ $attachment->original_name }} ({{ number_format($attachment->size / 1024, 0) }} Ko)
                                </a>
                            @endforeach
                        </div>
                    </footer>
                @endif
            </article>
        @empty
            <div class="bg-white border border-stone-200 rounded-lg px-6 py-16 text-center text-stone-500 text-sm">
                Aucune note interne ne vous est destinée.
            </div>
        @endforelse

        @if($notes->hasPages())
            {{ $notes->links() }}
        @endif

        <section class="pt-6 border-t border-stone-200">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-semibold text-stone-800">Mes notes envoyées</h2>
                    <p class="text-xs text-stone-500 mt-1">La modification et la suppression nécessitent une validation superviseur.</p>
                </div>
                <span class="text-xs text-stone-500">{{ $sentNotes->count() }} note(s)</span>
            </div>

            <div class="space-y-3">
                @forelse($sentNotes as $note)
                    <article class="bg-white border border-stone-200 rounded-lg px-4 py-3 flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm text-stone-800 truncate">{{ $note->title }}</p>
                            <p class="text-xs text-stone-500 mt-1">
                                {{ $note->display_location === 'global_banner' ? 'Bannière globale' : ($note->display_location === 'employee_banner' ? 'Bannière salarié' : 'Messagerie') }}
                                · {{ $note->is_for_all ? 'Tous' : ($note->target_role ?: $note->recipients()->count() . ' destinataire(s)') }}
                                @if($note->expires_at) · Supprimée le {{ $note->expires_at->format('d/m/Y à H:i') }} @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3 text-sm font-medium">
                            <a href="{{ route('employee.internal-notes.edit', $note) }}" class="text-amber-700 hover:text-amber-900">Modifier</a>
                            <form action="{{ route('employee.internal-notes.destroy', $note) }}" method="POST" onsubmit="return confirm('Supprimer définitivement cette note interne ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Supprimer</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-stone-500">Vous n’avez encore envoyé aucune note.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-employee-layout>
