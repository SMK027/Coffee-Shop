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

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
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

        <p class="text-xs text-stone-500 mb-3">
            Cliquez-glissez sur la grille pour ajouter un créneau de travail ou une réunion/formation, cliquez sur une case de
            la ligne « Journée » pour poser un congé ou une absence, cliquez sur un événement existant pour le modifier ou le
            supprimer. Un congé occupe toute la journée et ne peut cohabiter avec rien d'autre. Une absence partielle peut
            cohabiter avec un service, mais une absence d'une journée entière ne le peut pas.
        </p>

        @if($bypassOpeningHours)
            <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-xs text-blue-700 mb-3">
                Ce salarié dispose du statut modérateur : les horaires peuvent être saisis même en dehors des heures
                d'ouverture de la boutique.
            </div>
        @endif

        <div id="planning-calendar" class="mb-5"></div>

        <form method="POST" action="{{ route('employee.plannings.update') }}" id="planning-save-form">
            @csrf
            <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            <div id="events-fields"></div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>

    {{-- Modale d'ajout/modification d'un événement --}}
    <div id="event-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-5 space-y-4">
            <h3 id="event-modal-title" class="text-base font-semibold text-stone-800">Ajouter un événement</h3>

            <div>
                <label for="event-type" class="block text-xs font-medium text-stone-600 mb-1">Type</label>
                <select id="event-type" class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    <option value="work">Travail</option>
                    <option value="meeting">Réunion / formation</option>
                    <option value="leave">Congé</option>
                    <option value="absence">Absence</option>
                </select>
            </div>

            <div id="event-fullday-wrapper" class="flex items-center gap-2">
                <input type="checkbox" id="event-fullday" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                <label for="event-fullday" class="text-xs text-stone-600">Journée entière</label>
            </div>

            <div id="event-time-wrapper" class="grid grid-cols-2 gap-2">
                <div>
                    <label for="event-start" class="block text-xs font-medium text-stone-600 mb-1">Début</label>
                    <input type="time" id="event-start" class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="event-end" class="block text-xs font-medium text-stone-600 mb-1">Fin</label>
                    <input type="time" id="event-end" class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label for="event-title" class="block text-xs font-medium text-stone-600 mb-1">Titre / motif (optionnel)</label>
                <input type="text" id="event-title" maxlength="120" placeholder="Ex : Service, Congés payés, Rendez-vous médical..."
                       class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <p id="event-modal-error" class="hidden text-xs text-red-600"></p>

            <div class="flex items-center pt-2">
                <button type="button" id="event-delete" class="hidden text-xs text-red-600 hover:text-red-800">Supprimer</button>
                <div class="flex gap-2 ml-auto">
                    <button type="button" id="event-cancel" class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Annuler</button>
                    <button type="button" id="event-save" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Valider</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.21/locales-all.global.min.js"></script>
    <script>
    (function () {
        const initialEvents = @js($initialEvents);
        const weekStart = @js($weekStart->toDateString());
        const openRanges = @js($openRanges);
        const openingHoursMargin = @js($openingHoursMargin);
        const bypassOpeningHours = @js($bypassOpeningHours);

        function pad(n) { return String(n).padStart(2, '0'); }
        function toDateStr(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function toTimeStr(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }

        function toMinutes(hhmm) {
            const parts = hhmm.split(':').map(Number);
            return parts[0] * 60 + parts[1];
        }

        // Bornes horaires autorisées (en minutes depuis minuit) pour une date donnée, marge de
        // tolérance incluse. Retourne null si la boutique est fermée ce jour-là (fermeture
        // régulière ou exceptionnelle) : aucun service ne peut alors y être planifié.
        function allowedBoundsFor(dateStr) {
            const range = openRanges[dateStr];
            if (!range) return null;
            return {
                start: toMinutes(range.from) - openingHoursMargin,
                end: toMinutes(range.to) + openingHoursMargin,
            };
        }

        const businessHours = bypassOpeningHours ? [] : Object.keys(openRanges).reduce(function (acc, dateStr) {
            const range = openRanges[dateStr];
            if (range) {
                const dow = new Date(dateStr + 'T00:00:00').getDay();
                acc.push({ daysOfWeek: [dow], startTime: range.from, endTime: range.to });
            }
            return acc;
        }, []);

        function typeLabel(type) {
            if (type === 'leave') return 'Congé';
            if (type === 'absence') return 'Absence';
            if (type === 'meeting') return 'Réunion / formation';
            return 'Travail';
        }

        function colorFor(type) {
            if (type === 'leave') return '#16a34a';
            if (type === 'absence') return '#dc2626';
            if (type === 'meeting') return '#2563eb';
            return '#b45309';
        }

        function displayTitle(type, rawTitle) {
            if (type === 'work') return rawTitle || 'Travail';
            return rawTitle ? typeLabel(type) + ' — ' + rawTitle : typeLabel(type);
        }

        function buildEventInput(type, dateStr, startTime, endTime, rawTitle) {
            const allDay = !startTime;
            return {
                title: displayTitle(type, rawTitle),
                start: allDay ? dateStr : (dateStr + 'T' + startTime + ':00'),
                end: allDay ? undefined : (dateStr + 'T' + endTime + ':00'),
                allDay: allDay,
                backgroundColor: colorFor(type),
                borderColor: colorFor(type),
                extendedProps: { type: type, rawTitle: rawTitle || '' },
            };
        }

        const calendarEl = document.getElementById('planning-calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            initialDate: weekStart,
            locale: 'fr',
            firstDay: 1,
            headerToolbar: false,
            allDaySlot: true,
            allDayText: 'Journée',
            slotMinTime: '06:00:00',
            slotMaxTime: '23:00:00',
            height: 'auto',
            selectable: true,
            editable: true,
            businessHours: businessHours,
            selectAllow: function (info) {
                // La ligne "Journée" (congés/absences) n'est pas soumise aux horaires d'ouverture.
                if (info.allDay || bypassOpeningHours) return true;

                const dateStr = info.startStr.slice(0, 10);
                const bounds = allowedBoundsFor(dateStr);
                if (!bounds) return false;

                const startMin = info.start.getHours() * 60 + info.start.getMinutes();
                const endMin = info.end.getHours() * 60 + info.end.getMinutes();
                return startMin >= bounds.start && endMin <= bounds.end;
            },
            events: initialEvents.map(function (e) {
                return buildEventInput(e.type, e.date, e.start_time, e.end_time, e.title);
            }),
            select: function (info) {
                calendar.unselect();
                const dateStr = info.startStr.slice(0, 10);
                const startTime = info.allDay ? null : info.startStr.slice(11, 16);
                const endTime = info.allDay ? null : info.endStr.slice(11, 16);
                openModal(null, dateStr, startTime, endTime);
            },
            eventClick: function (info) {
                openModal(info.event, null, null, null);
            },
        });
        calendar.render();

        // Modale
        const modal = document.getElementById('event-modal');
        const modalTitleEl = document.getElementById('event-modal-title');
        const typeSelect = document.getElementById('event-type');
        const fullDayCheckbox = document.getElementById('event-fullday');
        const timeWrapper = document.getElementById('event-time-wrapper');
        const startInput = document.getElementById('event-start');
        const endInput = document.getElementById('event-end');
        const titleInput = document.getElementById('event-title');
        const errorEl = document.getElementById('event-modal-error');
        const deleteBtn = document.getElementById('event-delete');
        const saveBtn = document.getElementById('event-save');
        const cancelBtn = document.getElementById('event-cancel');

        let editingEvent = null;
        let currentDate = null;

        function updateFieldsVisibility() {
            const type = typeSelect.value;
            if (type === 'leave') {
                fullDayCheckbox.checked = true;
                fullDayCheckbox.disabled = true;
                timeWrapper.classList.add('hidden');
            } else if (type === 'work' || type === 'meeting') {
                fullDayCheckbox.checked = false;
                fullDayCheckbox.disabled = true;
                timeWrapper.classList.remove('hidden');
            } else {
                fullDayCheckbox.disabled = false;
                timeWrapper.classList.toggle('hidden', fullDayCheckbox.checked);
            }
        }

        typeSelect.addEventListener('change', updateFieldsVisibility);
        fullDayCheckbox.addEventListener('change', updateFieldsVisibility);

        function showError(message) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }

        function openModal(existingEvent, date, startTime, endTime) {
            editingEvent = existingEvent;
            errorEl.classList.add('hidden');

            if (existingEvent) {
                modalTitleEl.textContent = 'Modifier l\'événement';
                deleteBtn.classList.remove('hidden');
                currentDate = toDateStr(existingEvent.start);
                typeSelect.value = existingEvent.extendedProps.type;
                fullDayCheckbox.checked = existingEvent.allDay;
                titleInput.value = existingEvent.extendedProps.rawTitle || '';
                startInput.value = existingEvent.allDay ? '' : toTimeStr(existingEvent.start);
                endInput.value = existingEvent.allDay || !existingEvent.end ? '' : toTimeStr(existingEvent.end);
            } else {
                modalTitleEl.textContent = 'Ajouter un événement';
                deleteBtn.classList.add('hidden');
                currentDate = date;
                typeSelect.value = startTime ? 'work' : 'leave';
                fullDayCheckbox.checked = !startTime;
                titleInput.value = '';
                startInput.value = startTime || '09:00';
                endInput.value = endTime || '12:00';
            }

            updateFieldsVisibility();
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            editingEvent = null;
        }

        // Un congé occupe la journée entière : rien d'autre ne peut cohabiter avec lui. Une
        // absence partielle peut cohabiter avec un service, mais une absence d'une journée
        // entière ne le peut pas. Une réunion/formation ne peut pas cohabiter avec un congé ou
        // une absence (partielle ou entière).
        function dayConsistencyError(type, isFullDay, dateStr, excludeEvent) {
            const dayEvents = calendar.getEvents()
                .filter(function (ev) { return ev !== excludeEvent && toDateStr(ev.start) === dateStr; })
                .map(function (ev) { return { type: ev.extendedProps.type, fullDay: ev.allDay }; });
            dayEvents.push({ type: type, fullDay: isFullDay });

            const hasLeave = dayEvents.some(function (e) { return e.type === 'leave'; });
            const hasWork = dayEvents.some(function (e) { return e.type === 'work'; });
            const hasAnyAbsence = dayEvents.some(function (e) { return e.type === 'absence'; });
            const hasFullDayAbsence = dayEvents.some(function (e) { return e.type === 'absence' && e.fullDay; });
            const hasMeeting = dayEvents.some(function (e) { return e.type === 'meeting'; });

            if (hasLeave && (hasWork || hasAnyAbsence)) {
                return 'Un congé ne peut pas cohabiter avec une autre activité le même jour.';
            }

            if (hasFullDayAbsence && hasWork) {
                return 'Une absence d\'une journée entière ne peut pas cohabiter avec un service le même jour.';
            }

            if (hasMeeting && (hasLeave || hasAnyAbsence)) {
                return 'Une réunion ou une formation ne peut pas être ajoutée un jour de congé ou d\'absence.';
            }

            return null;
        }

        saveBtn.addEventListener('click', function () {
            const type = typeSelect.value;
            const isFullDay = fullDayCheckbox.checked;
            const start = isFullDay ? null : startInput.value;
            const end = isFullDay ? null : endInput.value;

            if (!isFullDay && (!start || !end)) {
                showError('Heure de début et de fin requises.');
                return;
            }
            if (!isFullDay && end <= start) {
                showError('L\'heure de fin doit être après l\'heure de début.');
                return;
            }

            const conflictMessage = dayConsistencyError(type, isFullDay, currentDate, editingEvent);
            if (conflictMessage) {
                showError(conflictMessage);
                return;
            }

            if ((type === 'work' || type === 'meeting') && !bypassOpeningHours) {
                const bounds = allowedBoundsFor(currentDate);
                if (!bounds) {
                    showError('La boutique est fermée ce jour-là : aucun horaire ne peut y être planifié.');
                    return;
                }
                if (toMinutes(start) < bounds.start || toMinutes(end) > bounds.end) {
                    showError('Le créneau doit rester dans les horaires d\'ouverture (marge de 30 minutes tolérée).');
                    return;
                }
            }

            const rawTitle = titleInput.value.trim();
            const eventInput = buildEventInput(type, currentDate, start, end, rawTitle);

            if (editingEvent) {
                editingEvent.remove();
            }
            calendar.addEvent(eventInput);
            closeModal();
        });

        deleteBtn.addEventListener('click', function () {
            if (editingEvent) editingEvent.remove();
            closeModal();
        });

        cancelBtn.addEventListener('click', closeModal);

        // Sérialisation des événements du calendrier dans le formulaire au moment de l'enregistrement.
        document.getElementById('planning-save-form').addEventListener('submit', function () {
            const container = document.getElementById('events-fields');
            container.innerHTML = '';

            calendar.getEvents().forEach(function (ev, index) {
                const type = ev.extendedProps.type;
                const date = toDateStr(ev.start);
                const startTime = ev.allDay ? '' : toTimeStr(ev.start);
                const endTime = ev.allDay || !ev.end ? '' : toTimeStr(ev.end);
                const prefix = 'events[' + index + ']';

                [
                    [prefix + '[type]', type],
                    [prefix + '[date]', date],
                    [prefix + '[start_time]', startTime],
                    [prefix + '[end_time]', endTime],
                    [prefix + '[title]', ev.extendedProps.rawTitle || ''],
                ].forEach(function (pair) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = pair[0];
                    input.value = pair[1];
                    container.appendChild(input);
                });
            });
        });
    })();
    </script>

</x-employee-layout>
