<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tabel bisnis milik web admin tidak ada di SQLite tes; buat skema minimal di sini saja.
 */
function buatSkemaUangMakan(): void
{
    Schema::create('pengguna', function (Blueprint $table) {
        $table->increments('id');
        $table->string('nama')->default('');
        $table->string('username')->default('');
        $table->string('email')->default('');
        $table->string('password')->default('');
        $table->string('remember_token')->nullable();
        $table->integer('id_pengguna_grup')->default(2);
        $table->boolean('status')->default(true);
        $table->boolean('row_status')->default(true);
        $table->timestamps();
    });

    Schema::create('absensi', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_pengguna');
        $table->date('tgl');
        $table->tinyInteger('status')->default(0);
        $table->boolean('row_status')->default(true);
    });

    Schema::create('uang_makan_hari', function (Blueprint $table) {
        $table->increments('id');
        $table->date('berlaku_mulai');
        $table->string('hari', 20)->default('');
        $table->boolean('row_status')->default(true);
    });

    Schema::create('uang_makan_staff', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_pengguna');
        $table->date('berlaku_mulai');
        $table->boolean('aktif')->default(true);
        $table->integer('nominal')->default(0);
        $table->boolean('row_status')->default(true);
    });
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatStaffUangMakan(string $nama = 'Staff', array $atribut = []): User
{
    return User::forceCreate(['nama' => $nama, 'username' => strtolower($nama), ...$atribut]);
}

function jadwalAmbil(string $berlakuMulai, string $hari): void
{
    DB::table('uang_makan_hari')->insert(['berlaku_mulai' => $berlakuMulai, 'hari' => $hari, 'row_status' => 1]);
}

function aturUangMakan(User $user, string $berlakuMulai, int $nominal, bool $aktif = true): void
{
    DB::table('uang_makan_staff')->insert([
        'id_pengguna' => $user->id, 'berlaku_mulai' => $berlakuMulai, 'aktif' => $aktif, 'nominal' => $nominal, 'row_status' => 1,
    ]);
}

function absen(User $user, string $tgl, int $status, int $rowStatus = 1): void
{
    DB::table('absensi')->insert(['id_pengguna' => $user->id, 'tgl' => $tgl, 'status' => $status, 'row_status' => $rowStatus]);
}

beforeEach(function () {
    buatSkemaUangMakan();
    $this->withoutVite();
});

it('redirects guests to login', function () {
    $this->get('/uang-makan')->assertRedirect(route('login'));
});

