<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Competition;
use Inertia\Inertia;

class FileVerificationController extends Controller
{
    /**
     * Show Split-Pane File Verification Reviewer.
     */
    public function index(Request $request)
    {
        $query = Registration::orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('competition')) {
            $query->where('competition_id', $request->competition);
        }

        $registrations = $query->get();
        $competitions = Competition::orderBy('title')->get();

        $stats = [
            'total' => Registration::count(),
            'verified' => Registration::where('payment_status', 'Terverifikasi')->count(),
            'pending' => Registration::where('payment_status', 'Menunggu Verifikasi')->count(),
            'rejected' => Registration::where('payment_status', 'Ditolak / Revisi')->count(),
            'verified_count' => Registration::where('payment_status', 'Terverifikasi')->count(),
            'pending_count' => Registration::where('payment_status', 'Menunggu Verifikasi')->count(),
            'rejected_count' => Registration::where('payment_status', 'Ditolak / Revisi')->count(),
        ];

        $selectedId = $request->query('select', $request->query('id'));
        $selectedRegistration = $selectedId 
            ? Registration::find($selectedId) 
            : $registrations->first();

        return view('admin.file-verification', compact('registrations', 'competitions', 'stats', 'selectedRegistration'));
    }

    /**
     * Update verification decision & notes.
     */
    public function updateStatus(Request $request, $id)
    {
        $reg = Registration::findOrFail($id);

        if ($request->has('registration_status')) {
            $reg->registration_status = $request->input('registration_status');
        }

        if ($request->has('payment_status')) {
            $reg->payment_status = $request->input('payment_status');
        }

        if ($request->has('status')) {
            $type = $request->input('type', 'all');
            if ($type === 'registration') {
                $reg->registration_status = $request->input('status');
            } elseif ($type === 'payment') {
                $reg->payment_status = $request->input('status');
            } else {
                $reg->payment_status = $request->input('status');
                $reg->registration_status = $request->input('status');
            }
        }

        if ($request->filled('verification_notes') || $request->filled('notes')) {
            $reg->verification_notes = $request->input('verification_notes', $request->input('notes'));
        }
        $reg->save();

        $authUser = auth()->user();
        \App\Models\ActivityLog::record(
            'verification',
            'Keputusan Verifikasi Berkas',
            "Panitia " . ($authUser ? $authUser->name : 'Sistem') . " memperbarui verifikasi berkas {$reg->school_name} ({$reg->competition_title}): {$reg->payment_status}.",
            [
                'user' => $authUser,
                'highlight' => $reg->school_name,
                'activity_type' => 'verification',
                'properties' => ['reg_code' => $reg->reg_code, 'status' => $reg->payment_status],
            ]
        );

        return redirect()->back()->with('success', "Keputusan verifikasi pendaftaran & pembayaran {$reg->reg_code} berhasil disimpan!");
    }
}
