<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom header ke tabel pengajuans
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('div_dept_cc')->nullable()->after('created_by');
            $table->string('keperluan')->nullable()->after('div_dept_cc');
        });

        // Tabel penandatangan per pengajuan
        Schema::create('pengajuan_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index(['pengajuan_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_signatories');

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['div_dept_cc', 'keperluan']);
        });
    }
};