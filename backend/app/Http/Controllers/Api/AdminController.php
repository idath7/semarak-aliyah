<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Competition;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function overview()
    {
        $registrations = Registration::orderBy('id', 'desc')->get();
        $competitions = Competition::orderBy('id', 'asc')->get();

        $totalPendaftar = $registrations->count();
        $terverifikasi = $registrations->where('payment_status', 'Terverifikasi')->count();
        $pending = $registrations->where('payment_status', 'Menunggu Verifikasi')->count();
        $totalDana = $registrations->where('payment_status', 'Terverifikasi')->sum('total_fee');

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver' => 'mysql',
                'stats' => [
                    'totalPendaftar' => $totalPendaftar,
                    'terverifikasi' => $terverifikasi,
                    'pending' => $pending,
                    'totalDana' => $totalDana
                ],
                'competitions' => $competitions,
                'registrations' => $registrations
            ]
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required|string'
        ]);

        $reg = Registration::findOrFail($request->id);
        $reg->payment_status = $request->status;
        $reg->save();

        if ($reg->package_type === 'Paket All-In') {
            Registration::where('package_type', 'Paket All-In')
                ->where(function ($q) use ($reg) {
                    $ownerId = $reg->registrant_id ?: $reg->user_id;
                    if ($ownerId) {
                        $q->where('registrant_id', $ownerId)->orWhere('user_id', $ownerId);
                    } else {
                        $q->where('school_name', $reg->school_name);
                    }
                })
                ->where('id', '!=', $reg->id)
                ->update(['payment_status' => $reg->payment_status]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status verifikasi berhasil diperbarui oleh Laravel!'
        ]);
    }

    public function toggleQuota(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'status' => 'required|string'
        ]);

        $comp = Competition::findOrFail($request->id);
        $comp->status = $request->status;
        $comp->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status kuota kompetisi berhasil diperbarui oleh Laravel!'
        ]);
    }

    public function export(\Illuminate\Http\Request $request)
    {
        return app(\App\Http\Controllers\AdminPanelController::class)->export($request);
    }
}
