<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->date('prescribed_at');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable()->comment('Instructions générales');
            $table->boolean('is_printed')->default(false);
            $table->string('pdf_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'prescribed_at']);
        });

        Schema::create('prescription_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medication_name', 200)->comment('Nom libre ou depuis la base');
            $table->string('dosage', 150)->nullable()->comment('Ex: 1 comprimé matin et soir');
            $table->string('frequency', 100)->nullable()->comment('Ex: 2 fois/jour');
            $table->integer('duration_days')->nullable();
            $table->string('route', 80)->nullable()->comment('Oral, IV, IM...');
            $table->text('instructions')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_lines');
        Schema::dropIfExists('prescriptions');
    }
};
