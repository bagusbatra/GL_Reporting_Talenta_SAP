<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_strategy_d_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('gl_mapping_profiles')->cascadeOnDelete();
            $table->json('debit_accounts')->comment('["7000000005","5204000009",...]');
            $table->json('debit_keywords')->nullable()->comment('["pengembalian"]');
            $table->enum('default_dc', ['Debit', 'Credit'])->default('Credit');
            $table->timestamps();

            $table->unique('profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_strategy_d_configs');
    }
};