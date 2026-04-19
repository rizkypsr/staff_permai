<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get assets with latest maintenance data using Eloquent
        $assets = Asset::forUser($user->id)
            ->with(['latestMaintenance'])
            ->withCount(['maintenances as total_record' => function ($query) {
                $query->where('row_status', 1);
            }])
            ->orderBy('id')
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'id_pegawai' => $asset->id_pegawai,
                    'nama' => $asset->nama,
                    'model' => $asset->model,
                    'tgl_pembelian' => $asset->tgl_pembelian?->format('Y-m-d'),
                    'waktu_maintenance' => $asset->waktu_maintenance,
                    'periode_maintenance' => strtolower($asset->periode_maintenance),
                    'tgl_maintenance' => $asset->latestMaintenance?->tgl_maintenance?->format('Y-m-d') ?? '-',
                    'total_record' => $asset->total_record,
                    'usia' => $asset->usia,
                    'keterangan' => $asset->latestMaintenance?->keterangan ?? null,
                ];
            });

        return Inertia::render('Profile', [
            'user' => [
                'nama' => $user->nama,
                'email' => $user->email,
                'username' => $user->username,
            ],
            'assets' => $assets,
        ]);
    }
}