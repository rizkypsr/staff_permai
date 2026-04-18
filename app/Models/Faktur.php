<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faktur extends Model
{
    protected $table = 'faktur';

    protected $fillable = [
        'no_transaksi',
        'tgl',
        'id_pelanggan',
        'id_penjualan',
        'keterangan',
        'qty_kirim',
        'total',
        'diskon_faktur',
        'biaya_lain',
        'keterangan_biaya_lain',
        'grand_total',
        'uang_muka',
        'rek_pembayaran_dp',
        'dp',
        'sisa_tagihan',
        'row_status',
        'is_kirim',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tgl' => 'date',
        'row_status' => 'boolean',
        'is_kirim' => 'integer',
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan');
    }
}
