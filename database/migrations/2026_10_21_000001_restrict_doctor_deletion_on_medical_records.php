<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empêche la base de supprimer en cascade les dossiers médicaux d'un médecin.
 * Avant : supprimer un utilisateur effaçait ses RDV, consultations, ordonnances…
 * Après : la suppression est refusée tant qu'il a des dossiers (il faut le désactiver).
 */
return new class extends Migration
{
    private array $tables = [
        'appointments',
        'consultations',
        'prescriptions',
        'lab_tests',
        'medical_images',
        'medical_reports',
        'payments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['doctor_id']);
                $t->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['doctor_id']);
                $t->foreign('doctor_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
