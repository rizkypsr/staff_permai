<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap uang makan SELURUH karyawan per hari ambil (hanya baca; pemilik memutuskan semua
 * staff boleh melihatnya). Bentuknya mengikuti tanda terima admin (Uang_makan::cetak()),
 * hitungannya port dari application/libraries/Uang_makan_lib.php; angkanya harus sama persis.
 *
 * - Dibayar pada hari ambil (riwayat uang_makan_hari, berlaku_mulai). Satu hari ambil
 *   membayar hari sesudah hari ambil sebelumnya sampai dan termasuk hari ambil itu.
 * - Hari dihitung kalau absensi MAX(status) per tanggal = 1 atau 2; status 0 = menunggu.
 * - Nominal dibaca PER HARI hadir dari riwayat uang_makan_staff yang berlaku pada hari itu.
 * - Karyawan = Penggajian_lib::karyawan() (aktif, bukan grup 1); per hari ambil hanya yang
 *   berhak di minimal satu hari rentangnya, walau 0 hari hadir.
 * - Tidak ada yang disimpan; semua diturunkan ulang dari absensi.
 */
class UangMakanController extends Controller
{
    private const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const NAMA_HARI = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private const HARI_PENDEK = [1 => 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    /** @var Collection<int, object>|null */
    private ?Collection $riwayatHari = null;

    /** @var array<int, list<object>> riwayat uang_makan_staff per id_pengguna, urut naik */
    private array $riwayatStaff = [];

    /** @var array<int, array<string, int>> status absensi per id_pengguna per tanggal */
    private array $absensi = [];

    public function index(Request $request): Response
    {
        $periode = (string) $request->query('periode', '');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $periode = now()->format('Y-m');
        }

        $awal = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfDay();
        $hariIni = now()->toDateString();

        $tglAmbil = $this->tanggalAmbil($periode);
        $rentangBerjalan = $periode === now()->format('Y-m') ? $this->rentangBerjalan($hariIni) : null;

        $karyawan = $this->karyawan();
        $this->muatData($karyawan->pluck('id')->all(), $tglAmbil, $rentangBerjalan, $hariIni);

        $ambil = [];
        $perKaryawan = [];
        $dari = $tglAmbil === [] ? null : $this->dariAmbil($tglAmbil[0]);

        foreach ($tglAmbil as $tgl) {
            $baris = $this->barisKaryawan($karyawan, $dari, $tgl);

            foreach ($baris as $b) {
                $perKaryawan[$b['id_pengguna']] ??= ['id_pengguna' => $b['id_pengguna'], 'nama' => $b['nama'], 'jumlah_hari' => 0, 'total' => 0];
                $perKaryawan[$b['id_pengguna']]['jumlah_hari'] += $b['jumlah_hari'];
                $perKaryawan[$b['id_pengguna']]['total'] += $b['total'];
            }

            $ambil[] = [
                'tgl' => $tgl,
                'tgl_label' => $this->tglIndo($tgl),
                'rentang_label' => $this->tglIndo($dari, false).' - '.$this->tglIndo($tgl, false),
                'lewat' => $tgl <= $hariIni,
                'hari_ini' => $tgl === $hariIni,
                'baris' => $baris,
                'total' => array_sum(array_column($baris, 'total')),
            ];

            $dari = $this->geser($tgl, 1);
        }

        $ringkasan = $karyawan
            ->filter(fn (object $k): bool => isset($perKaryawan[(int) $k->id]))
            ->map(fn (object $k): array => $perKaryawan[(int) $k->id])
            ->values()
            ->all();

        return Inertia::render('UangMakan', [
            'periode' => $periode,
            'label' => $awal->translatedFormat('F Y'),
            'sebelum' => $awal->copy()->subMonthNoOverflow()->format('Y-m'),
            'sesudah' => $awal->copy()->addMonthNoOverflow()->format('Y-m'),
            'ambil' => $ambil,
            'per_karyawan' => $ringkasan,
            'total' => array_sum(array_column($ambil, 'total')),
            'berjalan' => $this->berjalan($karyawan, $rentangBerjalan, $hariIni),
        ]);
    }

