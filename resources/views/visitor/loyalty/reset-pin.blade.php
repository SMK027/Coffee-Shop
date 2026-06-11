<x-visitor-layout title="Nouveau code PIN" description="Définissez un nouveau code PIN pour votre carte de fidélité Le Coffee Shop.">

    <section class="bg-amber-900 text-amber-50 py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold mb-3">Nouveau code PIN</h1>
            <p class="text-amber-200 text-lg">Carte {{ chunk_split($card->card_number, 4, ' ') }}</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-sm text-red-700">
                    <p class="font-medium mb-1">Veuillez corriger les erreurs suivantes :</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-stone-100 p-6 sm:p-8">
                <p class="text-sm text-stone-500 mb-6">
                    Bonjour <strong>{{ $card->full_name }}</strong>, choisissez un nouveau code PIN
                    pour votre carte de fidélité.
                </p>

                <form action="{{ route('loyalty.pin.reset', $token) }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="pin" class="block text-sm font-medium text-stone-700 mb-1.5">Nouveau code PIN (4 à 6 chiffres) *</label>
                        <input type="password" name="pin" id="pin" required inputmode="numeric"
                               pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none tracking-widest">
                    </div>

                    <div>
                        <label for="pin_confirmation" class="block text-sm font-medium text-stone-700 mb-1.5">Confirmer le code PIN *</label>
                        <input type="password" name="pin_confirmation" id="pin_confirmation" required inputmode="numeric"
                               pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none tracking-widest">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                            Enregistrer mon nouveau code PIN
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

</x-visitor-layout>
