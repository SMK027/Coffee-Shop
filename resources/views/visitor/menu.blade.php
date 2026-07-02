<x-visitor-layout title="Notre Menu" description="Découvrez notre sélection de cafés artisanaux et boissons de spécialité">

    {{-- Hero --}}
    <div class="bg-amber-900 text-white py-16 text-center relative">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative max-w-3xl mx-auto px-4">
            <p class="text-amber-300 text-sm font-semibold uppercase tracking-widest mb-3">Nos boissons</p>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Notre Menu</h1>
            <p class="text-amber-100 text-lg">Cafés de spécialité, thés, infusions et créations maison préparés avec soin.</p>
        </div>
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 40L1440 40L1440 20C1200 40 960 0 720 20C480 40 240 0 0 20L0 40Z" fill="#fafaf9"/>
            </svg>
        </div>
    </div>

    {{-- Menu par catégories --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        {{-- Navigation catégories --}}
        @if($categories->count() > 1)
        <div class="flex flex-wrap gap-2 justify-center mb-12">
            @foreach($categories as $category)
                <a href="#cat-{{ $category->slug }}"
                   class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-5 py-2 rounded-full text-sm font-medium transition-colors">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
        @endif

        @foreach($categories as $category)
        <section id="cat-{{ $category->slug }}" class="mb-14">
            <div class="flex items-center gap-4 mb-8">
                <h2 class="text-2xl font-bold text-stone-800">{{ $category->name }}</h2>
                <div class="flex-1 h-px bg-amber-200"></div>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($category->availableDrinks as $drink)
                <div class="bg-white rounded-2xl border border-stone-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden group">
                    @if($drink->image)
                        <img src="{{ Storage::url($drink->image) }}" alt="{{ $drink->name }}"
                             loading="lazy"
                             class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="w-full h-36 bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center">
                            <svg class="w-12 h-12 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-bold text-stone-800 text-base">{{ $drink->name }}</h3>
                            <span class="font-bold text-amber-700 text-base flex-shrink-0">{{ number_format($drink->price, 2, ',', ' ') }} €</span>
                        </div>
                        @if($drink->description)
                            <p class="text-stone-500 text-sm leading-relaxed">{{ $drink->description }}</p>
                        @endif
                        @if($drink->loyalty_points > 0)
                            <div class="mt-3 inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-medium px-2 py-1 rounded-full">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ $drink->loyalty_points }} pt{{ $drink->loyalty_points > 1 ? 's' : '' }}
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endforeach

        @if($categories->isEmpty())
            <div class="text-center py-16 text-stone-500">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                </svg>
                <p class="text-lg font-medium">Le menu est en cours de mise à jour.</p>
                <p class="text-sm mt-2">Revenez bientôt !</p>
            </div>
        @endif

    </div>

</x-visitor-layout>
