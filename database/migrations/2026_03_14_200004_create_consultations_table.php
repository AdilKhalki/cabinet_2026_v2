<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('consultation_type', [
                'general', 'specialized', 'followup', 'emergency', 'control', 'teleconsultation'
            ])->default('general');
            $table->enum('status', ['in_progress', 'completed', 'archived'])->default('in_progress');
            $table->dateTime('consultation_date');
            // Clinique
            $table->text('reason')->nullable()->comment('Motif de consultation');
            $table->text('symptoms')->nullable();
            $table->text('clinical_exam')->nullable()->comment('Examen clinique');
            // Signes vitaux
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->string('blood_pressure', 20)->nullable()->comment('Ex: 120/80');
            $table->integer('heart_rate')->nullable()->comment('bpm');
            $table->decimal('temperature', 4, 1)->nullable()->comment('°C');
            $table->integer('spo2')->nullable()->comment('Saturation O2 %');
            // Diagnostic
            $table->text('diagnosis')->nullable();
            $table->string('icd10_code', 20)->nullable()->comment('Code CIM-10');
            $table->text('treatment_plan')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('summary')->nullable()->comment('Résumé de consultation');
            $table->boolean('followup_required')->default(false);
            $table->date('followup_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'consultation_date']);
            $table->index(['doctor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
