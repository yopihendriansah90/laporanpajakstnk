<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class Kir extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia,SoftDeletes;
    protected $fillable = [
        'stnk_id',
        'nomor_uji_kendaraan',
        'masa_berlaku',
        'nominal_biaya_uji',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('kir_attachments');
    }
    public function stnk()
    {
        return $this->belongsTo(Stnk::class)->withTrashed();
    }
}
