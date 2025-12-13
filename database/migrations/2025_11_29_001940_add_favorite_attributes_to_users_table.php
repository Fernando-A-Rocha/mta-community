<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('favorite_city')->nullable()->after('profile_visibility');
            $table->string('favorite_vehicle')->nullable()->after('favorite_city');
            $table->string('favorite_character')->nullable()->after('favorite_vehicle');
            $table->string('favorite_gang')->nullable()->after('favorite_character');
            $table->string('favorite_weapon')->nullable()->after('favorite_gang');
            $table->string('favorite_radio_station')->nullable()->after('favorite_weapon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'favorite_city',
                'favorite_vehicle',
                'favorite_character',
                'favorite_gang',
                'favorite_weapon',
                'favorite_radio_station',
            ]);
        });
    }
};
