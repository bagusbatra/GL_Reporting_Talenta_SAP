<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_text_references', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 20);
            $table->string('cost_center', 20)->nullable();
            $table->string('text_value', 255);
            $table->string('learned_from', 255)->nullable()->comment('manual_input, upload_2026_03, dst');
            $table->integer('use_count')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['account_number', 'cost_center'], 'uniq_acc_cc');
            $table->index('account_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_text_references');
    }
};