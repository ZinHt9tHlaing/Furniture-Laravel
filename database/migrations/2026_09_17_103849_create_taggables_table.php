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
        // pivot table
        Schema::create('taggables', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tag_id')->constrained()->cascadeOnDelete();
            
            // Creates taggable_id (unsignedBigInteger) and taggable_type (string)
            $table->ulidMorphs('taggable');

            // Prevent duplicate tag assignments
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
