<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_entities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('cs_semarang, staff_kmi, dst');
            $table->string('name', 100)->comment('CS Semarang, Staff KMI');
            $table->enum('region', ['semarang', 'surabaya']);
            $table->string('ledger_code', 100)->comment('G_L CS Semarang, dst');
            $table->string('branch_id', 20)->comment('21087, 21089, 21090');
            $table->enum('ledger_id_strategy', ['single', 'multi_try'])->default('single');
            $table->json('ledger_id_list')->nullable()->comment('[900] atau [900,901,902,903]');
            $table->string('doc_header_template', 200);
            $table->string('output_filename_template', 200);
            $table->enum('extraction_strategy', ['A', 'B', 'C', 'D', 'E', 'F']);
            $table->string('company_code', 10)->default('KMI');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('region');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_entities');
    }
};