<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanKirSignatory extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_kir_signatories';

    protected $fillable = [
        'pengajuan_kir_id',
        'name',
        'order',
    ];

    protected $casts = [
        'pengajuan_kir_id' => 'integer',
        'order' => 'integer',
    ];

    public function pengajuanKir()
    {
        return $this->belongsTo(PengajuanKir::class, 'pengajuan_kir_id');
    }
}