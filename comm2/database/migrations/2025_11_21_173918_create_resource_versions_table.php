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
        Schema::create('resource_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->onDelete('cascade');
            $table->string('version'); // Semantic version string
            $table->text('changelog');
            $table->string('zip_path'); // Private storage path
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index('resource_id');
            $table->index(['resource_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_versions');
    }
};
