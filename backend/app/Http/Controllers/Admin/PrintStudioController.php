<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Competition;

class PrintStudioController extends Controller
{
    /**
     * Show Print Studio Hub.
     */
    public function index(Request $request)
    {
        $competitions = Competition::orderBy('title')->get();
        $query = Registration::with('competition')->orderBy('id', 'desc');

        if ($request->filled('competition')) {
            $query->where('competition_id', $request->competition);
        }

        $registrations = $query->get();

        return view('admin.print-studio', compact('competitions', 'registrations'));
    }

    /**
     * Print Official ID Cards with Barcodes (Batch or Single).
     */
    public function printIdCards(Request $request)
    {
        $query = Registration::orderBy('school_name');

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        } elseif ($request->filled('competition')) {
            $query->where('competition_id', $request->competition);
        }

        $participants = $query->get();

        return view('admin.print-id-cards', compact('participants'));
    }

    /**
     * Print Official Certificate of Participation / Winner.
     */
    public function printCertificate($id, Request $request)
    {
        $reg = Registration::with('competition')->findOrFail($id);
        $rank = $request->input('rank', 'Peserta'); // Peserta, Juara 1, Juara 2, dll.

        return view('admin.print-certificate', compact('reg', 'rank'));
    }
}
