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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('long_name');
            $table->text('short_description');
            $table->text('long_description')->nullable();
            $table->enum('category', ['gamemode', 'script', 'map', 'misc']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('downloads_count')->default(0);
            $table->boolean('oop_enabled')->default(false);
            $table->string('github_url')->nullable();
            $table->string('forum_thread_url')->nullable();
            $table->string('min_mta_version')->nullable();
            $table->json('compatible_gamemodes')->nullable();
            $table->boolean('is_disabled')->default(false);
            $table->timestamps();

            $table->index('name');
            $table->index('category');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
