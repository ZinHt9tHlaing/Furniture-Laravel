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
        Schema::create('otps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('phone', 15)->unique();
            $table->string('otp');
            $table->string('remember_token');
            $table->string('verify_token')->nullable();
            $table->tinyInteger('count')->default(0);
            $table->tinyInteger('error')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
