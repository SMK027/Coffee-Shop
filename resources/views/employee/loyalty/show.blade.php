<x-employee-layout title="Carte {{ chunk_split($loyaltyCard->card_number, 4, ' ') }}" subtitle="{{ $loyaltyCard->full_name }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

        {{-- Infos titulaire --}}
        <div class="space-y-4 sm:space-y-6">
            <div class="bg-gradient-to-br from-amber-800 to-amber-900 text-amber-50 rounded-xl shadow-sm p-6 text-center">
                <p class="text-amber-300 text-xs uppercase tracking-wider mb-2">Solde de points</p>
                <p class="text-5xl font-bold">{{ $loyaltyCard->points }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Titulaire</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-stone-500">Nom complet</dt><dd class="font-medium text-stone-800">{{ $loyaltyCard->full_name }}</dd></div>
                    <div><dt class="text-stone-500">Email</dt><dd class="text-stone-800">{{ $loyaltyCard->email }}</dd></div>
                    <div><dt class="text-stone-500">Téléphone</dt><dd class="text-stone-800">{{ $loyaltyCard->phone }}</dd></div>
                    <div><dt class="text-stone-500">Date de naissance</dt><dd class="text-stone-800">{{ $loyaltyCard->birth_date->format('d/m/Y') }} ({{ $loyaltyCard->age }} ans)</dd></div>
                    <div><dt class="text-stone-500">Carte créée le</dt><dd class="text-stone-800">{{ $loyaltyCard->created_at->format('d/m/Y') }}</dd></div>
                </dl>
            </div>

            @if(auth()->user()->isSuperAdmin())
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-2">Code PIN</h2>
                <p class="text-sm text-stone-500 mb-4">
                    Envoyez au titulaire un lien par email pour qu'il définisse un nouveau code PIN.
                    Le lien expire dans 30 minutes.
                </p>
                <form action="{{ route('employee.loyalty.pin.send', $loyaltyCard) }}" method="POST"
                      onsubmit="return confirm('Envoyer un lien de réinitialisation du code PIN à {{ $loyaltyCard->email }} ?');">
                    @csrf
                    <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Réinitialiser le code PIN par email
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Commandes raccordées --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
                <h2 class="font-semibold text-stone-800 px-4 sm:px-6 py-4 border-b border-stone-100">Commandes raccordées</h2>
                @if($loyaltyCard->orders->isEmpty())
                    <div class="px-6 py-12 text-center text-stone-500"><p>Aucune commande raccordée à cette carte.</p></div>
                @else
                    <div class="divide-y divide-stone-50">
                        @foreach($loyaltyCard->orders as $order)
                        <a href="{{ route('employee.orders.show', $order) }}" class="flex items-center justify-between px-4 sm:px-6 py-3 hover:bg-stone-50 transition-colors">
                            <div>
                                <p class="font-medium text-stone-800 text-sm">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }} · {{ $order->status_label }}</p>
                                <p class="text-xs text-stone-400">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-stone-800">{{ number_format($order->total_amount, 2, ',', ' ') }} €</p>
                                @if($order->points_credited)
                                    <p class="text-xs text-green-600">+{{ $order->points_awarded }} pts</p>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-employee-layout>
