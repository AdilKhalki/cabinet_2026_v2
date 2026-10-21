<?php

namespace Tests\Feature\Payment;

use App\Livewire\Payment\PaymentIndex;
use App\Models\MedicalAct;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\MedicalActSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->seedRoles();
        $this->seed(MedicalActSeeder::class);
    }

    private function act(string $code): MedicalAct
    {
        return MedicalAct::where('code', $code)->firstOrFail();
    }

    /** Facture : consultation (150) + ECG (100) + 2 injections IM (2 × 80) = 410 MAD */
    private function fillInvoiceForm($component, int $patientId, int $doctorId)
    {
        $component->call('openForm')
            ->set('patient_id', $patientId)
            ->set('doctor_id', $doctorId)
            ->set('payment_date', now()->format('Y-m-d'))
            ->set('lines.0.medical_act_id', $this->act('CS')->id)
            ->call('addLine')
            ->set('lines.1.medical_act_id', $this->act('ECG')->id)
            ->call('addLine')
            ->set('lines.2.medical_act_id', $this->act('INJIM')->id)
            ->set('lines.2.quantity', 2);

        return $component;
    }

    private function createPayment(User $doctor, array $attributes = []): Payment
    {
        $payment = Payment::create(array_merge([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'payment_method' => 'cash',
            'subtotal' => 200, 'discount' => 0, 'insurance_coverage' => 0,
            'total_amount' => 200, 'amount_paid' => 200, 'amount_due' => 0,
            'status' => 'paid',
            'payment_date' => now()->format('Y-m-d'),
        ], $attributes));
        $payment->lines()->create(['description' => 'Consultation', 'quantity' => 1, 'unit_price' => 200, 'total_price' => 200]);

        return $payment;
    }

    public function test_secretary_invoices_acts_with_discount_and_insurance_then_completes_payment(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $patient = $this->makePatient(['insurance_name' => 'CNOPS']);

        $component = $this->fillInvoiceForm(Livewire::actingAs($secretary)->test(PaymentIndex::class), $patient->id, $doctor->id)
            ->assertSet('lines.0.description', 'Consultation générale')
            ->assertSet('lines.2.total_price', 160.0)
            ->set('discount', '10')
            ->set('insurance_coverage', '200')
            ->set('payment_method', 'cash')
            ->set('amount_paid', '100')
            ->call('save')
            ->assertHasNoErrors();

        $payment = Payment::with('lines')->sole();
        $this->assertMatchesRegularExpression('/^FAC-'.now()->year.'-\d{5}$/', $payment->invoice_number);
        $this->assertEquals(410.00, (float) $payment->subtotal);
        $this->assertEquals(200.00, (float) $payment->total_amount); // 410 - 10 - 200
        $this->assertEquals(100.00, (float) $payment->amount_paid);
        $this->assertEquals(100.00, (float) $payment->amount_due);
        $this->assertSame('partial', $payment->status);
        $this->assertSame($secretary->id, $payment->created_by);
        $this->assertCount(3, $payment->lines);
        $this->assertEquals((float) $payment->subtotal, (float) $payment->lines->sum('total_price'));
        $this->assertSame($this->act('INJIM')->id, $payment->lines[2]->medical_act_id);

        // Complète le règlement : la facture est mise à jour, pas dupliquée
        $component->call('openEdit', $payment->id)
            ->assertSet('payment_date', now()->format('Y-m-d'))
            ->set('amount_paid', '200')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Payment::count());
        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertEquals(0.0, (float) $payment->amount_due);
        $this->assertCount(3, $payment->lines()->get());
    }

    public function test_amounts_are_validated(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $patient = $this->makePatient();

        $component = $this->fillInvoiceForm(Livewire::actingAs($secretary)->test(PaymentIndex::class), $patient->id, $doctor->id);

        // Trop-perçu
        $component->set('amount_paid', '500')->call('save')->assertHasErrors('amount_paid');
        // Remise + assurance > sous-total
        $component->set('amount_paid', '0')->set('discount', '300')->set('insurance_coverage', '200')
            ->call('save')->assertHasErrors('discount');
        // Montant négatif
        $component->set('discount', '-50')->set('insurance_coverage', '0')->call('save')->assertHasErrors('discount');
        // Mode de paiement hors enum DB
        $component->set('discount', '0')->set('payment_method', 'card')->call('save')->assertHasErrors('payment_method');

        $this->assertSame(0, Payment::count());
    }

    public function test_client_side_line_total_is_recomputed_server_side(): void
    {
        $secretary = $this->secretary();

        Livewire::actingAs($secretary)->test(PaymentIndex::class)
            ->call('openForm')
            ->set('patient_id', $this->makePatient()->id)
            ->set('doctor_id', $this->doctor()->id)
            ->set('lines.0.description', 'Consultation')
            ->set('lines.0.quantity', 2)
            ->set('lines.0.unit_price', '150')
            ->set('lines.0.total_price', 1) // falsifié
            ->set('amount_paid', '300')
            ->call('save')
            ->assertHasNoErrors();

        $payment = Payment::with('lines')->sole();
        $this->assertEquals(300.0, (float) $payment->lines[0]->total_price);
        $this->assertEquals(300.0, (float) $payment->total_amount);
        $this->assertSame('paid', $payment->status);
    }

    public function test_secretary_cancels_an_invoice_which_then_becomes_read_only(): void
    {
        $secretary = $this->secretary();
        $payment = $this->createPayment($this->doctor());

        $component = Livewire::actingAs($secretary)->test(PaymentIndex::class)
            ->call('cancel', $payment->id)
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $payment->fresh()->status);

        $component->call('openEdit', $payment->id)->assertForbidden();
    }

    public function test_secretary_cannot_delete_invoices(): void
    {
        $payment = $this->createPayment($this->doctor());

        Livewire::actingAs($this->secretary())->test(PaymentIndex::class)
            ->call('confirmDelete', $payment->id)
            ->assertForbidden();

        Livewire::actingAs($this->secretary())->test(PaymentIndex::class)
            ->set('confirmingDeleteId', $payment->id)
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($payment);
    }

    public function test_admin_can_delete_an_invoice(): void
    {
        $payment = $this->createPayment($this->doctor());

        Livewire::actingAs($this->admin())->test(PaymentIndex::class)
            ->call('confirmDelete', $payment->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertSoftDeleted($payment);
    }

    public function test_invoice_pdf_downloads(): void
    {
        $payment = $this->createPayment($this->doctor(), [
            'subtotal' => 1250.5, 'total_amount' => 1250.5, 'amount_paid' => 1250.5,
        ]);

        $response = $this->actingAs($this->secretary())->get(route('payments.pdf', $payment));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString($payment->invoice_number, $response->headers->get('content-disposition'));
    }

    public function test_invoice_pdf_still_works_when_patient_is_archived(): void
    {
        $payment = $this->createPayment($this->doctor());
        $payment->patient->delete();

        $this->actingAs($this->admin())->get(route('payments.pdf', $payment))->assertOk();
        Livewire::actingAs($this->admin())->test(PaymentIndex::class)->assertSee($payment->invoice_number);
    }

    public function test_user_without_view_payments_permission_gets_403(): void
    {
        $payment = $this->createPayment($this->doctor());
        $user = User::factory()->create(['is_active' => true]); // aucun rôle

        $this->actingAs($user)->get(route('payments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('payments.pdf', $payment))->assertForbidden();
    }

    public function test_doctor_only_sees_and_prints_own_invoices_and_cannot_create(): void
    {
        $doctor = $this->doctor();
        $other = $this->doctor();
        $own = $this->createPayment($doctor);
        $foreign = $this->createPayment($other);

        $this->actingAs($doctor)->get(route('payments.index'))->assertOk();

        Livewire::actingAs($doctor)->test(PaymentIndex::class)
            ->assertSee($own->invoice_number)
            ->assertDontSee($foreign->invoice_number)
            ->call('openForm')->assertForbidden();

        $this->actingAs($doctor)->get(route('payments.pdf', $own))->assertOk();
        $this->actingAs($doctor)->get(route('payments.pdf', $foreign))->assertForbidden();

        // Appel direct de save() / cancel() malgré l'absence de bouton
        Livewire::actingAs($doctor)->test(PaymentIndex::class)->call('save')->assertForbidden();
        Livewire::actingAs($doctor)->test(PaymentIndex::class)->call('cancel', $own->id)->assertForbidden();
    }

    public function test_search_by_invoice_number_and_status_filter(): void
    {
        $doctor = $this->doctor();
        $paid = $this->createPayment($doctor);
        $pending = $this->createPayment($doctor, ['amount_paid' => 0, 'amount_due' => 200, 'status' => 'pending']);

        Livewire::actingAs($this->secretary())->test(PaymentIndex::class)
            ->set('search', $pending->invoice_number)
            ->assertSee($pending->invoice_number)
            ->assertDontSee($paid->invoice_number)
            ->set('search', '')
            ->set('statusFilter', 'paid')
            ->assertSee($paid->invoice_number)
            ->assertDontSee($pending->invoice_number);
    }
}
