<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanSignatory extends Model
{
    protected $table = 'pengajuan_signatories';

    protected $fillable = [
        'pengajuan_id',
        'name',
        'order',
    ];

    protected $casts = [
        'pengajuan_id' => 'integer',
        'order' => 'integer',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }
}