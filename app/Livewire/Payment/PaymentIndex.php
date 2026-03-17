<?php

namespace App\Livewire\Payment;

use App\Models\MedicalAct;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentLine;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $periodFrom = '';
    public string $periodTo = '';
    public int $perPage = 15;

    public bool $showForm = false;
    public ?int $editingId = null;

    // Champs formulaire
    public int|string $patient_id = '';
    public int|string $doctor_id = '';
    public int|string $consultation_id = '';
    public string $payment_method = 'cash';
    public string $discount = '0';
    public string $insurance_coverage = '0';
    public string $amount_paid = '0';
    public string $payment_date = '';
    public string $notes = '';
    public array $lines = [];

    protected $queryString = ['search', 'statusFilter'];

    public function mount(): void
    {
        $this->payment_date = today()->format('Y-m-d');
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';
        $this->addLine();
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'medical_act_id' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'total_price' => 0,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function updatedLines(): void
    {
        foreach ($this->lines as $i => &$line) {
            $line['total_price'] = (float)($line['quantity'] ?: 1) * (float)($line['unit_price'] ?: 0);
        }
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->lines)->sum(fn ($l) => (float)($l['quantity'] ?: 1) * (float)($l['unit_price'] ?: 0));
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal - (float)$this->discount - (float)$this->insurance_coverage;
    }

    public function getAmountDueProperty(): float
    {
        return $this->total - (float)$this->amount_paid;
    }

    public function fillFromAct(int $lineIndex, int $actId): void
    {
        $act = MedicalAct::find($actId);
        if ($act) {
            $this->lines[$lineIndex]['description'] = $act->name;
            $this->lines[$lineIndex]['unit_price'] = $act->price;
            $this->lines[$lineIndex]['total_price'] = $act->price * ($this->lines[$lineIndex]['quantity'] ?? 1);
        }
    }

    public function save(): void
    {
        $this->validate([
            'patient_id'    => 'required|exists:patients,id',
            'doctor_id'     => 'required|exists:users,id',
            'payment_method'=> 'required|in:cash,bank_transfer,insurance,mixed',
            'payment_date'  => 'required|date',
            'lines'         => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity'    => 'required|integer|min:1',
            'lines.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $subtotal = $this->subtotal;
        $total = $this->total;
        $amountDue = $this->amountDue;

        $payment = Payment::create([
            'patient_id'         => $this->patient_id,
            'doctor_id'          => $this->doctor_id,
            'consultation_id'    => $this->consultation_id ?: null,
            'payment_method'     => $this->payment_method,
            'subtotal'           => $subtotal,
            'discount'           => $this->discount,
            'insurance_coverage' => $this->insurance_coverage,
            'total_amount'       => $total,
            'amount_paid'        => $this->amount_paid,
            'amount_due'         => $amountDue,
            'status'             => $amountDue <= 0 ? 'paid' : ($this->amount_paid > 0 ? 'partial' : 'pending'),
            'payment_date'       => $this->payment_date,
            'notes'              => $this->notes ?: null,
            'created_by'         => auth()->id(),
        ]);

        foreach ($this->lines as $line) {
            PaymentLine::create([
                'payment_id'    => $payment->id,
                'medical_act_id'=> $line['medical_act_id'] ?: null,
                'description'   => $line['description'],
                'quantity'      => $line['quantity'],
                'unit_price'    => $line['unit_price'],
                'total_price'   => $line['total_price'],
            ]);
        }

        session()->flash('success', 'Paiement enregistré.');
        $this->showForm = false;
    }

    public function generatePdf(int $id): void
    {
        $this->redirect(route('payments.pdf', $id));
    }

    public function openForm(): void
    {
        $this->reset(['editingId', 'patient_id', 'doctor_id', 'consultation_id', 'discount', 'insurance_coverage', 'amount_paid', 'notes', 'lines']);
        $this->payment_method = 'cash';
        $this->payment_date = today()->format('Y-m-d');
        $this->discount = '0';
        $this->insurance_coverage = '0';
        $this->amount_paid = '0';
        $this->doctor_id = auth()->user()->isMedecin() ? auth()->id() : '';
        $this->addLine();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $payment = Payment::with('lines')->findOrFail($id);
        $this->editingId = $id;
        $this->patient_id = $payment->patient_id;
        $this->doctor_id = $payment->doctor_id;
        $this->consultation_id = $payment->consultation_id ?? '';
        $this->payment_method = $payment->payment_method;
        $this->discount = (string) $payment->discount;
        $this->insurance_coverage = (string) $payment->insurance_coverage;
        $this->amount_paid = (string) $payment->amount_paid;
        $this->payment_date = $payment->payment_date ?? today()->format('Y-m-d');
        $this->notes = $payment->notes ?? '';
        $this->lines = $payment->lines->map(fn($l) => [
            'medical_act_id' => $l->medical_act_id ?? '',
            'description'    => $l->description,
            'quantity'       => $l->quantity,
            'unit_price'     => $l->unit_price,
            'total_price'    => $l->total_price,
        ])->toArray();
        $this->showForm = true;
    }

    public function closeForm(): void { $this->showForm = false; $this->editingId = null; }

    public ?int $confirmingDeleteId = null;
    public function confirmDelete(int $id): void { $this->confirmingDeleteId = $id; }
    public function cancelDelete(): void { $this->confirmingDeleteId = null; }
    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            Payment::findOrFail($this->confirmingDeleteId)->delete();
            session()->flash('success', 'Paiement supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->payments);
        }
    }

    #[Computed]
    public function payments()
    {
        return Payment::with(['patient', 'doctor'])
            ->when($this->search, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->search($this->search)))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->periodFrom, fn ($q) => $q->where('payment_date', '>=', $this->periodFrom))
            ->when($this->periodTo, fn ($q) => $q->where('payment_date', '<=', $this->periodTo))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function medicalActs()
    {
        return MedicalAct::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.payment.payment-index', [
            'patients' => Patient::active()->orderBy('last_name')->get(),
            'doctors'  => User::doctors()->active()->get(),
        ])
            ->layout('layouts.cabinet', [
                'title' => 'Paiements',
                'header' => 'Gestion des Paiements',
                'headerIcon' => 'icon-credit-card',
                'breadcrumbs' => [['label' => 'Paiements']],
            ]);
    }
}
