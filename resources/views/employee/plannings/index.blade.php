<x-employee-layout title="Plannings salariés">

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 mb-4 flex flex-wrap items-end gap-4">
        <form method="GET" action="{{ route('employee.plannings.index') }}" id="planning-employee-form" class="flex flex-col relative">
            <label for="planning-employee-search" class="text-xs font-medium text-stone-500 mb-1">Salarié</label>
            <input type="text" id="planning-employee-search" autocomplete="off" placeholder="Rechercher un salarié..."
                   value="{{ $selectedUser?->name }}"
                   class="border border-stone-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none min-w-[220px]">
            <input type="hidden" name="user_id" id="planning-employee-id" value="{{ $selectedUser?->id }}">
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
            <ul id="planning-employee-dropdown" class="hidden absolute top-full left-0 mt-1 w-full bg-white border border-stone-200 rounded-lg shadow-lg z-10 max-h-64 overflow-y-auto"></ul>
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('employee.plannings.index', ['user_id' => $selectedUser?->id, 'week' => $weekStart->copy()->subWeek()->toDateString()]) }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                ← Semaine précédente
            </a>
            <span class="text-sm font-medium text-stone-700 px-2">
                Semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}
            </span>
            <a href="{{ route('employee.plannings.index', ['user_id' => $selectedUser?->id, 'week' => $weekStart->copy()->addWeek()->toDateString()]) }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                Semaine suivante →
            </a>
        </div>
    </div>

    @if($selectedUser)
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div>
                    <h2 class="text-base font-semibold text-stone-800">Planning de {{ $selectedUser->name }}</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Total travaillé cette semaine : <span class="font-medium text-stone-700">{{ $totalWorkedLabel }}</span></p>
                </div>

                @if($isEditableWeek)
                    <button type="button" onclick="var p = document.getElementById('planning-edit-panel'); p.classList.toggle('hidden'); if (!p.classList.contains('hidden')) { p.scrollIntoView({behavior: 'smooth', block: 'start'}); }"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Modifier le planning
                    </button>
                @else
                    <span class="text-xs text-stone-400 italic">Semaine passée — lecture seule</span>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-7 gap-2">
                @foreach($days as $day)
                    <div class="border border-stone-100 rounded-lg overflow-hidden">
                        <div class="bg-stone-50 px-3 py-2 border-b border-stone-100">
                            <p class="text-xs font-semibold text-stone-700">{{ ucfirst($day->translatedFormat('l')) }}</p>
                            <p class="text-xs text-stone-400">{{ $day->format('d/m') }}</p>
                        </div>
                        <div class="p-2 space-y-1.5 min-h-[64px]">
                            @forelse($events->get($day->toDateString(), collect()) as $shift)
                                @if($shift->type === \App\Models\ScheduleShift::TYPE_WORK)
                                    <div class="bg-amber-50 border border-amber-100 rounded-lg px-2 py-1.5">
                                        <p class="text-xs font-medium text-amber-900">
                                            {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                                        </p>
                                        @if($shift->title)
                                            <p class="text-xs text-amber-700">{{ $shift->title }}</p>
                                        @endif
                                    </div>
                                @else
                                    <div class="{{ $shift->type === \App\Models\ScheduleShift::TYPE_LEAVE ? 'bg-green-50 border-green-100 text-green-800' : 'bg-red-50 border-red-100 text-red-800' }} border rounded-lg px-2 py-1.5">
                                        <p class="text-xs font-semibold">
                                            {{ $shift->typeLabel() }}
                                            @if(! $shift->isFullDay())
                                                ({{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }})
                                            @endif
                                        </p>
                                        @if($shift->title)
                                            <p class="text-xs opacity-80">{{ $shift->title }}</p>
                                        @endif
                                    </div>
                                @endif
                            @empty
                                <p class="text-xs text-stone-300 italic">—</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($isEditableWeek)
            <div id="planning-edit-panel" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-5 mb-4 space-y-4">
                <form method="POST" action="{{ route('employee.plannings.edit') }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

                    <p class="text-sm font-semibold text-amber-800 mb-1">Validation superviseur obligatoire</p>
                    <p class="text-xs text-amber-700 mb-4">La modification du planning exige une authentification superviseur supplémentaire.</p>

                    @include('employee.shared.supervisor-auth-fields')

                    <div class="flex justify-end mt-4">
                        <button type="submit"
                                class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                            Continuer vers l'édition
                        </button>
                    </div>
                </form>
            </div>
        @endif
    @else
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-10 text-center text-stone-500 text-sm">
            Aucun salarié disponible.
        </div>
    @endif

    @if($employees->isNotEmpty())
        <form action="{{ route('employee.plannings.pdf') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 space-y-5">
            @csrf
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">

            <div>
                <h2 class="text-base font-semibold text-stone-800">Générer le planning au format PDF</h2>
                <p class="text-xs text-stone-500 mt-1">
                    Sélectionnez un salarié pour un planning individuel, ou plusieurs pour un tableau de service — semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}.
                </p>
                @error('selected_users')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="overflow-x-auto border border-stone-100 rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium text-stone-600">Inclure</th>
                            <th class="px-4 py-2.5 text-left font-medium text-stone-600">Salarié</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($employees as $employee)
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <input type="checkbox"
                                           name="selected_users[]"
                                           value="{{ $employee->id }}"
                                           {{ $selectedUser && (int) $selectedUser->id === (int) $employee->id ? 'checked' : '' }}
                                           class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                                </td>
                                <td class="px-4 py-3 align-top text-stone-800">{{ $employee->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-4">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-amber-800">Validation superviseur obligatoire</p>
                    <p class="text-xs text-amber-700">La génération du planning au format PDF exige une authentification superviseur supplémentaire.</p>
                </div>

                @include('employee.shared.supervisor-auth-fields')
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Générer le PDF
                </button>
            </div>
        </form>
    @endif

    <script>
    (function () {
        const form     = document.getElementById('planning-employee-form');
        const search   = document.getElementById('planning-employee-search');
        const hidden   = document.getElementById('planning-employee-id');
        const dropdown = document.getElementById('planning-employee-dropdown');
        const searchUrl = @json(route('employee.plannings.employees.search'));
        let debounce;

        function closeDropdown() {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        }

        function render(results) {
            dropdown.innerHTML = '';
            if (!results.length) {
                dropdown.innerHTML = '<li class="px-3 py-2.5 text-sm text-stone-400 italic">Aucun salarié trouvé</li>';
                dropdown.classList.remove('hidden');
                return;
            }
            results.forEach(u => {
                const li = document.createElement('li');
                li.className = 'px-3 py-2.5 cursor-pointer text-sm hover:bg-stone-50';
                li.textContent = u.name;
                li.addEventListener('click', () => {
                    hidden.value = u.id;
                    search.value = u.name;
                    closeDropdown();
                    form.submit();
                });
                dropdown.appendChild(li);
            });
            dropdown.classList.remove('hidden');
        }

        search.addEventListener('input', () => {
            hidden.value = '';
            const q = search.value.trim();
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(r => r.json())
                    .then(render)
                    .catch(closeDropdown);
            }, 200);
        });

        document.addEventListener('click', (e) => {
            if (!form.contains(e.target)) closeDropdown();
        });
    })();
    </script>

</x-employee-layout>
