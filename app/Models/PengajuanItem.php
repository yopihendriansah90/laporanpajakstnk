<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pengajuan;
use App\Models\Stnk;

class PengajuanItem extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_items';

    protected $fillable = [
        'pengajuan_id',
        'stnk_id',
        'snapshot_nomor_polisi',
        'snapshot_nama_pemilik',
        'snapshot_nominal_pokok_pajak',
        'admin_fee',
        'subtotal',
    ];

    protected $casts = [
        'pengajuan_id' => 'integer',
        'stnk_id' => 'integer',
        'snapshot_nominal_pokok_pajak' => 'integer',
        'admin_fee' => 'integer',
        'subtotal' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function (PengajuanItem $item) {
            // guard non-negative and set subtotal
            $item->admin_fee = max(0, (int) $item->admin_fee);
            $item->snapshot_nominal_pokok_pajak = max(0, (int) $item->snapshot_nominal_pokok_pajak);
            $item->subtotal = $item->computeSubtotal();
        });

        static::saved(function (PengajuanItem $item) {
            if ($item->pengajuan) {
                $item->pengajuan->recalcTotals();
            }
        });

        static::deleted(function (PengajuanItem $item) {
            if ($item->pengajuan) {
                $item->pengajuan->recalcTotals();
            }
        });
    }

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function stnk()
    {
        return $this->belongsTo(Stnk::class)->withTrashed();
    }

    public function computeSubtotal(): int
    {
        return (int) $this->snapshot_nominal_pokok_pajak + (int) $this->admin_fee;
    }
}