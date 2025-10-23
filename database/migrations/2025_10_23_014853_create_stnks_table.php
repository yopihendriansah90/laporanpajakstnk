<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stnks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_polisi');
            $table->string('nama_pemilik');
            $table->string('alamat_pemilik')->nullable();
            $table->string('merk_kendaraan')->nullable();
            $table->string('tipe_kendaraan')->nullable();
            $table->string('jenis_kendaraan')->nullable();
            $table->string('model_kendaraan')->nullable();
            $table->integer('tahun_pembuatan')->nullable();
            $table->string('warna_kendaraan')->nullable();
            $table->string('nomor_rangka')->nullable();
            $table->string('nomor_mesin')->nullable();
            $table->integer('kapasitas_silinder')->nullable();
            $table->date('masa_berlaku_5');
            $table->date('masa_berlaku_1');
            $table->bigInteger('nominal_pokok_pajak')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stnks');
    }
};
