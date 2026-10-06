<?php

use App\Models\User;
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

function buatStaffUangMakan(string $nama = 'Staff'): User
{
    return User::forceCreate(['nama' => $nama, 'username' => strtolower($nama)]);
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

it('pays a range that crosses months with a mid-range raise, pending days and duplicate attendance rows', function () {
    $this->travelTo('2026-11-20 10:00:00');
    $user = buatStaffUangMakan();
    $lain = buatStaffUangMakan('Lain');

    jadwalAmbil('2026-10-01', '3');
    aturUangMakan($user, '2026-07-01', 15000);
    aturUangMakan($user, '2026-11-02', 25000);
    aturUangMakan($lain, '2026-07-01', 99000);

    absen($user, '2026-10-29', 1);
    absen($user, '2026-10-30', 2);
    absen($user, '2026-10-31', 0);
    absen($user, '2026-10-31', 1);
    absen($user, '2026-11-01', 1, rowStatus: 0);
    absen($user, '2026-11-02', 1);
    absen($user, '2026-11-03', 0);
    absen($user, '2026-11-04', 3);
    absen($user, '2026-11-04', 0);
    absen($lain, '2026-11-02', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('UangMakan')
            ->where('periode', '2026-11')
            ->where('label', 'November 2026')
            ->where('sebelum', '2026-10')
            ->where('sesudah', '2026-12')
            ->where('terdaftar', true)
            ->has('ambil', 4)
            ->where('ambil.0.tgl', '2026-11-04')
            ->where('ambil.0.tgl_label', 'Rabu, 4 Nov')
            ->where('ambil.0.rentang_label', '29 Okt - 4 Nov')
            ->where('ambil.0.jumlah_hari', 4)
            ->where('ambil.0.total', 70000)
            ->where('ambil.0.menunggu', 1)
            ->where('ambil.0.lewat', true)
            ->where('ambil.0.rincian_nominal', [
                ['nominal' => 15000, 'hari' => 3],
                ['nominal' => 25000, 'hari' => 1],
            ])
            ->where('ambil.0.hari', [
                ['tgl' => '2026-10-29', 'label' => 'Kamis 29', 'kelas' => 'hadir'],
                ['tgl' => '2026-10-30', 'label' => 'Jumat 30', 'kelas' => 'hadir'],
                ['tgl' => '2026-10-31', 'label' => 'Sabtu 31', 'kelas' => 'hadir'],
                ['tgl' => '2026-11-02', 'label' => 'Senin 2', 'kelas' => 'hadir'],
                ['tgl' => '2026-11-03', 'label' => 'Selasa 3', 'kelas' => 'menunggu'],
            ])
            ->where('ambil.1.rentang_label', '5 Nov - 11 Nov')
            ->where('ambil.1.total', 0)
            ->where('ambil.3.tgl', '2026-11-25')
            ->where('ambil.3.lewat', false)
            ->where('total', 70000)
            ->where('total_lewat', 70000)
            ->where('jumlah_hari', 4)
            ->where('menunggu', 1)
            ->where('nominal_bulan', [
                ['rentang_label' => '1 Nov - 1 Nov', 'nominal' => 15000],
                ['rentang_label' => '2 Nov - 30 Nov', 'nominal' => 25000],
            ])
        );
});

it('starts the first pickup at the first schedule date, not before', function () {
    $this->travelTo('2026-10-20 10:00:00');
    $user = buatStaffUangMakan();

    jadwalAmbil('2026-10-01', '3,6');
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
            ->where('ambil.0.tgl', '2026-10-03')
            ->where('ambil.0.rentang_label', '1 Okt - 3 Okt')
            ->where('ambil.0.jumlah_hari', 3)
            ->where('ambil.0.total', 60000)
            ->where('ambil.1.tgl', '2026-10-07')
            ->where('ambil.1.rentang_label', '4 Okt - 7 Okt')
            ->where('ambil.1.total', 20000)
            ->where('total', 80000)
        );
});

it('has no pickup days before the meal allowance started', function () {
    $this->travelTo('2026-10-20 10:00:00');
    $user = buatStaffUangMakan();
    jadwalAmbil('2026-10-01', '3,6');
    aturUangMakan($user, '2026-07-01', 20000);
    absen($user, '2026-09-30', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('terdaftar', true)
            ->has('ambil', 0)
            ->where('total', 0)
        );
});

it('shows a message instead of an error when the user is not registered', function () {
    $this->travelTo('2026-10-20 10:00:00');
    $user = buatStaffUangMakan();
    jadwalAmbil('2026-10-01', '3,6');
    absen($user, '2026-10-01', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('periode', '2026-10')
            ->where('terdaftar', false)
            ->where('total', 0)
        );
});

it('counts attended days without allowance as not paid', function () {
    $this->travelTo('2026-10-20 10:00:00');
    $user = buatStaffUangMakan();
    jadwalAmbil('2026-10-01', '6');
    aturUangMakan($user, '2026-07-01', 20000);
    aturUangMakan($user, '2026-10-02', 20000, aktif: false);

    absen($user, '2026-10-01', 1);
    absen($user, '2026-10-02', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('terdaftar', true)
            ->where('ambil.0.jumlah_hari', 1)
            ->where('ambil.0.total', 20000)
            ->where('ambil.0.hari.1.kelas', 'tidak-dapat')
        );
});

it('falls back to the current month for an invalid period', function () {
    $this->travelTo('2026-10-20 10:00:00');

    $this->actingAs(buatStaffUangMakan())
        ->get(route('uang-makan.index', ['periode' => 'abc']))
        ->assertInertia(fn (Assert $page) => $page->where('periode', '2026-10'));
});

it('shows the running total up to today, paid on the next pickup day', function () {
    Carbon\Carbon::setTestNow('2026-10-09 10:00:00');
    $user = buatStaffUangMakan();
    jadwalAmbil('2026-10-01', '3,6');
    aturUangMakan($user, '2026-07-01', 20000);

    absen($user, '2026-10-07', 1);
    absen($user, '2026-10-08', 1);
    absen($user, '2026-10-08', 0);
    absen($user, '2026-10-09', 0);

    $this->actingAs($user)
        ->get(route('uang-makan.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periode', '2026-10')
            ->where('berjalan', [
                'tgl' => '2026-10-10',
                'tgl_label' => 'Sabtu, 10 Okt',
                'jumlah_hari' => 1,
                'total' => 20000,
                'menunggu' => 1,
            ])
        );
});

it('hides the running total when today is a pickup day', function () {
    Carbon\Carbon::setTestNow('2026-10-10 10:00:00');
    $user = buatStaffUangMakan();
    jadwalAmbil('2026-10-01', '3,6');
    aturUangMakan($user, '2026-07-01', 20000);
    absen($user, '2026-10-08', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index'))
        ->assertInertia(fn (Assert $page) => $page->where('berjalan', null));
});

it('hides the running total for another month', function () {
    Carbon\Carbon::setTestNow('2026-10-09 10:00:00');
    $user = buatStaffUangMakan();
    jadwalAmbil('2026-10-01', '3,6');
    aturUangMakan($user, '2026-07-01', 20000);
    absen($user, '2026-10-08', 1);

    $this->actingAs($user)
        ->get(route('uang-makan.index', ['periode' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page->where('berjalan', null));
});
