<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_account_prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('account_number', 20)->unique();
            $table->string('prefix', 100)->comment('Gaji, Hutang BPJS, dst');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_account_prefixes');
    }
};