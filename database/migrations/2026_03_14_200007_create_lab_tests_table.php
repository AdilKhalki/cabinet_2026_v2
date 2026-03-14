<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 100)->nullable()->comment('Hématologie, Biochimie, Bactériologie...');
            $table->string('unit', 50)->nullable();
            $table->decimal('normal_min', 10, 3)->nullable();
            $table->decimal('normal_max', 10, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 30)->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->date('requested_at');
            $table->date('result_date')->nullable();
            $table->enum('status', ['requested', 'pending', 'completed', 'cancelled'])->default('requested');
            $table->string('laboratory_name', 150)->nullable();
            $table->text('clinical_info')->nullable();
            $table->string('pdf_path')->nullable()->comment('PDF importé du labo');
            $table->text('doctor_interpretation')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'requested_at']);
        });

        Schema::create('lab_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parameter_name', 150);
            $table->string('value', 100)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('normal_min', 10, 3)->nullable();
            $table->decimal('normal_max', 10, 3)->nullable();
            $table->enum('flag', ['normal', 'low', 'high', 'critical'])->default('normal');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_results');
        Schema::dropIfExists('lab_tests');
        Schema::dropIfExists('lab_test_types');
    }
};
