<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'fitness_certificate',    // Certificat d'aptitude
                'sick_leave',             // Arrêt de travail
                'medical_report',         // Compte-rendu médical
                'referral',               // Lettre de référence
                'other',
            ]);
            $table->string('title', 200);
            $table->text('content');
            $table->date('report_date');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('sick_leave_days')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_reports');
    }
};
