<?php

namespace Tests\Feature;

use App\Livewire\Payment\PaymentIndex;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class QuickActionsNavbarTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
    }

    public function test_secretary_sees_front_desk_shortcuts_only(): void
    {
        $this->actingAs($this->secretary())->get(route('home'))
            ->assertOk()
            ->assertSee(route('patients.create'), false)
            ->assertSee(route('appointments.create'), false)
            ->assertSee(route('appointments.waiting-room'), false)
            ->assertSee(route('payments.index', ['nouveau' => 1]), false)
            ->assertDontSee(route('consultations.create'), false)
            ->assertDontSee(route('prescriptions.create'), false);
    }

    public function test_doctor_sees_medical_shortcuts_but_cannot_invoice(): void
    {
        $this->actingAs($this->doctor())->get(route('home'))
            ->assertOk()
            ->assertSee(route('consultations.create'), false)
            ->assertSee(route('prescriptions.create'), false)
            ->assertDontSee(route('payments.index', ['nouveau' => 1]), false);
    }

    public function test_admin_sees_every_shortcut(): void
    {
        $response = $this->actingAs($this->admin())->get(route('home'))->assertOk();

        foreach (['patients.create', 'appointments.create', 'appointments.waiting-room', 'consultations.create', 'prescriptions.create'] as $route) {
            $response->assertSee(route($route), false);
        }
        $response->assertSee(route('payments.index', ['nouveau' => 1]), false);
    }

    public function test_waiting_room_badge_counts_todays_waiting_patients(): void
    {
        $doctor = $this->doctor();
        foreach (['waiting', 'waiting', 'in_progress', 'planned'] as $status) {
            Appointment::create([
                'patient_id' => $this->makePatient()->id,
                'doctor_id' => $doctor->id,
                'scheduled_at' => now()->startOfDay()->addHours(10),
                'status' => $status,
            ]);
        }

        $this->actingAs($this->secretary())->get(route('home'))
            ->assertSee('<span class="qa-badge">3</span>', false);
    }

    public function test_encaisser_shortcut_opens_the_payment_form(): void
    {
        Livewire::withQueryParams(['nouveau' => 1])
            ->actingAs($this->secretary())
            ->test(PaymentIndex::class)
            ->assertSet('showForm', true);
    }

    public function test_payment_list_opens_without_form_by_default(): void
    {
        Livewire::actingAs($this->secretary())
            ->test(PaymentIndex::class)
            ->assertSet('showForm', false);
    }
}
