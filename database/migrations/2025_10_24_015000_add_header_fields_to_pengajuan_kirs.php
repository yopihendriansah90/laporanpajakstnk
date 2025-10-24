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
        Schema::table('pengajuan_kirs', function (Blueprint $table) {
            $table->string('div_dept_cc')->nullable()->after('created_by');
            $table->string('keperluan')->nullable()->after('div_dept_cc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_kirs', function (Blueprint $table) {
            $table->dropColumn(['div_dept_cc', 'keperluan']);
        });
    }
};