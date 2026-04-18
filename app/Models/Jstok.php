<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jstok extends Model
{
    protected $table = 'jstok';

    protected $fillable = [
        'no_referensi',
        'tgl',
        'jenis_trx',
        'id_produk',
        'qty',
        'id_header',
        'id_detail',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tgl' => 'date',
        'qty' => 'integer',
        'row_status' => 'boolean',
    ];
}
