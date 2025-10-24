<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanKirLog extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_kir_logs';

    protected $fillable = [
        'pengajuan_kir_id',
        'status_from',
        'status_to',
        'message',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'pengajuan_kir_id' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
    ];

    public function pengajuanKir(): BelongsTo
    {
        return $this->belongsTo(PengajuanKir::class, 'pengajuan_kir_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}