    /**
     * Satu baris per karyawan yang berhak di minimal satu hari [dari, sampai], urut nama.
     *
     * @param  Collection<int, object>  $karyawan
     * @return list<array<string, mixed>>
     */
    private function barisKaryawan(Collection $karyawan, string $dari, string $sampai): array
    {
        $baris = [];

        foreach ($karyawan as $k) {
            $r = $this->rentang((int) $k->id, $dari, $sampai);
            if (! $r['berhak']) {
                continue;
            }

            unset($r['berhak']);
            $baris[] = ['id_pengguna' => (int) $k->id, 'nama' => $k->nama, ...$r];
        }

        return $baris;
    }

    /**
     * Uang makan satu karyawan untuk rentang [dari, sampai]. Port rentang() admin;
     * `nominal` = nominal berbeda di hari-hari yang berhak (kolom "Per Hari" tanda terima).
     *
     * @return array{berhak: bool, jumlah_hari: int, total: int, menunggu: int, nominal: list<int>, rincian_nominal: list<array{nominal: int, hari: int}>, hari_hadir: list<string>}
     */
    private function rentang(int $idPengguna, string $dari, string $sampai): array
    {
        $status = $this->absensi[$idPengguna] ?? [];
        $hasil = ['berhak' => false, 'jumlah_hari' => 0, 'total' => 0, 'menunggu' => 0, 'nominal' => [], 'rincian_nominal' => [], 'hari_hadir' => []];
        $nominal = [];
        $perNominal = [];

        for ($d = $dari; $d <= $sampai; $d = $this->geser($d, 1)) {
            $s = $status[$d] ?? null;
            $atur = $this->staffPada($idPengguna, $d);
            $hadir = $s === 1 || $s === 2;

            if ($s === 0) {
                $hasil['menunggu']++;
            }

            if ($atur['aktif']) {
                $hasil['berhak'] = true;
                $nominal[$atur['nominal']] = true;
            }

            if ($hadir && $atur['aktif']) {
                $hasil['jumlah_hari']++;
                $hasil['total'] += $atur['nominal'];
                $perNominal[$atur['nominal']] = ($perNominal[$atur['nominal']] ?? 0) + 1;

                $tanggal = Carbon::parse($d);
                $hasil['hari_hadir'][] = self::HARI_PENDEK[$tanggal->dayOfWeekIso].' '.$tanggal->day;
            }
        }

        $hasil['nominal'] = array_map('intval', array_keys($nominal));
        foreach ($perNominal as $n => $hari) {
            $hasil['rincian_nominal'][] = ['nominal' => (int) $n, 'hari' => $hari];
        }

        return $hasil;
    }

    /**
     * Kotak "Terkumpul sampai hari ini" untuk semua karyawan: rentang yang sedang berjalan,
     * dibayar pada hari ambil berikutnya. Port berjalan() + data_rincian() admin.
     *
     * @param  Collection<int, object>  $karyawan
     * @param  array{dari: string, berikut: string}|null  $rentang
     * @return array{tgl: string, tgl_label: string, jumlah_staff: int, jumlah_hari: int, total: int, menunggu: int}|null
     */
    private function berjalan(Collection $karyawan, ?array $rentang, string $hariIni): ?array
    {
        if ($rentang === null) {
            return null;
        }

        $baris = $this->barisKaryawan($karyawan, $rentang['dari'], $hariIni);

        return [
            'tgl' => $rentang['berikut'],
            'tgl_label' => $this->tglIndo($rentang['berikut']),
            'jumlah_staff' => count($baris),
            'jumlah_hari' => array_sum(array_column($baris, 'jumlah_hari')),
            'total' => array_sum(array_column($baris, 'total')),
            'menunggu' => array_sum(array_column($baris, 'menunggu')),
        ];
    }

