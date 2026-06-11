<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Employés | {{ config('app.name', 'Le Coffee Shop') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-amber-950 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <svg class="w-12 h-12 text-amber-400 mx-auto mb-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                <path d="M18 3c0-1.1-.9-2-2-2H8C6.9 1 6 1.9 6 3v2h12V3z"/>
            </svg>
            <h1 class="text-2xl font-bold text-white">Espace Employé</h1>
            <p class="text-amber-300 text-sm mt-1">Le Coffee Shop</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-2xl">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Adresse email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-stone-700 mb-1.5">Mot de passe</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="captcha" class="block text-sm font-medium text-stone-700 mb-1.5">Captcha</label>
                    <p class="text-xs text-stone-500 mb-2">{{ $captchaQuestion }}</p>
                    <input id="captcha" type="text" name="captcha" required value="{{ old('captcha') }}"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
                    @error('captcha')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-stone-600">
                        <input type="checkbox" name="remember" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                        Se souvenir de moi
                    </label>
                </div>
                <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white py-3 rounded-lg font-semibold text-sm transition-colors">
                    Se connecter
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-amber-300 hover:text-amber-200 text-sm transition-colors">← Retour au site</a>
        </div>
    </div>
</body>
</html>
