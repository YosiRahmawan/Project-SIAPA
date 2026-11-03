<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif', function (Blueprint $table) {
            $table->id();
            $table->string('jenis'); // spp | daftar_ulang
            $table->unsignedTinyInteger('kelas');
            $table->unsignedBigInteger('nominal'); // rupiah penuh
            $table->timestamps();
            $table->unique(['jenis', 'kelas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif');
    }
};
