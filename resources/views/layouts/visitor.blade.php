<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $description ?? 'Bienvenue au Coffee Shop - Votre café artisanal au cœur de la ville' }}">
    <title>{{ $title ?? 'Accueil' }} | {{ config('app.name', 'Le Coffee Shop') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-stone-800 font-sans">

    {{-- Navigation --}}
    <nav class="bg-amber-900 text-amber-50 shadow-lg sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-amber-50 hover:text-amber-200 transition-colors">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2 21v-2h2V7h16v2h-2v12h2v2H2zm4-2h8v-2H6v2zm0-4h8v-2H6v2zm0-4h8V9H6v2zm10 6h2v-2h-2v2zm0-4h2v-2h-2v2z"/>
                        <path d="M18 3c0-1.1-.9-2-2-2H8C6.9 1 6 1.9 6 3v2h12V3z"/>
                    </svg>
                    <span class="text-xl font-bold tracking-wide">Le Coffee Shop</span>
                </a>

                {{-- Menu Desktop --}}
                <div class="hidden md:flex items-center gap-8">
                    <a href="{{ route('home') }}" class="text-amber-100 hover:text-white transition-colors font-medium {{ request()->routeIs('home') ? 'text-white border-b-2 border-amber-300' : '' }}">Accueil</a>
                    <a href="{{ route('menu') }}" class="text-amber-100 hover:text-white transition-colors font-medium {{ request()->routeIs('menu') ? 'text-white border-b-2 border-amber-300' : '' }}">Notre Menu</a>
                    <a href="{{ route('loyalty.create') }}" class="text-amber-100 hover:text-white transition-colors font-medium {{ request()->routeIs('loyalty.*') ? 'text-white border-b-2 border-amber-300' : '' }}">Fidélité</a>
                    <a href="{{ route('contact') }}" class="text-amber-100 hover:text-white transition-colors font-medium {{ request()->routeIs('contact') ? 'text-white border-b-2 border-amber-300' : '' }}">Contact</a>
                    @auth
                        <a href="{{ route('employee.dashboard') }}" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg font-medium transition-colors text-sm">
                            Espace Salarié
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium transition-colors text-sm border border-amber-500">
                            Connexion Salariés
                        </a>
                    @endauth
                </div>

                {{-- Menu Mobile Toggle --}}
                <button id="mobile-menu-btn" class="md:hidden text-amber-100 hover:text-white focus:outline-none" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            {{-- Menu Mobile --}}
            <div id="mobile-menu" class="hidden md:hidden pb-4">
                <div class="flex flex-col gap-2">
                    <a href="{{ route('home') }}" class="text-amber-100 hover:text-white py-2 font-medium">Accueil</a>
                    <a href="{{ route('menu') }}" class="text-amber-100 hover:text-white py-2 font-medium">Notre Menu</a>
                    <a href="{{ route('loyalty.create') }}" class="text-amber-100 hover:text-white py-2 font-medium">Fidélité</a>
                    <a href="{{ route('contact') }}" class="text-amber-100 hover:text-white py-2 font-medium">Contact</a>
                    @auth
                        <a href="{{ route('employee.dashboard') }}" class="text-amber-100 hover:text-white py-2 font-medium">Espace Salarié</a>
                    @else
                        <a href="{{ route('login') }}" class="text-amber-100 hover:text-white py-2 font-medium">Connexion Salariés</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-800 px-4 py-3 mx-auto max-w-6xl mt-4 rounded-r-lg" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 mx-auto max-w-6xl mt-4 rounded-r-lg" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- Contenu principal --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-stone-900 text-stone-300 mt-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-amber-400 font-bold text-lg mb-4">Le Coffee Shop</h3>
                    <p class="text-sm leading-relaxed">Un espace chaleureux où chaque tasse est préparée avec passion. Café de spécialité, ambiance conviviale.</p>
                </div>
                <div>
                    <h3 class="text-amber-400 font-bold text-lg mb-4">Horaires</h3>
                    <ul class="text-sm space-y-1">
                        <li>Lundi – Vendredi : 7h00 – 19h00</li>
                        <li>Samedi : 8h00 – 20h00</li>
                        <li>Dimanche : 9h00 – 18h00</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-amber-400 font-bold text-lg mb-4">Nous trouver</h3>
                    <address class="text-sm not-italic space-y-1">
                        <p>12 Rue des Arômes</p>
                        <p>75001 Paris</p>
                        <p class="mt-2">
                            <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300 underline">Nous contacter</a>
                        </p>
                    </address>
                </div>
            </div>
            <div class="border-t border-stone-700 mt-8 pt-6 text-center text-xs text-stone-500">
                <p>&copy; {{ date('Y') }} Le Coffee Shop. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
