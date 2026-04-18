<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $today = Carbon::today();

        // Check if already absen today
        $existingAbsen = Absensi::where('id_pengguna', $user->id)
            ->whereDate('tgl', $today)
            ->where('row_status', 1)
            ->first();

        if ($existingAbsen) {
            return back()->with('error', 'Anda sudah absen hari ini');
        }

        // Create new absensi with status 0 (Unapproved - menunggu persetujuan)
        Absensi::create([
            'id_pengguna' => $user->id,
            'tgl' => $today,
            'masuk' => Carbon::now()->format('H:i:s'),
            'keluar' => null,
            'status' => 0, // Status 0 = Unapproved (menunggu persetujuan)
            'row_status' => 1,
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Absen berhasil dicatat');
    }
}
