<div>
    <div class="card">
        <div class="card-header">
            <h5><i class="feather icon-calendar"></i> Calendrier des Rendez-vous</h5>
            @if(count($doctors) > 1 && !auth()->user()->isMedecin())
                <div class="card-header-right">
                    <select wire:model.live="doctorFilter" class="form-control" style="width:200px;display:inline-block;">
                        <option value="">Tous les médecins</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="card-block">
            <div id="fullcalendar"></div>
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
                fetch(`/api/appointments/calendar?start=${info.startStr}&end=${info.endStr}`)
                    .then(r => r.json())
                    .then(data => successCallback(data))
                    .catch(() => failureCallback());
            },
            eventClick: function(info) {
                const p = info.event.extendedProps;
                const msg = `Patient: ${info.event.title}\nStatut: ${p.status_label}\nType: ${p.type}\nMédecin: ${p.doctor}\nDossier: ${p.dossier}`;
                alert(msg);
            },
            dateClick: function(info) {
                window.location = `/appointments/create?date=${info.dateStr}`;
            },
        });

        calendar.render();

        // Écouter les changements Livewire pour rafraîchir
        Livewire.on('calendarRefresh', () => calendar.refetchEvents());
    });
</script>
@endpush
