<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_run_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('gl_entities');
            $table->foreignId('profile_id')->constrained('gl_mapping_profiles');
            $table->integer('period_year');
            $table->tinyInteger('period_month');
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'validated'])->default('pending');
            $table->integer('total_records')->nullable();
            $table->bigInteger('total_debit')->nullable();
            $table->bigInteger('total_credit')->nullable();
            $table->string('output_file_path', 500)->nullable();
            $table->string('output_filled_path', 500)->nullable()->comment('Setelah fill text');
            $table->enum('validation_status', ['not_validated', 'match', 'mismatch'])->default('not_validated');
            $table->json('validation_details')->nullable();
            $table->text('error_message')->nullable();
            $table->string('run_by', 100)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_id', 'period_year', 'period_month']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_run_histories');
    }
};