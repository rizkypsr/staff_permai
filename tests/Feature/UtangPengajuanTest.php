<?php

use App\Models\User;
use App\Models\UtangPengajuan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tabel bisnis milik web admin tidak ada di SQLite tes; buat skema minimal di sini saja.
 */
function buatSkemaUtang(): void
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
        $table->bigInteger('utang_awal')->default(0);
        $table->bigInteger('utang_baru')->default(0);
        $table->bigInteger('utang_pengajuan')->default(0);
        $table->bigInteger('utang_potong')->default(0);
        $table->bigInteger('utang_sisa')->default(0);
        $table->boolean('row_status')->default(true);
        $table->timestamps();
    });

    Schema::create('utang_pengajuan', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_pengguna');
        $table->date('tgl');
        $table->bigInteger('nominal')->default(0);
        $table->string('keterangan')->default('');
        $table->tinyInteger('status')->default(0);
        $table->string('catatan_admin')->default('');
        $table->dateTime('approved_at')->nullable();
        $table->integer('approved_by')->nullable();
        $table->boolean('row_status')->default(true);
        $table->timestamp('created_at')->useCurrent();
        $table->integer('created_by')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->integer('updated_by')->nullable();
    });
}

function buatPengguna(string $nama = 'Staff'): User
{
    return User::forceCreate(['nama' => $nama, 'username' => strtolower($nama)]);
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatPengajuan(User $user, array $atribut = []): UtangPengajuan
{
    return UtangPengajuan::forceCreate([
        'id_pengguna' => $user->id,
        'tgl' => '2026-10-04',
        'nominal' => 100000,
        'keterangan' => 'Keperluan',
        'status' => UtangPengajuan::STATUS_MENUNGGU,
        'row_status' => 1,
        ...$atribut,
    ]);
}

beforeEach(function () {
    buatSkemaUtang();
    $this->withoutVite();
    $this->travelTo('2026-10-04 09:00:00');
});

it('redirects guests to login', function (string $method, string $url) {
    $this->{$method}($url)->assertRedirect(route('login'));
})->with([
    ['get', '/utang'],
    ['get', '/utang/ajukan'],
    ['post', '/utang'],
    ['delete', '/utang/1'],
]);

it('shows the create page', function () {
    $this->actingAs(buatPengguna())
        ->get(route('utang.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('UtangCreate'));
});

it('stores a pending request for the logged in user only from server values', function () {
    $user = buatPengguna();
    $other = buatPengguna('Lain');

    $this->actingAs($user)
        ->post(route('utang.store'), [
            'nominal' => 250000,
            'keterangan' => 'Biaya berobat',
            'id_pengguna' => $other->id,
            'status' => 1,
            'tgl' => '2026-01-01',
            'row_status' => 0,
            'created_by' => $other->id,
        ])
        ->assertRedirect(route('utang.index'));

    $row = DB::table('utang_pengajuan')->sole();

    expect($row->id_pengguna)->toBe($user->id)
        ->and((int) $row->status)->toBe(0)
        ->and($row->tgl)->toStartWith('2026-10-04')
        ->and((int) $row->nominal)->toBe(250000)
        ->and($row->keterangan)->toBe('Biaya berobat')
        ->and((int) $row->row_status)->toBe(1)
        ->and($row->created_by)->toBe($user->id);
});

it('rejects invalid input', function (array $data, string $field) {
    $this->actingAs(buatPengguna())
        ->post(route('utang.store'), $data)
        ->assertSessionHasErrors($field);

    expect(DB::table('utang_pengajuan')->count())->toBe(0);
})->with([
    'nominal nol' => [['nominal' => 0, 'keterangan' => 'x'], 'nominal'],
    'nominal negatif' => [['nominal' => -5000, 'keterangan' => 'x'], 'nominal'],
    'nominal kosong' => [['keterangan' => 'x'], 'nominal'],
    'nominal bukan angka' => [['nominal' => 'abc', 'keterangan' => 'x'], 'nominal'],
    'keterangan kosong' => [['nominal' => 1000, 'keterangan' => ''], 'keterangan'],
    'keterangan terlalu panjang' => [['nominal' => 1000, 'keterangan' => str_repeat('a', 256)], 'keterangan'],
]);

it('lists only the user\'s own active requests, newest first', function () {
    $user = buatPengguna();
    $other = buatPengguna('Lain');

    $lama = buatPengajuan($user, ['tgl' => '2026-09-01']);
    $baru = buatPengajuan($user, ['tgl' => '2026-10-02', 'status' => 2, 'catatan_admin' => 'Saldo kurang']);
    buatPengajuan($user, ['row_status' => 0]);
    buatPengajuan($other);

    $this->actingAs($user)
        ->get(route('utang.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Utang')
            ->has('pengajuan', 2)
            ->where('pengajuan.0.id', $baru->id)
            ->where('pengajuan.0.status', 2)
            ->where('pengajuan.0.catatan_admin', 'Saldo kurang')
            ->where('pengajuan.1.id', $lama->id)
        );
});

it('cancels the user\'s own pending request with a soft delete', function () {
    $user = buatPengguna();
    $pengajuan = buatPengajuan($user);

    $this->actingAs($user)
        ->delete(route('utang.destroy', $pengajuan->id))
        ->assertRedirect(route('utang.index'));

    $row = DB::table('utang_pengajuan')->where('id', $pengajuan->id)->sole();

    expect((int) $row->row_status)->toBe(0)
        ->and($row->updated_by)->toBe($user->id);
});

it('does not cancel another user\'s request', function () {
    $pengajuan = buatPengajuan(buatPengguna('Lain'));

    $this->actingAs(buatPengguna())
        ->delete(route('utang.destroy', $pengajuan->id))
        ->assertNotFound();

    expect((int) DB::table('utang_pengajuan')->value('row_status'))->toBe(1);
});

it('does not cancel a processed request', function (int $status) {
    $user = buatPengguna();
    $pengajuan = buatPengajuan($user, ['status' => $status]);

    $this->actingAs($user)
        ->delete(route('utang.destroy', $pengajuan->id))
        ->assertForbidden();

    expect((int) DB::table('utang_pengajuan')->value('row_status'))->toBe(1);
})->with([
    'disetujui' => UtangPengajuan::STATUS_DISETUJUI,
    'ditolak' => UtangPengajuan::STATUS_DITOLAK,
]);

it('sums all approved requests as remaining debt when there is no payslip', function () {
    $user = buatPengguna();
    buatPengajuan($user, ['tgl' => '2026-08-10', 'nominal' => 100000, 'status' => 1]);
    buatPengajuan($user, ['tgl' => '2026-10-01', 'nominal' => 50000, 'status' => 1]);
    buatPengajuan($user, ['nominal' => 70000, 'status' => 0]);
    buatPengajuan($user, ['nominal' => 30000, 'status' => 0]);
    buatPengajuan($user, ['nominal' => 999000, 'status' => 2]);
    buatPengajuan($user, ['nominal' => 999000, 'status' => 1, 'row_status' => 0]);
    buatPengajuan(buatPengguna('Lain'), ['nominal' => 999000, 'status' => 1]);

    $this->actingAs($user)
        ->get(route('utang.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ringkasan.sisa_utang', 150000)
            ->where('ringkasan.menunggu', 100000)
        );
});

it('uses utang_sisa of the latest active payslip', function () {
    $user = buatPengguna();
    DB::table('gaji')->insert([
        ['id_pengguna' => $user->id, 'periode' => '2026-08', 'utang_sisa' => 400000, 'row_status' => 1],
        ['id_pengguna' => $user->id, 'periode' => '2026-09', 'utang_sisa' => 300000, 'row_status' => 1],
        ['id_pengguna' => $user->id, 'periode' => '2026-10', 'utang_sisa' => 1, 'row_status' => 0],
    ]);
    buatPengajuan($user, ['tgl' => '2026-09-15', 'nominal' => 80000, 'status' => 1]);

    $this->actingAs($user)
        ->get(route('utang.index'))
        ->assertInertia(fn (Assert $page) => $page->where('ringkasan.sisa_utang', 300000));
});

it('adds approved requests dated after the latest payslip month', function () {
    $user = buatPengguna();
    DB::table('gaji')->insert([
        'id_pengguna' => $user->id, 'periode' => '2026-08', 'utang_sisa' => 300000, 'row_status' => 1,
    ]);
    buatPengajuan($user, ['tgl' => '2026-08-31', 'nominal' => 80000, 'status' => 1]);
    buatPengajuan($user, ['tgl' => '2026-09-01', 'nominal' => 50000, 'status' => 1]);
    buatPengajuan($user, ['tgl' => '2026-10-03', 'nominal' => 25000, 'status' => 1]);
    buatPengajuan($user, ['tgl' => '2026-10-04', 'nominal' => 10000, 'status' => 0]);

    $this->actingAs($user)
        ->get(route('utang.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ringkasan.sisa_utang', 375000)
            ->where('ringkasan.menunggu', 10000)
        );
});
