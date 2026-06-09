<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_mapping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('gl_entities')->cascadeOnDelete();
            $table->string('name', 100)->comment('Default, Custom-April-2026, dst');
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index(['entity_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_mapping_profiles');
    }
};