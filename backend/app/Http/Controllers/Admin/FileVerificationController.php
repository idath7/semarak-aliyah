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
        $request->validate([
            'status' => 'required|string',
            'verification_notes' => 'nullable|string'
        ]);

        $reg = Registration::findOrFail($id);
        $reg->payment_status = $request->status;
        if ($request->filled('verification_notes')) {
            $reg->verification_notes = $request->verification_notes;
        }
        $reg->save();

        return redirect()->back()->with('success', "Keputusan verifikasi berkas {$reg->reg_code} berhasil disimpan!");
    }
}
