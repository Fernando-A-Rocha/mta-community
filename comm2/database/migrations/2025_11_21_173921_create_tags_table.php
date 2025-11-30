<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index('slug');
        });

        // Insert hardcoded tag values
        $tags = [
            'utility', 'shader', 'vehicle', 'weapon', 'ui', 'hud', 'framework', 'library', 'tool', 'admin', 'animation', 'audio', 'sound', 'camera', 'chat', 'clothing', 'skin', 'environment', 'lighting', 'effects', 'physics', 'ai', 'npc', 'job', 'economy', 'inventory', 'housing', 'gang', 'police', 'race', 'drift', 'freeroam', 'rpg', 'survival', 'pvp', 'pve', 'roleplay', 'interface', 'database', 'mysql', 'sqlite', 'editor', 'debug', 'logging', 'optimization', 'performance', 'handling', 'theme', 'minigame', 'quest', 'mission', 'cutscene', 'localization', 'weather', 'timecycle', 'particles',
        ];

        // Remove duplicates and insert
        $uniqueTags = array_unique($tags);
        foreach ($uniqueTags as $tagName) {
            DB::table('tags')->insert([
                'name' => $tagName,
                'slug' => Str::slug($tagName),
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
        Schema::dropIfExists('tags');
    }
};
