<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefProduk extends Model
{
    protected $table = 'ref_produk';

    protected $fillable = [
        'kode',
        'id_tipe',
        'nama',
        'id_satuan',
        'id_jenis',
        'id_merek',
        'harga_beli',
        'harga_jual',
        'is_qty_editable',
        'keterangan',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'row_status' => 'boolean',
    ];

    public function tipe(): BelongsTo
    {
        return $this->belongsTo(RefLookup::class, 'id_tipe');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(RefLookup::class, 'id_satuan');
    }
}
