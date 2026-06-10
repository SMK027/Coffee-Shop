<x-visitor-layout title="Accueil" description="Bienvenue au Coffee Shop - Votre café artisanal au cœur de la ville">

    {{-- Hero Section --}}
    <section class="relative bg-amber-900 text-white overflow-hidden">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-36 text-center">
            <p class="text-amber-300 text-sm font-semibold uppercase tracking-widest mb-4">Bienvenue</p>
            <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
                Le Coffee Shop<br>
                <span class="text-amber-300">Un café, une histoire</span>
            </h1>
            <p class="text-amber-100 text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
                Des cafés de spécialité sélectionnés avec soin, préparés par des baristas passionnés dans un cadre chaleureux et convivial.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('menu') }}" class="bg-amber-500 hover:bg-amber-400 text-white px-8 py-3 rounded-full font-semibold text-lg transition-colors shadow-lg">
                    Découvrir notre menu
                </a>
                <a href="{{ route('contact') }}" class="border-2 border-amber-300 text-amber-100 hover:bg-amber-800 px-8 py-3 rounded-full font-semibold text-lg transition-colors">
                    Nous contacter
                </a>
            </div>
        </div>
        {{-- Décoration vague --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 60L1440 60L1440 30C1200 60 960 0 720 30C480 60 240 0 0 30L0 60Z" fill="#fafaf9"/>
            </svg>
        </div>
    </section>

    {{-- Présentation --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <p class="text-amber-600 font-semibold text-sm uppercase tracking-widest mb-3">Notre histoire</p>
                <h2 class="text-3xl font-bold text-stone-800 mb-6">Plus qu'un café,<br>une expérience</h2>
                <p class="text-stone-600 leading-relaxed mb-4">
                    Fondé avec passion, Le Coffee Shop est né d'un amour profond pour le café de qualité. Nous sélectionnons nos grains directement auprès de producteurs engagés, garantissant une traçabilité totale et un goût exceptionnel dans chaque tasse.
                </p>
                <p class="text-stone-600 leading-relaxed mb-6">
                    Notre équipe de baristas certifiés maîtrise chaque étape de la préparation, de la mouture à l'extraction, pour vous offrir une boisson à la hauteur de vos attentes.
                </p>
                <div class="grid grid-cols-3 gap-4 mt-8">
                    <div class="text-center p-4 bg-amber-50 rounded-xl">
                        <p class="text-3xl font-bold text-amber-700">15+</p>
                        <p class="text-sm text-stone-600 mt-1">Boissons au menu</p>
                    </div>
                    <div class="text-center p-4 bg-amber-50 rounded-xl">
                        <p class="text-3xl font-bold text-amber-700">100%</p>
                        <p class="text-sm text-stone-600 mt-1">Café artisanal</p>
                    </div>
                    <div class="text-center p-4 bg-amber-50 rounded-xl">
                        <p class="text-3xl font-bold text-amber-700">★ 4.9</p>
                        <p class="text-sm text-stone-600 mt-1">Note clients</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-amber-100 rounded-2xl h-48 md:h-64 flex items-center justify-center overflow-hidden">
                    <div class="text-center text-amber-700">
                        <svg class="w-16 h-16 mx-auto mb-2 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                        </svg>
                        <p class="text-sm font-medium opacity-60">Photo ambiance</p>
                    </div>
                </div>
                <div class="bg-stone-200 rounded-2xl h-48 md:h-64 mt-8 flex items-center justify-center overflow-hidden">
                    <div class="text-center text-stone-500">
                        <svg class="w-16 h-16 mx-auto mb-2 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                        </svg>
                        <p class="text-sm font-medium opacity-60">Photo barista</p>
                    </div>
                </div>
                <div class="bg-amber-200 rounded-2xl h-36 md:h-48 flex items-center justify-center overflow-hidden col-span-2">
                    <div class="text-center text-amber-800">
                        <svg class="w-16 h-16 mx-auto mb-2 opacity-40" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2 21v-2h2V7h16v2h-2v12h2v2H2zm4-2h8v-2H6v2z"/>
                        </svg>
                        <p class="text-sm font-medium opacity-60">Photo salle</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Nos valeurs --}}
    <section class="bg-amber-50 py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="text-amber-600 font-semibold text-sm uppercase tracking-widest mb-3">Ce qui nous distingue</p>
                <h2 class="text-3xl font-bold text-stone-800">Nos engagements</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-sm text-center">
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-7 h-7 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-stone-800 text-lg mb-3">Qualité Premium</h3>
                    <p class="text-stone-600 text-sm leading-relaxed">Des grains sélectionnés parmi les meilleures origines mondiales, torréfiés localement pour préserver leurs arômes.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 shadow-sm text-center">
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-7 h-7 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-stone-800 text-lg mb-3">Fait avec passion</h3>
                    <p class="text-stone-600 text-sm leading-relaxed">Chaque boisson est préparée à la commande par nos baristas passionnés, qui mettent tout leur savoir-faire dans chaque tasse.</p>
                </div>
                <div class="bg-white rounded-2xl p-8 shadow-sm text-center">
                    <div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-7 h-7 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-stone-800 text-lg mb-3">Commerce équitable</h3>
                    <p class="text-stone-600 text-sm leading-relaxed">Nous travaillons directement avec les producteurs pour assurer une rémunération juste et des pratiques durables.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Témoignages --}}
    @if($testimonials->isNotEmpty())
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-12">
            <p class="text-amber-600 font-semibold text-sm uppercase tracking-widest mb-3">Ils nous font confiance</p>
            <h2 class="text-3xl font-bold text-stone-800">Témoignages clients</h2>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($testimonials as $testimonial)
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-100">
                <div class="flex items-center mb-4">
                    <div class="flex text-amber-400">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $testimonial->rating)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @else
                                <svg class="w-4 h-4 text-stone-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endif
                        @endfor
                    </div>
                </div>
                <p class="text-stone-600 text-sm leading-relaxed mb-4 italic">"{{ $testimonial->content }}"</p>
                <p class="font-semibold text-stone-800 text-sm">{{ $testimonial->author_name }}</p>
            </div>
            @endforeach
        </div>

        {{-- Formulaire de témoignage --}}
        <div class="mt-12 bg-amber-50 rounded-2xl p-8">
            <h3 class="text-xl font-bold text-stone-800 mb-2">Partagez votre expérience</h3>
            <p class="text-stone-600 text-sm mb-6">Votre témoignage sera publié après validation par notre équipe.</p>
            <form action="{{ route('testimonial.submit') }}" method="POST" class="grid md:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label for="author_name" class="block text-sm font-medium text-stone-700 mb-1">Votre prénom *</label>
                    <input type="text" name="author_name" id="author_name" required maxlength="100"
                           value="{{ old('author_name') }}"
                           class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('author_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1">Note *</label>
                    <div class="flex gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="{{ $i }}" class="sr-only" {{ old('rating', 5) == $i ? 'checked' : '' }}>
                                <span class="text-2xl hover:text-amber-400 transition-colors {{ old('rating', 5) >= $i ? 'text-amber-400' : 'text-stone-300' }}">★</span>
                            </label>
                        @endfor
                    </div>
                    @error('rating')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="content" class="block text-sm font-medium text-stone-700 mb-1">Votre témoignage *</label>
                    <textarea name="content" id="content" required minlength="20" maxlength="1000" rows="3"
                              class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('content') }}</textarea>
                    @error('content')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium transition-colors text-sm">
                        Envoyer mon témoignage
                    </button>
                </div>
            </form>
        </div>
    </section>
    @endif

    {{-- CTA --}}
    <section class="bg-amber-900 text-white py-16">
        <div class="max-w-2xl mx-auto text-center px-4">
            <h2 class="text-3xl font-bold mb-4">Prêt à vivre l'expérience Coffee Shop ?</h2>
            <p class="text-amber-200 mb-8">Découvrez notre sélection de boissons artisanales et venez nous rendre visite.</p>
            <a href="{{ route('menu') }}" class="bg-white text-amber-900 hover:bg-amber-50 px-8 py-3 rounded-full font-semibold text-lg transition-colors inline-block">
                Voir notre menu
            </a>
        </div>
    </section>

</x-visitor-layout>
