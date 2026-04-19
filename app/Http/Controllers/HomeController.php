<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Set Carbon locale to Indonesian
        Carbon::setLocale('id');

        // Get current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get absensi data for current user, grouped by month
        $absensiData = [];

        // Get last 3 months including current month
        for ($i = 2; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;
            $monthName = $date->translatedFormat('F Y');

            $absensi = Absensi::where('id_pengguna', $user->id)
                ->whereYear('tgl', $year)
                ->whereMonth('tgl', $month)
                ->where('row_status', 1)
                ->orderBy('tgl', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'tgl' => $item->tgl->format('Y-m-d'),
                        'tgl_formatted' => $item->tgl->translatedFormat('l, d F Y'),
                        'masuk' => $item->masuk,
                        'keluar' => $item->keluar,
                        'status' => $item->status,
                        'status_text' => $this->getStatusText($item->status),
                    ];
                });

            $absensiData[] = [
                'month' => $monthName,
                'data' => $absensi,
            ];
        }

        // Get today's absensi
        $todayAbsensi = Absensi::where('id_pengguna', $user->id)
            ->whereDate('tgl', Carbon::today())
            ->where('row_status', 1)
            ->first();

        // Check if can absen (after 6 AM and not already absen today)
        $now = Carbon::now();
        $canAbsen = $now->hour >= 6 && ! $todayAbsensi;

        return Inertia::render('Welcome', [
            'absensiData' => $absensiData,
            'todayAbsensi' => $todayAbsensi ? [
                'id' => $todayAbsensi->id,
                'masuk' => $todayAbsensi->masuk,
                'keluar' => $todayAbsensi->keluar,
                'status' => $todayAbsensi->status,
                'status_text' => $this->getStatusText($todayAbsensi->status),
            ] : null,
            'canAbsen' => $canAbsen,
        ]);
    }

    private function getStatusText(int $status): string
    {
        return match ($status) {
            0 => 'PENDING',
            1 => 'MASUK',
            2 => 'TELAT',
            3 => 'ABSEN',
            default => 'TIDAK DIKETAHUI',
        };
    }
}
