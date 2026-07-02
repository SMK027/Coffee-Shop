<x-employee-layout title="Paramètres boutique">

    @if(session('success'))
        <div class="mb-5 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-2xl">

        <p class="text-sm text-stone-500 mb-6">
            Ces informations apparaissent sur la page <strong>Contact</strong> et dans le <strong>pied de page</strong> du site public.
        </p>

        <form action="{{ route('employee.shop-settings.update') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Adresse --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <h2 class="font-semibold text-stone-800">Coordonnées</h2>

                <div>
                    <label for="address" class="block text-sm font-medium text-stone-700 mb-1.5">Adresse</label>
                    <textarea name="address" id="address" rows="3" required maxlength="300"
                              class="w-full border {{ $errors->has('address') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('address', $address) }}</textarea>
                    <p class="text-xs text-stone-400 mt-1">Une ligne par élément (rue, code postal et ville…).</p>
                    @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-stone-700 mb-1.5">Téléphone</label>
                    <input type="text" name="phone" id="phone" maxlength="50"
                           value="{{ old('phone', $phone) }}"
                           placeholder="Ex : 01 23 45 67 89"
                           class="w-full border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Email de contact</label>
                    <input type="email" name="email" id="email" required maxlength="150"
                           value="{{ old('email', $email) }}"
                           class="w-full border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Horaires --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Horaires d'ouverture</h2>
                <textarea name="hours" id="hours" rows="6" required maxlength="500"
                          class="w-full border {{ $errors->has('hours') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('hours', $hours) }}</textarea>
                <p class="text-xs text-stone-400 mt-1">Une ligne par plage horaire. Ex : <code>Lun – Ven : 7h00 – 19h00</code></p>
                @error('hours')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Enregistrer les modifications
                </button>
            </div>

        </form>

        {{-- Aperçu --}}
        <div class="mt-8 bg-stone-50 border border-stone-200 rounded-xl p-5">
            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wide mb-3">Aperçu actuel sur le site</p>
            <div class="grid sm:grid-cols-2 gap-4 text-sm text-stone-700">
                <div>
                    <p class="font-semibold text-stone-800 mb-1">Adresse</p>
                    <address class="not-italic text-stone-600 leading-relaxed">
                        {!! nl2br(e($address)) !!}
                    </address>
                    @if($phone)
                        <p class="mt-2 text-stone-600">{{ $phone }}</p>
                    @endif
                    <p class="mt-1 text-stone-600">{{ $email }}</p>
                </div>
                <div>
                    <p class="font-semibold text-stone-800 mb-1">Horaires</p>
                    <ul class="space-y-0.5 text-stone-600">
                        @foreach(array_filter(explode("\n", $hours)) as $line)
                            <li>{{ trim($line) }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    </div>

</x-employee-layout>
