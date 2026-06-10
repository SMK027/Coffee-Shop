<x-employee-layout title="Messages de contact">

    {{-- Filtres --}}
    <div class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-6 flex flex-wrap gap-2">
        @foreach(['all' => 'Tous'] + $statusLabels as $key => $label)
            <a href="{{ route('employee.contacts.index', $key !== 'all' ? ['status' => $key] : []) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                      {{ (request('status', 'all') === $key) ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($contacts->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">Aucun message trouvé.</div>
        @else
            <div class="divide-y divide-stone-50">
                @foreach($contacts as $contact)
                @php
                    $statusColors = ['new' => 'bg-blue-100 text-blue-700', 'read' => 'bg-stone-100 text-stone-600', 'replied' => 'bg-green-100 text-green-700', 'archived' => 'bg-stone-100 text-stone-400'];
                @endphp
                <a href="{{ route('employee.contacts.show', $contact) }}"
                   class="flex items-start gap-3 px-4 sm:px-6 py-4 hover:bg-stone-50 transition-colors {{ $contact->status === 'new' ? 'font-medium' : '' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mb-0.5">
                            <p class="text-sm text-stone-800 {{ $contact->status === 'new' ? 'font-semibold' : '' }}">{{ $contact->name }}</p>
                            <span class="text-xs text-stone-400 truncate">{{ $contact->email }}</span>
                        </div>
                        <p class="text-sm text-stone-500 truncate">{{ $contact->subject }}</p>
                        <div class="flex items-center gap-2 mt-1.5 sm:hidden">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$contact->status] ?? '' }}">{{ $contact->status_label }}</span>
                            <span class="text-xs text-stone-400">{{ $contact->created_at->format('d/m') }}</span>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-3 flex-shrink-0">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$contact->status] ?? '' }}">{{ $contact->status_label }}</span>
                        <p class="text-xs text-stone-400">{{ $contact->created_at->format('d/m/Y') }}</p>
                    </div>
                    <svg class="w-4 h-4 text-stone-300 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
            @if($contacts->hasPages())
                <div class="px-5 py-4 border-t border-stone-100">{{ $contacts->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>

</x-employee-layout>
