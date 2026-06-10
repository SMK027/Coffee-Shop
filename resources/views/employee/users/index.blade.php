<x-employee-layout title="Gestion des employés">
    <x-slot name="headerActions">
        <a href="{{ route('employee.users.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvel employé
        </a>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($users->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm">Aucun employé trouvé.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-50 border-b border-stone-100">
                    <tr>
                        <th class="px-5 py-3 text-left font-medium text-stone-600">Employé</th>
                        <th class="px-5 py-3 text-left font-medium text-stone-600">Identifiant</th>
                        <th class="px-5 py-3 text-left font-medium text-stone-600">Email</th>
                        <th class="px-5 py-3 text-left font-medium text-stone-600">Rôle</th>
                        <th class="px-5 py-3 text-left font-medium text-stone-600">Membre depuis</th>
                        <th class="px-5 py-3 text-right font-medium text-stone-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">
                    @foreach($users as $user)
                    <tr class="hover:bg-stone-50 transition-colors {{ $user->id === auth()->id() ? 'bg-amber-50/50' : '' }}">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                                    {{ $user->isSuperAdmin() ? 'bg-amber-200 text-amber-800' : 'bg-stone-200 text-stone-600' }}">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-stone-800">{{ $user->name }}</p>
                                    @if($user->id === auth()->id())
                                        <p class="text-xs text-amber-600">Vous</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-stone-500 font-mono text-xs">{{ $user->username }}</td>
                        <td class="px-5 py-3 text-stone-500">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            @if($user->isSuperAdmin())
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">Super Admin</span>
                            @else
                                <span class="px-2.5 py-1 bg-stone-100 text-stone-600 text-xs font-medium rounded-full">Admin</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-stone-400 text-xs">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Seuls les superadmins peuvent modifier un autre superadmin --}}
                                @if(!$user->isSuperAdmin() || auth()->user()->isSuperAdmin())
                                    <a href="{{ route('employee.users.edit', $user) }}"
                                       class="text-amber-600 hover:text-amber-700 text-xs font-medium px-2 py-1 rounded hover:bg-amber-50 transition-colors">
                                        Modifier
                                    </a>
                                    {{-- Lien de reset : superadmin uniquement, pas sur son propre compte --}}
                                    @if(auth()->user()->isSuperAdmin() && $user->id !== auth()->id())
                                        <form action="{{ route('employee.users.reset-link', $user) }}" method="POST"
                                              onsubmit="return confirm('Envoyer un lien de réinitialisation de mot de passe à {{ addslashes($user->name) }} ({{ addslashes($user->email) }}) ?')">
                                            @csrf
                                            <button type="submit" class="text-sky-500 hover:text-sky-700 text-xs font-medium px-2 py-1 rounded hover:bg-sky-50 transition-colors"
                                                    title="Envoyer un lien de réinitialisation par email">
                                                Reset MDP
                                            </button>
                                        </form>
                                    @endif
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('employee.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Supprimer le compte de {{ addslashes($user->name) }} ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-600 text-xs font-medium px-2 py-1 rounded hover:bg-red-50 transition-colors">
                                                Supprimer
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-xs text-stone-400 italic">Accès limité</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
        <p class="text-xs text-amber-700">
            <strong>Rôles :</strong>
            <strong>Super Admin</strong> — accès complet, peut gérer les autres super admins.
            <strong>Admin</strong> — accès à l'espace employé complet.
        </p>
    </div>

</x-employee-layout>
