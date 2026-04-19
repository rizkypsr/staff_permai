<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PengembalianController extends Controller
{
    public function create(Request $request): Response
    {
        // Get pengiriman list that can have pengembalian (optimized query)
        $pengirimanList = DB::table('pengiriman as p')
            ->join('pelanggan as pl', 'pl.id', '=', 'p.id_pelanggan')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pengiriman_detail')
                    ->whereColumn('pengiriman_detail.id_pengiriman', 'p.id')
                    ->where('pengiriman_detail.row_status', 1);
            })
            ->where('p.row_status', 1)
            ->where('p.status', 1)
            ->where('p.is_approve', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pengembalian_pipa')
                    ->whereColumn('pengembalian_pipa.id_pengiriman', 'p.id')
                    ->where('pengembalian_pipa.row_status', 1)
                    ->where('pengembalian_pipa.is_approve', 1);
            })
            ->select(
                'p.id',
                DB::raw("CONCAT(p.no_transaksi, ' · ', pl.nama) as kode"),
                'p.keterangan'
            )
            ->orderBy('p.id', 'desc')
            ->get()
            ->toArray();

        // Get the latest one for auto-select
        $latestPengiriman = ! empty($pengirimanList) ? $pengirimanList[0] : null;

        return Inertia::render('PengembalianCreate', [
            'pengirimanList' => $pengirimanList,
            'latestPengiriman' => $latestPengiriman,
        ]);
    }

    public function getPengirimanDetail($id)
    {
        // Get pengiriman detail with product name
        $pengirimanDetail = DB::table('pengiriman_detail as pdp')
            ->join('ref_produk as p', 'p.id', '=', 'pdp.id_produk')
            ->where('pdp.row_status', 1)
            ->where('pdp.id_pengiriman', $id)
            ->select(
                'pdp.*',
                DB::raw("CONCAT(p.kode, ' · ', p.nama) as nama_produk")
            )
            ->get();

        return response()->json($pengirimanDetail);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tgl' => 'required|date',
            'id_pengiriman' => 'required|exists:pengiriman,id',
            'keterangan' => 'nullable|string|max:255',
            'produk' => 'required|array|min:1',
            'produk.*.id_produk' => 'required|exists:ref_produk,id',
            'produk.*.id_satuan' => 'required',
            'produk.*.satuan' => 'required|string',
            'produk.*.qty_bawa' => 'required|numeric|min:0',
            'produk.*.qty_kembali' => 'required|numeric|min:0',
        ]);

        // Custom validation: qty_kembali must be <= qty_bawa
        foreach ($validated['produk'] as $index => $produk) {
            if ($produk['qty_kembali'] > $produk['qty_bawa']) {
                return back()->withErrors([
                    "produk.{$index}.qty_kembali" => 'Qty Dikembalikan tidak boleh lebih dari Qty Dibawa',
                ]);
            }
        }

        DB::beginTransaction();

        try {
            // Get source no_transaksi from pengiriman
            $pengiriman = DB::table('pengiriman')
                ->where('id', $validated['id_pengiriman'])
                ->first();

            if (! $pengiriman) {
                return back()->withErrors(['error' => 'Pengiriman tidak ditemukan']);
            }

            // Generate no_transaksi: replace P-A with RP-
            $noTransaksi = str_replace('P-A', 'RP-', $pengiriman->no_transaksi);

            // Create pengembalian_pipa record
            $pengembalianId = DB::table('pengembalian_pipa')->insertGetId([
                'no_transaksi' => $noTransaksi,
                'tgl' => $validated['tgl'],
                'id_pengiriman' => $validated['id_pengiriman'],
                'keterangan' => $validated['keterangan'],
                'qty' => 0, // Will be calculated from detail
                'row_status' => 1,
                'is_approve' => 0, // Pending approval
                'created_at' => now(),
                'created_by' => $request->user()->id,
            ]);

            // Prepare detail records (only for qty_kembali > 0)
            $detailRecords = [];
            $totalQtyKembali = 0;

            foreach ($validated['produk'] as $produk) {
                if ($produk['qty_kembali'] > 0) {
                    $detailRecords[] = [
                        'id_pengembalian_pipa' => $pengembalianId,
                        'id_produk' => $produk['id_produk'],
                        'id_satuan' => $produk['id_satuan'],
                        'satuan' => $produk['satuan'],
                        'qty_bawa' => $produk['qty_bawa'],
                        'qty_kembali' => $produk['qty_kembali'],
                        'row_status' => 1,
                        'created_at' => now(),
                        'created_by' => $request->user()->id,
                    ];

                    // Sum total qty_kembali
                    $totalQtyKembali += $produk['qty_kembali'];
                }
            }

            // Insert batch detail records
            if (count($detailRecords) > 0) {
                DB::table('pengembalian_pipa_detail')->insert($detailRecords);

                // Update qty in pengembalian_pipa with total qty_kembali
                DB::table('pengembalian_pipa')
                    ->where('id', $pengembalianId)
                    ->update(['qty' => $totalQtyKembali]);
            }

            DB::commit();

            return redirect()->route('pengiriman')->with('success', 'Pengembalian berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal membuat pengembalian: '.$e->getMessage()]);
        }
    }
}
