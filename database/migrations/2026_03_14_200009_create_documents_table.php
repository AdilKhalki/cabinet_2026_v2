<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', [
                'medical_certificate',
                'hospital_report',
                'external_results',
                'insurance',
                'prescription_external',
                'other',
            ]);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name', 255);
            $table->string('file_type', 50)->comment('pdf, jpg, docx...');
            $table->bigInteger('file_size')->nullable();
            $table->date('document_date')->nullable();
            $table->string('source', 150)->nullable()->comment('Hôpital, labo, assurance...');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
