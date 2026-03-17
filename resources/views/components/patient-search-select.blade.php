@props(['patients', 'selected' => '', 'disabled' => false, 'wireModel' => 'patient_id'])

<div x-data="{
    open: false,
    search: '',
    selectedId: @entangle($wireModel),
    selectedLabel: '',
    patients: @js($patients->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->full_name,
        'dossier' => $p->dossier_number,
        'cin' => $p->cin ?? '',
        'ip' => $p->ip_number ?? '',
    ])),
    get filtered() {
        if (!this.search) return this.patients;
        const s = this.search.toLowerCase();
        return this.patients.filter(p =>
            p.name.toLowerCase().includes(s) ||
            p.dossier.toLowerCase().includes(s) ||
            p.cin.toLowerCase().includes(s) ||
            p.ip.toLowerCase().includes(s)
        );
    },
    init() {
        this.setLabel();
        this.$watch('selectedId', () => this.setLabel());
    },
    setLabel() {
        const p = this.patients.find(p => p.id == this.selectedId);
        this.selectedLabel = p ? p.name + ' (' + p.dossier + ')' : '';
    },
    select(patient) {
        this.selectedId = patient.id;
        this.selectedLabel = patient.name + ' (' + patient.dossier + ')';
        this.search = '';
        this.open = false;
    },
    clear() {
        this.selectedId = '';
        this.selectedLabel = '';
        this.search = '';
    }
}" x-init="init()" class="position-relative">

    {{-- Champ affiché --}}
    @if($disabled)
        <input type="text" class="form-control" :value="selectedLabel" disabled>
    @else
        <div class="input-group" @click.away="open = false">
            <input type="text"
                   class="form-control"
                   x-show="!open && selectedId"
                   :value="selectedLabel"
                   @click="open = true; $nextTick(() => $refs.searchInput.focus())"
                   readonly
                   style="cursor: pointer; background: #fff;">
            <input type="text"
                   class="form-control"
                   x-show="open || !selectedId"
                   x-ref="searchInput"
                   x-model="search"
                   @focus="open = true"
                   @click="open = true"
                   placeholder="Rechercher par nom, CIN, IP, dossier...">
            <span class="input-group-addon" style="cursor: pointer;"
                  x-show="selectedId" @click="clear()">
                <i class="feather icon-x"></i>
            </span>
            <span class="input-group-addon" x-show="!selectedId">
                <i class="feather icon-search"></i>
            </span>
        </div>

        {{-- Liste déroulante --}}
        <div x-show="open"
             x-transition
             style="position: absolute; z-index: 1050; width: 100%; max-height: 250px; overflow-y: auto;
                    background: #fff; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <template x-for="patient in filtered" :key="patient.id">
                <div @click="select(patient)"
                     style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; font-size: 13px;"
                     onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='#fff'">
                    <strong x-text="patient.name"></strong>
                    <span style="color: #888; margin-left: 8px;" x-text="'(' + patient.dossier + ')'"></span>
                    <template x-if="patient.cin">
                        <span style="color: #aaa; margin-left: 5px; font-size: 11px;">
                            CIN: <span x-text="patient.cin"></span>
                        </span>
                    </template>
                    <template x-if="patient.ip">
                        <span style="color: #aaa; margin-left: 5px; font-size: 11px;">
                            IP: <span x-text="patient.ip"></span>
                        </span>
                    </template>
                </div>
            </template>
            <div x-show="filtered.length === 0" style="padding: 10px 12px; color: #999; text-align: center; font-size: 13px;">
                Aucun patient trouvé
            </div>
        </div>
    @endif
</div>
