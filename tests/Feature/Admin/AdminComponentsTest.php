<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ActivityLog;
use App\Livewire\Admin\CabinetSettings;
use App\Livewire\Admin\MedicalActIndex;
use App\Livewire\Admin\MedicationIndex;
use App\Models\Medication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class AdminComponentsTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
    }

    public static function adminComponents(): array
    {
        return [
            'medical acts' => [MedicalActIndex::class],
            'medications' => [MedicationIndex::class],
            'activity log' => [ActivityLog::class],
            'settings' => [CabinetSettings::class],
        ];
    }

    #[DataProvider('adminComponents')]
    public function test_non_admins_are_forbidden(string $component): void
    {
        foreach (['medecin', 'secretaire'] as $role) {
            $this->actingAs($this->userWithRole($role));
            Livewire::test($component)->assertForbidden();
        }
    }

    #[DataProvider('adminComponents')]
    public function test_admin_can_render(string $component): void
    {
        $this->actingAs($this->admin());

        Livewire::test($component)->assertOk();
    }

    public function test_admin_saves_cabinet_settings(): void
    {
        Storage::fake();
        $this->actingAs($this->admin());

        Livewire::test(CabinetSettings::class)
            ->set('cabinet_name', 'Cabinet Al Amal')
            ->set('cabinet_phone', '0522000000')
            ->set('cabinet_city', 'Casablanca')
            ->set('doctor_name', 'Dr. Alami')
            ->set('appointment_reminder_hours', 48)
            ->call('save')
            ->assertHasNoErrors();

        $data = json_decode(Storage::get('cabinet_settings.json'), true);
        $this->assertSame('Cabinet Al Amal', $data['cabinet_name']);
        $this->assertSame(48, $data['appointment_reminder_hours']);

        // Rechargement
        Livewire::test(CabinetSettings::class)
            ->assertSet('cabinet_name', 'Cabinet Al Amal')
            ->assertSet('cabinet_city', 'Casablanca');
    }

    public function test_cabinet_settings_are_validated(): void
    {
        Storage::fake();
        $this->actingAs($this->admin());

        Livewire::test(CabinetSettings::class)
            ->set('cabinet_name', '')
            ->set('cabinet_email', 'pas-un-email')
            ->set('appointment_reminder_hours', 0)
            ->call('save')
            ->assertHasErrors(['cabinet_name', 'cabinet_email', 'appointment_reminder_hours']);

        Storage::assertMissing('cabinet_settings.json');
    }

    public function test_doctor_cannot_delete_medication_through_livewire(): void
    {
        $med = Medication::create(['name' => 'Doliprane', 'is_active' => true]);
        $this->actingAs($this->doctor());

        try {
            Livewire::test(MedicationIndex::class)->call('delete', $med->id);
        } catch (\Throwable) {
            // composant refusé dès le boot
        }

        $this->assertNotNull(Medication::find($med->id));
    }

    public function test_admin_manages_medical_acts(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(MedicalActIndex::class)
            ->call('addNew')
            ->set('newForm.name', 'Consultation générale')
            ->set('newForm.code', 'CG01')
            ->set('newForm.price', '250')
            ->call('saveNew')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('medical_acts', ['code' => 'CG01', 'name' => 'Consultation générale']);
    }

    public function test_activity_log_lists_entries(): void
    {
        config(['activitylog.enabled' => true]);
        $admin = $this->admin();
        $this->actingAs($admin);
        activity()->log('Action de test journalisée');

        Livewire::test(ActivityLog::class)
            ->assertSee('Action de test journalisée')
            ->set('search', 'introuvable')
            ->assertDontSee('Action de test journalisée');
    }
}
