<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rekap uang makan milik user yang login (hanya baca). Port dari
 * application/libraries/Uang_makan_lib.php di web admin; angkanya harus sama persis.
 *
 * - Dibayar pada hari ambil (riwayat uang_makan_hari, berlaku_mulai). Satu hari ambil
 *   membayar hari sesudah hari ambil sebelumnya sampai dan termasuk hari ambil itu.
 * - Hari dihitung kalau absensi MAX(status) per tanggal = 1 atau 2; status 0 = menunggu.
 * - Nominal dibaca PER HARI hadir dari riwayat uang_makan_staff yang berlaku pada hari itu.
 * - Tidak ada yang disimpan; semua diturunkan ulang dari absensi.
 */
class UangMakanController extends Controller
{
    private const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const NAMA_HARI = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /** @var Collection<int, object>|null */
    private ?Collection $riwayatHari = null;

    /** @var Collection<int, object>|null */
    private ?Collection $riwayatStaff = null;

    private int $userId;

    public function index(Request $request): Response
    {
        $this->userId = $request->user()->id;

        $periode = (string) $request->query('periode', '');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode)) {
            $periode = now()->format('Y-m');
        }

        $awal = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfDay();
        $hitung = $this->hitung($periode);

        return Inertia::render('UangMakan', [
            'periode' => $periode,
            'label' => $awal->translatedFormat('F Y'),
            'sebelum' => $awal->copy()->subMonthNoOverflow()->format('Y-m'),
            'sesudah' => $awal->copy()->addMonthNoOverflow()->format('Y-m'),
            'terdaftar' => $this->dapatDiBulan($periode),
            'nominal_bulan' => $this->segmenNominal($periode),
            'ambil' => $hitung['ambil'],
            'total' => $hitung['total'],
            'total_lewat' => $hitung['total_lewat'],
            'jumlah_hari' => $hitung['jumlah_hari'],
            'menunggu' => $hitung['menunggu'],
            'berjalan' => $periode === now()->format('Y-m') ? $this->berjalan(now()->toDateString()) : null,
        ]);
    }

    /**
     * Uang makan satu bulan: hari ambil yang TANGGALNYA jatuh di bulan ini.
     *
     * @return array{ambil: list<array<string, mixed>>, total: int, total_lewat: int, jumlah_hari: int, menunggu: int}
     */
    private function hitung(string $periode): array
    {
        $awal = $periode.'-01';
        $akhir = Carbon::parse($awal)->endOfMonth()->toDateString();
        $hariIni = now()->toDateString();

        $tglAmbil = [];
        for ($d = $awal; $d <= $akhir; $d = $this->geser($d, 1)) {
            if ($this->hariAmbil($d)) {
                $tglAmbil[] = $d;
            }
        }

        $hasil = ['ambil' => [], 'total' => 0, 'total_lewat' => 0, 'jumlah_hari' => 0, 'menunggu' => 0];

        if ($tglAmbil === []) {
            return $hasil;
        }

        $dari = $this->dariAmbil($tglAmbil[0]);
        $status = $this->absensi($dari, end($tglAmbil));

        foreach ($tglAmbil as $tgl) {
            $baris = $this->rentang($dari, $tgl, $status);
            $lewat = $tgl <= $hariIni;

            $hasil['ambil'][] = [
                'tgl' => $tgl,
                'tgl_label' => $this->tglIndo($tgl),
                'rentang_label' => $this->tglIndo($dari, false).' - '.$this->tglIndo($tgl, false),
                'lewat' => $lewat,
                'hari_ini' => $tgl === $hariIni,
                ...$baris,
            ];
            $hasil['total'] += $baris['total'];
            $hasil['jumlah_hari'] += $baris['jumlah_hari'];
            $hasil['menunggu'] += $baris['menunggu'];
            if ($lewat) {
                $hasil['total_lewat'] += $baris['total'];
            }

            $dari = $this->geser($tgl, 1);
        }

        return $hasil;
    }

    /**
     * Rentang yang sedang berjalan sampai $hariIni, dibayar pada hari ambil berikutnya.
     * Null kalau jadwal kosong, kalau hari ini sendiri hari ambil (sudah ada di daftar),
     * atau kalau rentangnya belum mulai. Port berjalan() + data_rincian() admin.
     *
     * @return array{tgl: string, tgl_label: string, jumlah_hari: int, total: int, menunggu: int}|null
     */
    private function berjalan(string $hariIni): ?array
    {
        $berikut = $this->ambilBerikutnya($hariIni);
        if ($berikut === null || $berikut === $hariIni) {
            return null;
        }

        $dari = $this->dariAmbil($berikut);
        if ($dari > $hariIni) {
            return null;
        }

        $baris = $this->rentang($dari, $hariIni, $this->absensi($dari, $hariIni));

        return [
            'tgl' => $berikut,
            'tgl_label' => $this->tglIndo($berikut),
            'jumlah_hari' => $baris['jumlah_hari'],
            'total' => $baris['total'],
            'menunggu' => $baris['menunggu'],
        ];
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

    /**
     * Satu rentang [dari, sampai] dari peta status absensi.
     *
     * @param  array<string, int>  $status
     * @return array{jumlah_hari: int, total: int, menunggu: int, rincian_nominal: list<array{nominal: int, hari: int}>, hari: list<array{tgl: string, label: string, kelas: string}>}
     */
    private function rentang(string $dari, string $sampai, array $status): array
    {
        $baris = ['jumlah_hari' => 0, 'total' => 0, 'menunggu' => 0, 'rincian_nominal' => [], 'hari' => []];
        $perNominal = [];

        for ($d = $dari; $d <= $sampai; $d = $this->geser($d, 1)) {
            $s = $status[$d] ?? null;
            $atur = $this->staffPada($d);
            $hadir = $s === 1 || $s === 2;

            if ($s === 0) {
                $baris['menunggu']++;
            }

            if ($hadir && $atur['aktif']) {
                $baris['jumlah_hari']++;
                $baris['total'] += $atur['nominal'];
                $perNominal[$atur['nominal']] = ($perNominal[$atur['nominal']] ?? 0) + 1;
            }

            if ($s !== null && $s !== 3) {
                $tanggal = Carbon::parse($d);
                $baris['hari'][] = [
                    'tgl' => $d,
                    'label' => self::NAMA_HARI[$tanggal->dayOfWeekIso].' '.$tanggal->day,
                    'kelas' => $hadir ? ($atur['aktif'] ? 'hadir' : 'tidak-dapat') : 'menunggu',
                ];
            }
        }

        foreach ($perNominal as $nominal => $hari) {
            $baris['rincian_nominal'][] = ['nominal' => (int) $nominal, 'hari' => $hari];
        }

        return $baris;
    }

    /**
     * Status absensi per tanggal; tanggal ganda memakai MAX(status) seperti admin.
     *
     * @return array<string, int>
     */
    private function absensi(string $dari, string $sampai): array
    {
        return DB::table('absensi')
            ->where('row_status', 1)
            ->where('id_pengguna', $this->userId)
            ->whereBetween('tgl', [$dari, $sampai])
            ->groupBy('tgl')
            ->selectRaw('tgl, MAX(status) AS status')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                Carbon::parse($row->tgl)->toDateString() => (int) $row->status,
            ])
            ->all();
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
     * Pengaturan staff pada $tgl; tidak diatur = tidak dapat.
     *
     * @return array{aktif: bool, nominal: int}
     */
    private function staffPada(string $tgl): array
    {
        $hasil = ['aktif' => false, 'nominal' => 0];
        foreach ($this->riwayatStaff() as $row) {
            if ($row->berlaku_mulai > $tgl) {
                break;
            }
            $hasil = ['aktif' => (int) $row->aktif === 1, 'nominal' => (int) $row->nominal];
        }

        return $hasil;
    }

    /** Dapat uang makan di salah satu hari bulan ini. */
    private function dapatDiBulan(string $periode): bool
    {
        $awal = $periode.'-01';
        $akhir = Carbon::parse($awal)->endOfMonth()->toDateString();

        if ($this->staffPada($awal)['aktif']) {
            return true;
        }

        return $this->riwayatStaff()->contains(
            fn (object $row): bool => (int) $row->aktif === 1 && $row->berlaku_mulai >= $awal && $row->berlaku_mulai <= $akhir
        );
    }

    /**
     * Nominal per hari sepanjang bulan, dipecah di tanggal perubahannya (null = tidak dapat).
     *
     * @return list<array{rentang_label: string, nominal: int|null}>
     */
    private function segmenNominal(string $periode): array
    {
        $awal = $periode.'-01';
        $akhir = Carbon::parse($awal)->endOfMonth()->toDateString();
        $segmen = [];

        for ($d = $awal; $d <= $akhir; $d = $this->geser($d, 1)) {
            $atur = $this->staffPada($d);
            $nilai = $atur['aktif'] ? $atur['nominal'] : null;
            $n = count($segmen);

            if ($n > 0 && $segmen[$n - 1]['nominal'] === $nilai) {
                $segmen[$n - 1]['sampai'] = $d;
            } else {
                $segmen[] = ['dari' => $d, 'sampai' => $d, 'nominal' => $nilai];
            }
        }

        return array_map(fn (array $s): array => [
            'rentang_label' => $this->tglIndo($s['dari'], false).' - '.$this->tglIndo($s['sampai'], false),
            'nominal' => $s['nominal'],
        ], $segmen);
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

    /**
     * @return Collection<int, object>
     */
    private function riwayatStaff(): Collection
    {
        return $this->riwayatStaff ??= DB::table('uang_makan_staff')
            ->where('row_status', 1)
            ->where('id_pengguna', $this->userId)
            ->orderBy('berlaku_mulai')
            ->orderBy('id')
            ->get(['berlaku_mulai', 'aktif', 'nominal'])
            ->map(fn (object $row): object => (object) [
                'berlaku_mulai' => Carbon::parse($row->berlaku_mulai)->toDateString(),
                'aktif' => (int) $row->aktif,
                'nominal' => (int) $row->nominal,
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
