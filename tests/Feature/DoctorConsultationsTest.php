<?php

namespace Tests\Feature;

use App\Livewire\Consultation\ConsultationIndex;
use App\Livewire\Dashboard\DashboardStats;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DoctorConsultationsTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        // Only load the tables exercised here; unrelated fulltext indexes require MySQL.
        return ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php',
            'database/migrations/2026_03_14_143132_create_permission_tables.php',
            'database/migrations/2026_03_14_200001_create_patients_table.php',
            'database/migrations/2026_03_14_200003_create_appointments_table.php',
            'database/migrations/2026_03_14_200004_create_consultations_table.php',
        ]];
    }

    public function test_doctor_only_sees_own_consultations_in_dashboard_and_list(): void
    {
        config(['activitylog.enabled' => false]);

        $doctor = User::factory()->create();
        $doctor->assignRole(Role::create(['name' => 'medecin', 'guard_name' => 'web']));
        $otherDoctor = User::factory()->create();
        $patient = Patient::create([
            'dossier_number' => 'TEST-001',
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'phone' => '0600000000',
        ]);
        $ownConsultation = Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => now(),
        ]);
        Consultation::create([
            'patient_id' => $patient->id,
            'doctor_id' => $otherDoctor->id,
            'consultation_date' => now()->addMinute(),
        ]);

        $this->actingAs($doctor);

        $this->assertSame(
            [$ownConsultation->id],
            app(DashboardStats::class)->recentConsultations()->modelKeys(),
        );
        $this->assertSame(
            [$ownConsultation->id],
            app(ConsultationIndex::class)->consultations()->getCollection()->modelKeys(),
        );
    }
}
