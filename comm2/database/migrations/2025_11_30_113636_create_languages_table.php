<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // e.g., 'en', 'es', 'multi'
            $table->string('name'); // e.g., 'English', 'Spanish'
            $table->integer('order')->default(0); // For ordering in UI
            $table->timestamps();

            $table->index('code');
            $table->index('order');
        });

        // Insert hardcoded language values
        $languages = [
            ['code' => 'en', 'name' => 'English', 'order' => 1],
            ['code' => 'es', 'name' => 'Spanish', 'order' => 2],
            ['code' => 'pt', 'name' => 'Portuguese', 'order' => 3],
            ['code' => 'fr', 'name' => 'French', 'order' => 4],
            ['code' => 'de', 'name' => 'German', 'order' => 5],
            ['code' => 'ru', 'name' => 'Russian', 'order' => 6],
            ['code' => 'pl', 'name' => 'Polish', 'order' => 7],
            ['code' => 'tr', 'name' => 'Turkish', 'order' => 8],
            ['code' => 'it', 'name' => 'Italian', 'order' => 9],
            ['code' => 'nl', 'name' => 'Dutch', 'order' => 10],
            ['code' => 'zh', 'name' => 'Chinese', 'order' => 11],
            ['code' => 'ja', 'name' => 'Japanese', 'order' => 12],
            ['code' => 'ko', 'name' => 'Korean', 'order' => 13],
            ['code' => 'ar', 'name' => 'Arabic', 'order' => 14],
        ];

        foreach ($languages as $language) {
            DB::table('languages')->insert([
                'code' => $language['code'],
                'name' => $language['name'],
                'order' => $language['order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
