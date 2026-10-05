<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap tugas penagihan milik kurir yang login (hanya baca).
 * Tugas dibuat di web admin dan ditandai selesai per nota di sana; penagihan.status
 * diatur otomatis oleh web admin. Pembayaran juga dicatat di sana.
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
            ->selectRaw('SUM(CASE WHEN pd.status = ? THEN 1 ELSE 0 END) AS nota_selesai', [self::STATUS_SELESAI])
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
                'rd.nota_selesai',
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
                'nota_selesai' => (int) $row->nota_selesai,
                'total_tagihan' => (int) $row->total_tagihan,
            ]);

        $dalamPenagihan = $tugas->where('status', self::STATUS_DALAM_PENAGIHAN);

        return Inertia::render('Penagihan', [
            'tugas' => $tugas->values(),
            'ringkasan' => [
                'jumlah_dalam_penagihan' => $dalamPenagihan->count(),
                'total_dalam_penagihan' => $dalamPenagihan->sum('total_tagihan'),
                'nota_belum_selesai' => $dalamPenagihan->sum(
                    fn (array $item): int => $item['jumlah_nota'] - $item['nota_selesai']
                ),
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
                'pd.status',
                'pd.tgl_selesai',
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
                    'selesai' => (int) $row->status === self::STATUS_SELESAI,
                    'tgl_selesai' => $row->tgl_selesai
                        ? Carbon::parse($row->tgl_selesai)->translatedFormat('d M Y')
                        : null,
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
            'kelompok' => $this->kelompokkanPerPelanggan($nota),
            'total' => [
                'sisa_tagihan' => $nota->sum('sisa_tagihan'),
                'sisa_sekarang' => $nota->sum(fn (array $item): int => max($item['sisa_sekarang'], 0)),
            ],
        ]);
    }

    /**
     * Kelompokkan nota per nama pelanggan yang tampil (trim, tanpa beda huruf besar/kecil),
     * bukan per id_pelanggan: nota atas pelanggan umum "Cash" berisi pembeli berbeda.
     * Urutan kelompok mengikuti kemunculan pertama; nota di dalamnya tetap urut.
     *
     * @param  Collection<int, array<string, mixed>>  $nota
     * @return list<array{nama_pelanggan: string, jumlah_nota: int, nota_selesai: int, sisa_tagihan: int, sisa_sekarang: int, nota: list<array<string, mixed>>}>
     */
    private function kelompokkanPerPelanggan(Collection $nota): array
    {
        return $nota
            ->groupBy(fn (array $item): string => mb_strtolower(trim($item['nama_pelanggan'])))
            ->map(fn (Collection $isi): array => [
                'nama_pelanggan' => $isi->first()['nama_pelanggan'],
                'jumlah_nota' => $isi->count(),
                'nota_selesai' => $isi->where('selesai', true)->count(),
                'sisa_tagihan' => $isi->sum('sisa_tagihan'),
                'sisa_sekarang' => $isi->sum(fn (array $item): int => max($item['sisa_sekarang'], 0)),
                'nota' => $isi->values()->all(),
            ])
            ->values()
            ->all();
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
