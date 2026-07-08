<x-visitor-layout title="Mes points de fidélité" description="Consultez le solde de points de votre carte de fidélité.">

    <section class="py-12">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">

            <h1 class="text-2xl sm:text-3xl font-bold text-stone-800 text-center mb-8">Mes points de fidélité</h1>

            @isset($card)
                {{-- Solde --}}
                <div class="bg-gradient-to-br from-amber-800 to-amber-900 text-amber-50 rounded-2xl shadow-lg p-6 text-center mb-6">
                    <p class="text-amber-300 text-sm uppercase tracking-wider mb-2">Solde de points</p>
                    <p class="text-5xl font-bold mb-4">{{ $card->points }}</p>
                    <p class="text-amber-200 text-sm">{{ $card->full_name }}</p>
                    <p class="text-amber-300 text-xs font-mono mt-1">{{ chunk_split($card->card_number, 4, ' ') }}</p>
                </div>

                {{-- Historique des points --}}
                <div class="bg-white rounded-2xl shadow-sm border border-stone-100 overflow-hidden mb-6">
                    <h2 class="font-semibold text-stone-800 px-5 py-4 border-b border-stone-100">Historique de mes points</h2>
                    @if($card->pointAdjustments->isEmpty())
                        <div class="px-5 py-10 text-center text-stone-500 text-sm">
                            <p>Aucun mouvement de points pour le moment.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-stone-50">
                            @foreach($card->pointAdjustments as $adj)
                            @php
                                $isCredit = $adj->isCredit();
                                $source   = $adj->source;
                            @endphp
                            <li class="flex items-start justify-between gap-3 px-5 py-3">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div class="flex-shrink-0 mt-0.5 w-6 h-6 rounded-full flex items-center justify-center {{ $isCredit ? 'bg-green-100' : 'bg-red-100' }}">
                                        @if($isCredit)
                                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        @else
                                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-sm font-medium {{ $isCredit ? 'text-green-700' : 'text-red-700' }}">
                                                {{ $isCredit ? '+' : '−' }}{{ $adj->points }} pts
                                            </span>
                                            @if($source === 'order_debit')
                                                <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">R&eacute;duction</span>
                                            @elseif($source === 'order_credit')
                                                <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Points gagn&eacute;s</span>
                                            @elseif($source === 'refund')
                                                <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Remboursement</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600">Ajustement</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-stone-400 mt-0.5">{{ $adj->created_at->format('d/m/Y \à H:i') }}</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs text-stone-400">Solde : {{ $adj->balance_after }}</p>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Historique des commandes --}}
                <div class="bg-white rounded-2xl shadow-sm border border-stone-100 overflow-hidden mb-6">
                    <h2 class="font-semibold text-stone-800 px-5 py-4 border-b border-stone-100">Mes 5 dernières commandes</h2>
                    @if($card->orders->isEmpty())
                        <div class="px-5 py-10 text-center text-stone-500 text-sm">
                            <p>Aucune commande validée pour le moment.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-stone-50">
                            @foreach($card->orders as $order)
                            <li class="flex items-center justify-between px-5 py-3 gap-3">
                                <div>
                                    <p class="font-medium text-stone-800 text-sm">Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</p>
                                    <p class="text-xs text-stone-400">{{ $order->created_at->format('d/m/Y à H:i') }} · {{ $order->status_label }}</p>
                                    <a href="{{ route('loyalty.balance.order.show', $order) }}" class="inline-block mt-1 text-xs font-medium text-amber-700 hover:text-amber-600 underline">
                                        Voir le détail
                                    </a>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-stone-800">{{ number_format($order->total_amount, 2, ',', ' ') }} €</p>
                                    @if($order->points_credited)
                                        <p class="text-xs text-green-600">+{{ $order->points_awarded }} pts</p>
                                    @endif
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="text-center">
                    <a href="{{ route('loyalty.balance.form') }}" class="text-amber-700 hover:text-amber-600 text-sm font-medium underline">
                        Consulter une autre carte
                    </a>
                </div>
            @else
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="bg-white rounded-2xl shadow-sm border border-stone-100 p-6 sm:p-8">
                    <form action="{{ route('loyalty.balance') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label for="card_number" class="block text-sm font-medium text-stone-700 mb-1.5">Numéro de carte *</label>
                            <input type="text" name="card_number" id="card_number" required maxlength="20"
                                   value="{{ old('card_number') }}" placeholder="Ex. 3433 7056 2183"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono tracking-wider">
                            <p class="text-xs text-stone-400 mt-1">Vous pouvez saisir des espaces entre les chiffres.</p>
                        </div>
                        <div>
                            <label for="pin" class="block text-sm font-medium text-stone-700 mb-1.5">Code PIN *</label>
                            <input type="password" name="pin" id="pin" required inputmode="numeric" maxlength="6" autocomplete="off"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none tracking-widest">
                        </div>
                        <div>
                            <label for="captcha" class="block text-sm font-medium text-stone-700 mb-1.5">Captcha *</label>
                            <p class="text-xs text-stone-500 mb-2">{{ $captchaQuestion }}</p>
                            <input type="text" name="captcha" id="captcha" required
                                   value="{{ old('captcha') }}" placeholder="Votre reponse"
                                   class="w-full sm:w-64 border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            @error('captcha')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                            Consulter mes points
                        </button>
                    </form>
                </div>

                <p class="text-center text-sm text-stone-500 mt-6">
                    Pas encore de carte ?
                    <a href="{{ route('loyalty.create') }}" class="text-amber-700 hover:text-amber-600 font-medium underline">Créer ma carte</a>
                </p>
            @endisset
        </div>
    </section>

</x-visitor-layout>
