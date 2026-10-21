<?php

namespace Tests\Feature\Patient;

use App\Livewire\Patient\PatientForm;
use App\Livewire\Patient\PatientIndex;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->withoutVite();
    }

    public function test_secretary_creates_patient_with_auto_generated_dossier_number(): void
    {
        $secretary = $this->secretary();
        $this->actingAs($secretary);

        Livewire::test(PatientForm::class)
            ->set('first_name', 'Fatima')
            ->set('last_name', 'Bennani')
            ->set('cin', 'AB123456')
            ->set('birth_date', '1990-03-15')
            ->set('gender', 'female')
            ->set('phone', '0612345678')
            ->set('insurance_name', 'CNOPS')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $patient = Patient::where('cin', 'AB123456')->firstOrFail();
        $this->assertMatchesRegularExpression('/^DOS-'.date('Y').'-\d{5}$/', $patient->dossier_number);
        $this->assertSame($secretary->id, $patient->created_by);
        $this->assertTrue($patient->is_active);

        // Le numéro suivant est incrémenté
        $next = $this->makePatient();
        $this->assertSame(
            (int) substr($patient->dossier_number, -5) + 1,
            (int) substr($next->dossier_number, -5)
        );
    }

    public function test_patient_form_validates_required_fields_and_unique_cin(): void
    {
        $this->actingAs($this->secretary());
        $this->makePatient(['cin' => 'XX999']);

        Livewire::test(PatientForm::class)
            ->set('cin', 'XX999')
            ->set('birth_date', now()->addDay()->format('Y-m-d'))
            ->set('gender', 'other')
            ->set('email', 'pas-un-email')
            ->call('save')
            ->assertHasErrors([
                'first_name' => 'required',
                'last_name' => 'required',
                'phone' => 'required',
                'cin' => 'unique',
                'birth_date' => 'before',
                'gender' => 'in',
                'email' => 'email',
            ]);

        $this->assertSame(1, Patient::count());
    }

    public function test_photo_upload_rejects_non_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->secretary());

        Livewire::test(PatientForm::class)
            ->set('first_name', 'Ali')
            ->set('last_name', 'Idrissi')
            ->set('birth_date', '1980-01-01')
            ->set('gender', 'male')
            ->set('phone', '0600000001')
            ->set('photo', UploadedFile::fake()->create('virus.php', 10, 'application/x-php'))
            ->call('save')
            ->assertHasErrors(['photo']);
    }

    public function test_editing_patient_keeps_original_author_and_can_deactivate(): void
    {
        $creator = $this->secretary();
        $patient = $this->makePatient(['created_by' => $creator->id]);
        $doctor = $this->doctor();
        $this->actingAs($doctor);

        Livewire::test(PatientForm::class, ['patient' => $patient])
            ->assertSet('isEdit', true)
            ->assertSet('first_name', 'Patient')
            ->set('city', 'Casablanca')
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('patients.show', $patient));

        $patient->refresh();
        $this->assertSame('Casablanca', $patient->city);
        $this->assertFalse($patient->is_active);
        $this->assertSame($creator->id, $patient->created_by, "L'auteur initial ne doit pas être écrasé");
    }

    public function test_editing_a_patient_keeps_its_own_cin(): void
    {
        $this->actingAs($this->secretary());
        $patient = $this->makePatient(['cin' => 'BE778899']);

        Livewire::test(PatientForm::class, ['patient' => $patient])
            ->set('phone', '0699999999')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('0699999999', $patient->fresh()->phone);
    }

    public function test_index_search_by_name_phone_dossier_and_full_name(): void
    {
        $this->actingAs($this->secretary());
        $target = $this->makePatient(['first_name' => 'Ahmed', 'last_name' => 'Alami', 'phone' => '0661112233', 'cin' => 'K445566']);
        $this->makePatient(['first_name' => 'Youssef', 'last_name' => 'Tazi', 'phone' => '0677000000']);

        foreach (['Alami', '0661112233', $target->dossier_number, 'K445566', 'Ahmed Alami', 'alami ahmed'] as $term) {
            Livewire::test(PatientIndex::class)
                ->set('search', $term)
                ->assertSee($target->dossier_number)
                ->assertDontSee('Tazi');
        }
    }

    public function test_index_ignores_invalid_sort_column_and_gender_filter_works(): void
    {
        $this->actingAs($this->secretary());
        $this->makePatient(['first_name' => 'Karim', 'last_name' => 'Homme', 'gender' => 'male']);
        $this->makePatient(['first_name' => 'Salma', 'last_name' => 'Femme', 'gender' => 'female']);

        Livewire::withQueryParams(['sortBy' => 'password; drop table patients', 'sortDir' => 'sideways'])
            ->test(PatientIndex::class)
            ->assertOk()
            ->set('genderFilter', 'female')
            ->assertSee('Salma')
            ->assertDontSee('Karim');
    }

    public function test_secretary_cannot_delete_patient(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($this->secretary());

        Livewire::test(PatientIndex::class)
            ->call('confirmDelete', $patient->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($patient);
    }

    public function test_admin_archives_and_restores_patient(): void
    {
        $patient = $this->makePatient(['last_name' => 'Archivable']);
        $this->actingAs($this->admin());

        Livewire::test(PatientIndex::class)
            ->call('confirmDelete', $patient->id)
            ->assertSet('showDeleteModal', true)
            ->call('delete')
            ->assertSet('showDeleteModal', false)
            ->assertDontSee('Archivable');

        $this->assertSoftDeleted($patient);

        Livewire::test(PatientIndex::class)
            ->set('showArchived', true)
            ->assertSee('Archivable')
            ->call('restore', $patient->id);

        $this->assertNotSoftDeleted($patient);
    }

    public function test_patient_routes_respect_permissions(): void
    {
        $patient = $this->makePatient();

        $this->actingAs($this->secretary());
        $this->get(route('patients.index'))->assertOk()->assertSee($patient->dossier_number);
        $this->get(route('patients.create'))->assertOk();
        $this->get(route('patients.edit', $patient))->assertOk();
        $this->get(route('patients.show', $patient))->assertOk()->assertSee($patient->full_name);

        // Un utilisateur sans rôle n'accède à rien
        $this->actingAs(User::factory()->create());
        $this->get(route('patients.index'))->assertForbidden();
        $this->get(route('patients.show', $patient))->assertForbidden();
    }
}
