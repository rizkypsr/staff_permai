<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RekapAbsensiController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $selectedYear = (int) $request->input('year', now()->year);
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        // Get last 3 years for dropdown
        $years = [];
        for ($i = 2; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;
            $years[] = [
                'value' => $year,
                'label' => $year,
            ];
        }

        // Get months based on selected year
        $months = [];
        if ($selectedYear == now()->year) {
            // Current year: show months from January to current month
            $currentMonth = now()->month;
            for ($month = 1; $month <= $currentMonth; $month++) {
                $date = Carbon::createFromDate($selectedYear, $month, 1);
                $months[] = [
                    'value' => $date->format('Y-m'),
                    'label' => $date->format('M'),
                    'full_label' => $date->translatedFormat('F Y'),
                ];
            }
        } else {
            // Previous years: show all 12 months
            for ($month = 1; $month <= 12; $month++) {
                $date = Carbon::createFromDate($selectedYear, $month, 1);
                $months[] = [
                    'value' => $date->format('Y-m'),
                    'label' => $date->format('M'),
                    'full_label' => $date->translatedFormat('F Y'),
                ];
            }
        }

        // Parse selected month - extract month part and combine with selected year
        $monthPart = now()->format('m'); // Default to current month
        if ($selectedMonth) {
            try {
                $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth);
                $monthPart = $monthDate->format('m');
            } catch (\Exception $e) {
                // If parsing fails, use current month
                $monthPart = now()->format('m');
            }
        } else {
            // If no month parameter, use current month for current year, or January for other years
            if ($selectedYear == now()->year) {
                $monthPart = now()->format('m');
            } else {
                $monthPart = '01'; // January for previous years
            }
        }

        // Create date with selected year and month
        $actualDate = Carbon::createFromDate($selectedYear, (int) $monthPart, 1);
        $startDate = $actualDate->copy()->startOfMonth();
        $endDate = $actualDate->copy()->endOfMonth();

        // Update selectedMonth to reflect actual year-month being queried
        $selectedMonth = $actualDate->format('Y-m');

        // Get holiday dates from tanggal table
        $holidayDates = DB::table('tanggal')
            ->whereBetween('tgl', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->pluck('tgl')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Get existing absensi data for selected month
        $existingAbsensi = DB::table('absensi')
            ->where('id_pengguna', $userId)
            ->whereBetween('tgl', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('tgl'); // Key by date for easy lookup

        // Generate all dates in the month (up to today)
        $absensiData = collect();
        $current = $startDate->copy();
        $today = now();

        while ($current->lte($endDate) && $current->lte($today)) {
            $currentDateString = $current->format('Y-m-d');
            $existingRecord = $existingAbsensi->get($currentDateString);

            if ($existingRecord) {
                // Use existing absensi record
                $status = match ($existingRecord->status) {
                    1 => 'MASUK',
                    2 => 'TELAT',
                    3 => 'ALPHA',
                    default => 'ALPHA', // status 0 or others
                };

                // Check for LIBUR status when absen = 0
                if ($existingRecord->status == 0) {
                    // Check if it's Sunday (day 7)
                    if ($current->dayOfWeek === Carbon::SUNDAY) {
                        $status = 'LIBUR';
                    }

                    // Check if date is in holiday list
                    if (in_array($currentDateString, $holidayDates)) {
                        $status = 'LIBUR';
                    }
                }

                $absensiData->push([
                    'id' => $existingRecord->id,
                    'tgl' => $currentDateString,
                    'tgl_formatted' => $current->translatedFormat('d M Y'),
                    'jam_masuk' => $existingRecord->masuk ? Carbon::parse($existingRecord->masuk)->format('H:i') : '-',
                    'status_code' => $existingRecord->status,
                    'status' => $status,
                ]);
            } else {
                // No absensi record exists, determine status based on day
                $status = 'ALPHA'; // Default

                // Check if it's Sunday
                if ($current->dayOfWeek === Carbon::SUNDAY) {
                    $status = 'LIBUR';
                }

                // Check if date is in holiday list
                if (in_array($currentDateString, $holidayDates)) {
                    $status = 'LIBUR';
                }

                $absensiData->push([
                    'id' => null,
                    'tgl' => $currentDateString,
                    'tgl_formatted' => $current->translatedFormat('d M Y'),
                    'jam_masuk' => '-',
                    'status_code' => 0,
                    'status' => $status,
                ]);
            }

            $current->addDay();
        }

        // Sort by date ascending (from date 1)
        $absensiData = $absensiData->sortBy('tgl')->values();

        // Calculate statistics
        $totalHariKerja = $this->getWorkingDaysInMonth($startDate, $endDate, $holidayDates);
        $totalHariMasuk = $absensiData->whereIn('status_code', [1, 2])->count();
        $totalHariTelat = $absensiData->where('status_code', 2)->count();

        $statistics = [
            'total_hari_kerja' => $totalHariKerja,
            'total_hari_masuk' => $totalHariMasuk,
            'total_hari_telat' => $totalHariTelat,
        ];

        return Inertia::render('RekapAbsensi', [
            'years' => $years,
            'selectedYear' => $selectedYear,
            'months' => $months,
            'selectedMonth' => $selectedMonth,
            'absensiData' => $absensiData,
            'statistics' => $statistics,
        ]);
    }

    private function getWorkingDaysInMonth(Carbon $startDate, Carbon $endDate, array $holidayDates = []): int
    {
        $workingDays = 0;
        $current = $startDate->copy();
        $today = now();

        while ($current->lte($endDate) && $current->lte($today)) {
            $currentDateString = $current->format('Y-m-d');

            // Skip Sunday
            if ($current->dayOfWeek === Carbon::SUNDAY) {
                $current->addDay();

                continue;
            }

            // Skip holiday dates
            if (in_array($currentDateString, $holidayDates)) {
                $current->addDay();

                continue;
            }

            // Count as working day (Monday to Saturday, excluding holidays)
            $workingDays++;
            $current->addDay();
        }

        return $workingDays;
    }
}
