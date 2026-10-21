<?php

namespace Tests\Feature\Patient;

use App\Livewire\Document\DocumentForm;
use App\Livewire\Document\DocumentIndex;
use App\Livewire\Patient\PatientShow;
use App\Models\Consultation;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class DocumentTest extends TestCase
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

    protected function uploadDocument(array $overrides = []): Document
    {
        $patient = $overrides['patient'] ?? $this->makePatient();

        Livewire::withQueryParams(['patient' => $patient->id])
            ->test(DocumentForm::class)
            ->assertSet('patient_id', $patient->id)
            ->set('category', 'external_results')
            ->set('title', 'Bilan sanguin externe')
            ->set('source', 'Laboratoire Ibn Sina')
            ->set('file', $overrides['file'] ?? UploadedFile::fake()->create('bilan.pdf', 200, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('documents.index'));

        return Document::latest('id')->firstOrFail();
    }

    public function test_secretary_uploads_document_stored_on_private_disk(): void
    {
        $secretary = $this->secretary();
        $this->actingAs($secretary);

        $document = $this->uploadDocument();

        $this->assertSame('bilan.pdf', $document->file_name);
        $this->assertSame('pdf', $document->file_type);
        $this->assertSame($secretary->id, $document->uploaded_by);
        $this->assertStringStartsWith('documents/', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    public function test_upload_rejects_dangerous_or_missing_files(): void
    {
        $this->actingAs($this->secretary());
        $patient = $this->makePatient();

        Livewire::test(DocumentForm::class)
            ->set('patient_id', $patient->id)
            ->set('category', 'other')
            ->set('title', 'Sans fichier')
            ->call('save')
            ->assertHasErrors(['file' => 'required']);

        Livewire::test(DocumentForm::class)
            ->set('patient_id', $patient->id)
            ->set('category', 'other')
            ->set('title', 'Script')
            ->set('file', UploadedFile::fake()->create('shell.php', 5, 'application/x-php'))
            ->call('save')
            ->assertHasErrors(['file']);

        Livewire::test(DocumentForm::class)
            ->set('patient_id', $patient->id)
            ->set('category', 'ordonnance') // ancienne catégorie inexistante en base
            ->set('title', 'Mauvaise catégorie')
            ->set('file', UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['category']);

        $this->assertSame(0, Document::count());
    }

    public function test_linked_consultation_must_belong_to_the_patient(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $other = $this->makePatient();
        $foreign = Consultation::create(['patient_id' => $other->id, 'doctor_id' => $doctor->id, 'consultation_date' => now()]);

        $this->actingAs($doctor);

        Livewire::test(DocumentForm::class)
            ->set('patient_id', $patient->id)
            ->set('consultation_id', $foreign->id)
            ->set('category', 'other')
            ->set('title', 'Doc')
            ->set('file', UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['consultation_id']);
    }

    public function test_document_listing_download_and_patient_tab(): void
    {
        $this->actingAs($this->secretary());
        $document = $this->uploadDocument();

        // Le médecin voit aussi les documents déposés par la secrétaire
        $this->actingAs($this->doctor());

        Livewire::test(DocumentIndex::class)
            ->assertSee('Bilan sanguin externe')
            ->set('categoryFilter', 'external_results')
            ->assertSee('Bilan sanguin externe')
            ->set('categoryFilter', 'insurance')
            ->assertDontSee('Bilan sanguin externe');

        Livewire::test(DocumentIndex::class)
            ->set('search', $document->patient->last_name)
            ->assertSee('Bilan sanguin externe')
            ->call('download', $document->id)
            ->assertFileDownloaded('bilan.pdf');

        Livewire::test(PatientShow::class, ['patient' => $document->patient])
            ->call('setTab', 'documents')
            ->assertSee('Bilan sanguin externe')
            ->call('downloadDocument', $document->id)
            ->assertFileDownloaded('bilan.pdf');
    }

    public function test_only_users_with_delete_permission_can_delete_documents(): void
    {
        $this->actingAs($this->secretary());
        $document = $this->uploadDocument();

        Livewire::test(DocumentIndex::class)->call('delete', $document->id)->assertForbidden();
        $this->actingAs($this->doctor());
        Livewire::test(DocumentIndex::class)->call('delete', $document->id)->assertForbidden();
        $this->assertNotSoftDeleted($document);

        $this->actingAs($this->admin());
        Livewire::test(DocumentIndex::class)->call('delete', $document->id)->assertHasNoErrors();

        $this->assertSoftDeleted($document);
        // Le fichier est conservé (suppression logique, restauration possible)
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_editing_document_replaces_file_and_keeps_uploader(): void
    {
        $secretary = $this->secretary();
        $this->actingAs($secretary);
        $document = $this->uploadDocument();
        $oldPath = $document->file_path;

        $this->actingAs($this->doctor());

        Livewire::test(DocumentForm::class, ['document' => $document])
            ->assertSet('isEdit', true)
            ->set('title', 'Bilan corrigé')
            ->set('file', UploadedFile::fake()->image('scan.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $document->refresh();
        $this->assertSame('Bilan corrigé', $document->title);
        $this->assertSame('scan.jpg', $document->file_name);
        $this->assertSame($secretary->id, $document->uploaded_by);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_document_routes_require_permission(): void
    {
        $this->actingAs($this->secretary());
        $this->get(route('documents.index'))->assertOk();
        $this->get(route('documents.create'))->assertOk();

        $this->actingAs(User::factory()->create());
        $this->get(route('documents.index'))->assertForbidden();
    }
}