    /**
     * Null kalau jadwal kosong, kalau hari ini sendiri hari ambil (sudah ada di daftar),
     * atau kalau rentangnya belum mulai.
     *
     * @return array{dari: string, berikut: string}|null
     */
    private function rentangBerjalan(string $hariIni): ?array
    {
        $berikut = $this->ambilBerikutnya($hariIni);
        if ($berikut === null || $berikut === $hariIni) {
            return null;
        }

        $dari = $this->dariAmbil($berikut);

        return $dari > $hariIni ? null : ['dari' => $dari, 'berikut' => $berikut];
    }

    /**
     * Karyawan seperti Penggajian_lib::karyawan(): aktif, belum dihapus, bukan grup Superadmin.
     *
     * @return Collection<int, object>
     */
    private function karyawan(): Collection
    {
        return DB::table('pengguna')
            ->where('row_status', 1)
            ->where('status', 1)
            ->where('id_pengguna_grup', '!=', 1)
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    /**
     * Muat riwayat uang_makan_staff dan absensi semua karyawan sekali untuk seluruh bulan.
     *
     * @param  list<int>  $ids
     * @param  list<string>  $tglAmbil
     * @param  array{dari: string, berikut: string}|null  $rentangBerjalan
     */
    private function muatData(array $ids, array $tglAmbil, ?array $rentangBerjalan, string $hariIni): void
    {
        if ($ids === []) {
            return;
        }

        DB::table('uang_makan_staff')
            ->where('row_status', 1)
            ->whereIn('id_pengguna', $ids)
            ->orderBy('berlaku_mulai')
            ->orderBy('id')
            ->get(['id_pengguna', 'berlaku_mulai', 'aktif', 'nominal'])
            ->each(function (object $row): void {
                $this->riwayatStaff[(int) $row->id_pengguna][] = (object) [
                    'berlaku_mulai' => Carbon::parse($row->berlaku_mulai)->toDateString(),
                    'aktif' => (int) $row->aktif,
                    'nominal' => (int) $row->nominal,
                ];
            });

        $rentang = [];
        if ($tglAmbil !== []) {
            $rentang[] = [$this->dariAmbil($tglAmbil[0]), end($tglAmbil)];
        }
        if ($rentangBerjalan !== null) {
            $rentang[] = [$rentangBerjalan['dari'], $hariIni];
        }
        if ($rentang === []) {
            return;
        }

        DB::table('absensi')
            ->where('row_status', 1)
            ->whereIn('id_pengguna', $ids)
            ->whereBetween('tgl', [min(array_column($rentang, 0)), max(array_column($rentang, 1))])
            ->groupBy('id_pengguna', 'tgl')
            ->selectRaw('id_pengguna, tgl, MAX(status) AS status')
            ->get()
            ->each(function (object $row): void {
                $this->absensi[(int) $row->id_pengguna][Carbon::parse($row->tgl)->toDateString()] = (int) $row->status;
            });
    }

    /**
     * @return list<string> hari ambil yang tanggalnya jatuh di bulan ini
     */
    private function tanggalAmbil(string $periode): array
    {
        $awal = $periode.'-01';
        $akhir = Carbon::parse($awal)->endOfMonth()->toDateString();
        $tgl = [];

        for ($d = $awal; $d <= $akhir; $d = $this->geser($d, 1)) {
            if ($this->hariAmbil($d)) {
                $tgl[] = $d;
            }
        }

        return $tgl;
    }

    /**
     * Tanggal pertama yang dibayar hari ambil $tgl: sehari sesudah hari ambil sebelumnya.
     * Hari ambil pertama membayar sejak berlaku_mulai jadwal pertama, maksimal 31 hari ke belakang.
     */
    private function dariAmbil(string $tgl): string
    {
        $sebelum = $this->ambilSebelumnya($tgl);
        if ($sebelum !== null) {
            return $this->geser($sebelum, 1);
        }

        $mulai = $tgl;
        foreach ($this->riwayatHari() as $row) {
            if ($this->uraiHari($row->hari) !== []) {
                $mulai = min($tgl, $row->berlaku_mulai);

                break;
            }
        }

        return max($mulai, $this->geser($tgl, -31));
    }

    /** Hari ambil terakhir sebelum $tgl dalam 31 hari, dengan jadwal yang berlaku pada tiap tanggal. */
    private function ambilSebelumnya(string $tgl): ?string
    {
        for ($i = 1; $i <= 31; $i++) {
            $d = $this->geser($tgl, -$i);
            if ($this->hariAmbil($d)) {
                return $d;
            }
        }

        return null;
    }

    /** Hari ambil berikutnya mulai $tgl (termasuk $tgl), maksimal 31 hari ke depan. */
    private function ambilBerikutnya(string $tgl): ?string
    {
        for ($i = 0; $i <= 31; $i++) {
            $d = $this->geser($tgl, $i);
            if ($this->hariAmbil($d)) {
                return $d;
            }
        }

        return null;
    }

    private function hariAmbil(string $tgl): bool
    {
        return in_array(Carbon::parse($tgl)->dayOfWeekIso, $this->hariAmbilPada($tgl), true);
    }

    /**
     * @return list<int> nomor hari ISO (1 Senin ... 7 Minggu)
     */
    private function hariAmbilPada(string $tgl): array
    {
        $pakai = '';
        foreach ($this->riwayatHari() as $row) {
            if ($row->berlaku_mulai > $tgl) {
                break;
            }
            $pakai = $row->hari;
        }

        return $this->uraiHari($pakai);
    }

    /**
     * @return list<int>
     */
    private function uraiHari(string $csv): array
    {
        $hari = [];
        foreach (explode(',', $csv) as $h) {
            $h = (int) trim($h);
            if ($h >= 1 && $h <= 7) {
                $hari[$h] = $h;
            }
        }
        ksort($hari);

        return array_values($hari);
    }

    /**
     * Pengaturan satu karyawan pada $tgl; tidak diatur = tidak dapat.
     *
     * @return array{aktif: bool, nominal: int}
     */
    private function staffPada(int $idPengguna, string $tgl): array
    {
        $hasil = ['aktif' => false, 'nominal' => 0];
        foreach ($this->riwayatStaff[$idPengguna] ?? [] as $row) {
            if ($row->berlaku_mulai > $tgl) {
                break;
            }
            $hasil = ['aktif' => $row->aktif === 1, 'nominal' => $row->nominal];
        }

        return $hasil;
    }

    /**
     * @return Collection<int, object>
     */
    private function riwayatHari(): Collection
    {
        return $this->riwayatHari ??= DB::table('uang_makan_hari')
            ->where('row_status', 1)
            ->orderBy('berlaku_mulai')
            ->orderBy('id')
            ->get(['berlaku_mulai', 'hari'])
            ->map(fn (object $row): object => (object) [
                'berlaku_mulai' => Carbon::parse($row->berlaku_mulai)->toDateString(),
                'hari' => (string) $row->hari,
            ]);
    }

    private function geser(string $tgl, int $hari): string
    {
        return Carbon::parse($tgl)->addDays($hari)->toDateString();
    }

    /** "Rabu, 1 Okt" (tahun ditambah kalau bukan tahun ini), sama dengan tgl_indo() admin. */
    private function tglIndo(string $tgl, bool $denganHari = true): string
    {
        $t = Carbon::parse($tgl);
        $teks = $t->day.' '.self::BULAN[$t->month];

        if ($t->year !== now()->year) {
            $teks .= ' '.$t->year;
        }

        return $denganHari ? self::NAMA_HARI[$t->dayOfWeekIso].', '.$teks : $teks;
    }
}
