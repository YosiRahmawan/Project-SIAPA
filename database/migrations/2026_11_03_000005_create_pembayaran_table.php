<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan');
            $table->string('metode'); // manual | qris
            $table->unsignedBigInteger('jumlah'); // rupiah penuh
            $table->string('bukti')->nullable();
            $table->string('status'); // menunggu | diterima | ditolak | dibatalkan
            $table->text('alasan')->nullable(); // alasan batal atau tolak
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->date('tanggal');
            $table->timestamps();
            $table->index(['tagihan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
