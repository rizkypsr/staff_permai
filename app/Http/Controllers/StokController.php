<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StokController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $perPage = 10;

        // Build optimized query for stock
        $query = DB::table('ref_produk as p')
            ->join('ref_lookup as l', 'l.id', '=', 'p.id_satuan')
            ->leftJoin(DB::raw('(SELECT id_produk, SUM(qty) as qty FROM jstok WHERE row_status = 1 GROUP BY id_produk) as x'), 'x.id_produk', '=', 'p.id')
            ->where('p.row_status', 1)
            ->select(
                'p.id',
                'p.kode',
                'p.nama',
                'p.id_satuan',
                'l.nama as satuan',
                'p.id_tipe',
                'p.is_qty_editable',
                DB::raw('CAST(CASE WHEN x.qty IS NULL THEN 0 WHEN x.qty < 0 THEN 0 ELSE x.qty END AS UNSIGNED) as qty')
            );

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'like', "%{$search}%")
                    ->orWhere('p.kode', 'like', "%{$search}%");
            });
        }

        $stok = $query->orderBy('qty', 'desc')->paginate($perPage);

        return Inertia::render('Stok', [
            'stok' => Inertia::scroll($stok),
            'search' => $search,
        ]);
    }
}
