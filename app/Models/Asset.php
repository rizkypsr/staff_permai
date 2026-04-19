<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Asset extends Model
{
    protected $table = 'asset';

    protected $fillable = [
        'id_pegawai',
        'nama',
        'model',
        'tgl_pembelian',
        'waktu_maintenance',
        'periode_maintenance',
        'row_status',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pembelian' => 'date',
            'waktu_maintenance' => 'integer',
            'row_status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pegawai');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class, 'id_asset');
    }

    public function latestMaintenance(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenance::class, 'id', 'id_asset')
            ->where('row_status', 1)
            ->latest('tgl_maintenance');
    }

    public function getUsiaAttribute(): string
    {
        if (!$this->tgl_pembelian) {
            return '-';
        }

        $diff = Carbon::parse($this->tgl_pembelian)->diff(Carbon::now());
        
        if ($diff->y > 0) {
            return $diff->y . ' tahun ' . $diff->m . ' bulan';
        }
        
        return $diff->m . ' bulan';
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('id_pegawai', $userId)->where('row_status', 1);
    }
}