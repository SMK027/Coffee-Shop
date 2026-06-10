<x-guest-layout>
    <div class="mb-4 text-sm text-stone-600">
        Bonjour <strong>{{ $employee->name }}</strong>, définissez votre nouveau mot de passe ci-dessous.
    </div>

    <form method="POST" action="{{ route('employee.password.reset', $token) }}">
        @csrf

        {{-- Champ caché : email pour accessibilité des gestionnaires de mots de passe --}}
        <input type="hidden" name="email" value="{{ $employee->email }}" autocomplete="username">

        <div>
            <x-input-label for="password" :value="'Nouveau mot de passe'" />
            <x-text-input id="password" class="block mt-1 w-full" type="password"
                          name="password" required autocomplete="new-password" />
            <p class="text-xs text-stone-400 mt-1">Minimum 8 caractères, avec des lettres et des chiffres.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Confirmer le mot de passe'" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                          name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                Enregistrer le nouveau mot de passe
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