it('lists every eligible active employee per pickup day with per-day rates and totals', function () {
    Carbon::setTestNow('2026-11-20 10:00:00');
    jadwalAmbil('2026-10-01', '3');

    $budi = buatStaffUangMakan('Budi');
    aturUangMakan($budi, '2026-07-01', 15000);
    aturUangMakan($budi, '2026-11-02', 25000);
    absen($budi, '2026-10-29', 1);
    absen($budi, '2026-10-30', 2);
    absen($budi, '2026-10-31', 0);
    absen($budi, '2026-10-31', 1);
    absen($budi, '2026-11-01', 1, rowStatus: 0);
    absen($budi, '2026-11-02', 1);
    absen($budi, '2026-11-03', 0);
    absen($budi, '2026-11-04', 3);
    absen($budi, '2026-11-04', 0);
    absen($budi, '2026-11-20', 0);

    $andi = buatStaffUangMakan('Andi');
    aturUangMakan($andi, '2026-07-01', 20000);
    absen($andi, '2026-10-29', 1);
    absen($andi, '2026-11-04', 2);
    absen($andi, '2026-11-05', 1);
    absen($andi, '2026-11-19', 1);
    absen($andi, '2026-11-20', 1);

    $citra = buatStaffUangMakan('Citra');
    aturUangMakan($citra, '2026-07-01', 10000);

    $tidakTerdaftar = buatStaffUangMakan('Dodi');
    absen($tidakTerdaftar, '2026-11-02', 1);

    $tidakDapat = buatStaffUangMakan('Eka');
    aturUangMakan($tidakDapat, '2026-07-01', 10000, aktif: false);
    absen($tidakDapat, '2026-11-02', 1);

    foreach ([
        buatStaffUangMakan('Admin', ['id_pengguna_grup' => 1]),
        buatStaffUangMakan('Fani', ['status' => 0]),
        buatStaffUangMakan('Gina', ['row_status' => 0]),
    ] as $bukanKaryawan) {
        aturUangMakan($bukanKaryawan, '2026-07-01', 50000);
        absen($bukanKaryawan, '2026-11-02', 1);
    }

    $this->actingAs($budi)
        ->get(route('uang-makan.index', ['periode' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('UangMakan')
            ->where('periode', '2026-11')
            ->where('label', 'November 2026')
            ->has('ambil', 4)
            ->where('ambil.0.tgl_label', 'Rabu, 4 Nov')
            ->where('ambil.0.rentang_label', '29 Okt - 4 Nov')
            ->where('ambil.0.lewat', true)
            ->has('ambil.0.baris', 3)
            ->where('ambil.0.baris.0.nama', 'Andi')
            ->where('ambil.0.baris.0.jumlah_hari', 2)
            ->where('ambil.0.baris.0.total', 40000)
            ->where('ambil.0.baris.0.nominal', [20000])
            ->where('ambil.0.baris.0.hari_hadir', ['Kam 29', 'Rab 4'])
            ->where('ambil.0.baris.1.nama', 'Budi')
            ->where('ambil.0.baris.1.jumlah_hari', 4)
            ->where('ambil.0.baris.1.total', 70000)
            ->where('ambil.0.baris.1.menunggu', 1)
            ->where('ambil.0.baris.1.nominal', [15000, 25000])
            ->where('ambil.0.baris.1.rincian_nominal', [
                ['nominal' => 15000, 'hari' => 3],
                ['nominal' => 25000, 'hari' => 1],
            ])
            ->where('ambil.0.baris.2.nama', 'Citra')
            ->where('ambil.0.baris.2.jumlah_hari', 0)
            ->where('ambil.0.baris.2.total', 0)
            ->where('ambil.0.baris.2.nominal', [10000])
            ->where('ambil.0.total', 110000)
            ->where('ambil.1.rentang_label', '5 Nov - 11 Nov')
            ->where('ambil.1.total', 20000)
            ->where('ambil.2.total', 0)
            ->where('ambil.3.tgl', '2026-11-25')
            ->where('ambil.3.lewat', false)
            ->where('ambil.3.baris.0.total', 40000)
            ->where('ambil.3.baris.1.menunggu', 1)
            ->where('ambil.3.total', 40000)
            ->where('total', 170000)
            ->where('per_karyawan', [
                ['id_pengguna' => $andi->id, 'nama' => 'Andi', 'jumlah_hari' => 5, 'total' => 100000],
                ['id_pengguna' => $budi->id, 'nama' => 'Budi', 'jumlah_hari' => 4, 'total' => 70000],
                ['id_pengguna' => $citra->id, 'nama' => 'Citra', 'jumlah_hari' => 0, 'total' => 0],
            ])
            ->where('berjalan', [
                'tgl' => '2026-11-25',
                'tgl_label' => 'Rabu, 25 Nov',
                'jumlah_staff' => 3,
                'jumlah_hari' => 2,
                'total' => 40000,
                'menunggu' => 1,
            ])
        );
});

it('starts the first pickup at the first schedule date, not before', function () {
    Carbon::setTestNow('2026-10-20 10:00:00');
    jadwalAmbil('2026-10-01', '3,6');
    $user = buatStaffUangMakan();
    aturUangMakan($user, '2026-07-01', 20000);

    absen($user, '2026-09-30', 1);
    absen($user, '2026-10-01', 1);
    absen($user, '2026-10-02', 2);
    absen($user, '2026-10-03', 1);
    absen($user, '2026-10-05', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('ambil', 9)
            ->where('ambil.0.rentang_label', '1 Okt - 3 Okt')
            ->where('ambil.0.baris.0.jumlah_hari', 3)
            ->where('ambil.0.total', 60000)
            ->where('ambil.1.rentang_label', '4 Okt - 7 Okt')
            ->where('ambil.1.total', 20000)
            ->where('total', 80000)
        );
});

it('shows no employee row for a pickup when nobody is eligible in its range', function () {
    Carbon::setTestNow('2026-10-20 10:00:00');
    jadwalAmbil('2026-10-01', '6');
    $user = buatStaffUangMakan();
    aturUangMakan($user, '2026-10-05', 20000);
    absen($user, '2026-10-02', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ambil.0.tgl', '2026-10-03')
            ->has('ambil.0.baris', 0)
            ->where('ambil.0.total', 0)
            ->has('ambil.1.baris', 1)
        );
});

it('has no pickup days before the meal allowance started', function () {
    Carbon::setTestNow('2026-10-20 10:00:00');
    jadwalAmbil('2026-10-01', '3,6');
    $user = buatStaffUangMakan();
    aturUangMakan($user, '2026-07-01', 20000);
    absen($user, '2026-09-30', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('ambil', 0)
            ->where('total', 0)
            ->where('per_karyawan', [])
            ->where('berjalan', null)
        );
});

it('hides the running total when today is a pickup day', function () {
    Carbon::setTestNow('2026-10-10 10:00:00');
    jadwalAmbil('2026-10-01', '3,6');
    $user = buatStaffUangMakan();
    aturUangMakan($user, '2026-07-01', 20000);
    absen($user, '2026-10-08', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periode', '2026-10')
            ->where('berjalan', null)
        );
});

it('falls back to the current month for an invalid period', function () {
    Carbon::setTestNow('2026-10-20 10:00:00');

    $this->actingAs(buatStaffUangMakan())
        ->get(route('uang-makan.index', ['periode' => 'abc']))
        ->assertInertia(fn (Assert $page) => $page->where('periode', '2026-10'));
});
