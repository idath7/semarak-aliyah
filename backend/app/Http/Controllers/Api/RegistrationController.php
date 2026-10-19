<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Competition;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:200',
            'mentor_name' => 'required|string|max:150',
            'mentor_wa' => 'required|string|max:30',
            'package_type' => 'nullable|string|max:100',
            'competition_id' => 'nullable|string|max:50',
            'competition_title' => 'nullable|string|max:150',
            'participants_names' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $regCode = 'SA26-' . rand(100000, 999999);
        $package = $validated['package_type'] ?? 'Paket Satuan (Rp 50.000 / Cabang)';
        $totalFee = str_contains($package, '200.000') ? 200000 : 50000;

        $reg = Registration::create([
            'reg_code' => $regCode,
            'school_name' => $validated['school_name'],
            'mentor_name' => $validated['mentor_name'],
            'mentor_wa' => $validated['mentor_wa'],
            'package_type' => $package,
            'competition_id' => $validated['competition_id'] ?? 'all-in',
            'competition_title' => $validated['competition_title'] ?? ($validated['competition_id'] ?? 'Semarak Aliyah'),
            'participants_names' => $validated['participants_names'],
            'notes' => $validated['notes'] ?? '',
            'total_fee' => $totalFee,
            'payment_status' => 'Menunggu Verifikasi',
            'payment_method' => 'Transfer Bank / Tunai'
        ]);

        // Increment competition quota if single competition
        if (!empty($validated['competition_id']) && $validated['competition_id'] !== 'all-in') {
            Competition::where('id', $validated['competition_id'])->increment('current_quota');
        }

        // Generate WA Link
        $waMsg = 
"*PENDAFTARAN RESMI SEMARAK ALIYAH XII 2026*
━━━━━━━━━━━━━━━━━━━━
*Kode Registrasi:* {$regCode}
*Asal Sekolah:* {$reg->school_name}
*Guru Pendamping:* {$reg->mentor_name}
*Nomor WhatsApp:* {$reg->mentor_wa}
*Pilihan Paket:* {$reg->package_type}
*Cabang Lomba:* {$reg->competition_title}
*Nama Peserta:* 
{$reg->participants_names}
" . ($reg->notes ? "*Catatan Tambahan:* {$reg->notes}\n" : "") .
"*Total Biaya:* Rp " . number_format($totalFee, 0, ',', '.') . "
━━━━━━━━━━━━━━━━━━━━
Halo Panitia Semarak Aliyah 2026, data registrasi di atas sudah tersimpan di sistem database Laravel website resmi. Mohon konfirmasi rekening transfer dan petunjuk verifikasi berikutnya. Terima kasih!";

        $waUrl = "https://wa.me/6283850070767?text=" . urlencode($waMsg);

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil disimpan ke database Laravel!',
            'regCode' => $regCode,
            'totalFee' => $totalFee,
            'waUrl' => $waUrl
        ], 201);
    }
}
