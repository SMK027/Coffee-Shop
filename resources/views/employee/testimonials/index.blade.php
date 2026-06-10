<x-employee-layout title="Témoignages clients">

    {{-- Filtres --}}
    <div class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-6 flex flex-wrap gap-2">
        @foreach(['all' => 'Tous', 'pending' => 'En attente', 'published' => 'Publiés', 'rejected' => 'Rejetés'] as $key => $label)
            <a href="{{ route('employee.testimonials.index', $key !== 'all' ? ['status' => $key] : []) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                      {{ (request('status', 'all') === $key) ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse($testimonials as $t)
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <p class="font-semibold text-stone-800 text-sm">{{ $t->author_name }}</p>
                        <div class="flex text-amber-400 text-xs">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $t->rating ? 'text-amber-400' : 'text-stone-300' }}">★</span>
                            @endfor
                        </div>
                        @php
                            $badgeColors = ['pending' => 'bg-amber-100 text-amber-700', 'published' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-600'];
                            $badges = ['pending' => 'En attente', 'published' => 'Publié', 'rejected' => 'Rejeté'];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColors[$t->status] ?? '' }}">{{ $badges[$t->status] ?? $t->status }}</span>
                    </div>
                    <p class="text-stone-600 text-sm leading-relaxed">{{ $t->content }}</p>
                    <p class="text-xs text-stone-400 mt-2">{{ $t->created_at->format('d/m/Y à H:i') }}</p>
                </div>
                <div class="flex flex-col gap-2 flex-shrink-0">
                    @if($t->status !== 'published')
                    <form action="{{ route('employee.testimonials.publish', $t) }}" method="POST">
                        @csrf @method('PATCH')
                        <button class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 text-xs font-medium rounded-lg transition-colors w-full">Publier</button>
                    </form>
                    @endif
                    @if($t->status !== 'rejected')
                    <form action="{{ route('employee.testimonials.reject', $t) }}" method="POST">
                        @csrf @method('PATCH')
                        <button class="px-3 py-1.5 bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-medium rounded-lg transition-colors w-full">Rejeter</button>
                    </form>
                    @endif
                    <form action="{{ route('employee.testimonials.destroy', $t) }}" method="POST" onsubmit="return confirm('Supprimer ce témoignage ?')">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium rounded-lg transition-colors w-full">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 px-6 py-16 text-center text-stone-500">
                <p>Aucun témoignage trouvé.</p>
            </div>
        @endforelse
    </div>

    @if($testimonials->hasPages())
        <div class="mt-6">{{ $testimonials->withQueryString()->links() }}</div>
    @endif

</x-employee-layout>
