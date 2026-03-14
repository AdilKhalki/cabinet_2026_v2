<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', [
                'chest_xray',
                'abdominal_xray',
                'limb_xray',
                'ct_scan',
                'mri',
                'ultrasound',
                'other',
            ]);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_type', 20)->comment('jpeg, png, dicom');
            $table->bigInteger('file_size')->nullable()->comment('Bytes');
            $table->text('radiological_report')->nullable()->comment('Interprétation du médecin');
            $table->date('image_date');
            $table->string('body_part', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'image_date']);
            $table->index(['patient_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
};
