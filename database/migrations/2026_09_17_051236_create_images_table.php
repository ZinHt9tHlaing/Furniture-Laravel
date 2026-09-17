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
        // for polymorphic relationship
        Schema::create('images', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('image_url');
            $table->string('public_id');

            // auto create imageable_type and imageable_id columns in the database
            $table->ulidMorphs('imageable');

            // image order for specific model (optional)
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
