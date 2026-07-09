{{-- Boutons d'actions sur un compte salarié (partagé desktop/mobile) --}}
@if(!$user->isSuperAdmin() || auth()->user()->isSuperAdmin())
    <a href="{{ route('employee.users.edit', $user) }}"
       class="text-amber-600 hover:text-amber-700 text-xs font-medium px-2 py-1.5 rounded hover:bg-amber-50 transition-colors">
        Modifier
    </a>
    @if(auth()->user()->isSuperAdmin() && $user->id !== auth()->id())
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
