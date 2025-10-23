<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\PengajuanItem;
use App\Models\PengajuanLog;
use App\Models\User;
use App\Models\PengajuanSignatory;

class Pengajuan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nomor',
        'status',
        'total_pokok',
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
        'total_pokok'   => 'int',
        'total_admin'   => 'int',
        'grand_total'   => 'int',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'paid_at'       => 'datetime',
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
            $model->total_pokok ??= 0;
            $model->total_admin ??= 0;
            $model->grand_total = (int) $model->total_pokok + (int) $model->total_admin;
        });

        static::saving(function (self $model): void {
            // Keep grand_total consistent
            $model->grand_total = (int) $model->total_pokok + (int) $model->total_admin;
        });
    }

    public static function generateNomor(): string
    {
        // Pola: {PREFIX}{YYYYMM}-{SEQ4}, PREFIX dari env PENGAJUAN_PREFIX (default: "AJG-")
        $prefixConfig = (string) env('PENGAJUAN_PREFIX', 'MJM-');
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
        return $this->hasMany(PengajuanItem::class);
    }

    public function logs()
    {
        return $this->hasMany(PengajuanLog::class);
    }

    public function signatories()
    {
        return $this->hasMany(PengajuanSignatory::class)->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function recalcTotals(): void
    {
        $this->loadMissing('items');

        $totalPokok = (int) $this->items->sum('snapshot_nominal_pokok_pajak');
        $totalAdmin = (int) $this->items->sum('admin_fee');

        $this->total_pokok = $totalPokok;
        $this->total_admin = $totalAdmin;
        $this->grand_total = $totalPokok + $totalAdmin;

        $this->save();
    }

    // Status transitions (optional helpers)
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