<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('gl_mapping_profiles')->cascadeOnDelete();
            $table->string('mapping_key', 100)->comment('5204000009, 2010000005_DENDA_SAKIT, dst');
            $table->string('account_number', 20);
            $table->string('account_name', 200);
            $table->enum('account_type', ['Cost center', 'Aggregate', 'Individual']);
            $table->enum('transaction_value', ['Debit', 'Credit']);
            $table->string('cost_center', 20)->nullable()->comment('Fixed CC jika ada');
            $table->string('profit_center', 20)->nullable()->default('200301');
            $table->boolean('use_profit_center')->default(false);
            $table->json('components')->nullable()->comment('["Basic Salary","Tunjangan Jabatan"]');
            $table->string('match_account_name', 200)->nullable()->comment('Untuk multi-variant matching');
            $table->json('match_keywords')->nullable()->comment('["denda sakit"]');
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('profile_id');
            $table->index('account_number');
            $table->index(['profile_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_account_mappings');
    }
};