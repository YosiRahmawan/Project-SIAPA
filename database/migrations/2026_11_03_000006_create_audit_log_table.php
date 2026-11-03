<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('aksi');
            $table->string('tabel');
            $table->string('baris_id')->nullable();
            $table->json('sebelum')->nullable();
            $table->json('sesudah')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('waktu')->useCurrent();
            $table->index('aksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
