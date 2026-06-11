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
