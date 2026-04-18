<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengirimanPerson extends Model
{
    protected $table = 'pengiriman_person';

    protected $fillable = [
        'id_pengiriman',
        'id_pengguna',
        'tipe',
        'keterangan',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'row_status' => 'boolean',
    ];

    public function pengiriman(): BelongsTo
    {
        return $this->belongsTo(Pengiriman::class, 'id_pengiriman');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }
}
