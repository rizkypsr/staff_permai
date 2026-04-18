<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefLookup extends Model
{
    protected $table = 'ref_lookup';

    protected $fillable = [
        'kategori',
        'nama',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'row_status' => 'boolean',
    ];
}
