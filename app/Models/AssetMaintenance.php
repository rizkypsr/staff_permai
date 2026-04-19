<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $table = 'asset_maintenance';

    protected $fillable = [
        'id_asset',
        'tgl_maintenance',
        'keterangan',
        'row_status',
    ];

    protected function casts(): array
    {
        return [
            'tgl_maintenance' => 'date',
            'row_status' => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'id_asset');
    }
}