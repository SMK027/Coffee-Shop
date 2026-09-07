{{-- Boutons d'actions sur un compte salarié (partagé desktop/mobile) --}}
@if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    <a href="{{ route('employee.users.edit', $user) }}"
       class="text-amber-600 hover:text-amber-700 text-xs font-medium px-2 py-1.5 rounded hover:bg-amber-50 transition-colors">
        Modifier
    </a>
    @if($user->quick_login_disabled)
        <form action="{{ route('employee.users.quick-login.reactivate', $user) }}" method="POST"
              onsubmit="return confirm('Réactiver la connexion rapide de {{ addslashes($user->name) }} ?')">
            @csrf
            <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium px-2 py-1.5 rounded hover:bg-green-50 transition-colors">
                Réactiver QR
            </button>
        </form>
    @endif
    @if((auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()) && $user->id !== auth()->id())
        <form action="{{ route('employee.users.reset-link', $user) }}" method="POST"
              onsubmit="return confirm('Envoyer un lien de réinitialisation à {{ addslashes($user->name) }} ?')">
            @csrf
            <button type="submit"
                    class="text-sky-500 hover:text-sky-700 text-xs font-medium px-2 py-1.5 rounded hover:bg-sky-50 transition-colors">
                Reset MDP
            </button>
        </form>
        <form action="{{ route('employee.users.toggle-activation', $user) }}" method="POST"
              onsubmit="return confirm('{{ $user->is_active ? 'Désactiver' : 'Réactiver' }} le compte de {{ addslashes($user->name) }} ?')">
            @csrf @method('PATCH')
            <button type="submit"
                    class="text-xs font-medium px-2 py-1.5 rounded transition-colors {{ $user->is_active ? 'text-red-500 hover:text-red-700 hover:bg-red-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50' }}">
                {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
            </button>
        </form>
    @endif
    @if(auth()->user()->isSuperAdmin() && !session()->has('impersonation.original_user_id') && $user->id !== auth()->id())
        <form action="{{ route('employee.users.take-control', $user) }}" method="POST"
              onsubmit="return confirm('Prendre le contrôle du compte de {{ addslashes($user->name) }} ?')">
            @csrf
            <button type="submit" class="text-violet-600 hover:text-violet-800 text-xs font-medium px-2 py-1.5 rounded hover:bg-violet-50 transition-colors">
                Prendre la main
            </button>
        </form>
    @endif
    @if($user->id !== auth()->id())
        <form action="{{ route('employee.users.destroy', $user) }}" method="POST"
              onsubmit="return confirm('Supprimer le compte de {{ addslashes($user->name) }} ?')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="text-red-400 hover:text-red-600 text-xs font-medium px-2 py-1.5 rounded hover:bg-red-50 transition-colors">
                Supprimer
            </button>
        </form>
    @endif
@else
    <span class="text-xs text-stone-400 italic">Accès limité</span>
@endif
