<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengiriman extends Model
{
    protected $table = 'pengiriman';

    protected $fillable = [
        'no_transaksi',
        'tgl',
        'id_pelanggan',
        'id_faktur',
        'alamat',
        'keterangan',
        'status',
        'row_status',
        'is_approve',
        'created_by',
        'updated_by',
        'qty_pesan',
        'qty_nota',
    ];

    protected $casts = [
        'tgl' => 'date',
        'status' => 'integer',
        'row_status' => 'boolean',
        'is_approve' => 'boolean',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan');
    }

    public function persons(): HasMany
    {
        return $this->hasMany(PengirimanPerson::class, 'id_pengiriman');
    }
}
