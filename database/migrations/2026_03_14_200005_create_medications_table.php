<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->comment('Nom commercial');
            $table->string('generic_name', 200)->nullable()->comment('DCI');
            $table->string('dosage_form', 100)->nullable()->comment('Comprimé, sirop, injectable...');
            $table->string('strength', 100)->nullable()->comment('Ex: 500mg, 10mg/ml');
            $table->string('manufacturer', 150)->nullable();
            $table->text('description')->nullable();
            $table->text('contraindications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'generic_name']);
            $table->fullText(['name', 'generic_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
