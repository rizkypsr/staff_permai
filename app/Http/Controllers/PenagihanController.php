<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap tugas penagihan milik kurir yang login (hanya baca).
 * Tugas dibuat dan diselesaikan di web admin; pembayaran juga dicatat di sana.
 */
class PenagihanController extends Controller
{
    public const STATUS_DALAM_PENAGIHAN = 0;

    public const STATUS_SELESAI = 1;

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $ringkasanDetail = $this->detailAktif()
            ->select('pd.id_penagihan')
            ->selectRaw('COUNT(*) AS jumlah_nota')
            ->selectRaw('SUM(pd.sisa_tagihan) AS total_tagihan')
            ->groupBy('pd.id_penagihan');

        $tugas = DB::table('penagihan as p')
            ->leftJoinSub($ringkasanDetail, 'rd', 'rd.id_penagihan', '=', 'p.id')
            ->where('p.id_pengguna', $userId)
            ->where('p.row_status', 1)
            ->orderByDesc('p.tgl')
            ->orderByDesc('p.id')
            ->get([
                'p.id',
                'p.no_transaksi',
                'p.tgl',
                'p.status',
                'p.keterangan',
                'rd.jumlah_nota',
                'rd.total_tagihan',
            ])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'no_transaksi' => $row->no_transaksi,
                'tgl' => $row->tgl,
                'tgl_formatted' => Carbon::parse($row->tgl)->translatedFormat('d M Y'),
                'status' => (int) $row->status,
                'keterangan' => $row->keterangan,
                'jumlah_nota' => (int) $row->jumlah_nota,
                'total_tagihan' => (int) $row->total_tagihan,
            ]);

        $dalamPenagihan = $tugas->where('status', self::STATUS_DALAM_PENAGIHAN);

        return Inertia::render('Penagihan', [
            'tugas' => $tugas->values(),
            'ringkasan' => [
                'jumlah_dalam_penagihan' => $dalamPenagihan->count(),
                'total_dalam_penagihan' => $dalamPenagihan->sum('total_tagihan'),
            ],
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $penagihan = DB::table('penagihan')
            ->where('id', $id)
            ->where('id_pengguna', $request->user()->id)
            ->where('row_status', 1)
            ->first(['id', 'no_transaksi', 'tgl', 'status', 'keterangan']);

        abort_if($penagihan === null, 404);

        $pembayaran = DB::table('pembayaran_faktur')
            ->select('id_faktur')
            ->selectRaw('SUM(nominal) AS total_bayar')
            ->where('row_status', 1)
            ->groupBy('id_faktur');

        $nota = $this->detailAktif()
            ->leftJoin('pelanggan as pl', function ($join) {
                $join->on('pl.id', '=', 'f.id_pelanggan')->where('pl.row_status', 1);
            })
            ->leftJoinSub($pembayaran, 'bayar', 'bayar.id_faktur', '=', 'f.id')
            ->where('pd.id_penagihan', $penagihan->id)
            ->orderBy('pd.urutan')
            ->orderBy('pd.id')
            ->get([
                'pd.id',
                'pd.sisa_tagihan',
                'f.no_transaksi',
                'f.tgl',
                'f.grand_total',
                'f.nama_pelanggan as snapshot_nama',
                'f.alamat as snapshot_alamat',
                'f.no_telp as snapshot_telp',
                'pl.nama as pelanggan_nama',
                'pl.alamat as pelanggan_alamat',
                'pl.no_hp as pelanggan_hp',
                'pl.no_telp as pelanggan_telp',
                'bayar.total_bayar',
            ])
            ->map(function (object $row): array {
                $sisaSekarang = (int) $row->grand_total - (int) $row->total_bayar;

                return [
                    'id' => (int) $row->id,
                    'no_nota' => $row->no_transaksi,
                    'tgl_nota' => Carbon::parse($row->tgl)->translatedFormat('d M Y'),
                    'nama_pelanggan' => $this->pertamaTerisi($row->snapshot_nama, $row->pelanggan_nama),
                    'alamat' => $this->pertamaTerisi($row->snapshot_alamat, $row->pelanggan_alamat),
                    'no_telp' => $this->pertamaTerisi($row->snapshot_telp, $row->pelanggan_hp, $row->pelanggan_telp),
                    'sisa_tagihan' => (int) $row->sisa_tagihan,
                    'sisa_sekarang' => $sisaSekarang,
                    'lunas' => $sisaSekarang <= 0,
                ];
            });

        return Inertia::render('PenagihanDetail', [
            'penagihan' => [
                'id' => (int) $penagihan->id,
                'no_transaksi' => $penagihan->no_transaksi,
                'tgl_formatted' => Carbon::parse($penagihan->tgl)->translatedFormat('d M Y'),
                'status' => (int) $penagihan->status,
                'keterangan' => $penagihan->keterangan,
            ],
            'nota' => $nota,
            'total' => [
                'sisa_tagihan' => $nota->sum('sisa_tagihan'),
                'sisa_sekarang' => $nota->sum(fn (array $item): int => max($item['sisa_sekarang'], 0)),
            ],
        ]);
    }

    /**
     * Baris penagihan_detail aktif yang fakturnya juga aktif.
     */
    private function detailAktif(): Builder
    {
        return DB::table('penagihan_detail as pd')
            ->join('faktur as f', function ($join) {
                $join->on('f.id', '=', 'pd.id_faktur')->where('f.row_status', 1);
            })
            ->where('pd.row_status', 1);
    }

    /**
     * Snapshot faktur dulu, lalu master pelanggan.
     */
    private function pertamaTerisi(?string ...$nilai): string
    {
        foreach ($nilai as $item) {
            if ($item !== null && trim($item) !== '') {
                return trim($item);
            }
        }

        return '';
    }
}
