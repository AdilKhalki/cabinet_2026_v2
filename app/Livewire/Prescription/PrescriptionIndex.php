<?php
namespace App\Livewire\Prescription;
use App\Models\Prescription;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PrescriptionIndex extends Component {
    use WithPagination;
    public string $search = '';
    public int $perPage = 15;

    #[Computed]
    public function prescriptions() {
        return Prescription::with(['patient','doctor'])
            ->when($this->search, fn($q) => $q->whereHas('patient', fn($pq) => $pq->search($this->search)))
            ->when(auth()->user()->isMedecin(), fn($q) => $q->where('doctor_id', auth()->id()))
            ->orderByDesc('prescribed_at')->paginate($this->perPage);
    }

    public function render() {
        return view('livewire.prescription.prescription-index')
            ->layout('layouts.cabinet', ['title'=>'Ordonnances','header'=>'Ordonnances','headerIcon'=>'icon-file-text','breadcrumbs'=>[['label'=>'Ordonnances']]]);
    }
}
