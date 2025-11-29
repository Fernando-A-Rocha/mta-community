<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'utility', 'shader', 'vehicle', 'weapon', 'ui', 'hud', 'framework', 'library', 'tool', 'admin', 'animation', 'audio', 'sound', 'camera', 'chat', 'clothing', 'skin', 'environment', 'lighting', 'effects', 'physics', 'ai', 'npc', 'job', 'economy', 'inventory', 'housing', 'gang', 'police', 'race', 'drift', 'freeroam', 'rpg', 'survival', 'pvp', 'pve', 'roleplay', 'interface', 'database', 'mysql', 'sqlite', 'editor', 'debug', 'logging', 'optimization', 'performance', 'handling', 'theme', 'minigame', 'quest', 'mission', 'cutscene', 'localization', 'weather', 'timecycle', 'particles', 'skin',
        ];

        foreach ($tags as $tagName) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => $tagName]
            );
        }
    }
}
