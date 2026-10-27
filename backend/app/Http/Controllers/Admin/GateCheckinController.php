<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Competition;
use App\Events\GateCheckinLogged;
use Inertia\Inertia;

class GateCheckinController extends Controller
{
    /**
     * Show Gate Check-in Scanner Dashboard.
     */
    public function index(Request $request)
    {
        $competitions = Competition::orderBy('title')->get();
        $totalPeserta = Registration::count();
        $hadirCount = Registration::where('checkin_status', 'hadir')->count();
        $belumHadirCount = $totalPeserta - $hadirCount;
        $persenHadir = $totalPeserta > 0 ? round(($hadirCount / $totalPeserta) * 100) : 0;

        // Recent check-ins
        $recentCheckins = Registration::where('checkin_status', 'hadir')
            ->orderBy('checkin_at', 'desc')
            ->limit(15)
            ->get();

        return view('admin.gate-checkin', compact(
            'competitions',
            'totalPeserta',
            'hadirCount',
            'belumHadirCount',
            'persenHadir',
            'recentCheckins'
        ));
    }

    /**
     * Process barcode scanner input (USB HID Scanner / Manual).
     */
    public function scan(Request $request)
    {
        $code = trim($request->input('code'));

        if (!$code) {
            return redirect()->back()->with('error', 'Kode barcode atau ID registrasi tidak boleh kosong.');
        }

        // Find registration by reg_code or barcode
        $reg = Registration::where('reg_code', $code)
            ->orWhere('barcode', $code)
            ->first();

        if (!$reg) {
            return redirect()->back()->with('error', "Data peserta dengan kode \"{$code}\" tidak ditemukan di sistem!");
        }

        // Check if already checked in
        $isAlreadyCheckedIn = ($reg->checkin_status === 'hadir');

        // Mark as checked in
        $reg->checkin_status = 'hadir';
        if (!$reg->checkin_at) {
            $reg->checkin_at = now();
        }
        $reg->save();

        // Broadcast real-time event via Reverb
        try {
            broadcast(new GateCheckinLogged($reg, 'checkin'));
        } catch (\Throwable $e) {}

        // Catat Log Aktivitas Panitia Gate Check-in
        $authUser = auth()->user();
        \App\Models\ActivityLog::record(
            'checkin',
            'Gate Check-in Kontingen',
            "Kontingen {$reg->school_name} ({$reg->competition_title}) scan masuk gate arena.",
            [
                'user' => $authUser,
                'highlight' => $reg->school_name,
                'activity_type' => 'checkin',
                'properties' => ['reg_code' => $reg->reg_code, 'barcode' => $reg->barcode],
            ]
        );

        $msg = $isAlreadyCheckedIn 
            ? "Peserta {$reg->mentor_name} ({$reg->school_name}) telah check-in sebelumnya pada {$reg->checkin_at->format('H:i:s')} WIB."
            : "Check-in BERHASIL! {$reg->school_name} terdaftar hadir di gerbang.";

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'already' => $isAlreadyCheckedIn,
                'message' => $msg,
                'data' => $reg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Undo checkin status if scanned accidentally.
     */
    public function undo($id)
    {
        $reg = Registration::findOrFail($id);
        $reg->checkin_status = 'belum_hadir';
        $reg->checkin_at = null;
        $reg->save();

        try {
            broadcast(new GateCheckinLogged($reg, 'undo'));
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Status check-in peserta {$reg->reg_code} ({$reg->school_name}) berhasil dibatalkan.");
    }
}
