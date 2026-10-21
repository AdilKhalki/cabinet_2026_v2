<?php

namespace Tests\Feature\Patient;

use App\Livewire\MedicalImage\MedicalImageForm;
use App\Livewire\MedicalImage\MedicalImageIndex;
use App\Livewire\MedicalImage\MedicalImageShow;
use App\Livewire\Patient\PatientShow;
use App\Models\MedicalImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class MedicalImageTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
    }

    protected function uploadImage(User $doctor, array $set = []): MedicalImage
    {
        $patient = $set['patient'] ?? $this->makePatient();
        $this->actingAs($doctor);

        Livewire::withQueryParams(['patient' => $patient->id])
            ->test(MedicalImageForm::class)
            ->assertSet('doctor_id', $doctor->id)
            ->set('category', 'chest_xray')
            ->set('title', 'Radio thorax de face')
            ->set('body_part', 'Thorax')
            ->set('imageFile', $set['file'] ?? UploadedFile::fake()->image('radio.jpg', 800, 600))
            ->call('save')
            ->assertHasNoErrors();

        return MedicalImage::latest('id')->firstOrFail();
    }

    public function test_doctor_uploads_image_to_private_disk_and_views_it(): void
    {
        $doctor = $this->doctor();
        $image = $this->uploadImage($doctor);

        $this->assertSame($doctor->id, $image->doctor_id);
        $this->assertSame($doctor->id, $image->created_by);
        $this->assertSame('jpg', $image->file_type);
        $this->assertSame(today()->format('Y-m-d'), $image->image_date->format('Y-m-d'));
        Storage::disk('local')->assertExists($image->file_path);
        Storage::disk('public')->assertMissing($image->file_path);

        $this->get(route('medical-images.show', $image))
            ->assertOk()
            ->assertSee('Radio thorax de face')
            ->assertSee('Radiographie Thorax');

        Livewire::test(MedicalImageShow::class, ['medicalImage' => $image])
            ->call('download')
            ->assertFileDownloaded();

        Livewire::test(MedicalImageIndex::class)->assertSee('Radio thorax de face');
    }

    public function test_file_doctor_and_date_are_required(): void
    {
        $this->actingAs($this->admin());
        $patient = $this->makePatient();

        Livewire::test(MedicalImageForm::class)
            ->set('patient_id', $patient->id)
            ->set('category', 'mri')
            ->set('title', 'IRM')
            ->set('image_date', '')
            ->call('save')
            ->assertHasErrors(['imageFile' => 'required', 'doctor_id' => 'required', 'image_date' => 'required']);

        // Un utilisateur non médecin ne peut pas être désigné comme médecin
        Livewire::test(MedicalImageForm::class)
            ->set('patient_id', $patient->id)
            ->set('doctor_id', $this->secretary()->id)
            ->set('category', 'mri')
            ->set('title', 'IRM')
            ->set('imageFile', UploadedFile::fake()->create('evil.svg', 2, 'image/svg+xml'))
            ->call('save')
            ->assertHasErrors(['doctor_id', 'imageFile']);

        $this->assertSame(0, MedicalImage::count());
    }

    public function test_admin_can_upload_pdf_for_a_chosen_doctor(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $this->actingAs($this->admin());

        Livewire::test(MedicalImageForm::class)
            ->set('patient_id', $patient->id)
            ->set('doctor_id', $doctor->id)
            ->set('category', 'ultrasound')
            ->set('title', 'Échographie abdominale')
            ->set('imageFile', UploadedFile::fake()->create('echo.pdf', 300, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $image = MedicalImage::firstOrFail();
        $this->assertSame('pdf', $image->file_type);
        $this->assertFalse($image->is_image);
    }

    public function test_secretary_has_no_access_to_medical_images(): void
    {
        $image = $this->uploadImage($this->doctor());
        $this->actingAs($this->secretary());

        $this->get(route('medical-images.index'))->assertForbidden();
        $this->get(route('medical-images.create'))->assertForbidden();
        $this->get(route('medical-images.show', $image))->assertForbidden();

        Livewire::test(MedicalImageShow::class, ['medicalImage' => $image])->assertForbidden();
        Livewire::test(MedicalImageForm::class)->assertForbidden();
    }

    public function test_doctor_updates_radiological_report(): void
    {
        $doctor = $this->doctor();
        $image = $this->uploadImage($doctor);

        Livewire::test(MedicalImageShow::class, ['medicalImage' => $image])
            ->call('startEditing')
            ->assertSet('editing', true)
            ->set('radiologicalReport', 'Pas de foyer pulmonaire.')
            ->call('saveReport')
            ->assertSet('editing', false)
            ->assertSee('Pas de foyer pulmonaire.');

        $this->assertSame('Pas de foyer pulmonaire.', $image->fresh()->radiological_report);
    }

    public function test_doctor_edits_image_and_replaces_file(): void
    {
        $doctor = $this->doctor();
        $image = $this->uploadImage($doctor);
        $oldPath = $image->file_path;

        Livewire::test(MedicalImageForm::class, ['medicalImage' => $image])
            ->assertSet('isEdit', true)
            ->set('title', 'Radio thorax de profil')
            ->set('imageFile', UploadedFile::fake()->image('profil.png'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('medical-images.show', $image));

        $image->refresh();
        $this->assertSame('Radio thorax de profil', $image->title);
        $this->assertSame('png', $image->file_type);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($image->file_path);
    }

    public function test_delete_from_index_soft_deletes_image(): void
    {
        $doctor = $this->doctor();
        $image = $this->uploadImage($doctor);

        Livewire::test(MedicalImageIndex::class)
            ->call('delete', $image->id)
            ->assertDontSee('Radio thorax de face');

        $this->assertSoftDeleted($image);
        Storage::disk('local')->assertExists($image->file_path);
    }

    public function test_doctor_list_only_shows_own_images_but_patient_file_shows_all(): void
    {
        $patient = $this->makePatient();
        $drA = $this->doctor();
        $drB = $this->doctor();
        $this->uploadImage($drA, ['patient' => $patient]);
        $mine = $this->uploadImage($drB, ['patient' => $patient]);
        $mine->update(['title' => 'Image du Dr B']);

        $this->actingAs($drA);
        Livewire::test(MedicalImageIndex::class)
            ->assertSee('Radio thorax de face')
            ->assertDontSee('Image du Dr B');

        Livewire::test(PatientShow::class, ['patient' => $patient])
            ->call('setTab', 'images')
            ->assertSee('Imagerie (2)')
            ->assertSee('Image du Dr B');
    }
}
