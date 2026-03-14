<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('dossier_number', 20)->unique()->comment('Numéro de dossier auto-généré');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('cin', 20)->nullable()->unique()->comment('Carte Identité Nationale');
            $table->string('ip_number', 30)->nullable()->comment('Numéro IPP');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('phone', 20);
            $table->string('phone_secondary', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('blood_group', 5)->nullable()->comment('A+, A-, B+, B-, AB+, AB-, O+, O-');
            $table->text('known_allergies')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('profession', 100)->nullable();
            $table->string('insurance_name', 150)->nullable()->comment('Nom de la mutuelle/assurance');
            $table->string('insurance_number', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('phone');
            $table->index('dossier_number');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
