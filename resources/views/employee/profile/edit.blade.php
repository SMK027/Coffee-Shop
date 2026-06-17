<x-employee-layout title="Mon profil">

    <div class="max-w-2xl space-y-5">

        {{-- Informations personnelles --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Informations personnelles</h2>
            <p class="text-sm text-stone-500 mb-5">Mettez à jour votre nom et votre adresse e-mail.</p>

            <form method="POST" action="{{ route('employee.profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')

                <div>
                    <x-input-label for="name" value="Nom affiché *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                        :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" value="Adresse e-mail *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                        :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-1" :messages="$errors->get('email')" />
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Enregistrer
                    </button>
                    @if(session('status') === 'profile-updated')
                        <p class="text-sm text-green-600">Profil mis à jour.</p>
                    @endif
                </div>
            </form>
        </div>

        {{-- Mot de passe --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Mot de passe</h2>
            <p class="text-sm text-stone-500 mb-5">Utilisez un mot de passe long et aléatoire pour sécuriser votre compte.</p>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <x-input-label for="current_password" value="Mot de passe actuel *" />
                    <x-text-input id="current_password" name="current_password" type="password"
                        class="mt-1 block w-full" autocomplete="current-password" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password" value="Nouveau mot de passe *" />
                    <x-text-input id="password" name="password" type="password"
                        class="mt-1 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmer le mot de passe *" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                        class="mt-1 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Changer le mot de passe
                    </button>
                    @if(session('status') === 'password-updated')
                        <p class="text-sm text-green-600">Mot de passe mis à jour.</p>
                    @endif
                </div>
            </form>
        </div>

    </div>

</x-employee-layout>
