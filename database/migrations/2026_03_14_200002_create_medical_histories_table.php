<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'medical',      // maladies chroniques
                'surgical',     // chirurgies
                'hospitalization',
                'family',       // antécédents familiaux
                'allergy',
                'vaccination',
                'habit',        // tabac, alcool, activité physique
            ]);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('date_occurred')->nullable();
            $table->string('relation', 50)->nullable()->comment('Pour les antécédents familiaux: père, mère, etc.');
            // Habitudes
            $table->enum('habit_type', ['smoking', 'alcohol', 'physical_activity', 'other'])->nullable();
            $table->string('habit_frequency', 100)->nullable();
            // Vaccinations
            $table->string('vaccine_name', 150)->nullable();
            $table->date('vaccine_date')->nullable();
            $table->date('vaccine_next_date')->nullable();
            $table->string('vaccine_batch', 50)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_histories');
    }
};
