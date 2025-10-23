<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Pengajuan;
use App\Models\User;

class PengajuanLog extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_logs';

    protected $fillable = [
        'pengajuan_id',
        'status_from',
        'status_to',
        'message',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'pengajuan_id' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}