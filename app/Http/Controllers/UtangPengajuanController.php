<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUtangPengajuanRequest;
use App\Models\UtangPengajuan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UtangPengajuanController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $pengajuan = UtangPengajuan::aktif()
            ->milik($userId)
            ->orderByDesc('tgl')
            ->orderByDesc('id')
            ->get()
            ->map(fn (UtangPengajuan $item): array => [
                'id' => $item->id,
                'tgl' => $item->tgl->format('Y-m-d'),
                'tgl_formatted' => $item->tgl->translatedFormat('d M Y'),
                'nominal' => $item->nominal,
                'keterangan' => $item->keterangan,
                'status' => $item->status,
                'catatan_admin' => $item->status === UtangPengajuan::STATUS_DITOLAK ? $item->catatan_admin : '',
            ]);

        return Inertia::render('Utang', [
            'pengajuan' => $pengajuan,
            'ringkasan' => [
                'sisa_utang' => $this->sisaUtang($userId),
                'menunggu' => (int) UtangPengajuan::aktif()
                    ->milik($userId)
                    ->where('status', UtangPengajuan::STATUS_MENUNGGU)
                    ->sum('nominal'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('UtangCreate');
    }

    public function store(StoreUtangPengajuanRequest $request): RedirectResponse
    {
        $userId = $request->user()->id;

        UtangPengajuan::create([
            'id_pengguna' => $userId,
            'tgl' => Carbon::today(),
            'nominal' => $request->integer('nominal'),
            'keterangan' => $request->string('keterangan')->toString(),
            'status' => UtangPengajuan::STATUS_MENUNGGU,
            'row_status' => 1,
            'created_by' => $userId,
        ]);

        return redirect()->route('utang.index')->with('success', 'Pengajuan utang berhasil dikirim');
    }

    /**
     * Batalkan pengajuan sendiri yang masih menunggu (soft delete).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $userId = $request->user()->id;

        $pengajuan = UtangPengajuan::aktif()->milik($userId)->findOrFail($id);

        abort_if($pengajuan->status !== UtangPengajuan::STATUS_MENUNGGU, 403, 'Pengajuan sudah diproses admin');

        $dibatalkan = UtangPengajuan::whereKey($pengajuan->id)
            ->milik($userId)
            ->aktif()
            ->where('status', UtangPengajuan::STATUS_MENUNGGU)
            ->update([
                'row_status' => 0,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);

        abort_if($dibatalkan === 0, 403, 'Pengajuan sudah diproses admin');

        return redirect()->route('utang.index')->with('success', 'Pengajuan dibatalkan');
    }

    /**
     * Sisa utang = utang_sisa slip gaji terakhir + pengajuan disetujui di bulan
     * sesudah periode slip itu. Tanpa slip: semua pengajuan disetujui.
     * Hanya membaca tabel gaji; rantai utang dihitung web admin.
     */
    private function sisaUtang(int $userId): int
    {
        $slipTerakhir = DB::table('gaji')
            ->where('id_pengguna', $userId)
            ->where('row_status', 1)
            ->orderByDesc('periode')
            ->first(['periode', 'utang_sisa']);

        $disetujui = UtangPengajuan::aktif()
            ->milik($userId)
            ->where('status', UtangPengajuan::STATUS_DISETUJUI);

        if ($slipTerakhir === null) {
            return (int) $disetujui->sum('nominal');
        }

        $awalBulanBerikut = Carbon::createFromFormat('Y-m-d', $slipTerakhir->periode.'-01')
            ->startOfMonth()
            ->addMonthNoOverflow()
            ->toDateString();

        return (int) $slipTerakhir->utang_sisa
            + (int) $disetujui->where('tgl', '>=', $awalBulanBerikut)->sum('nominal');
    }
}
