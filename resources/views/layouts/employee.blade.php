<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Espace Employé' }} | {{ config('app.name', 'Le Coffee Shop') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-100 font-sans">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside class="w-64 bg-amber-900 text-amber-50 flex flex-col flex-shrink-0" id="sidebar">
            <div class="px-6 py-5 border-b border-amber-800">
                <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-3 hover:text-amber-200 transition-colors">
                    <svg class="w-7 h-7 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2 21v-2h2V7h16v2h-2v12h2v2H2zm4-2h8v-2H6v2zm0-4h8v-2H6v2zm0-4h8V9H6v2zm10 6h2v-2h-2v2zm0-4h2v-2h-2v2z"/>
                        <path d="M18 3c0-1.1-.9-2-2-2H8C6.9 1 6 1.9 6 3v2h12V3z"/>
                    </svg>
                    <span class="font-bold text-lg leading-tight">Coffee Shop</span>
                </a>
                <p class="text-amber-300 text-xs mt-1">Espace Employé</p>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                @php
                    $navItems = [
                        ['route' => 'employee.dashboard',      'label' => 'Tableau de bord', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'employee.orders.index',   'label' => 'Commandes',       'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['route' => 'employee.drinks.index',   'label' => 'Gestion du menu', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
                        ['route' => 'employee.testimonials.index', 'label' => 'Témoignages', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                        ['route' => 'employee.contacts.index', 'label' => 'Contacts',        'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        ['route' => 'employee.stats.index',    'label' => 'Statistiques',    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ];
                @endphp

                <ul class="space-y-1">
                    @foreach($navItems as $item)
                        <li>
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm font-medium
                                      {{ request()->routeIs($item['route'] . '*') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="px-3 py-4 border-t border-amber-800">
                <div class="flex items-center gap-3 px-3 py-2 mb-2">
                    <div class="w-8 h-8 bg-amber-700 rounded-full flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-amber-300 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-amber-200 hover:text-white hover:bg-amber-800 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Site public
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-amber-200 hover:text-white hover:bg-red-800 rounded-lg transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Déconnexion
                    </button>
                </form>
            </div>
        </aside>

        {{-- Contenu principal --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Header --}}
            <header class="bg-white border-b border-stone-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-stone-800">{{ $title ?? 'Espace Employé' }}</h1>
                    @isset($subtitle)
                        <p class="text-sm text-stone-500 mt-0.5">{{ $subtitle }}</p>
                    @endisset
                </div>
                <div class="flex items-center gap-3">
                    {{ $headerActions ?? '' }}
                </div>
            </header>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mx-6 mt-4 bg-green-50 border-l-4 border-green-500 text-green-800 px-4 py-3 rounded-r-lg" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mx-6 mt-4 bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-3 rounded-r-lg" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

</body>
</html>
