<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanKirItem extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_kir_items';

    protected $fillable = [
        'pengajuan_kir_id',
        'kir_id',
        'snapshot_nomor_uji',
        'snapshot_masa_berlaku',
        'snapshot_nominal_biaya_uji',
        'admin_fee',
        'subtotal',
    ];

    protected $casts = [
        'pengajuan_kir_id' => 'integer',
        'kir_id' => 'integer',
        'snapshot_nominal_biaya_uji' => 'integer',
        'admin_fee' => 'integer',
        'subtotal' => 'integer',
        'snapshot_masa_berlaku' => 'date',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function (PengajuanKirItem $item) {
            $item->admin_fee = max(0, (int) $item->admin_fee);
            $item->snapshot_nominal_biaya_uji = max(0, (int) $item->snapshot_nominal_biaya_uji);
            $item->subtotal = $item->computeSubtotal();
        });

        static::saved(function (PengajuanKirItem $item) {
            if ($item->pengajuanKir) {
                $item->pengajuanKir->recalcTotals();
            }
        });

        static::deleted(function (PengajuanKirItem $item) {
            if ($item->pengajuanKir) {
                $item->pengajuanKir->recalcTotals();
            }
        });
    }

    public function pengajuanKir()
    {
        return $this->belongsTo(PengajuanKir::class, 'pengajuan_kir_id');
    }

    public function kir()
    {
        return $this->belongsTo(Kir::class)->withTrashed();
    }

    public function computeSubtotal(): int
    {
        return (int) $this->snapshot_nominal_biaya_uji + (int) $this->admin_fee;
    }
}