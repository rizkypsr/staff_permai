<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Slip gaji milik user yang login (hanya baca). Semua angka dihitung dan disimpan
 * web admin (Penggajian_lib::rantai()); di sini hanya ditampilkan apa adanya,
 * mengikuti slip cetak admin (application/modules/gaji/views/slip_isi.php).
 */
class GajiController extends Controller
{
    public function index(Request $request): Response
    {
        $slip = DB::table('gaji')
            ->where('id_pengguna', $request->user()->id)
            ->where('row_status', 1)
            ->orderByDesc('periode')
            ->orderByDesc('id')
            ->get(['periode', 'total_diterima', 'utang_sisa'])
            ->unique('periode')
            ->map(fn (object $row): array => [
                'periode' => $row->periode,
                'label' => $this->labelPeriode($row->periode),
                'total_diterima' => (int) $row->total_diterima,
                'utang_sisa' => (int) $row->utang_sisa,
            ])
            ->values();

        return Inertia::render('Gaji', ['slip' => $slip]);
    }

    public function show(Request $request, string $periode): Response
    {
        $slip = DB::table('gaji')
            ->where('id_pengguna', $request->user()->id)
            ->where('periode', $periode)
            ->where('row_status', 1)
            ->orderByDesc('id')
            ->first();

        abort_if($slip === null, 404);

        $detail = DB::table('gaji_detail')
            ->where('id_gaji', $slip->id)
            ->where('row_status', 1)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get(['jenis', 'nama', 'bulanan', 'hari', 'harga', 'nominal']);

        $baris = fn (string $jenis): array => $detail
            ->where('jenis', $jenis)
            ->map(fn (object $row): array => [
                'nama' => $row->nama,
                'keterangan' => $this->keterangan($row),
                'nominal' => (int) $row->nominal,
            ])
            ->values()
            ->all();

        return Inertia::render('GajiDetail', [
            'slip' => [
                'periode' => $slip->periode,
                'label' => $this->labelPeriode($slip->periode),
                'hari_masuk' => (int) $slip->hari_masuk,
                'hari_telat' => (int) $slip->hari_telat,
                'hari_absen' => (int) $slip->hari_absen,
                'total_pendapatan' => (int) $slip->total_pendapatan,
                'total_potongan' => (int) $slip->total_potongan,
                'utang_awal' => (int) $slip->utang_awal,
                'utang_baru' => (int) $slip->utang_baru,
                'utang_pengajuan' => (int) ($slip->utang_pengajuan ?? 0),
                'utang_potong' => (int) $slip->utang_potong,
                'utang_sisa' => (int) $slip->utang_sisa,
                'total_diterima' => (int) $slip->total_diterima,
                'catatan' => trim((string) $slip->catatan),
            ],
            'pendapatan' => $baris('pendapatan'),
            'potongan' => $baris('potongan'),
        ]);
    }

    /**
     * "9 x 200.000", atau "22 dari 27 hari" untuk gaji bulanan (hari kerja =
     * round(bulanan / harga)). Kosong kalau baris tidak memakai hari dan harga.
     * Sama dengan $hitungan di slip_isi.php admin.
     */
    private function keterangan(object $row): string
    {
        $hari = (float) $row->hari;
        $harga = (int) $row->harga;

        if ($hari <= 0 || $harga <= 0) {
            return '';
        }

        $teksHari = rtrim(rtrim(number_format($hari, 2, ',', ''), '0'), ',');

        if ((int) $row->bulanan > 0) {
            return $teksHari.' dari '.(int) round($row->bulanan / $harga).' hari';
        }

        return $teksHari.' x '.number_format($harga, 0, ',', '.');
    }

    private function labelPeriode(string $periode): string
    {
        return Carbon::createFromFormat('Y-m-d', $periode.'-01')->translatedFormat('F Y');
    }
}
