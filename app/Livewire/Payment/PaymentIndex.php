<?php

namespace App\Livewire\Payment;

use App\Models\MedicalAct;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
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

        // Accès rapide « Encaisser » de la barre du haut : /payments?nouveau=1
        if (request()->boolean('nouveau') && auth()->user()->can('create', Payment::class)) {
            $this->openForm();
        }
    }

    public function updating($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'periodFrom', 'periodTo'], true)) {
            $this->resetPage();
        }
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

    public function updatedLines($value = null, ?string $key = null): void
    {
        // Sélection d'un acte médical : pré-remplir description et prix
        if ($key && str_ends_with($key, '.medical_act_id') && $value) {
            $this->fillFromAct((int) explode('.', $key)[0], (int) $value);
        }

        foreach ($this->lines as $i => $line) {
            $this->lines[$i]['total_price'] = self::lineTotal($line);
        }
    }

    private static function lineTotal(array $line): float
    {
        return round(max(1, (int) ($line['quantity'] ?: 1)) * (float) ($line['unit_price'] ?: 0), 2);
    }

    public function getSubtotalProperty(): float
    {
        return round(collect($this->lines)->sum(fn ($l) => self::lineTotal($l)), 2);
    }

    public function getTotalProperty(): float
    {
        return round($this->subtotal - (float) $this->discount - (float) $this->insurance_coverage, 2);
    }

    public function getAmountDueProperty(): float
    {
        return round($this->total - (float) $this->amount_paid, 2);
    }

    public function fillFromAct(int $lineIndex, int $actId): void
    {
        $act = MedicalAct::find($actId);
        if ($act && isset($this->lines[$lineIndex])) {
            $this->lines[$lineIndex]['medical_act_id'] = $act->id;
            $this->lines[$lineIndex]['description'] = $act->name;
            $this->lines[$lineIndex]['unit_price'] = (float) $act->price;
            $this->lines[$lineIndex]['total_price'] = self::lineTotal($this->lines[$lineIndex]);
        }
    }

    public function save(): void
    {
        $payment = $this->editingId ? Payment::findOrFail($this->editingId) : null;
        $payment ? $this->authorize('update', $payment) : $this->authorize('create', Payment::class);

        // Un médecin ne peut facturer qu'en son nom
        if (Payment::restrictedToOwn(auth()->user())) {
            $this->doctor_id = auth()->id();
        }

        $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'payment_method' => 'required|in:'.implode(',', array_keys(Payment::METHOD_LABELS)),
            'payment_date' => 'required|date',
            'discount' => 'required|numeric|min:0',
            'insurance_coverage' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'lines' => 'required|array|min:1',
            'lines.*.medical_act_id' => 'nullable|exists:medical_acts,id',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ], [], [
            'patient_id' => 'patient', 'doctor_id' => 'médecin', 'payment_method' => 'mode de paiement',
            'payment_date' => 'date', 'discount' => 'remise', 'insurance_coverage' => 'couverture assurance',
            'amount_paid' => 'montant payé', 'lines.*.description' => 'description',
            'lines.*.quantity' => 'quantité', 'lines.*.unit_price' => 'prix unitaire',
        ]);

        // Montants recalculés côté serveur (jamais confiance aux totaux envoyés par le client)
        $subtotal = $this->subtotal;
        $total = $this->total;
        $amountPaid = round((float) $this->amount_paid, 2);

        if ($total < 0) {
            $this->addError('discount', 'La remise et la couverture assurance ne peuvent pas dépasser le sous-total.');

            return;
        }
        if ($amountPaid - $total > 0.004) {
            $this->addError('amount_paid', 'Le montant payé ne peut pas dépasser le total ('.number_format($total, 2, ',', ' ').' MAD).');

            return;
        }

        $data = [
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'consultation_id' => $this->consultation_id ?: null,
            'payment_method' => $this->payment_method,
            'subtotal' => $subtotal,
            'discount' => round((float) $this->discount, 2),
            'insurance_coverage' => round((float) $this->insurance_coverage, 2),
            'total_amount' => $total,
            'amount_paid' => $amountPaid,
            'amount_due' => round($total - $amountPaid, 2),
            'status' => Payment::statusFor($total, $amountPaid),
            'payment_date' => $this->payment_date,
            'notes' => $this->notes ?: null,
        ];

        $isNew = ! $payment;

        DB::transaction(function () use (&$payment, $data) {
            if ($payment) {
                $payment->update($data);
                $payment->lines()->delete();
            } else {
                $payment = Payment::create($data + ['created_by' => auth()->id()]);
            }

            foreach ($this->lines as $line) {
                $payment->lines()->create([
                    'medical_act_id' => $line['medical_act_id'] ?: null,
                    'description' => $line['description'],
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => round((float) $line['unit_price'], 2),
                    'total_price' => self::lineTotal($line),
                ]);
            }
        });

        if ($isNew) {
            NotificationService::paymentCreated($payment->id, (int) $this->patient_id, (float) $payment->total_amount, $payment->status);
        }

        session()->flash('success', $isNew ? 'Paiement enregistré.' : 'Paiement mis à jour.');
        $this->showForm = false;
        $this->editingId = null;
        unset($this->payments);
    }

    /** Annulation d'une facture : conservée pour la traçabilité, exclue du CA */
    public function cancel(int $id): void
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('cancel', $payment);

        $payment->update(['status' => 'cancelled']);

        session()->flash('success', "Facture {$payment->invoice_number} annulée.");
        unset($this->payments);
    }

    public function generatePdf(int $id): void
    {
        $this->authorize('view', Payment::findOrFail($id));
        $this->redirect(route('payments.pdf', $id));
    }

    public function openForm(): void
    {
        $this->authorize('create', Payment::class);

        $this->resetValidation();
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
        $this->authorize('update', $payment);

        $this->resetValidation();
        $this->editingId = $id;
        $this->patient_id = $payment->patient_id;
        $this->doctor_id = $payment->doctor_id;
        $this->consultation_id = $payment->consultation_id ?? '';
        $this->payment_method = $payment->payment_method ?? 'cash';
        $this->discount = (string) $payment->discount;
        $this->insurance_coverage = (string) $payment->insurance_coverage;
        $this->amount_paid = (string) $payment->amount_paid;
        $this->payment_date = ($payment->payment_date ?? today())->format('Y-m-d');
        $this->notes = $payment->notes ?? '';
        $this->lines = $payment->lines->map(fn ($l) => [
            'medical_act_id' => $l->medical_act_id ?? '',
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => (float) $l->unit_price,
            'total_price' => (float) $l->total_price,
        ])->toArray();
        if (empty($this->lines)) {
            $this->addLine();
        }
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    public ?int $confirmingDeleteId = null;

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Payment::findOrFail($id));
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteId) {
            $payment = Payment::findOrFail($this->confirmingDeleteId);
            $this->authorize('delete', $payment);
            $payment->delete();
            session()->flash('success', 'Paiement supprimé.');
            $this->confirmingDeleteId = null;
            unset($this->payments);
        }
    }

    #[Computed]
    public function payments()
    {
        return Payment::with(['patient', 'doctor'])
            ->visibleTo(auth()->user())
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq
                ->where('invoice_number', 'like', '%'.$this->search.'%')
                ->orWhereHas('patient', fn ($pq) => $pq->search($this->search))))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->periodFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->periodFrom))
            ->when($this->periodTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->periodTo))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function medicalActs()
    {
        return MedicalAct::active()->orderBy('name')->get();
    }

    public function render()
    {
        // Listes du formulaire chargées uniquement quand il est ouvert
        return view('livewire.payment.payment-index', [
            'patients' => $this->showForm
                ? Patient::where(fn ($q) => $q->active()->orWhere('id', $this->patient_id ?: 0))->orderBy('last_name')->get()
                : collect(),
            'doctors' => $this->showForm ? User::doctors()->active()->orderBy('name')->get() : collect(),
        ])
            ->layout('layouts.cabinet', [
                'title' => 'Paiements',
                'header' => 'Gestion des Paiements',
                'headerIcon' => 'icon-credit-card',
                'breadcrumbs' => [['label' => 'Paiements']],
            ]);
    }
}
