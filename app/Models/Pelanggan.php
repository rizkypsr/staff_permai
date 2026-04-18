<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'kota',
        'provinsi',
        'kode_pos',
        'no_telp',
        'no_hp',
        'email',
        'kontak',
        'bank',
        'no_rekening',
        'pemilik_rekening',
        'npwp',
        'keterangan',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'row_status' => 'boolean',
    ];
}
