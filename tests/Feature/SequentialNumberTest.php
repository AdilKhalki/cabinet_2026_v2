<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class SequentialNumberTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
    }

    public function test_numbers_continue_after_gaps(): void
    {
        $year = date('Y');
        $first = $this->makePatient();
        $this->assertSame("DOS-{$year}-00001", $first->dossier_number);

        // Trou dans la séquence (ex. numéro importé) : on repart du plus grand
        $this->makePatient(['dossier_number' => "DOS-{$year}-00010"]);
        $this->assertSame("DOS-{$year}-00011", $this->makePatient()->dossier_number);
    }

    public function test_simultaneous_save_gets_the_next_number_instead_of_failing(): void
    {
        $doctor = $this->doctor();
        $patient = $this->makePatient();
        $year = date('Y');

        // Simule un collègue qui enregistre une ordonnance au même instant :
        // il prend le numéro juste après que le nôtre a été calculé.
        $simulated = false;
        new Prescription; // initialise le modèle : notre écouteur passe après l'attribution du numéro
        Prescription::creating(function (Prescription $p) use (&$simulated, $doctor, $patient) {
            if ($simulated) {
                return;
            }
            $simulated = true;
            DB::table('prescriptions')->insert([
                'prescription_number' => $p->prescription_number,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'prescribed_at' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'prescribed_at' => now()->toDateString(),
        ]);

        $this->assertTrue($simulated);
        $this->assertSame("ORD-{$year}-00002", $prescription->prescription_number);
        $this->assertSame(2, Prescription::count());
    }

    public function test_a_manually_entered_duplicate_number_is_still_rejected(): void
    {
        $this->makePatient(['dossier_number' => 'DOS-2026-00005']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->makePatient(['dossier_number' => 'DOS-2026-00005']);
    }

    public function test_patient_helper_still_works(): void
    {
        $this->assertSame('DOS-'.date('Y').'-00001', Patient::generateDossierNumber());
    }
}
