<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Tabel bisnis milik web admin tidak ada di SQLite tes; buat skema minimal di sini saja.
 */
function buatSkemaPenagihan(): void
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

    Schema::create('pelanggan', function (Blueprint $table) {
        $table->increments('id');
        $table->string('nama')->default('');
        $table->text('alamat')->nullable();
        $table->string('no_telp')->nullable();
        $table->string('no_hp')->nullable();
        $table->boolean('row_status')->default(true);
    });

    Schema::create('faktur', function (Blueprint $table) {
        $table->increments('id');
        $table->string('no_transaksi');
        $table->date('tgl');
        $table->integer('id_pelanggan');
        $table->string('nama_pelanggan')->nullable();
        $table->text('alamat')->nullable();
        $table->string('no_telp')->nullable();
        $table->integer('grand_total');
        $table->boolean('row_status')->default(true);
    });

    Schema::create('pembayaran_faktur', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_faktur');
        $table->integer('nominal')->nullable();
        $table->boolean('row_status')->default(true);
    });

    Schema::create('penagihan', function (Blueprint $table) {
        $table->increments('id');
        $table->string('no_transaksi')->default('');
        $table->date('tgl');
        $table->integer('id_pengguna');
        $table->string('keterangan')->default('');
        $table->tinyInteger('status')->default(0);
        $table->boolean('row_status')->default(true);
    });

    Schema::create('penagihan_detail', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('id_penagihan');
        $table->integer('id_faktur');
        $table->integer('urutan')->default(1);
        $table->bigInteger('sisa_tagihan')->default(0);
        $table->tinyInteger('status')->default(0);
        $table->date('tgl_selesai')->nullable();
        $table->dateTime('selesai_at')->nullable();
        $table->integer('selesai_by')->nullable();
        $table->boolean('row_status')->default(true);
    });
}

