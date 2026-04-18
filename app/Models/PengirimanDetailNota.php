<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengirimanDetailNota extends Model
{
    protected $table = 'pengiriman_detail_nota';

    protected $fillable = [
        'id_pengiriman',
        'id_produk',
        'uraian',
        'id_satuan',
        'satuan',
        'qty',
        'harga_satuan',
        'diskon',
        'sub_total',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'integer',
        'diskon' => 'integer',
        'sub_total' => 'integer',
        'row_status' => 'boolean',
    ];
}
