<x-visitor-layout title="Carte créée" description="Votre carte de fidélité a bien été créée.">

    <section class="py-16">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-stone-800 mb-3">Bienvenue {{ $card->first_name }} !</h1>
            <p class="text-stone-500 mb-8">Votre carte de fidélité a bien été créée. Conservez précieusement votre numéro de carte.</p>

            {{-- Carte visuelle --}}
            <div class="bg-gradient-to-br from-amber-800 to-amber-900 text-amber-50 rounded-2xl shadow-lg p-6 text-left mb-8">
                <div class="flex items-center justify-between mb-8">
                    <span class="font-bold text-lg tracking-wide">{{ config('app.name') }}</span>
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2 21v-2h2V7h16v2h-2v12h2v2H2zm4-2h8v-2H6v2zm0-4h8v-2H6v2zm0-4h8V9H6v2z"/>
                        <path d="M18 3c0-1.1-.9-2-2-2H8C6.9 1 6 1.9 6 3v2h12V3z"/>
                    </svg>
                </div>
                <p class="text-amber-300 text-xs uppercase tracking-wider mb-1">Numéro de carte</p>
                <p class="text-2xl font-mono font-bold tracking-widest mb-6">{{ chunk_split($card->card_number, 4, ' ') }}</p>
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-amber-300 text-xs uppercase tracking-wider mb-1">Titulaire</p>
                        <p class="font-medium">{{ $card->full_name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-amber-300 text-xs uppercase tracking-wider mb-1">Points</p>
                        <p class="font-bold text-xl">{{ $card->points }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800 text-left mb-8">
                Présentez votre numéro de carte et votre code PIN lors de vos commandes pour cumuler des points.
                Chaque euro dépensé vous rapporte <strong>{{ \App\Models\Setting::pointsPerEuro() }} points</strong>.
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('loyalty.balance.form') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    Consulter mes points
                </a>
                <a href="{{ route('home') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-3 rounded-lg font-medium transition-colors">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </section>

</x-visitor-layout>
