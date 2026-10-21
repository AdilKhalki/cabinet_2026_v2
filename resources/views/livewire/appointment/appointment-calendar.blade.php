<div>
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-calendar"></i> Calendrier des Rendez-vous</h5>
            @if(count($doctors) > 1 && !auth()->user()->isMedecin())
                <div class="card-header-right" wire:ignore>
                    <select id="calendarDoctorFilter" class="form-control" style="width:200px;display:inline-block;">
                        <option value="">Tous les médecins</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">Dr. {{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="card-block">
            <div id="fullcalendar" wire:ignore></div>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/fr.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calEl = document.getElementById('fullcalendar');
        if (!calEl) return;

        const doctorSelect = document.getElementById('calendarDoctorFilter');
        const eventsUrl = @js(route('api.appointments.calendar'));
        const createUrl = @js(route('appointments.create'));
        const canCreate = @js(auth()->user()->can('create', \App\Models\Appointment::class));

        const calendar = new FullCalendar.Calendar(calEl, {
            locale: 'fr',
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            slotMinTime: '07:00:00',
            slotMaxTime: '21:00:00',
            height: 'auto',
            nowIndicator: true,
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6],
                startTime: '08:00',
                endTime: '18:00',
            },
            events: function(info, successCallback, failureCallback) {
                const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
                if (doctorSelect && doctorSelect.value) params.append('doctor_id', doctorSelect.value);

                fetch(`${eventsUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                    .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
                    .then(data => successCallback(data))
                    .catch(err => failureCallback(err));
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const p = info.event.extendedProps;
                const msg = `Patient : ${info.event.title}\nStatut : ${p.status_label}\nType : ${p.type}\nMédecin : ${p.doctor}\nDossier : ${p.dossier}`
                    + (p.phone ? `\nTéléphone : ${p.phone}` : '')
                    + '\n\nOuvrir le dossier patient ?';
                if (info.event.url && confirm(msg)) {
                    window.location = info.event.url;
                }
            },
            dateClick: function(info) {
                if (!canCreate) return;
                // On transmet l'heure murale sans décalage horaire (AAAA-MM-JJTHH:MM ou AAAA-MM-JJ)
                window.location = `${createUrl}?date=${encodeURIComponent(info.dateStr.substring(0, 16))}`;
            },
        });

        calendar.render();

        if (doctorSelect) {
            doctorSelect.addEventListener('change', () => calendar.refetchEvents());
        }
    });
</script>
@endpush
