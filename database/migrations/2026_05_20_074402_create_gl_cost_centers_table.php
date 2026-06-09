<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('cost_center_code', 20)->unique();
            $table->string('name', 200)->nullable();
            $table->string('description', 200)->nullable();
            $table->string('short_text', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('cost_center_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_cost_centers');
    }
};