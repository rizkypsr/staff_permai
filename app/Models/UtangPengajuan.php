<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kasbon diajukan staff. Status: 0 menunggu, 1 disetujui, 2 ditolak.
 * Persetujuan dan slip gaji diurus web admin; app ini hanya membuat baris
 * status 0 dan membatalkannya (row_status = 0) selama masih menunggu.
 */
class UtangPengajuan extends Model
{
    public const STATUS_MENUNGGU = 0;

    public const STATUS_DISETUJUI = 1;

    public const STATUS_DITOLAK = 2;

    protected $table = 'utang_pengajuan';

    protected $fillable = [
        'id_pengguna',
        'tgl',
        'nominal',
        'keterangan',
        'status',
        'row_status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tgl' => 'date',
        'nominal' => 'integer',
        'status' => 'integer',
        'row_status' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna');
    }

    /**
     * @param  Builder<UtangPengajuan>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where('row_status', 1);
    }

    /**
     * @param  Builder<UtangPengajuan>  $query
     */
    public function scopeMilik(Builder $query, int $idPengguna): void
    {
        $query->where('id_pengguna', $idPengguna);
    }
}
