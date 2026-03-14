<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->enum('consultation_type', [
                'general',
                'specialized',
                'followup',
                'emergency',
                'control',
                'teleconsultation',
            ])->default('general');
            $table->enum('status', [
                'planned',
                'confirmed',
                'waiting',     // en salle d'attente
                'in_progress', // en cours de consultation
                'completed',
                'cancelled',
                'no_show',     // absent
            ])->default('planned');
            $table->string('cancellation_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('color', 7)->nullable()->default('#01a9ac')->comment('Couleur pour FullCalendar');
            $table->boolean('reminder_sent')->default(false);
            $table->dateTime('arrived_at')->nullable()->comment('Heure d\'arrivée en salle d\'attente');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['doctor_id', 'scheduled_at']);
            $table->index(['patient_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
