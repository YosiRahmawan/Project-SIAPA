<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('nis');
            $table->foreign('nis')->references('nis')->on('siswa');
            $table->string('jenis'); // spp | daftar_ulang | jemputan
            $table->string('periode', 7); // YYYY-MM
            $table->unsignedBigInteger('nominal'); // rupiah penuh
            $table->date('jatuh_tempo');
            $table->string('status')->default('belum'); // belum | lunas
            $table->timestamps();
            $table->unique(['nis', 'jenis', 'periode']);
            $table->index(['status', 'jatuh_tempo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
