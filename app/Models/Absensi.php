<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'id_pengguna',
        'tgl',
        'masuk',
        'keluar',
        'status',
        'row_status',
        'created_by',
        'updated_by',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'tgl' => 'date',
        'status' => 'integer',
        'row_status' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }
}
