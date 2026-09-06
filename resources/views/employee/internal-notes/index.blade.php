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
    </div>
</x-employee-layout>
