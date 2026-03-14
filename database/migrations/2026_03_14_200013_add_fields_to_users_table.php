<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('speciality', 150)->nullable()->after('phone')->comment('Pour les médecins');
            $table->string('license_number', 50)->nullable()->after('speciality')->comment('RPPS / Numéro d\'ordre');
            $table->string('profile_photo_path')->nullable()->after('license_number');
            $table->text('signature_path')->nullable()->after('profile_photo_path')->comment('Image signature pour PDF');
            $table->string('stamp_path')->nullable()->after('signature_path')->comment('Cachet/tampon');
            $table->string('address', 255)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'speciality', 'license_number',
                'profile_photo_path', 'signature_path', 'stamp_path',
                'address', 'is_active',
            ]);
        });
    }
};
