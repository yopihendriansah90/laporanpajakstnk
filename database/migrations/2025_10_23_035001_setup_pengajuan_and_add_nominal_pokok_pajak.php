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
        // Alter STNKs: add nominal_pokok_pajak (Rupiah tanpa pecahan)
        if (! Schema::hasColumn('stnks', 'nominal_pokok_pajak')) {
            Schema::table('stnks', function (Blueprint $table) {
                $table->unsignedBigInteger('nominal_pokok_pajak')
                    ->default(0)
                    ->after('kapasitas_silinder');
            });
        }

        // Pengajuans (header)
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique(); // AJG-YYYYMM-####
            $table->string('status')->default('draft'); // draft, diajukan, disetujui, ditolak, dibayar

            // Totals (Rupiah tanpa pecahan)
            $table->unsignedBigInteger('total_pokok')->default(0);
            $table->unsignedBigInteger('total_admin')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);

            // Status timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Audit creator
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        // Pengajuan items (detail per STNK)
        Schema::create('pengajuan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->foreignId('stnk_id')->constrained('stnks');

            // Snapshot fields to preserve state at time of pengajuan
            $table->string('snapshot_nomor_polisi');
            $table->string('snapshot_nama_pemilik')->nullable();

            // Nominal pokok pajak (snapshot)
            $table->unsignedBigInteger('snapshot_nominal_pokok_pajak')->default(0);

            // Admin fee per unit (editable)
            $table->unsignedBigInteger('admin_fee')->default(0);

            // Subtotal = snapshot_nominal_pokok_pajak + admin_fee
            $table->unsignedBigInteger('subtotal')->default(0);

            $table->timestamps();
        });

        // Pengajuan logs (audit trail)
        Schema::create('pengajuan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();

            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('message')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop audit/detail/header tables
        Schema::dropIfExists('pengajuan_logs');
        Schema::dropIfExists('pengajuan_items');
        Schema::dropIfExists('pengajuans');

        // Remove column from STNKs
        if (Schema::hasColumn('stnks', 'nominal_pokok_pajak')) {
            Schema::table('stnks', function (Blueprint $table) {
                $table->dropColumn('nominal_pokok_pajak');
            });
        }
    }
};