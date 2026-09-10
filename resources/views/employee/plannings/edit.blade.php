<x-employee-layout title="Modifier le planning">

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div
        x-data="planningEditor({
            days: @js(collect($days)->map(fn ($day) => $day->toDateString())->values()),
            initialEvents: @js($initialEvents),
        })"
        class="bg-white rounded-xl shadow-sm border border-stone-100 p-5"
    >
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h1 class="text-lg font-semibold text-stone-800">Planning de {{ $selectedUser->name }}</h1>
                <p class="text-xs text-stone-500">Semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('employee.plannings.index', ['user_id' => $selectedUser->id, 'week' => $weekStart->toDateString()]) }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>

        <form method="POST" action="{{ route('employee.plannings.update') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">

            <div class="grid grid-cols-1 sm:grid-cols-7 gap-2">
                @foreach($days as $day)
                    @php($dateKey = $day->toDateString())
                    <div class="border border-stone-100 rounded-lg overflow-hidden">
                        <div class="bg-stone-50 px-3 py-2 border-b border-stone-100">
                            <p class="text-xs font-semibold text-stone-700">{{ ucfirst($day->translatedFormat('l')) }}</p>
                            <p class="text-xs text-stone-400">{{ $day->format('d/m') }}</p>
                        </div>
                        <div class="p-2 space-y-2">
                            <template x-for="(event, index) in eventsByDay['{{ $dateKey }}']" :key="event.uid">
                                <div class="border border-stone-200 rounded-lg p-2 space-y-1 bg-stone-50">
                                    <div class="flex gap-1">
                                        <input type="time" x-model="event.start_time" class="w-1/2 border border-stone-300 rounded px-1.5 py-1 text-xs">
                                        <input type="time" x-model="event.end_time" class="w-1/2 border border-stone-300 rounded px-1.5 py-1 text-xs">
                                    </div>
                                    <input type="text" x-model="event.title" placeholder="Intitulé (optionnel)" maxlength="120"
                                           class="w-full border border-stone-300 rounded px-1.5 py-1 text-xs">
                                    <button type="button" @click="removeEvent('{{ $dateKey }}', index)"
                                            class="text-xs text-red-600 hover:text-red-800">Supprimer</button>

                                    <input type="hidden" :name="'events[' + event.uid + '][date]'" value="{{ $dateKey }}">
                                    <input type="hidden" :name="'events[' + event.uid + '][start_time]'" x-model="event.start_time">
                                    <input type="hidden" :name="'events[' + event.uid + '][end_time]'" x-model="event.end_time">
                                    <input type="hidden" :name="'events[' + event.uid + '][title]'" x-model="event.title">
                                </div>
                            </template>

                            <button type="button" @click="addEvent('{{ $dateKey }}')"
                                    class="w-full text-xs text-amber-700 hover:text-amber-900 border border-dashed border-amber-300 rounded-lg py-1.5">
                                + Ajouter
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end mt-5">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    <script>
        function planningEditor({ days, initialEvents }) {
            let uidCounter = 0;

            return {
                eventsByDay: Object.fromEntries(days.map((day) => [day, []])),

                init() {
                    initialEvents.forEach((event) => {
                        if (this.eventsByDay[event.date]) {
                            this.eventsByDay[event.date].push({
                                uid: uidCounter++,
                                start_time: event.start_time,
                                end_time: event.end_time,
                                title: event.title ?? '',
                            });
                        }
                    });
                },

                addEvent(day) {
                    this.eventsByDay[day].push({
                        uid: uidCounter++,
                        start_time: '09:00',
                        end_time: '12:00',
                        title: '',
                    });
                },

                removeEvent(day, index) {
                    this.eventsByDay[day].splice(index, 1);
                },
            };
        }
    </script>

</x-employee-layout>
