<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class PengajuanKir extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_kirs';

    protected $fillable = [
        'nomor',
        'status',
        'total_biaya_uji',
        'total_admin',
        'grand_total',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'paid_at',
        'div_dept_cc',
        'keperluan',
        'created_by',
    ];

    protected $casts = [
        'total_biaya_uji' => 'int',
        'total_admin'     => 'int',
        'grand_total'     => 'int',
        'submitted_at'    => 'datetime',
        'approved_at'     => 'datetime',
        'rejected_at'     => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public static function booted(): void
    {
        static::creating(function (self $model): void {
            // Set creator if available
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            // Generate document number if empty
            if (empty($model->nomor)) {
                $model->nomor = static::generateNomor();
            }

            // Ensure status default
            if (empty($model->status)) {
                $model->status = 'draft';
            }

            // Initialize totals
            $model->total_biaya_uji ??= 0;
            $model->total_admin ??= 0;
            $model->grand_total = (int) $model->total_biaya_uji + (int) $model->total_admin;
        });

        static::saving(function (self $model): void {
            // Keep grand_total consistent
            $model->grand_total = (int) $model->total_biaya_uji + (int) $model->total_admin;
        });
    }

    public static function generateNomor(): string
    {
        // Pola: {PREFIX}{YYYYMM}-{SEQ4}, PREFIX dari env PENGAJUAN_KIR_PREFIX (default: "KIR-")
        $prefixConfig = (string) env('PENGAJUAN_KIR_PREFIX_KIR', 'KIR-');
        $prefix = $prefixConfig . date('Ym') . '-';

        $latest = static::withTrashed()
            ->where('nomor', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor');

        $next = 1;
        if ($latest && preg_match('/(\d{4})$/', $latest, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // Relations
    public function items()
    {
        return $this->hasMany(PengajuanKirItem::class, 'pengajuan_kir_id');
    }

    public function logs()
    {
        return $this->hasMany(PengajuanKirLog::class, 'pengajuan_kir_id');
    }

    public function signatories()
    {
        return $this->hasMany(PengajuanKirSignatory::class, 'pengajuan_kir_id')->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function recalcTotals(): void
    {
        $this->loadMissing('items');

        $totalBiayaUji = (int) $this->items->sum('snapshot_nominal_biaya_uji');
        $totalAdmin    = (int) $this->items->sum('admin_fee');

        $this->total_biaya_uji = $totalBiayaUji;
        $this->total_admin     = $totalAdmin;
        $this->grand_total     = $totalBiayaUji + $totalAdmin;

        $this->save();
    }

    // Status transitions (helpers)
    public function markSubmitted(?string $message = null, ?array $metadata = null, ?int $userId = null): void
    {
        $from = $this->status;
        $this->status = 'diajukan';
        $this->submitted_at = now();
        $this->save();

        $this->logs()->create([
            'status_from' => $from,
            'status_to' => 'diajukan',
            'message' => $message,
            'user_id' => $userId ?? Auth::id(),
            'metadata' => $metadata,
        ]);
    }

    public function markApproved(?string $message = null, ?array $metadata = null, ?int $userId = null): void
    {
        $from = $this->status;
        $this->status = 'disetujui';
        $this->approved_at = now();
        $this->save();

        $this->logs()->create([
            'status_from' => $from,
            'status_to' => 'disetujui',
            'message' => $message,
            'user_id' => $userId ?? Auth::id(),
            'metadata' => $metadata,
        ]);
    }

    public function markRejected(?string $message = null, ?array $metadata = null, ?int $userId = null): void
    {
        $from = $this->status;
        $this->status = 'ditolak';
        $this->rejected_at = now();
        $this->save();

        $this->logs()->create([
            'status_from' => $from,
            'status_to' => 'ditolak',
            'message' => $message,
            'user_id' => $userId ?? Auth::id(),
            'metadata' => $metadata,
        ]);
    }

    public function markPaid(?string $message = null, ?array $metadata = null, ?int $userId = null): void
    {
        $from = $this->status;
        $this->status = 'dibayar';
        $this->paid_at = now();
        $this->save();

        $this->logs()->create([
            'status_from' => $from,
            'status_to' => 'dibayar',
            'message' => $message,
            'user_id' => $userId ?? Auth::id(),
            'metadata' => $metadata,
        ]);
    }
}