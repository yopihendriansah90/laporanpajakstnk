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
        Schema::create('pengajuan_kirs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->string('status')->default('draft'); // draft, diajukan, disetujui, ditolak, dibayar

            // Totals (Rupiah tanpa pecahan)
            $table->unsignedBigInteger('total_biaya_uji')->default(0);
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

            // Helpful indexes
            $table->index('status');
            $table->index(['submitted_at', 'approved_at', 'paid_at']);
        });

        Schema::create('pengajuan_kir_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_kir_id')->constrained('pengajuan_kirs')->cascadeOnDelete();
            $table->foreignId('kir_id')->constrained('kirs');

            // Snapshot fields to preserve state at time of pengajuan KIR
            $table->string('snapshot_nomor_uji');
            $table->date('snapshot_masa_berlaku')->nullable();
            $table->unsignedBigInteger('snapshot_nominal_biaya_uji')->default(0);

            // Admin fee per unit (editable)
            $table->unsignedBigInteger('admin_fee')->default(0);

            // Subtotal = snapshot_nominal_biaya_uji + admin_fee
            $table->unsignedBigInteger('subtotal')->default(0);

            $table->timestamps();

            // Prevent duplicate KIR items within one pengajuan
            $table->unique(['pengajuan_kir_id', 'kir_id']);
        });

        Schema::create('pengajuan_kir_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_kir_id')->constrained('pengajuan_kirs')->cascadeOnDelete();

            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('message')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });

        Schema::create('pengajuan_kir_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_kir_id')->constrained('pengajuan_kirs')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index(['pengajuan_kir_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_kir_signatories');
        Schema::dropIfExists('pengajuan_kir_logs');
        Schema::dropIfExists('pengajuan_kir_items');
        Schema::dropIfExists('pengajuan_kirs');
    }
};