function buatKurir(string $nama = 'Kurir'): User
{
    return User::forceCreate(['nama' => $nama, 'username' => strtolower($nama)]);
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatPenagihan(User $kurir, array $atribut = []): int
{
    return DB::table('penagihan')->insertGetId([
        'no_transaksi' => 'TG2610-001',
        'tgl' => '2026-10-05',
        'id_pengguna' => $kurir->id,
        'keterangan' => '',
        'status' => 0,
        'row_status' => 1,
        ...$atribut,
    ]);
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatFaktur(array $atribut = []): int
{
    $idPelanggan = DB::table('pelanggan')->insertGetId([
        'nama' => 'Cash', 'alamat' => '', 'no_telp' => '', 'no_hp' => '',
    ]);

    return DB::table('faktur')->insertGetId([
        'no_transaksi' => 'A2610-001',
        'tgl' => '2026-10-01',
        'id_pelanggan' => $idPelanggan,
        'grand_total' => 1000000,
        'row_status' => 1,
        ...$atribut,
    ]);
}

function tambahNota(
    int $idPenagihan,
    int $idFaktur,
    int $sisaTagihan,
    int $urutan = 1,
    int $rowStatus = 1,
    ?string $tglSelesai = null,
): void {
    DB::table('penagihan_detail')->insert([
        'id_penagihan' => $idPenagihan,
        'id_faktur' => $idFaktur,
        'urutan' => $urutan,
        'sisa_tagihan' => $sisaTagihan,
        'status' => $tglSelesai === null ? 0 : 1,
        'tgl_selesai' => $tglSelesai,
        'row_status' => $rowStatus,
    ]);
}

beforeEach(function () {
    buatSkemaPenagihan();
    $this->withoutVite();
});

it('redirects guests to login', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with(['/penagihan', '/penagihan/1']);

it('lists only the courier\'s own active tasks with counts and totals', function () {
    $kurir = buatKurir();
    $lain = buatKurir('Lain');

    $lama = buatPenagihan($kurir, ['no_transaksi' => 'TG2609-001', 'tgl' => '2026-09-20', 'status' => 1]);
    $baru = buatPenagihan($kurir, ['no_transaksi' => 'TG2610-002', 'tgl' => '2026-10-05']);
    buatPenagihan($kurir, ['no_transaksi' => 'TG-HAPUS', 'row_status' => 0]);
    buatPenagihan($lain, ['no_transaksi' => 'TG-LAIN']);

    tambahNota($baru, buatFaktur(), 400000, 1);
    tambahNota($baru, buatFaktur(), 250000, 2);
    tambahNota($baru, buatFaktur(), 999000, 3, rowStatus: 0);
    tambahNota($baru, buatFaktur(['row_status' => 0]), 888000, 4);
    tambahNota($lama, buatFaktur(), 100000);

    $this->actingAs($kurir)
        ->get(route('penagihan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Penagihan')
            ->has('tugas', 2)
            ->where('tugas.0.id', $baru)
            ->where('tugas.0.status', 0)
            ->where('tugas.0.jumlah_nota', 2)
            ->where('tugas.0.total_tagihan', 650000)
            ->where('tugas.1.id', $lama)
            ->where('tugas.1.jumlah_nota', 1)
            ->where('ringkasan.jumlah_dalam_penagihan', 1)
            ->where('ringkasan.total_dalam_penagihan', 650000)
        );
});

it('returns 404 for another courier\'s task or a deleted task', function (bool $milikLain, int $rowStatus) {
    $kurir = buatKurir();
    $pemilik = $milikLain ? buatKurir('Lain') : $kurir;
    $id = buatPenagihan($pemilik, ['row_status' => $rowStatus]);

    $this->actingAs($kurir)
        ->get(route('penagihan.show', $id))
        ->assertNotFound();
})->with([
    'milik kurir lain' => [true, 1],
    'sudah dihapus' => [false, 0],
]);

it('computes the current balance from active payments only and flags paid notes', function () {
    $kurir = buatKurir();
    $id = buatPenagihan($kurir, ['keterangan' => 'Pasar Baru']);

    $sebagian = buatFaktur(['no_transaksi' => 'A-SEBAGIAN', 'grand_total' => 1000000]);
    DB::table('pembayaran_faktur')->insert([
        ['id_faktur' => $sebagian, 'nominal' => 300000, 'row_status' => 1],
        ['id_faktur' => $sebagian, 'nominal' => 500000, 'row_status' => 0],
    ]);

    $lunas = buatFaktur(['no_transaksi' => 'A-LUNAS', 'grand_total' => 200000]);
    DB::table('pembayaran_faktur')->insert([
        ['id_faktur' => $lunas, 'nominal' => 150000, 'row_status' => 1],
        ['id_faktur' => $lunas, 'nominal' => 50000, 'row_status' => 1],
    ]);

    tambahNota($id, $lunas, 200000, 2);
    tambahNota($id, $sebagian, 1000000, 1);

    $this->actingAs($kurir)
        ->get(route('penagihan.show', $id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('PenagihanDetail')
            ->where('penagihan.keterangan', 'Pasar Baru')
            ->has('nota', 2)
            ->where('nota.0.no_nota', 'A-SEBAGIAN')
            ->where('nota.0.sisa_tagihan', 1000000)
            ->where('nota.0.sisa_sekarang', 700000)
            ->where('nota.0.lunas', false)
            ->where('nota.1.no_nota', 'A-LUNAS')
            ->where('nota.1.sisa_sekarang', 0)
            ->where('nota.1.lunas', true)
            ->where('total.sisa_tagihan', 1200000)
            ->where('total.sisa_sekarang', 700000)
        );
});

it('uses the invoice contact snapshot and falls back to the customer record', function () {
    $kurir = buatKurir();
    $id = buatPenagihan($kurir);

    $pelangganLengkap = DB::table('pelanggan')->insertGetId([
        'nama' => 'Toko Master', 'alamat' => 'Jl. Master 1', 'no_telp' => '0211111', 'no_hp' => '0812222',
    ]);
    $pelangganTanpaHp = DB::table('pelanggan')->insertGetId([
        'nama' => 'Toko Telp', 'alamat' => 'Jl. Telp 2', 'no_telp' => '0213333', 'no_hp' => '',
    ]);

    $snapshot = buatFaktur([
        'id_pelanggan' => $pelangganLengkap,
        'nama_pelanggan' => 'Budi (pembeli)',
        'alamat' => 'Jl. Snapshot 9',
        'no_telp' => '0899999',
    ]);
    $fallbackHp = buatFaktur(['id_pelanggan' => $pelangganLengkap, 'nama_pelanggan' => '  ', 'alamat' => null, 'no_telp' => '']);
    $fallbackTelp = buatFaktur(['id_pelanggan' => $pelangganTanpaHp]);

    tambahNota($id, $snapshot, 1000, 1);
    tambahNota($id, $fallbackHp, 1000, 2);
    tambahNota($id, $fallbackTelp, 1000, 3);

    $this->actingAs($kurir)
        ->get(route('penagihan.show', $id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nota.0.nama_pelanggan', 'Budi (pembeli)')
            ->where('nota.0.alamat', 'Jl. Snapshot 9')
            ->where('nota.0.no_telp', '0899999')
            ->where('nota.1.nama_pelanggan', 'Toko Master')
            ->where('nota.1.alamat', 'Jl. Master 1')
            ->where('nota.1.no_telp', '0812222')
            ->where('nota.2.nama_pelanggan', 'Toko Telp')
            ->where('nota.2.no_telp', '0213333')
        );
});

it('counts finished notes per task and unfinished notes of open tasks', function () {
    $kurir = buatKurir();
    $terbuka = buatPenagihan($kurir, ['no_transaksi' => 'TG2610-002', 'tgl' => '2026-10-05']);
    $selesai = buatPenagihan($kurir, ['no_transaksi' => 'TG2610-001', 'tgl' => '2026-10-01', 'status' => 1]);

    foreach (range(1, 6) as $urutan) {
        tambahNota($terbuka, buatFaktur(), 1000, $urutan, tglSelesai: '2026-10-05');
    }
    foreach (range(7, 9) as $urutan) {
        tambahNota($terbuka, buatFaktur(), 1000, $urutan, tglSelesai: '2026-10-07');
    }
    tambahNota($terbuka, buatFaktur(), 1000, 10);
    tambahNota($terbuka, buatFaktur(), 1000, 11, rowStatus: 0, tglSelesai: '2026-10-05');
    tambahNota($terbuka, buatFaktur(['row_status' => 0]), 1000, 12, tglSelesai: '2026-10-05');
    tambahNota($terbuka, buatFaktur(['row_status' => 0]), 1000, 13);

    tambahNota($selesai, buatFaktur(), 1000, 1, tglSelesai: '2026-10-02');
    tambahNota($selesai, buatFaktur(), 1000, 2, tglSelesai: '2026-10-03');

    $this->actingAs($kurir)
        ->get(route('penagihan.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tugas.0.id', $terbuka)
            ->where('tugas.0.jumlah_nota', 10)
            ->where('tugas.0.nota_selesai', 9)
            ->where('tugas.1.id', $selesai)
            ->where('tugas.1.jumlah_nota', 2)
            ->where('tugas.1.nota_selesai', 2)
            ->where('ringkasan.jumlah_dalam_penagihan', 1)
            ->where('ringkasan.nota_belum_selesai', 1)
        );
});

it('shows each note\'s finished status and date separately from paid status', function () {
    $kurir = buatKurir();
    $id = buatPenagihan($kurir);

    $belumLunas = buatFaktur(['no_transaksi' => 'A-SELESAI', 'grand_total' => 500000]);
    $lunasBelumSelesai = buatFaktur(['no_transaksi' => 'A-LUNAS', 'grand_total' => 100000]);
    DB::table('pembayaran_faktur')->insert(['id_faktur' => $lunasBelumSelesai, 'nominal' => 100000, 'row_status' => 1]);

    tambahNota($id, $lunasBelumSelesai, 100000, 2);
    tambahNota($id, $belumLunas, 500000, 1, tglSelesai: '2026-10-07');

    $this->actingAs($kurir)
        ->get(route('penagihan.show', $id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nota.0.no_nota', 'A-SELESAI')
            ->where('nota.0.selesai', true)
            ->where('nota.0.tgl_selesai', '07 Okt 2026')
            ->where('nota.0.lunas', false)
            ->where('nota.1.no_nota', 'A-LUNAS')
            ->where('nota.1.selesai', false)
            ->where('nota.1.tgl_selesai', null)
            ->where('nota.1.lunas', true)
        );
});

it('groups notes by displayed customer name in first-appearance order', function () {
    $kurir = buatKurir();
    $id = buatPenagihan($kurir);

    $ptA = DB::table('pelanggan')->insertGetId(['nama' => 'PT A', 'alamat' => '', 'no_telp' => '', 'no_hp' => '']);
    $ptB = DB::table('pelanggan')->insertGetId(['nama' => 'PT B', 'alamat' => '', 'no_telp' => '', 'no_hp' => '']);

    $a1 = buatFaktur(['no_transaksi' => 'A-1', 'id_pelanggan' => $ptA, 'grand_total' => 100000]);
    $b1 = buatFaktur(['no_transaksi' => 'B-1', 'id_pelanggan' => $ptB, 'grand_total' => 50000]);
    $a2 = buatFaktur(['no_transaksi' => 'A-2', 'id_pelanggan' => $ptA, 'nama_pelanggan' => '  pt a ', 'grand_total' => 100000]);
    $cash = DB::table('pelanggan')->insertGetId(['nama' => 'Cash', 'alamat' => '', 'no_telp' => '', 'no_hp' => '']);
    $cashBudi = buatFaktur(['no_transaksi' => 'C-1', 'id_pelanggan' => $cash, 'nama_pelanggan' => 'Budi', 'grand_total' => 30000]);
    $cashSiti = buatFaktur(['no_transaksi' => 'C-2', 'id_pelanggan' => $cash, 'nama_pelanggan' => 'Siti', 'grand_total' => 20000]);
    DB::table('pembayaran_faktur')->insert(['id_faktur' => $a2, 'nominal' => 40000, 'row_status' => 1]);

    tambahNota($id, $a1, 100000, 1, tglSelesai: '2026-10-05');
    tambahNota($id, $b1, 50000, 2);
    tambahNota($id, $cashBudi, 30000, 3);
    tambahNota($id, $a2, 100000, 4);
    tambahNota($id, $cashSiti, 20000, 5);

    $this->actingAs($kurir)
        ->get(route('penagihan.show', $id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('kelompok', 4)
            ->where('kelompok.0.nama_pelanggan', 'PT A')
            ->where('kelompok.0.jumlah_nota', 2)
            ->where('kelompok.0.nota_selesai', 1)
            ->where('kelompok.0.sisa_tagihan', 200000)
            ->where('kelompok.0.sisa_sekarang', 160000)
            ->where('kelompok.0.nota.0.no_nota', 'A-1')
            ->where('kelompok.0.nota.1.no_nota', 'A-2')
            ->where('kelompok.1.nama_pelanggan', 'PT B')
            ->where('kelompok.1.jumlah_nota', 1)
            ->where('kelompok.1.sisa_tagihan', 50000)
            ->where('kelompok.2.nama_pelanggan', 'Budi')
            ->where('kelompok.2.nota.0.no_nota', 'C-1')
            ->where('kelompok.3.nama_pelanggan', 'Siti')
            ->where('total.sisa_tagihan', 300000)
            ->where('total.sisa_sekarang', 260000)
        );
});
