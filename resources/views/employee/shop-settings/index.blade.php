<x-employee-layout title="Paramètres boutique">

    @if(session('success'))
        <div class="mb-5 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
            <ul class="space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @php
    $dayLabels = [
        'monday'    => 'Lundi',
        'tuesday'   => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday'  => 'Jeudi',
        'friday'    => 'Vendredi',
        'saturday'  => 'Samedi',
        'sunday'    => 'Dimanche',
    ];
    @endphp

    <div class="max-w-2xl space-y-6">

        {{-- ── Coordonnées ─────────────────────────────────── --}}
        <form action="{{ route('employee.shop-settings.update') }}" method="POST">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <h2 class="font-semibold text-stone-800">Coordonnées</h2>

                <div>
                    <label for="address" class="block text-sm font-medium text-stone-700 mb-1.5">Adresse</label>
                    <textarea name="address" id="address" rows="3" required maxlength="300"
                              class="w-full border {{ $errors->has('address') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('address', $address) }}</textarea>
                    @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-stone-700 mb-1.5">Téléphone</label>
                        <input type="text" name="phone" id="phone" maxlength="50"
                               value="{{ old('phone', $phone) }}" placeholder="01 23 45 67 89"
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
            </div>

            {{-- ── Horaires réguliers ───────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 mt-6">
                <h2 class="font-semibold text-stone-800 mb-4">Horaires d'ouverture réguliers</h2>

                <div class="space-y-2">
                    @foreach($dayLabels as $day => $label)
                    @php $d = $hours['regular'][$day] ?? ['open' => true, 'from' => '08:00', 'to' => '18:00']; @endphp
                    <div class="flex items-center gap-3 py-2 border-b border-stone-50 last:border-0"
                         x-data="{ isOpen: {{ $d['open'] ? 'true' : 'false' }} }">

                        <div class="w-28 flex items-center gap-2 flex-shrink-0">
                            <input type="checkbox" id="open-{{ $day }}" name="hours[{{ $day }}][open]" value="1"
                                   {{ $d['open'] ? 'checked' : '' }}
                                   @change="isOpen = $event.target.checked"
                                   class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                            <label for="open-{{ $day }}" class="text-sm font-medium text-stone-700 cursor-pointer select-none">
                                {{ $label }}
                            </label>
                        </div>

                        <div x-show="isOpen" class="flex items-center gap-2 flex-1">
                            <input type="time" name="hours[{{ $day }}][from]" value="{{ $d['from'] ?? '08:00' }}"
                                   class="border border-stone-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <span class="text-stone-400 text-sm">–</span>
                            <input type="time" name="hours[{{ $day }}][to]" value="{{ $d['to'] ?? '18:00' }}"
                                   class="border border-stone-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>

                        <span x-show="!isOpen" class="text-sm text-stone-400 italic">Fermé</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Enregistrer les coordonnées et horaires
                </button>
            </div>
        </form>

        {{-- ── Fermetures / Ouvertures exceptionnelles ─────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Fermetures et ouvertures exceptionnelles</h2>
            <p class="text-xs text-stone-400 mb-5">Ces dates remplacent les horaires réguliers sur la page publique.</p>

            @if(!empty($hours['exceptions']))
                <div class="space-y-2 mb-6">
                    @foreach(collect($hours['exceptions'])->sortBy('date') as $exc)
                    @php
                        $excDate = \Carbon\Carbon::parse($exc['date']);
                        $isPast  = $excDate->isPast() && !$excDate->isToday();
                    @endphp
                    <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-lg {{ $isPast ? 'bg-stone-50 opacity-50' : ($exc['open'] ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100') }}">
                        <div class="min-w-0">
                            <span class="text-sm font-medium text-stone-800">
                                {{ $excDate->isoFormat('dddd D MMMM YYYY') }}
                            </span>
                            <span class="mx-2 text-stone-300">·</span>
                            <span class="text-sm text-stone-600">{{ $exc['label'] }}</span>
                            @if($exc['open'])
                                <span class="ml-2 text-xs font-medium text-green-700">Ouvert {{ $exc['from'] ?? '' }} – {{ $exc['to'] ?? '' }}</span>
                            @else
                                <span class="ml-2 text-xs font-medium text-red-600">Fermé</span>
                            @endif
                            @if($isPast)
                                <span class="ml-2 text-xs text-stone-400">(passé)</span>
                            @endif
                        </div>
                        <form action="{{ route('employee.shop-settings.exception.remove', $exc['date']) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette exception ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-stone-400 hover:text-red-500 transition-colors flex-shrink-0" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-stone-400 italic mb-5">Aucune exception renseignée.</p>
            @endif

            <form action="{{ route('employee.shop-settings.exception.add') }}" method="POST"
                  x-data="{ excOpen: '0' }"
                  class="border-t border-stone-100 pt-5 space-y-4">
                @csrf
                <p class="text-sm font-medium text-stone-700">Ajouter une exception</p>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-1">Date</label>
                        <input type="date" name="date" required
                               min="{{ now()->format('Y-m-d') }}"
                               value="{{ old('date') }}"
                               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-1">Libellé</label>
                        <input type="text" name="label" required maxlength="100"
                               placeholder="Ex : Noël, Fête nationale…"
                               value="{{ old('label') }}"
                               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-stone-700">
                        <input type="radio" name="open" value="0" x-model="excOpen" class="text-red-500 focus:ring-red-400" checked>
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span> Fermé
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-stone-700">
                        <input type="radio" name="open" value="1" x-model="excOpen" class="text-green-500 focus:ring-green-400">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span> Ouverture exceptionnelle
                        </span>
                    </label>
                </div>

                <div x-show="excOpen === '1'" class="flex items-center gap-2">
                    <label class="text-xs text-stone-500">De</label>
                    <input type="time" name="from" value="{{ old('from', '09:00') }}"
                           class="border border-stone-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    <label class="text-xs text-stone-500">à</label>
                    <input type="time" name="to" value="{{ old('to', '17:00') }}"
                           class="border border-stone-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                </div>

                <div>
                    <button type="submit"
                            class="bg-stone-700 hover:bg-stone-600 text-white px-5 py-2 rounded-lg font-medium text-sm transition-colors">
                        + Ajouter l'exception
                    </button>
                </div>
            </form>
        </div>

    </div>

</x-employee-layout>
