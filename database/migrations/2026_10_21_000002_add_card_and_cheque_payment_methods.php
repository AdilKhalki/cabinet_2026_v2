<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les modes de paiement « carte bancaire » et « chèque »,
 * très utilisés dans les cabinets marocains.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'cheque', 'bank_transfer', 'insurance', 'mixed'])
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        // Les paiements carte / chèque deviennent « espèces » pour respecter l'ancienne liste
        DB::table('payments')->whereIn('payment_method', ['card', 'cheque'])->update(['payment_method' => 'cash']);

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'bank_transfer', 'insurance', 'mixed'])
                ->nullable()
                ->change();
        });
    }
};
