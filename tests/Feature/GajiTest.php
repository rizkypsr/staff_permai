<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tabel bisnis milik web admin tidak ada di SQLite tes; buat skema minimal di sini saja.
 */
function buatSkemaGaji(): void
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

    Schema::create('gaji', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_pengguna');
        $table->char('periode', 7);
        $table->integer('hari_masuk')->default(0);
        $table->integer('hari_telat')->default(0);
        $table->integer('hari_absen')->default(0);
        $table->bigInteger('total_pendapatan')->default(0);
        $table->bigInteger('total_potongan')->default(0);
        $table->bigInteger('utang_awal')->default(0);
        $table->bigInteger('utang_baru')->default(0);
        $table->bigInteger('utang_pengajuan')->default(0);
        $table->bigInteger('utang_potong')->default(0);
        $table->bigInteger('utang_sisa')->default(0);
        $table->bigInteger('total_diterima')->default(0);
        $table->text('catatan')->nullable();
        $table->boolean('row_status')->default(true);
    });

    Schema::create('gaji_detail', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_gaji');
        $table->string('jenis', 10);
        $table->integer('urutan')->default(1);
        $table->string('nama', 100)->default('');
        $table->bigInteger('bulanan')->default(0);
        $table->decimal('hari', 8, 2)->default(0);
        $table->bigInteger('harga')->default(0);
        $table->bigInteger('nominal')->default(0);
        $table->boolean('row_status')->default(true);
    });
}

function buatStaffGaji(string $nama = 'Staff'): User
{
    return User::forceCreate(['nama' => $nama, 'username' => strtolower($nama)]);
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatSlip(User $user, string $periode, array $atribut = []): int
{
    return DB::table('gaji')->insertGetId([
        'id_pengguna' => $user->id,
        'periode' => $periode,
        'row_status' => 1,
        ...$atribut,
    ]);
}

beforeEach(function () {
    buatSkemaGaji();
    $this->withoutVite();
});

it('redirects guests to login', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with(['/gaji', '/gaji/2026-10']);

it('lists only the user\'s own saved payslips, newest first', function () {
    $user = buatStaffGaji();
    $lain = buatStaffGaji('Lain');

    buatSlip($user, '2026-08', ['total_diterima' => 1800000, 'utang_sisa' => 300000]);
    buatSlip($user, '2026-09', ['total_diterima' => 2000000, 'utang_sisa' => 0]);
    buatSlip($user, '2026-10', ['total_diterima' => 999, 'row_status' => 0]);
    buatSlip($lain, '2026-10', ['total_diterima' => 777]);

    $this->actingAs($user)
        ->get(route('gaji.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gaji')
            ->has('slip', 2)
            ->where('slip.0.periode', '2026-09')
            ->where('slip.0.label', 'September 2026')
            ->where('slip.0.total_diterima', 2000000)
            ->where('slip.1.periode', '2026-08')
            ->where('slip.1.utang_sisa', 300000)
        );
});

it('returns 404 for another user\'s payslip, a deleted one or a missing month', function (string $kasus) {
    $user = buatStaffGaji();

    match ($kasus) {
        'milik orang lain' => buatSlip(buatStaffGaji('Lain'), '2026-10'),
        'dihapus' => buatSlip($user, '2026-10', ['row_status' => 0]),
        'tidak ada' => null,
    };

    $this->actingAs($user)
        ->get(route('gaji.show', '2026-10'))
        ->assertNotFound();
})->with(['milik orang lain', 'dihapus', 'tidak ada']);

it('returns 404 for a malformed period', function () {
    $this->actingAs(buatStaffGaji())
        ->get('/gaji/2026-13')
        ->assertNotFound();
});

it('shows the stored payslip numbers as they are', function () {
    $user = buatStaffGaji();
    $id = buatSlip($user, '2026-10', [
        'hari_masuk' => 20,
        'hari_telat' => 2,
        'hari_absen' => 3,
        'total_pendapatan' => 4111111,
        'total_potongan' => 50000,
        'utang_awal' => 500000,
        'utang_baru' => 0,
        'utang_pengajuan' => 150000,
        'utang_potong' => 200000,
        'utang_sisa' => 450000,
        'total_diterima' => 3861111,
        'catatan' => '  Bonus lebaran menyusul  ',
    ]);

    DB::table('gaji_detail')->insert([
        ['id_gaji' => $id, 'jenis' => 'pendapatan', 'urutan' => 2, 'nama' => 'Uang harian', 'bulanan' => 0, 'hari' => 9, 'harga' => 200000, 'nominal' => 1800000, 'row_status' => 1],
        ['id_gaji' => $id, 'jenis' => 'pendapatan', 'urutan' => 1, 'nama' => 'Gaji pokok', 'bulanan' => 2700000, 'hari' => 22, 'harga' => 100000, 'nominal' => 2200000, 'row_status' => 1],
        ['id_gaji' => $id, 'jenis' => 'pendapatan', 'urutan' => 3, 'nama' => 'Lembur', 'bulanan' => 0, 'hari' => 1.5, 'harga' => 74074, 'nominal' => 111111, 'row_status' => 1],
        ['id_gaji' => $id, 'jenis' => 'pendapatan', 'urutan' => 4, 'nama' => 'Insentif', 'bulanan' => 0, 'hari' => 0, 'harga' => 0, 'nominal' => 0, 'row_status' => 1],
        ['id_gaji' => $id, 'jenis' => 'pendapatan', 'urutan' => 5, 'nama' => 'Dihapus', 'bulanan' => 0, 'hari' => 1, 'harga' => 1, 'nominal' => 1, 'row_status' => 0],
        ['id_gaji' => $id, 'jenis' => 'potongan', 'urutan' => 1, 'nama' => 'BPJS', 'bulanan' => 0, 'hari' => 0, 'harga' => 0, 'nominal' => 50000, 'row_status' => 1],
    ]);

    $this->actingAs($user)
        ->get(route('gaji.show', '2026-10'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('GajiDetail')
            ->where('slip.label', 'Oktober 2026')
            ->where('slip.hari_masuk', 20)
            ->where('slip.hari_telat', 2)
            ->where('slip.hari_absen', 3)
            ->where('slip.total_pendapatan', 4111111)
            ->where('slip.total_potongan', 50000)
            ->where('slip.utang_awal', 500000)
            ->where('slip.utang_baru', 0)
            ->where('slip.utang_pengajuan', 150000)
            ->where('slip.utang_potong', 200000)
            ->where('slip.utang_sisa', 450000)
            ->where('slip.total_diterima', 3861111)
            ->where('slip.catatan', 'Bonus lebaran menyusul')
            ->has('pendapatan', 4)
            ->where('pendapatan.0', ['nama' => 'Gaji pokok', 'keterangan' => '22 dari 27 hari', 'nominal' => 2200000])
            ->where('pendapatan.1', ['nama' => 'Uang harian', 'keterangan' => '9 x 200.000', 'nominal' => 1800000])
            ->where('pendapatan.2', ['nama' => 'Lembur', 'keterangan' => '1,5 x 74.074', 'nominal' => 111111])
            ->where('pendapatan.3', ['nama' => 'Insentif', 'keterangan' => '', 'nominal' => 0])
            ->has('potongan', 1)
            ->where('potongan.0', ['nama' => 'BPJS', 'keterangan' => '', 'nominal' => 50000])
        );
});
