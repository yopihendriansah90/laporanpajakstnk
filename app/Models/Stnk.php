<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stnk extends Model implements HasMedia
{
    //
    use InteractsWithMedia,HasFactory,SoftDeletes;

    // fillabel
    protected $fillable = [
        'nomor_polisi',
        'nama_pemilik',
        'alamat_pemilik',
        'merk_kendaraan',
        'tipe_kendaraan',
        'jenis_kendaraan',
        'model_kendaraan',
        'tahun_pembuatan',
        'warna_kendaraan',
        'nomor_rangka',
        'nomor_mesin',
        'kapasitas_silinder',
        'masa_berlaku_5',
        'masa_berlaku_1',
        'nominal_pokok_pajak',
    ];
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('stnk_attachments');
    }
    // relasi dengan table kir one to one
    public function kir()
    {
        return $this->hasOne(Kir::class);
    }

    public static function booted(): void
    {
        static::deleting(function (self $model): void {
            // Cegah penghapusan permanen bila STNK dipakai pengajuan aktif
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                $used = \App\Models\PengajuanItem::where('stnk_id', $model->id)
                    ->whereHas('pengajuan', function ($q) {
                        $q->whereIn('status', ['draft', 'diajukan', 'disetujui']);
                    })
                    ->exists();

                if ($used) {
                    throw new \RuntimeException('Tidak dapat menghapus permanen STNK karena digunakan dalam pengajuan aktif.');
                }
            }
        });
    }
    
}
