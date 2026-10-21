<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCabinet;
use Tests\TestCase;

class MedicalRecordsIntegrityTest extends TestCase
{
    use InteractsWithCabinet, RefreshDatabase;

    public function test_database_refuses_to_delete_a_doctor_who_has_medical_records(): void
    {
        config(['activitylog.enabled' => false]);

        $doctor = $this->doctor();
        $appointment = Appointment::create([
            'patient_id' => $this->makePatient()->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay(),
        ]);

        try {
            $doctor->delete();
            $this->fail('La suppression du médecin aurait dû être refusée.');
        } catch (QueryException) {
            // attendu : contrainte RESTRICT
        }

        $this->assertDatabaseHas('users', ['id' => $doctor->id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
    }

    public function test_validation_messages_are_in_french(): void
    {
        $errors = validator(['birth_date' => ''], ['birth_date' => 'required'])->errors();

        $this->assertSame('Le champ date de naissance est obligatoire.', $errors->first('birth_date'));
    }
}
