<x-employee-layout title="Signaler des identifiants perdus">
    <x-slot name="headerActions">
        <a href="{{ route('employee.dashboard') }}" class="text-stone-500 hover:text-stone-700 text-sm">Retour au tableau de bord</a>
    </x-slot>

    <form action="{{ route('employee.lost-credentials.store') }}" method="POST" class="max-w-5xl space-y-6">
        @csrf

        <div class="bg-red-50 border border-red-200 rounded-lg p-5 text-sm text-red-800">
            <p class="font-semibold">Mesure de sécurité immédiate</p>
            <p class="mt-1">Les QR de connexion sélectionnés resteront bloqués jusqu'à la réinitialisation du mot de passe. Les superviseurs sélectionnés sont désactivés et placés en quarantaine pendant 14 jours ; un remplaçant temporaire est envoyé à leur détenteur.</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <section class="bg-white border border-stone-200 rounded-lg overflow-hidden">
            <header class="px-5 py-4 border-b border-stone-100">
                <h2 class="font-semibold text-stone-800">QR codes superviseur perdus</h2>
                <p class="text-xs text-stone-500 mt-1">Chaque superviseur reçoit un remplaçant temporaire commençant par 7, valable jusqu'à la fin de la quarantaine.</p>
            </header>
            <div class="divide-y divide-stone-100">
                @forelse($supervisors as $supervisor)
                    <label class="flex items-center gap-4 px-5 py-4 {{ $supervisor->is_temporary ? 'opacity-50' : 'hover:bg-stone-50 cursor-pointer' }}">
                        <input type="checkbox" name="supervisor_ids[]" value="{{ $supervisor->id }}" @disabled($supervisor->is_temporary) class="rounded border-stone-300 text-red-600 focus:ring-red-500">
                        <div class="flex-1 min-w-0">
                            <p class="font-mono font-medium text-sm text-stone-800">{{ $supervisor->supervisor_number }}</p>
                            <p class="text-xs text-stone-500 mt-1">Responsable : {{ $supervisor->superadmin?->name ?? 'Non défini' }} · Détenteur : {{ $supervisor->holderAdmin?->name ?? $supervisor->superadmin?->name ?? 'Non défini' }}</p>
                        </div>
                        @if($supervisor->quarantined_until?->isFuture())
                            <span class="text-xs font-medium text-red-700">Quarantaine jusqu'au {{ $supervisor->quarantined_until->format('d/m/Y') }}</span>
                        @elseif($supervisor->is_temporary)
                            <span class="text-xs font-medium text-amber-700">Temporaire</span>
                        @endif
                    </label>
                @empty
                    <p class="px-5 py-8 text-sm text-stone-500">Aucun superviseur disponible.</p>
                @endforelse
            </div>
        </section>

        <section class="bg-white border border-stone-200 rounded-lg overflow-hidden">
            <header class="px-5 py-4 border-b border-stone-100">
                <h2 class="font-semibold text-stone-800">QR codes de connexion rapide perdus</h2>
                <p class="text-xs text-stone-500 mt-1">La connexion rapide reste indisponible pour ces comptes jusqu'à la réinitialisation de leur mot de passe.</p>
            </header>
            <div class="divide-y divide-stone-100">
                @foreach($users as $user)
                    <label class="flex items-center gap-4 px-5 py-4 hover:bg-stone-50 cursor-pointer">
                        <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked($user->quick_login_disabled) class="rounded border-stone-300 text-red-600 focus:ring-red-500">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm text-stone-800">{{ $user->name }}</p>
                            <p class="text-xs text-stone-500 mt-1">{{ $user->username }} · {{ $user->email }}</p>
                        </div>
                        @if($user->quick_login_disabled)<span class="text-xs font-medium text-red-700">Déjà bloqué</span>@endif
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex gap-3">
            <button type="submit" class="bg-red-700 hover:bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium">Signaler et sécuriser</button>
            <a href="{{ route('employee.dashboard') }}" class="bg-stone-200 hover:bg-stone-300 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium">Annuler</a>
        </div>
    </form>
</x-employee-layout>
