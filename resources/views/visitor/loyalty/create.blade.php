<x-visitor-layout title="Carte de fidélité" description="Créez votre carte de fidélité Le Coffee Shop et cumulez des points à chaque commande.">

    <section class="bg-amber-900 text-amber-50 py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold mb-3">Votre carte de fidélité</h1>
            <p class="text-amber-200 text-lg">Cumulez des points à chaque commande et profitez d'avantages exclusifs.</p>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

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
                    La création d'une carte est gratuite et réservée aux personnes âgées d'au moins
                    <strong>{{ \App\Models\LoyaltyCard::MIN_AGE }} ans</strong>.
                </p>

                <form action="{{ route('loyalty.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-stone-700 mb-1.5">Prénom *</label>
                            <input type="text" name="first_name" id="first_name" required maxlength="100"
                                   value="{{ old('first_name') }}"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom *</label>
                            <input type="text" name="last_name" id="last_name" required maxlength="100"
                                   value="{{ old('last_name') }}"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Adresse email *</label>
                        <input type="email" name="email" id="email" required maxlength="150"
                               value="{{ old('email') }}"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-stone-700 mb-1.5">Numéro de téléphone *</label>
                            <input type="tel" name="phone" id="phone" required maxlength="30"
                                   value="{{ old('phone') }}" placeholder="06 12 34 56 78"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                        <div>
                            <label for="birth_date" class="block text-sm font-medium text-stone-700 mb-1.5">Date de naissance *</label>
                            <input type="date" name="birth_date" id="birth_date" required
                                   max="{{ now()->subYears(\App\Models\LoyaltyCard::MIN_AGE)->toDateString() }}"
                                   value="{{ old('birth_date') }}"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="pin" class="block text-sm font-medium text-stone-700 mb-1.5">Code PIN (4 à 6 chiffres) *</label>
                            <input type="password" name="pin" id="pin" required inputmode="numeric"
                                   pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none tracking-widest">
                            <p class="text-xs text-stone-400 mt-1">Vous en aurez besoin pour utiliser votre carte.</p>
                        </div>
                        <div>
                            <label for="pin_confirmation" class="block text-sm font-medium text-stone-700 mb-1.5">Confirmer le code PIN *</label>
                            <input type="password" name="pin_confirmation" id="pin_confirmation" required inputmode="numeric"
                                   pattern="[0-9]{4,6}" maxlength="6" autocomplete="new-password"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none tracking-widest">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                            Créer ma carte de fidélité
                        </button>
                    </div>
                </form>
            </div>

            <p class="text-center text-sm text-stone-500 mt-6">
                Vous avez déjà une carte ?
                <a href="{{ route('loyalty.balance.form') }}" class="text-amber-700 hover:text-amber-600 font-medium underline">Consulter mes points</a>
            </p>
        </div>
    </section>

</x-visitor-layout>
