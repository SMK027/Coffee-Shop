<x-employee-layout title="{{ $contact->subject }}" subtitle="{{ $contact->name }} &lt;{{ $contact->email }}&gt;">
    <x-slot name="headerActions">
        <a href="{{ route('employee.contacts.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-3xl space-y-6">

        {{-- Message original --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="font-semibold text-stone-800">{{ $contact->name }}</p>
                    <p class="text-sm text-stone-500">{{ $contact->email }}</p>
                    <p class="text-xs text-stone-400 mt-1">{{ $contact->created_at->format('d/m/Y à H:i') }}</p>
                </div>
                @php
                    $statusColors = ['new' => 'bg-blue-100 text-blue-700', 'read' => 'bg-stone-100 text-stone-600', 'replied' => 'bg-green-100 text-green-700', 'archived' => 'bg-stone-100 text-stone-400'];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$contact->status] ?? '' }}">{{ $contact->status_label }}</span>
            </div>
            <h2 class="font-semibold text-stone-700 mb-3 text-sm">{{ $contact->subject }}</h2>
            <div class="text-stone-600 text-sm leading-relaxed whitespace-pre-wrap bg-stone-50 rounded-lg p-4">{{ $contact->message }}</div>
        </div>

        {{-- Réponse existante --}}
        @if($contact->reply)
        <div class="bg-green-50 rounded-xl border border-green-100 p-6">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                <p class="font-medium text-green-800 text-sm">Réponse envoyée le {{ $contact->replied_at?->format('d/m/Y à H:i') }}</p>
            </div>
            <div class="text-green-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $contact->reply }}</div>
        </div>
        @endif

        {{-- Formulaire de réponse --}}
        @if($contact->status !== 'archived')
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <h3 class="font-semibold text-stone-800 mb-4">{{ $contact->reply ? 'Modifier la réponse' : 'Répondre' }}</h3>
            <form action="{{ route('employee.contacts.reply', $contact) }}" method="POST" class="space-y-4">
                @csrf
                <textarea name="reply" rows="6" required minlength="10" maxlength="3000"
                          class="w-full border border-stone-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none"
                          placeholder="Rédigez votre réponse...">{{ old('reply', $contact->reply) }}</textarea>
                @error('reply')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                <div class="flex gap-3">
                    <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Enregistrer la réponse
                    </button>
                    <button type="submit" form="archive-contact-form" class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Archiver
                    </button>
                </div>
            </form>
            <form id="archive-contact-form" action="{{ route('employee.contacts.archive', $contact) }}" method="POST">
                @csrf @method('PATCH')
            </form>
        </div>
        @endif

    </div>

</x-employee-layout>
