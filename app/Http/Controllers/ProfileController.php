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
        
        // Get assets for user (simplified)
        $assets = Asset::forUser($user->id)
            ->orderBy('id')
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'id_pegawai' => $asset->id_pegawai,
                    'nama' => $asset->nama,
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