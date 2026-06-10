{{-- Formulaire partagé create/edit --}}
<div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">

    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <label for="name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom complet *</label>
            <input type="text" name="name" id="name" required maxlength="100"
                   value="{{ old('name', $user->name ?? '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="username" class="block text-sm font-medium text-stone-700 mb-1.5">Identifiant (login) *</label>
            <input type="text" name="username" id="username" required maxlength="50"
                   value="{{ old('username', $user->username ?? '') }}"
                   placeholder="ex: jean.dupont"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono">
            @error('username')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Adresse email *</label>
        <input type="email" name="email" id="email" required maxlength="150"
               value="{{ old('email', $user->email ?? '') }}"
               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
        @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Mot de passe : champ visible uniquement en modification --}}
    @if(isset($user))
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <label for="password" class="block text-sm font-medium text-stone-700 mb-1.5">
                Mot de passe (laisser vide pour conserver)
            </label>
            <input type="password" name="password" id="password"
                   autocomplete="new-password"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            <p class="text-xs text-stone-400 mt-1">Minimum 8 caractères, avec lettres et chiffres.</p>
            @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-stone-700 mb-1.5">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   autocomplete="new-password"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
        </div>
    </div>
    @else
    {{-- À la création, un lien de définition du mot de passe est envoyé par email --}}
    <div class="flex items-start gap-3 bg-sky-50 border border-sky-200 rounded-lg px-4 py-3 text-sm text-sky-700">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <span>Un email sera envoyé à l'adresse renseignée avec un lien pour définir le mot de passe. Ce lien est valable <strong>30 minutes</strong>.</span>
    </div>
    @endif

    @if(auth()->user()->isSuperAdmin())
    <div>
        <label for="global_role" class="block text-sm font-medium text-stone-700 mb-1.5">Rôle *</label>
        <select name="global_role" id="global_role" required
                class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            <option value="admin" {{ old('global_role', $user->global_role ?? 'admin') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="superadmin" {{ old('global_role', $user->global_role ?? '') === 'superadmin' ? 'selected' : '' }}>Super Admin</option>
        </select>
        @error('global_role')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        <p class="text-xs text-stone-400 mt-1">Les Super Admins peuvent gérer tous les comptes et accéder à toutes les fonctionnalités.</p>
    </div>
    @else
        {{-- Les admins ne peuvent créer que des admins --}}
        <input type="hidden" name="global_role" value="admin">
    @endif

</div>
