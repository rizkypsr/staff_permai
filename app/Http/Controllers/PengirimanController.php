<?php

namespace App\Http\Controllers;

use App\Models\Faktur;
use App\Models\Jstok;
use App\Models\Pengiriman;
use App\Models\PengirimanDetailNota;
use App\Models\PengirimanPerson;
use App\Models\RefLookup;
use App\Models\RefProduk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PengirimanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get pengiriman IDs where user is involved
        $pengirimanIds = PengirimanPerson::where('id_pengguna', $user->id)
            ->where('row_status', 1)
            ->pluck('id_pengiriman');

        // Get pengiriman data - only approved (is_approve = 1)
        $pengiriman = Pengiriman::whereIn('id', $pengirimanIds)
            ->where('status', 1)
            ->where('row_status', 1)
            ->where('is_approve', 1) // Only show approved pengiriman
            ->with(['pelanggan', 'persons.pengguna'])
            ->orderBy('tgl', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'no_transaksi' => $item->no_transaksi,
                    'tgl' => $item->tgl->format('Y-m-d'),
                    'tgl_formatted' => $item->tgl->translatedFormat('d F Y'),
                    'pelanggan' => $item->pelanggan ? $item->pelanggan->nama : '-',
                    'alamat' => $item->alamat,
                    'status' => $item->status,
                    'status_text' => $this->getStatusText($item->status),
                    'is_approve' => $item->is_approve,
                    'qty_pesan' => $item->qty_pesan,
                    'qty_nota' => $item->qty_nota,
                    'persons' => $item->persons->map(function ($person) {
                        return [
                            'nama' => $person->pengguna->nama,
                        ];
                    }),
                ];
            });

        return Inertia::render('Pengiriman', [
            'pengiriman' => $pengiriman,
        ]);
    }

    public function show(Request $request, $id): Response
    {
        $user = $request->user();

        // Get pengiriman with relations
        $pengiriman = Pengiriman::with([
            'pelanggan',
            'persons.pengguna',
        ])
            ->where('id', $id)
            ->where('is_approve', 1)
            ->where('row_status', 1)
            ->firstOrFail();

        // Check if user is involved in this pengiriman
        $isInvolved = PengirimanPerson::where('id_pengiriman', $id)
            ->where('id_pengguna', $user->id)
            ->where('row_status', 1)
            ->exists();

        if (! $isInvolved) {
            abort(403, 'Anda tidak memiliki akses ke pengiriman ini');
        }

        // Get faktur info
        $faktur = Faktur::find($pengiriman->id_faktur);

        // Get produk nota
        $produkNota = PengirimanDetailNota::where('id_pengiriman', $id)
            ->where('row_status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'uraian' => $item->uraian,
                    'qty' => $item->qty,
                    'satuan' => $item->satuan,
                ];
            });

        // Get produk pipa
        $produkPipa = DB::table('pengiriman_detail')
            ->where('id_pengiriman', $id)
            ->where('row_status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'uraian' => $item->uraian,
                    'qty' => $item->qty,
                    'satuan' => $item->satuan,
                ];
            });

        // Get persons
        $persons = $pengiriman->persons->map(function ($person) {
            return [
                'id' => $person->id,
                'nama' => $person->pengguna->nama,
                'tipe' => $person->tipe,
            ];
        });

        $data = [
            'id' => $pengiriman->id,
            'no_transaksi' => $pengiriman->no_transaksi,
            'tgl' => $pengiriman->tgl->format('Y-m-d'),
            'tgl_formatted' => $pengiriman->tgl->translatedFormat('d F Y'),
            'no_nota' => $faktur ? $faktur->no_transaksi : '-',
            'pelanggan' => $pengiriman->pelanggan ? $pengiriman->pelanggan->nama : '-',
            'no_telp' => $pengiriman->pelanggan ? $pengiriman->pelanggan->no_hp : '-',
            'alamat' => $pengiriman->alamat,
            'keterangan' => $pengiriman->keterangan,
            'status' => $pengiriman->status,
            'status_text' => $this->getStatusText($pengiriman->status),
            'is_approve' => $pengiriman->is_approve,
            'qty_pesan' => $pengiriman->qty_pesan,
            'qty_nota' => $pengiriman->qty_nota,
            'produk_nota' => $produkNota,
            'produk_pipa' => $produkPipa,
            'persons' => $persons,
        ];

        return Inertia::render('PengirimanDetail', [
            'pengiriman' => $data,
        ]);
    }

    public function create(Request $request): Response
    {
        // Get faktur list (is_kirim = 0 or selected faktur)
        $fakturList = Faktur::where('row_status', 1)
            ->where('is_kirim', 0)
            ->with('pelanggan:id,nama,alamat')
            ->orderBy('no_transaksi', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'no_transaksi' => $item->no_transaksi,
                    'tgl' => $item->tgl->format('Y-m-d'),
                    'id_pelanggan' => $item->id_pelanggan,
                    'pelanggan_nama' => $item->pelanggan ? $item->pelanggan->nama : '-',
                    'pelanggan_alamat' => $item->pelanggan ? $item->pelanggan->alamat : '',
                    'keterangan' => $item->keterangan,
                ];
            });

        // Get the latest one for auto-select
        $latestFaktur = $fakturList->first();

        // Get produk nota from faktur_detail for the latest faktur
        $produkNota = [];
        if ($latestFaktur) {
            $produkNota = DB::table('ref_produk as a')
                ->join('ref_lookup as b', 'a.id_satuan', '=', 'b.id')
                ->join('faktur_detail as c', 'c.id_produk', '=', 'a.id')
                ->select(
                    'a.*',
                    'c.uraian as uraian_produk',
                    DB::raw("CONCAT(a.kode, ' · ', a.nama) as kode_nama"),
                    'b.nama as satuan',
                    'c.qty',
                    'c.id as id_faktur_detail',
                    'c.harga_satuan',
                    'c.diskon',
                    'c.sub_total',
                    'c.id_satuan'
                )
                ->where('a.row_status', 1)
                ->where('c.id_faktur', $latestFaktur['id'])
                ->orderBy('a.kode')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'id_faktur_detail' => $item->id_faktur_detail,
                        'id_produk' => $item->id,
                        'kode_nama' => $item->kode_nama,
                        'uraian' => $item->uraian_produk,
                        'qty' => $item->qty,
                        'qty_kirim' => $item->qty, // Default qty_kirim = qty
                        'satuan' => $item->satuan,
                        'harga_satuan' => $item->harga_satuan,
                        'diskon' => $item->diskon,
                        'sub_total' => $item->sub_total,
                        'id_satuan' => $item->id_satuan,
                    ];
                })
                ->toArray();
        }

        // Get pipa products (tipe = Pipa)
        $pipaTipeId = RefLookup::where('kategori', 'tipe')
            ->where('nama', 'Pipa')
            ->where('row_status', 1)
            ->value('id');

        $pipaList = [];
        if ($pipaTipeId) {
            $pipaList = RefProduk::where('id_tipe', $pipaTipeId)
                ->where('row_status', 1)
                ->get()
                ->map(function ($item) {
                    // Calculate stock from jstok
                    $stok = Jstok::where('id_produk', $item->id)
                        ->where('row_status', 1)
                        ->sum('qty');

                    return [
                        'id' => $item->id,
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'stok' => $stok,
                        'id_satuan' => $item->id_satuan,
                        'label' => "{$item->kode} - {$item->nama} | Qty: {$stok}",
                    ];
                })
                ->sortByDesc('stok') // Sort by stock descending
                ->values()
                ->toArray();
        }

        // Get users/pengguna list
        $penggunaList = User::where('status', 1)
            ->where('row_status', 1)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                ];
            })
            ->toArray();

        return Inertia::render('PengirimanCreate', [
            'fakturList' => $fakturList,
            'latestFaktur' => $latestFaktur,
            'produkNota' => $produkNota,
            'pipaList' => $pipaList,
            'penggunaList' => $penggunaList,
        ]);
    }

    public function getProdukNota($id)
    {
        // Get produk nota from ref_produk with join to ref_lookup and faktur_detail
        $produkNota = DB::table('ref_produk as a')
            ->join('ref_lookup as b', 'a.id_satuan', '=', 'b.id')
            ->join('faktur_detail as c', 'c.id_produk', '=', 'a.id')
            ->select(
                'a.*',
                'c.uraian as uraian_produk',
                DB::raw("CONCAT(a.kode, ' · ', a.nama) as kode_nama"),
                'b.nama as satuan',
                'c.qty',
                'c.id as id_faktur_detail',
                'c.harga_satuan',
                'c.diskon',
                'c.sub_total',
                'c.id_satuan'
            )
            ->where('a.row_status', 1)
            ->where('c.id_faktur', $id)
            ->orderBy('a.kode')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'id_faktur_detail' => $item->id_faktur_detail,
                    'id_produk' => $item->id,
                    'kode_nama' => $item->kode_nama,
                    'uraian' => $item->uraian_produk,
                    'qty' => $item->qty,
                    'qty_kirim' => $item->qty, // Default qty_kirim = qty
                    'satuan' => $item->satuan,
                    'harga_satuan' => $item->harga_satuan,
                    'diskon' => $item->diskon,
                    'sub_total' => $item->sub_total,
                    'id_satuan' => $item->id_satuan,
                ];
            });

        return response()->json($produkNota);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tgl' => 'required|date',
            'id_pelanggan' => 'required|exists:pelanggan,id',
            'id_faktur' => 'required|exists:faktur,id',
            'alamat' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'produk_nota' => 'required|array',
            'produk_nota.*.id_produk' => 'required|exists:ref_produk,id',
            'produk_nota.*.qty_kirim' => 'required|integer|min:1',
            'produk_nota.*.id_satuan' => 'required',
            'produk_nota.*.satuan' => 'required|string',
            'produk_nota.*.uraian' => 'required|string',
            'produk_nota.*.harga_satuan' => 'required|integer',
            'produk_nota.*.diskon' => 'required|integer',
            'produk_pipa' => 'nullable|array',
            'produk_pipa.*.id_produk' => 'required|exists:ref_produk,id',
            'produk_pipa.*.qty' => 'required|integer|min:1',
            'person_ids' => 'required|array|min:1',
            'person_ids.*' => 'required|exists:pengguna,id',
        ]);

        DB::beginTransaction();

        try {
            // Generate no_transaksi based on faktur
            $noTransaksi = $this->generateNoTransaksi($validated['id_faktur']);

            // Calculate qty_pesan (from produk pipa)
            $qtyPesan = 0;
            if (! empty($validated['produk_pipa'])) {
                foreach ($validated['produk_pipa'] as $pipa) {
                    $qtyPesan += $pipa['qty'];
                }
            }

            // Calculate qty_nota (from produk nota)
            $qtyNota = 0;
            foreach ($validated['produk_nota'] as $nota) {
                $qtyNota += $nota['qty_kirim'];
            }

            // 1. Create pengiriman
            $pengiriman = Pengiriman::create([
                'no_transaksi' => $noTransaksi,
                'tgl' => $validated['tgl'],
                'id_pelanggan' => $validated['id_pelanggan'],
                'id_faktur' => $validated['id_faktur'],
                'alamat' => $validated['alamat'],
                'keterangan' => $validated['keterangan'],
                'status' => 1,
                'row_status' => 1,
                'is_approve' => 0,
                'qty_pesan' => $qtyPesan,
                'qty_nota' => $qtyNota,
                'created_by' => $request->user()->id,
            ]);

            // 2. Update faktur - set is_kirim = 1
            Faktur::where('id', $validated['id_faktur'])->update([
                'is_kirim' => 1,
                'updated_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            // 3. Insert pengiriman_detail_nota (produk nota)
            foreach ($validated['produk_nota'] as $nota) {
                PengirimanDetailNota::create([
                    'id_pengiriman' => $pengiriman->id,
                    'id_produk' => $nota['id_produk'],
                    'uraian' => $nota['uraian'],
                    'id_satuan' => $nota['id_satuan'],
                    'satuan' => $nota['satuan'],
                    'qty' => $nota['qty_kirim'],
                    'harga_satuan' => $nota['harga_satuan'],
                    'diskon' => $nota['diskon'],
                    'sub_total' => 0,
                    'row_status' => 1,
                    'created_by' => $request->user()->id,
                ]);
            }

            // 4. Insert pengiriman_detail (produk pipa)
            if (! empty($validated['produk_pipa'])) {
                foreach ($validated['produk_pipa'] as $pipa) {
                    // Get satuan info from ref_produk
                    $produk = RefProduk::with('satuan')->find($pipa['id_produk']);

                    if ($produk) {
                        DB::table('pengiriman_detail')->insert([
                            'id_pengiriman' => $pengiriman->id,
                            'id_produk' => $pipa['id_produk'],
                            'id_satuan' => $produk->id_satuan,
                            'satuan' => $produk->satuan ? $produk->satuan->nama : '',
                            'qty' => $pipa['qty'],
                            'harga_satuan' => 0,
                            'uraian' => $produk->nama,
                            'sub_total' => 0,
                            'row_status' => 1,
                            'created_at' => now(),
                            'created_by' => $request->user()->id,
                        ]);
                    }
                }
            }

            // 5. Insert pengiriman_person
            foreach ($validated['person_ids'] as $personId) {
                PengirimanPerson::create([
                    'id_pengiriman' => $pengiriman->id,
                    'id_pengguna' => $personId,
                    'tipe' => 'supir',
                    'keterangan' => null,
                    'row_status' => 1,
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            return redirect()->route('pengiriman')->with('success', 'Pengiriman berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal membuat pengiriman: '.$e->getMessage()]);
        }
    }

    private function generateNoTransaksi(int $idFaktur): string
    {
        // 1. Ambil data faktur berdasarkan id_faktur
        $faktur = Faktur::find($idFaktur);

        if (! $faktur) {
            throw new \Exception('Faktur tidak ditemukan');
        }

        // 2. Hitung jumlah pengiriman yang sudah ada untuk faktur ini
        $countPengiriman = Pengiriman::where('id_faktur', $idFaktur)->count();

        // 3. Buat counter 3 digit (001, 002, 003, dst)
        $counter = str_pad($countPengiriman + 1, 3, '0', STR_PAD_LEFT);

        // 4. Format: P-{no_transaksi_faktur}-{counter}
        return 'P-'.$faktur->no_transaksi.'-'.$counter;
    }

    private function getStatusText(int $status): string
    {
        return match ($status) {
            1 => 'PENDING',
            2 => 'DALAM PERJALANAN',
            3 => 'SELESAI',
            default => 'TIDAK DIKETAHUI',
        };
    }
}
