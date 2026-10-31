<?php

namespace App\Http\Controllers\Registrant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Registration;
use App\Models\Competition;
use App\Models\School;
use App\Models\Setting;
use App\Models\User;
use App\Models\Registrant;
use App\Services\WhatsAppService;

class RegistrantDashboardController extends Controller
{
    /**
     * Dapatkan instance akun pendaftar yang sedang login via guard 'pendaftar'.
     *
     * @return \App\Models\Registrant|\App\Models\User|null
     */
    protected function getRegistrant(): Registrant|User|null
    {
        /** @var Registrant|User|null $user */
        $user = Auth::guard('pendaftar')->user() ?? Auth::user();
        return $user;
    }

    /**
     * Tampilkan Dashboard Khusus Pendaftar.
     */
    public function index(Request $request)
    {
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $cleanPhone = Setting::formatWaNumber($user->phone);

        // Ambil seluruh data pendaftaran:
        // 1. Yang dibuat oleh akun ini (registrant_id / user_id)
        // 2. Yang nomor WA mentor_wa sama dengan user ini
        // 3. Yang ditugaskan ke nomor WA user ini sebagai PIC (assigned_phone)
        $rawCollection = Registration::where(function ($q) use ($user, $cleanPhone) {
            $q->where('registrant_id', $user->id)
              ->orWhere('user_id', $user->id);
            if (!empty($user->phone)) {
                $q->orWhere('mentor_wa', $user->phone)
                  ->orWhere('mentor_wa', $cleanPhone)
                  ->orWhere('assigned_phone', $user->phone)
                  ->orWhere('assigned_phone', $cleanPhone);
            }
        })
        ->with('competition')
        ->orderBy('id', 'asc')
        ->get();

        $allinPrice = (int)Setting::get('package_allin_price', 200000);
        $allInRegs = $rawCollection->where('package_type', 'Paket All-In');
        $isAllInActive = $allInRegs->isNotEmpty();

        // 1. Konsolidasi Paket All-In (Fee Tunggal & Sinkronisasi Status Pembayaran)
        $sharedAllInProofUrl = null;
        $allInPaymentStatus = 'Menunggu Verifikasi';
        $masterAllInId = null;
        $masterAllInCompTitle = null;

        if ($isAllInActive) {
            // Deteksi bukti pembayaran bersama di salah satu cabang lomba All-In
            foreach ($allInRegs as $ar) {
                $files = $ar->file_documents ?: [];
                if (is_array($files)) {
                    foreach ($files as $f) {
                        if (stripos($f['title'] ?? '', 'bukti') !== false || stripos($f['filename'] ?? '', 'bukti') !== false) {
                            $sharedAllInProofUrl = $f['url'] ?? null;
                            break 2;
                        }
                    }
                }
                if (!$sharedAllInProofUrl && isset($ar->custom_fields['payment_proof']['url'])) {
                    $sharedAllInProofUrl = $ar->custom_fields['payment_proof']['url'];
                    break;
                }
            }

            // Cek apakah ada yang sudah lunas/terverifikasi
            $isAnyAllInPaid = $allInRegs->contains(fn($r) => in_array(strtolower((string)$r->payment_status), ['terverifikasi', 'lunas', 'verified']));
            if ($isAnyAllInPaid) {
                $allInPaymentStatus = 'Terverifikasi';
                Registration::whereIn('id', $allInRegs->pluck('id'))
                    ->whereNotIn('payment_status', ['Terverifikasi', 'Lunas'])
                    ->update(['payment_status' => 'Terverifikasi']);
                foreach ($allInRegs as $ar) {
                    $ar->payment_status = 'Terverifikasi';
                }
            } else {
                $allInPaymentStatus = $allInRegs->first()->payment_status ?: 'Menunggu Verifikasi';
            }

            // Tentukan Induk Tagihan Paket All-In (Hanya 1 pendaftaran memegang biaya paket Rp 200.000, lainnya Rp 0)
            $masterReg = $allInRegs->firstWhere('total_fee', '>', 0) ?: $allInRegs->first();
            $masterAllInId = $masterReg ? $masterReg->id : null;
            $masterAllInCompTitle = $masterReg ? $masterReg->competition_title : null;

            // Konsolidasikan fee secara ketat tanpa duplikasi tagihan ganda
            foreach ($allInRegs as $ar) {
                $targetFee = ($ar->id === $masterAllInId) ? $allinPrice : 0;
                if ((int)$ar->total_fee !== $targetFee) {
                    $ar->total_fee = $targetFee;
                    $ar->save();
                }
            }
        }

        $bankAccounts = Setting::getBankAccounts();
        $primaryAccount = Setting::getPrimaryBankAccount();
        $bankName = $primaryAccount['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)');
        $accountNumber = $primaryAccount['account_number'] ?? Setting::get('payment_account_number', '');
        $accountHolder = $primaryAccount['account_holder'] ?? Setting::get('payment_account_holder', 'Semarak Aliyah');

        $registrations = $rawCollection->map(function ($reg) use ($user, $cleanPhone, $sharedAllInProofUrl, $masterAllInId, $allInPaymentStatus, $bankName) {
            $comp = $reg->competition;
            $isCompClosed = $comp ? ($comp->status === 'closed' || $comp->isDeadlinePassed()) : false;
            
            // Pendaftar bisa mengedit selagi status pendaftaran lomba belum ditutup oleh panitia
            // dan verifikasi belum final
            $canEdit = !$isCompClosed;

            // Cari bukti pembayaran di file_documents atau custom_fields
            $files = $reg->file_documents ?: [];
            $paymentProofUrl = null;
            if (is_array($files)) {
                foreach ($files as $f) {
                    if (stripos($f['title'] ?? '', 'bukti') !== false || stripos($f['filename'] ?? '', 'bukti') !== false) {
                        $paymentProofUrl = $f['url'] ?? null;
                        break;
                    }
                }
            }
            if (!$paymentProofUrl && isset($reg->custom_fields['payment_proof']['url'])) {
                $paymentProofUrl = $reg->custom_fields['payment_proof']['url'];
            }
            if (!$paymentProofUrl && $reg->package_type === 'Paket All-In') {
                $paymentProofUrl = $sharedAllInProofUrl;
            }

            $isOwner = ($reg->registrant_id === $user->id || $reg->user_id === $user->id || Setting::formatWaNumber($reg->mentor_wa) === $cleanPhone);
            $isPic = (!empty($reg->assigned_phone) && (Setting::formatWaNumber($reg->assigned_phone) === $cleanPhone || $reg->assigned_phone === $user->phone));

            $isAllIn = ($reg->package_type === 'Paket All-In');
            $isAllInMaster = ($isAllIn && $reg->id === $masterAllInId);
            $isAllInIncluded = ($isAllIn && !$isAllInMaster);

            return [
                'id' => $reg->id,
                'reg_code' => $reg->reg_code,
                'barcode' => $reg->barcode ?: $reg->reg_code,
                'school_name' => $reg->school_name,
                'mentor_name' => $reg->mentor_name,
                'mentor_wa' => $reg->mentor_wa,
                'competition_id' => $reg->competition_id,
                'competition_title' => $reg->competition_title,
                'package_type' => $reg->package_type,
                'is_all_in' => $isAllIn,
                'is_all_in_master' => $isAllInMaster,
                'is_all_in_included' => $isAllInIncluded,
                'participants_names' => $reg->participants_names,
                'notes' => $reg->notes,
                'total_fee' => (int)$reg->total_fee,
                'registration_status' => $reg->registration_status ?: 'Menunggu Verifikasi',
                'payment_status' => $isAllIn ? $allInPaymentStatus : ($reg->payment_status ?: 'Menunggu Verifikasi'),
                'payment_method' => $reg->payment_method ?: ('Transfer ' . $bankName),
                'checkin_status' => $reg->checkin_status ?: 'belum_hadir',
                'verification_notes' => $reg->verification_notes,
                'draw_number' => $reg->draw_number,
                'custom_fields' => $reg->custom_fields ?: [],
                'file_documents' => $reg->file_documents ?: [],
                'payment_proof_url' => $paymentProofUrl,
                'assigned_name' => $reg->assigned_name,
                'assigned_phone' => $reg->assigned_phone,
                'assigned_role' => $reg->assigned_role ?: 'Koordinator Cabang',
                'assigned_notes' => $reg->assigned_notes,
                'is_owner' => $isOwner,
                'is_pic' => $isPic,
                'created_at' => $reg->created_at ? $reg->created_at->format('d/m/Y H:i') : '-',
                'created_at_raw' => $reg->created_at ? $reg->created_at->toIso8601String() : null,
                'can_edit' => $canEdit,
                'is_comp_closed' => $isCompClosed,
                'comp_deadline' => $comp ? $comp->registration_deadline : null,
                'comp_category' => $comp ? ($comp->category_label ?: 'SMP / MTs Sederajat') : 'SMP / MTs Sederajat',
                'comp_status' => $comp ? $comp->status : 'open',
            ];
        });

        // Hitung statistik ringkas pendaftar
        $totalRegs = $registrations->count();
        $verifiedCount = $registrations->where('registration_status', 'Terverifikasi')->count();
        $pendingCount = $registrations->where('registration_status', 'Menunggu Verifikasi')->count();
        $totalFee = $registrations->sum('total_fee');
        $paidFee = $registrations->whereIn('payment_status', ['Terverifikasi', 'Lunas'])->sum('total_fee');

        // Daftar cabang lomba aktif untuk pendaftaran lomba baru
        $competitions = Competition::orderBy('id', 'asc')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'title' => $c->title,
                'category_label' => $c->category_label ?: 'Umum',
                'fee' => (int)$c->fee,
                'status' => $c->status,
                'is_closed' => $c->status === 'closed' || $c->isDeadlinePassed(),
                'registration_deadline' => $c->registration_deadline,
                'custom_fields' => $c->custom_fields ?: [],
            ];
        });

        $helpdeskWa = Setting::get('contact_wa', '0838 5007 0767');

        $isCurrentUserPicOnly = $registrations->isNotEmpty() && $registrations->every(fn($r) => $r['is_pic'] && !$r['is_owner']);

        return Inertia::render('Registrant/Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'registrant_role' => $user->registrant_role ?: 'Pendamping',
                'gender' => $user->gender ?: 'Laki-laki',
                'school_name' => $user->school_name ?: '-',
                'school_district' => $user->school_district ?: '-',
            ],
            'registrations' => $registrations,
            'stats' => [
                'total_regs' => $totalRegs,
                'verified_count' => $verifiedCount,
                'pending_count' => $pendingCount,
                'total_fee' => $totalFee,
                'paid_fee' => $paidFee,
            ],
            'competitions' => $competitions,
            'paymentInfo' => [
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_holder' => $accountHolder,
                'bank_accounts' => $bankAccounts,
                'helpdesk_wa' => $helpdeskWa,
            ],
            'allInPricing' => [
                'price' => $allinPrice,
                'name' => Setting::get('package_allin_name', 'Paket All-In Komplit'),
                'is_active' => Setting::get('package_allin_is_active', '1') === '1',
                'original_price' => (int)Setting::get('package_allin_original_price', 450000),
                'active_competitions_count' => $competitions->count(),
                'savings' => max(0, (int)Setting::get('package_allin_original_price', 450000) - $allinPrice),
            ],
            'package_payment' => [
                'is_active' => $isAllInActive,
                'price' => $allinPrice,
                'payment_status' => $allInPaymentStatus,
                'is_paid' => in_array(strtolower($allInPaymentStatus), ['terverifikasi', 'lunas']),
                'master_reg_id' => $masterAllInId,
                'master_comp_title' => $masterAllInCompTitle,
                'payment_proof_url' => $sharedAllInProofUrl,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_holder' => $accountHolder,
            ],
            'isAllInActive' => $isAllInActive,
            'isCurrentUserPicOnly' => $isCurrentUserPicOnly,
        ]);
    }

    /**
     * Perbarui data peserta dan berkas pendaftaran (selagi belum ditutup).
     */
    public function updateRegistration(Request $request, int|string $id)
    {
        /** @var \App\Models\Registrant|\App\Models\User $user */
        $user = $this->getRegistrant();
        $cleanPhone = Setting::formatWaNumber($user->phone);

        $registration = Registration::where('id', $id)
            ->where(function ($q) use ($user, $cleanPhone) {
                $q->where('registrant_id', $user->id)
                  ->orWhere('user_id', $user->id)
                  ->orWhere('mentor_wa', $user->phone)
                  ->orWhere('mentor_wa', $cleanPhone)
                  ->orWhere('assigned_phone', $user->phone)
                  ->orWhere('assigned_phone', $cleanPhone);
            })
            ->firstOrFail();

        $comp = Competition::find($registration->competition_id);
        if ($comp && ($comp->status === 'closed' || $comp->isDeadlinePassed())) {
            return back()->withErrors([
                'message' => "Pendaftaran cabang lomba {$registration->competition_title} telah ditutup oleh panitia. Data tidak dapat diubah lagi.",
            ]);
        }

        $validated = $request->validate([
            'participants_names' => 'nullable|string',
            'mentor_name' => 'nullable|string|max:255',
            'mentor_wa' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'custom_files' => 'nullable|array',
            'custom_files.*' => 'nullable',
        ], [
            'payment_proof.mimes' => 'Bukti pembayaran harus berformat JPEG, PNG, JPG, WEBP, atau PDF.',
            'payment_proof.max' => 'Ukuran berkas bukti pembayaran maksimal 5MB.',
        ]);

        $isOwner = ($registration->registrant_id === $user->id || $registration->user_id === $user->id || Setting::formatWaNumber($registration->mentor_wa) === $cleanPhone);

        if ($isOwner) {
            if (!empty($validated['mentor_name'])) {
                $registration->mentor_name = trim($validated['mentor_name']);
            }
            if (!empty($validated['mentor_wa'])) {
                $registration->mentor_wa = Setting::formatWaNumber($validated['mentor_wa']);
            }
        }
        if (isset($validated['participants_names'])) {
            $registration->participants_names = trim($validated['participants_names']);
        }
        if (isset($validated['notes'])) {
            $registration->notes = trim($validated['notes']);
        }
        if (!empty($validated['payment_method'])) {
            $registration->payment_method = $validated['payment_method'];
        }

        // Custom Fields Update
        $existingCustom = $registration->custom_fields ?: [];
        $rawCustom = $request->input('custom_fields', []);
        if (is_string($rawCustom)) {
            $rawCustom = json_decode($rawCustom, true) ?: [];
        }
        if (is_array($rawCustom)) {
            $existingCustom = array_merge($existingCustom, $rawCustom);
            $registration->custom_fields = $existingCustom;
        }

        // Handle File Re-uploads & Attachments
        $existingFiles = $registration->file_documents ?: [];
        $regCode = $registration->reg_code;

        // 1. Bukti Pembayaran Baru (jika diupload ulang)
        $proofUrl = null;
        $proofExt = null;
        $proofOriginalName = null;

        if ($request->hasFile('payment_proof')) {
            $proof = $request->file('payment_proof');
            if ($proof && $proof->isValid()) {
                $proofExt = strtolower($proof->getClientOriginalExtension());
                $proofOriginalName = $proof->getClientOriginalName();
                $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $proofOriginalName);
                $path = $proof->storeAs("uploads/registrations/{$regCode}", time() . "_bukti_{$safeName}", 'public');
                $proofUrl = '/storage/' . $path;

                // Update / Replace existing payment proof in file_documents
                $newFiles = array_filter($existingFiles, fn($f) => ($f['title'] ?? '') !== 'Bukti Transfer Pembayaran Bank');
                $newFiles[] = [
                    'title' => 'Bukti Transfer Pembayaran Bank',
                    'url' => $proofUrl,
                    'filename' => $proofOriginalName,
                    'type' => $proofExt,
                    'uploaded_at' => now()->toIso8601String(),
                ];
                $existingFiles = array_values($newFiles);

                $existingCustom['payment_proof'] = [
                    'type' => 'file',
                    'label' => 'Bukti Transfer Pembayaran Bank',
                    'name' => $proofOriginalName,
                    'url' => $proofUrl,
                ];
                $registration->custom_fields = $existingCustom;
            }
        }

        // 2. Dynamic Custom Files
        $uploadedFiles = [];
        if ($request->hasFile('custom_files') && is_array($request->file('custom_files'))) {
            $uploadedFiles = array_merge($uploadedFiles, $request->file('custom_files'));
        }
        if ($request->hasFile('custom_fields') && is_array($request->file('custom_fields'))) {
            $uploadedFiles = array_merge($uploadedFiles, $request->file('custom_fields'));
        }

        if (!empty($uploadedFiles)) {
            $comp = $registration->competition;
            $compCustomFields = ($comp && is_array($comp->custom_fields)) ? $comp->custom_fields : [];
            $compFieldMap = [];
            foreach ($compCustomFields as $cf) {
                if (!empty($cf['name'])) $compFieldMap[$cf['name']] = $cf;
            }

                foreach ($uploadedFiles as $fieldKey => $fileOrFiles) {
                    $fieldDef = $compFieldMap[$fieldKey] ?? [];
                    $maxFiles = !empty($fieldDef['max_files']) ? (int)$fieldDef['max_files'] : 1;
                    $fieldLabel = !empty($fieldDef['label']) ? $fieldDef['label'] : ucwords(str_replace(['_', '-'], ' ', $fieldKey));

                    $fileList = is_array($fileOrFiles) 
                        ? array_values(array_filter($fileOrFiles, fn($f) => $f instanceof \Illuminate\Http\UploadedFile)) 
                        : ($fileOrFiles instanceof \Illuminate\Http\UploadedFile ? [$fileOrFiles] : []);

                    if ($maxFiles > 0 && count($fileList) > $maxFiles) {
                        $fileList = array_slice($fileList, 0, $maxFiles);
                    }

                    $processedMetas = [];
                    foreach ($fileList as $file) {
                        if ($file->isValid()) {
                            $customExt = strtolower($file->getClientOriginalExtension());
                            $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file->getClientOriginalName());
                            $path = $file->storeAs("uploads/registrations/{$regCode}", time() . "_" . uniqid() . "_{$fieldKey}_{$safeName}", 'public');
                            $fileUrl = '/storage/' . $path;

                            $existingFiles[] = [
                                'title' => $fieldLabel,
                                'url' => $fileUrl,
                                'filename' => $file->getClientOriginalName(),
                                'type' => $customExt,
                                'uploaded_at' => now()->toIso8601String(),
                            ];

                            $processedMetas[] = [
                                'type' => 'file',
                                'label' => $fieldLabel,
                                'name' => $file->getClientOriginalName(),
                                'url' => $fileUrl,
                                'size' => $file->getSize(),
                                'extension' => $customExt,
                            ];
                        }
                    }

                    if (!empty($processedMetas)) {
                        if ($maxFiles > 1) {
                            $existingCustom[$fieldKey] = $processedMetas;
                        } else {
                            $existingCustom[$fieldKey] = $processedMetas[0] ?? null;
                        }
                    }
                }
                $registration->custom_fields = $existingCustom;
            }

        $registration->file_documents = array_values($existingFiles);
        $registration->save();

        // Jika pendaftaran ini adalah bagian dari Paket All-In, sinkronkan bukti transfer & metode ke seluruh cabang All-In kontingen
        if ($registration->package_type === 'Paket All-In') {
            $siblingAllIns = Registration::where('package_type', 'Paket All-In')
                ->where(function ($q) use ($user, $cleanPhone) {
                    $q->where('user_id', $user->id);
                    if (!empty($user->phone)) {
                        $q->orWhere('mentor_wa', $user->phone)
                          ->orWhere('mentor_wa', $cleanPhone);
                    }
                })
                ->where('id', '!=', $registration->id)
                ->get();

            foreach ($siblingAllIns as $sib) {
                if ($proofUrl && $proofOriginalName) {
                    $sibFiles = $sib->file_documents ?: [];
                    $sibFiles = array_filter($sibFiles, fn($f) => ($f['title'] ?? '') !== 'Bukti Transfer Pembayaran Bank');
                    $sibFiles[] = [
                        'title' => 'Bukti Transfer Pembayaran Bank',
                        'url' => $proofUrl,
                        'filename' => $proofOriginalName,
                        'type' => $proofExt ?? 'jpg',
                        'uploaded_at' => now()->toIso8601String(),
                    ];
                    $sib->file_documents = array_values($sibFiles);

                    $sibCustom = $sib->custom_fields ?: [];
                    $sibCustom['payment_proof'] = [
                        'type' => 'file',
                        'label' => 'Bukti Transfer Pembayaran Bank',
                        'name' => $proofOriginalName,
                        'url' => $proofUrl,
                    ];
                    $sib->custom_fields = $sibCustom;
                }
                if (!empty($validated['payment_method'])) {
                    $sib->payment_method = $validated['payment_method'];
                }
                if ($proofUrl && $sib->payment_status === 'Ditolak') {
                    $sib->payment_status = 'Menunggu Verifikasi';
                }
                $sib->save();
            }
        }

        return redirect()->back()->with('success', "Data dan berkas pendaftaran [{$registration->reg_code}] berhasil diperbarui!");
    }

    /**
     * Perbarui Profil Pendaftar.
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'registrant_role' => 'required|string|in:Pendamping,Koordinator,Siswa Peserta',
            'gender' => 'required|string|in:Laki-laki,Perempuan',
            'school_name' => 'required|string|max:255',
            'school_district' => 'required|string|max:255',
        ]);

        $user->update([
            'name' => trim($validated['name']),
            'registrant_role' => $validated['registrant_role'],
            'gender' => $validated['gender'],
            'school_name' => trim($validated['school_name']),
            'school_district' => trim($validated['school_district']),
        ]);

        try {
            School::firstOrCreateFromRegistration($validated['school_name'], $user->phone, $validated['school_district']);
        } catch (\Throwable $e) {
            Log::warning('Sinkronisasi sekolah gagal saat update profil: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Profil akun pendaftar Anda berhasil diperbarui!');
    }

    /**
     * Tunjuk anggota / koordinator cabang lomba (PIC) oleh pemilik akun.
     */
    public function assignPic(Request $request, int|string $id)
    {
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $registration = Registration::findOrFail($id);
        $cleanPhone = Setting::formatWaNumber($user->phone);

        // Hanya pemilik akun pendaftar atau nomor mentor_wa yang berhak menunjuk anggota
        if ($registration->registrant_id !== $user->id && $registration->user_id !== $user->id && Setting::formatWaNumber($registration->mentor_wa) !== $cleanPhone) {
            return redirect()->back()->with('error', 'Hanya pemilik akun utama yang berwenang menunjuk koordinator cabang lomba.');
        }

        $validated = $request->validate([
            'assigned_name' => 'required|string|max:255',
            'assigned_phone' => 'required|string|min:8|max:25',
            'assigned_role' => 'required|string|in:Koordinator Cabang,Kapten Tim,Siswa Peserta',
            'assigned_notes' => 'nullable|string|max:500',
        ], [
            'assigned_name.required' => 'Nama anggota yang ditunjuk wajib diisi.',
            'assigned_phone.required' => 'Nomor WhatsApp anggota wajib diisi.',
            'assigned_role.required' => 'Peran penugasan wajib dipilih.',
        ]);

        $assignedCleanPhone = Setting::formatWaNumber($validated['assigned_phone']);

        $registration->assigned_name = trim($validated['assigned_name']);
        $registration->assigned_phone = $assignedCleanPhone;
        $registration->assigned_role = $validated['assigned_role'];
        $registration->assigned_notes = $validated['assigned_notes'] ?? null;
        $registration->save();

        // Pastikan akun pendaftar (registrant) untuk PIC tersebut sudah siap agar bisa langsung login via OTP WhatsApp
        $picUser = Registrant::where(function ($q) use ($assignedCleanPhone, $validated) {
            $q->where('phone', $assignedCleanPhone)
              ->orWhere('phone', $validated['assigned_phone']);
        })->first();

        if (!$picUser) {
            Registrant::create([
                'name' => $registration->assigned_name,
                'phone' => $assignedCleanPhone,
                'email' => 'pic_' . preg_replace('/\D/', '', $assignedCleanPhone) . '@semarak.id',
                'password' => Hash::make(Str::random(20)),
                'registrant_role' => $registration->assigned_role,
                'school_name' => $registration->school_name,
                'gender' => 'Laki-laki',
                'is_active' => true,
            ]);
        }

        // Kirim notifikasi WA penunjukan ke nomor anggota
        try {
            $loginUrl = url('/pendaftar/login');
            $waMsg = "*[SEMARAK ALIYAH XIV 2026]*\n\n"
                . "Assalamu'alaikum Wr. Wb., Halo *{$registration->assigned_name}*!\n\n"
                . "Anda telah ditunjuk oleh *{$user->name}* ({$registration->school_name}) sebagai *{$registration->assigned_role}* untuk pendaftaran cabang lomba:\n"
                . "🏆 *{$registration->competition_title}* (Kode: {$registration->reg_code})\n\n"
                . ($registration->assigned_notes ? "📝 *Pesan Pembina*: _{$registration->assigned_notes}_\n\n" : "")
                . "Anda dapat masuk ke Portal Pendaftar untuk melengkapi nama delegasi dan mengunggah berkas menggunakan *kode OTP WhatsApp* ke nomor ini di:\n"
                . "👉 {$loginUrl}\n\n"
                . "Terima kasih,\n*Panitia Semarak Aliyah XIV 2026*";
            WhatsAppService::sendMessage($assignedCleanPhone, $waMsg, 'delegation', $registration->assigned_name, true);
        } catch (\Throwable $e) {
            Log::warning('Kirim WA penunjukan PIC gagal: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Berhasil menugaskan [{$registration->assigned_name}] sebagai {$registration->assigned_role} pada cabang {$registration->competition_title}!");
    }

    /**
     * Hapus penunjukan anggota/PIC pada cabang lomba.
     */
    public function removePic(Request $request, int|string $id)
    {
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $registration = Registration::findOrFail($id);
        $cleanPhone = Setting::formatWaNumber($user->phone);

        if ($registration->registrant_id !== $user->id && $registration->user_id !== $user->id && Setting::formatWaNumber($registration->mentor_wa) !== $cleanPhone) {
            return redirect()->back()->with('error', 'Hanya pemilik akun utama yang berwenang mengubah penunjukan.');
        }

        $oldName = $registration->assigned_name;
        $registration->assigned_name = null;
        $registration->assigned_phone = null;
        $registration->assigned_role = null;
        $registration->assigned_notes = null;
        $registration->save();

        return redirect()->back()->with('success', "Penunjukan anggota [{$oldName}] untuk cabang {$registration->competition_title} telah dihapus.");
    }

    /**
     * Aktifkan Paket All-In Komplit untuk sekolah pendaftar.
     * Mendaftarkan seluruh cabang lomba yang belum terdaftar secara otomatis
     * sehingga pendaftar tinggal melengkapi data & berkas per cabang.
     */
    public function activateAllInPackage(Request $request)
    {
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $cleanPhone = Setting::formatWaNumber($user->phone);
        $allinPrice = (int)Setting::get('package_allin_price', 200000);
        $competitions = Competition::where('status', '!=', 'closed')->orderBy('id', 'asc')->get();

        if ($competitions->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada cabang lomba aktif yang tersedia saat ini.');
        }

        // Ambil pendaftaran yang sudah dimiliki pendaftar / sekolah ini
        $existingRegs = Registration::where(function ($q) use ($user, $cleanPhone) {
            $q->where('registrant_id', $user->id)
              ->orWhere('user_id', $user->id);
            if (!empty($user->phone)) {
                $q->orWhere('mentor_wa', $user->phone)
                  ->orWhere('mentor_wa', $cleanPhone);
            }
        })->orderBy('id', 'asc')->get();

        // Cek apakah ada yang sudah lunas/terverifikasi
        $isAlreadyPaid = $existingRegs->contains(fn($r) => in_array(strtolower((string)$r->payment_status), ['terverifikasi', 'lunas', 'verified']));
        $paymentStatus = $isAlreadyPaid ? 'Terverifikasi' : 'Menunggu Verifikasi';

        // Deteksi bukti transfer yang sudah ada di salah satu pendaftaran
        $existingProofFile = null;
        foreach ($existingRegs as $r) {
            $files = $r->file_documents ?: [];
            if (is_array($files)) {
                foreach ($files as $f) {
                    if (stripos($f['title'] ?? '', 'bukti') !== false || stripos($f['filename'] ?? '', 'bukti') !== false) {
                        $existingProofFile = $f;
                        break 2;
                    }
                }
            }
        }

        // Tentukan induk pendaftaran (master): pendaftaran pertama yang memegang tagihan paket
        $masterExisting = $existingRegs->firstWhere('total_fee', '>', 0) ?: $existingRegs->first();
        $masterId = $masterExisting ? $masterExisting->id : null;

        // 1. Ubah paket seluruh pendaftaran yang sudah ada menjadi Paket All-In
        // Pastikan hanya tepat 1 pendaftaran induk yang memiliki total_fee = $allinPrice, lainnya 0!
        foreach ($existingRegs as $r) {
            $r->package_type = 'Paket All-In';
            $r->payment_status = $paymentStatus;
            if ($r->id === $masterId) {
                $r->total_fee = $allinPrice;
            } else {
                $r->total_fee = 0;
            }
            if ($existingProofFile && empty($r->file_documents)) {
                $r->file_documents = [$existingProofFile];
            }
            $r->save();
        }

        $existingCompIds = $existingRegs->pluck('competition_id')->toArray();

        // 2. Daftarkan otomatis cabang-cabang lomba yang belum ada
        $isFirstCreated = ($existingRegs->isEmpty());
        $createdCount = 0;

        foreach ($competitions as $comp) {
            if (in_array($comp->id, $existingCompIds)) {
                continue;
            }

            $regCode = 'SA26-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $fee = $isFirstCreated ? $allinPrice : 0;
            if ($isFirstCreated) {
                $isFirstCreated = false;
            }

            $fileDocs = $existingProofFile ? [$existingProofFile] : null;

            Registration::create([
                'registrant_id' => $user->id,
                'user_id' => $user->id,
                'reg_code' => $regCode,
                'barcode' => $regCode,
                'school_name' => $user->school_name ?: '-',
                'mentor_name' => $user->name,
                'mentor_wa' => $user->phone,
                'package_type' => 'Paket All-In',
                'competition_id' => $comp->id,
                'competition_title' => $comp->title,
                'participants_names' => '',
                'notes' => 'Pendaftaran otomatis via Paket All-In Komplit',
                'total_fee' => $fee,
                'registration_status' => 'Menunggu Verifikasi',
                'payment_status' => $paymentStatus,
                'payment_method' => 'Transfer ' . (Setting::getPrimaryBankAccount()['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)')),
                'checkin_status' => 'belum_hadir',
                'file_documents' => $fileDocs,
            ]);

            $createdCount++;
        }

        return redirect()->back()->with('success', "Paket All-In Komplit berhasil diaktifkan! Seluruh cabang lomba telah otomatis terdaftar di tabel partisipasi kontingen Anda ({$createdCount} cabang baru ditambahkan). Tagihan pembayaran terpadu terkonsolidasi menjadi Rp " . number_format($allinPrice, 0, ',', '.') . " untuk seluruh cabang.");
    }

    /**
     * Upload Bukti Pembayaran Khusus Paket All-In Komplit dari Banner / Dialog Terpadu.
     */
    public function uploadPackageProof(Request $request)
    {
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payment_method' => 'nullable|string',
        ]);

        $cleanPhone = Setting::formatWaNumber($user->phone);
        $allInRegs = Registration::where('package_type', 'Paket All-In')
            ->where(function ($q) use ($user, $cleanPhone) {
                $q->where('registrant_id', $user->id)
                  ->orWhere('user_id', $user->id);
                if (!empty($user->phone)) {
                    $q->orWhere('mentor_wa', $user->phone)
                      ->orWhere('mentor_wa', $cleanPhone);
                }
            })
            ->get();

        if ($allInRegs->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ditemukan pendaftaran aktif dengan skema Paket All-In.');
        }

        $proof = $request->file('payment_proof');
        if (!$proof || !$proof->isValid()) {
            return redirect()->back()->with('error', 'File bukti pembayaran tidak valid.');
        }

        $ext = strtolower($proof->getClientOriginalExtension());
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $proof->getClientOriginalName());
        $masterReg = $allInRegs->firstWhere('total_fee', '>', 0) ?: $allInRegs->first();
        $regCode = $masterReg->reg_code;
        $path = $proof->storeAs("uploads/registrations/{$regCode}", time() . "_bukti_{$safeName}", 'public');
        $fileUrl = '/storage/' . $path;
        $primaryBankName = Setting::getPrimaryBankAccount()['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)');
        $paymentMethod = $request->input('payment_method') ?: ('Transfer ' . $primaryBankName);

        foreach ($allInRegs as $reg) {
            $existingFiles = $reg->file_documents ?: [];
            $existingFiles = array_filter($existingFiles, fn($f) => ($f['title'] ?? '') !== 'Bukti Transfer Pembayaran Bank');
            $existingFiles[] = [
                'title' => 'Bukti Transfer Pembayaran Bank',
                'url' => $fileUrl,
                'filename' => $proof->getClientOriginalName(),
                'type' => $ext,
                'uploaded_at' => now()->toIso8601String(),
            ];
            $reg->file_documents = array_values($existingFiles);

            $custom = $reg->custom_fields ?: [];
            $custom['payment_proof'] = [
                'type' => 'file',
                'label' => 'Bukti Transfer Pembayaran Bank',
                'name' => $proof->getClientOriginalName(),
                'url' => $fileUrl,
            ];
            $reg->custom_fields = $custom;
            $reg->payment_method = $paymentMethod;
            if ($reg->payment_status === 'Ditolak') {
                $reg->payment_status = 'Menunggu Verifikasi';
            }
            $reg->save();
        }

        return redirect()->back()->with('success', 'Bukti pembayaran Paket All-In Komplit (Rp ' . number_format((int)Setting::get('package_allin_price', 200000), 0, ',', '.') . ') berhasil diunggah! Status pembayaran seluruh cabang lomba kini menunggu verifikasi panitia.');
    }

    /**
     * Halaman Khusus Pendaftaran Cabang Lomba untuk Akun Pendaftar.
     */
    public function createRegistration(Request $request)
    {
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $competitions = Competition::orderBy('id', 'asc')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'title' => $c->title,
                'category_label' => $c->category_label ?: 'Umum',
                'fee' => (int)$c->fee,
                'status' => $c->status,
                'max_quota' => (int)$c->max_quota,
                'current_quota' => (int)$c->current_quota,
                'is_closed' => $c->status === 'closed' || $c->isDeadlinePassed(),
                'registration_deadline' => $c->registration_deadline,
                'description' => $c->description,
                'participant_type' => $c->participant_type ?: 'individu',
                'participants_rule' => $c->participants_rule,
                'max_participants' => (int)($c->max_participants ?: 1),
                'custom_fields' => $c->custom_fields ?: [],
                'registration_type' => $c->registration_type ?: 'online',
                'is_direct_registration' => $c->isDirectRegistration(),
                'direct_registration_note' => $c->direct_registration_note,
                'direct_registration_template' => $c->direct_registration_template,
                'direct_contact_wa_url' => $c->direct_contact_wa_url,
                'primary_coordinator' => $c->primary_coordinator,
                'coordinators' => $c->coordinators ?: [],
            ];
        });

        // Cabang lomba yang sudah terdaftar oleh user ini
        $existingCompIds = Registration::where(function ($q) use ($user) {
                $q->where('registrant_id', $user->id)->orWhere('user_id', $user->id);
            })
            ->pluck('competition_id')
            ->toArray();

        // Cek apakah sekolah ini telah terverifikasi Paket All-In
        $cleanTargetSchool = School::normalizeSchoolName($user->school_name);
        $existingAllIn = Registration::where('package_type', 'Paket All-In')
            ->get()
            ->first(function ($r) use ($user, $cleanTargetSchool) {
                if (empty($r->school_name)) return false;
                if (strcasecmp(trim($r->school_name), trim($user->school_name)) === 0) return true;
                return School::normalizeSchoolName($r->school_name) === $cleanTargetSchool;
            });

        $isAllInVerified = false;
        if ($existingAllIn) {
            $pStatus = strtolower($existingAllIn->payment_status ?? '');
            $rStatus = strtolower($existingAllIn->registration_status ?? 'terverifikasi');
            $isAllInVerified = in_array($pStatus, ['terverifikasi', 'lunas', 'verified']) && in_array($rStatus, ['terverifikasi', 'verified']);
        }

        $sumCompFees = (int)Competition::sum('fee');
        $isAutoOriginalPrice = Setting::get('package_allin_auto_original_price', '1') === '1';
        $allinOriginalPrice = $isAutoOriginalPrice ? $sumCompFees : (int)Setting::get('package_allin_original_price', $sumCompFees);

        $bankAccounts = Setting::getBankAccounts();
        $primaryAccount = Setting::getPrimaryBankAccount();

        $pricingSettings = [
            'allin_is_active' => Setting::get('package_allin_is_active', '1') === '1',
            'allin_name' => Setting::get('package_allin_name', 'Paket All-In Komplit'),
            'allin_price' => (int)Setting::get('package_allin_price', 200000),
            'allin_original_price' => $allinOriginalPrice ?: 400000,
            'bank_name' => $primaryAccount['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'),
            'account_number' => $primaryAccount['account_number'] ?? Setting::get('payment_account_number', ''),
            'account_holder' => $primaryAccount['account_holder'] ?? Setting::get('payment_account_holder', 'Semarak Aliyah'),
            'bank_accounts' => $bankAccounts,
            'confirmation_wa' => Setting::get('payment_confirmation_wa', '0838 5007 0767'),
            'instructions' => Setting::get('payment_instructions', ''),
            'cash_enabled' => Setting::get('payment_cash_enabled', '1') === '1',
        ];

        $matchedSchool = School::findExistingSchool($user->school_name);

        return Inertia::render('Registrant/RegisterCompetition', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'registrant_role' => $user->registrant_role ?: 'Pendamping',
                'gender' => $user->gender ?: 'Laki-laki',
                'school_name' => $user->school_name ?: '',
                'school_district' => $user->school_district ?: '',
            ],
            'matchedSchool' => [
                'name' => $matchedSchool?->name ?? ($user->school_name !== '-' ? $user->school_name : ''),
                'kecamatan' => $matchedSchool?->kecamatan ?? ($user->school_district !== '-' ? $user->school_district : ''),
                'npsn' => $matchedSchool?->npsn ?? '',
                'alamat' => $matchedSchool?->alamat ?? '',
            ],
            'competitions' => $competitions,
            'existingCompIds' => $existingCompIds,
            'isAllInVerified' => $isAllInVerified,
            'allInRegCode' => $existingAllIn?->reg_code,
            'allInSchoolName' => $existingAllIn?->school_name,
            'pricingSettings' => $pricingSettings,
            'generatedAllInCode' => session('generated_allin_code'),
            'generatedAllInSchool' => session('generated_allin_school'),
        ]);
    }

    /**
     * Simpan pendaftaran cabang lomba melalui akun pendaftar.
     */
    public function storeRegistration(Request $request)
    {
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $packageTypeInput = $request->input('package_type', 'Paket Satuan');

        // =========================================================================
        // 1. PENDAFTARAN PAKET KOMPLIT (ALL-IN)
        // Formulir HANYA mengisi 7 informasi sekolah & kontak koordinator.
        // Kode paket komplit akan terbuat secara otomatis.
        // =========================================================================
        if ($packageTypeInput === 'Paket All-In') {
            $validatedAllIn = $request->validate([
                'school_name' => 'required|string|max:150',
                'school_district' => 'required|string|max:100',
                'npsn' => 'nullable|string|max:30',
                'school_address' => 'required|string|max:300',
                'coordinator_name' => 'required|string|max:100',
                'coordinator_phone' => 'required|string|max:30',
                'coordinator_email' => 'required|email|max:100',
                'payment_method' => 'nullable|string',
                'notes' => 'nullable|string',
                'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            ], [
                'school_name.required' => 'Nama sekolah / madrasah wajib diisi.',
                'school_district.required' => 'Kecamatan sekolah / madrasah wajib diisi.',
                'school_address.required' => 'Alamat sekolah / madrasah wajib diisi.',
                'coordinator_name.required' => 'Nama koordinator / pendamping wajib diisi.',
                'coordinator_phone.required' => 'Nomor WhatsApp / kontak koordinator wajib diisi.',
                'coordinator_email.required' => 'Email koordinator / pendamping wajib diisi.',
                'coordinator_email.email' => 'Format email koordinator tidak valid.',
                'payment_proof.mimes' => 'Bukti pembayaran harus berformat JPEG, PNG, JPG, WEBP, atau PDF.',
                'payment_proof.max' => 'Ukuran berkas bukti pembayaran maksimal 5MB.',
            ]);

            // Terbitkan Kode Paket Komplit Otomatis
            $regCode = 'SA26-KMP-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

            $fileDocuments = [];
            // Upload Bukti Pembayaran jika ada
            if ($request->hasFile('payment_proof')) {
                $proof = $request->file('payment_proof');
                if ($proof && $proof->isValid()) {
                    $ext = strtolower($proof->getClientOriginalExtension());
                    $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $proof->getClientOriginalName());
                    $path = $proof->storeAs("uploads/registrations/{$regCode}", time() . "_bukti_{$safeOriginalName}", 'public');
                    $fileDocuments[] = [
                        'title' => 'Bukti Transfer Pembayaran Paket Komplit',
                        'filename' => $proof->getClientOriginalName(),
                        'url' => '/storage/' . $path,
                        'type' => $ext,
                        'uploaded_at' => now()->toIso8601String(),
                    ];
                }
            }

            $allinPrice = (int)Setting::get('package_allin_price', 200000);
            $allinName = Setting::get('package_allin_name', 'Paket All-In Komplit');
            $hasProof = !empty($fileDocuments);

            $registration = Registration::create([
                'registrant_id' => $user->id,
                'user_id' => $user->id,
                'reg_code' => $regCode,
                'barcode' => $regCode,
                'school_name' => $validatedAllIn['school_name'],
                'mentor_name' => $validatedAllIn['coordinator_name'],
                'mentor_wa' => $validatedAllIn['coordinator_phone'],
                'package_type' => 'Paket All-In',
                'competition_id' => 'all-in',
                'competition_title' => $allinName,
                'participants_names' => "Kontingen Resmi Paket Komplit - {$validatedAllIn['school_name']}\nKoordinator: {$validatedAllIn['coordinator_name']} ({$validatedAllIn['coordinator_phone']})",
                'notes' => $validatedAllIn['notes'] ?? 'Pendaftaran Paket Komplit Kontingen Sekolah (Kode Terbit Otomatis)',
                'total_fee' => $allinPrice,
                'registration_status' => 'Menunggu Verifikasi',
                'payment_status' => $hasProof ? 'Menunggu Verifikasi' : 'Menunggu Pembayaran',
                'payment_method' => $validatedAllIn['payment_method'] ?? ('Transfer ' . (Setting::getPrimaryBankAccount()['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'))),
                'checkin_status' => 'belum_hadir',
                'custom_fields' => [
                    'is_package_allin_master' => true,
                    'school_district' => $validatedAllIn['school_district'],
                    'school_npsn' => $validatedAllIn['npsn'] ?? null,
                    'npsn' => $validatedAllIn['npsn'] ?? null,
                    'school_address' => $validatedAllIn['school_address'],
                    'coordinator_email' => $validatedAllIn['coordinator_email'],
                    'coordinator_name' => $validatedAllIn['coordinator_name'],
                    'coordinator_phone' => $validatedAllIn['coordinator_phone'],
                ],
                'file_documents' => !empty($fileDocuments) ? $fileDocuments : null,
            ]);

            // Sinkronkan data sekolah di profil user jika belum lengkap
            if (empty($user->school_name) || $user->school_name === '-') {
                $user->school_name = $validatedAllIn['school_name'];
            }
            if (empty($user->school_district) || $user->school_district === '-') {
                $user->school_district = $validatedAllIn['school_district'];
            }
            $user->save();

            try {
                WhatsAppService::sendNewRegistrationNotification($registration);
            } catch (\Throwable $e) {
                Log::warning('Notifikasi WA gagal: ' . $e->getMessage());
            }

            return redirect()->route('pendaftar.daftar-lomba')->with([
                'success' => "Pendaftaran Paket Komplit berhasil! Kode Paket Komplit otomatis diterbitkan: {$regCode}",
                'generated_allin_code' => $regCode,
                'generated_allin_school' => $validatedAllIn['school_name'],
            ]);
        }

        // =========================================================================
        // 2. PENDAFTARAN PAKET SATUAN (PER-CABANG LOMBA)
        // Mengacu kepada pengaturan Form Builder cabang lomba yang dipilih.
        // =========================================================================
        if (!$request->filled('participants_names') && $request->has('custom_fields')) {
            $cf = $request->input('custom_fields');
            if (is_array($cf)) {
                foreach ($cf as $k => $v) {
                    if (is_array($v) && !empty($v) && isset($v[0]['name'])) {
                        $names = [];
                        foreach ($v as $idx => $item) {
                            if (!empty($item['name'])) {
                                $names[] = ($idx + 1) . '. ' . $item['name'] . (!empty($item['grade']) ? " ({$item['grade']})" : '');
                            }
                        }
                        if (!empty($names)) {
                            $request->merge(['participants_names' => implode("\n", $names)]);
                            break;
                        }
                    }
                }
            }
        }

        $validated = $request->validate([
            'competition_id' => 'required|string',
            'package_type' => 'nullable|string',
            'participants_names' => 'required|string',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'custom_fields' => 'nullable|array',
            'allin_code' => 'nullable|string',
            'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'file_documents.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx,zip,rar|max:10240',
            'custom_files' => 'nullable|array',
            'custom_files.*' => 'nullable',
        ], [
            'competition_id.required' => 'Pilih cabang lomba yang ingin didaftarkan.',
            'participants_names.required' => 'Daftar nama peserta / delegasi wajib diisi.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berformat JPEG, PNG, JPG, WEBP, atau PDF.',
            'payment_proof.max' => 'Ukuran berkas bukti pembayaran maksimal 5MB.',
            'file_documents.*.mimes' => 'Format berkas dokumen tidak didukung.',
            'file_documents.*.max' => 'Ukuran berkas dokumen maksimal 10MB.',
        ]);

        $competition = Competition::find($validated['competition_id']);

        if ($competition) {
            if ($competition->isDirectRegistration()) {
                $coordName = $competition->primary_coordinator['name'] ?? 'Koordinator Lomba';
                $coordPhone = $competition->primary_coordinator['contact'] ?? '';
                $contactInfo = $coordPhone ? "{$coordName} ({$coordPhone})" : $coordName;
                return redirect()->back()->withErrors([
                    'competition_id' => "Pendaftaran cabang lomba {$competition->title} hanya dapat dilakukan melalui kontak langsung ke Koordinator Lomba: {$contactInfo}."
                ]);
            }
            if ($competition->isDeadlinePassed()) {
                return redirect()->back()->withErrors([
                    'competition_id' => "Pendaftaran untuk cabang {$competition->title} telah ditutup karena melewati batas tanggal pendaftaran."
                ]);
            }
            if ($competition->isQuotaFull()) {
                return redirect()->back()->withErrors([
                    'competition_id' => "Kuota pendaftaran untuk cabang {$competition->title} telah penuh ({$competition->max_quota} peserta)."
                ]);
            }
            if ($competition->status === 'closed') {
                return redirect()->back()->withErrors([
                    'competition_id' => "Pendaftaran untuk cabang {$competition->title} telah ditutup oleh panitia."
                ]);
            }
        }
        $compTitle = $competition ? $competition->title : 'Semua Cabang Lomba (Paket All In)';

        // Cek verifikasi Paket All-In sekolah (melalui kode paket manual atau nama sekolah)
        $allInCodeInput = trim(
            $request->input('custom_fields.allin_code') 
            ?? $request->input('allin_code') 
            ?? $request->input('package_code') 
            ?? ''
        );

        $existingAllIn = null;
        $isAllInFromCode = false;

        // 1. Prioritas Utama: Cek input kode paket komplit manual
        if (!empty($allInCodeInput)) {
            $cleanCode = strtoupper($allInCodeInput);
            $existingAllIn = Registration::where('package_type', 'Paket All-In')
                ->where(function ($q) use ($cleanCode) {
                    $q->whereRaw('UPPER(reg_code) = ?', [$cleanCode])
                      ->orWhereRaw('UPPER(barcode) = ?', [$cleanCode]);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($existingAllIn) {
                $isAllInFromCode = true;
            }
        }

        // 2. Fallback: Deteksi otomatis dari nama sekolah akun pendaftar
        if (!$existingAllIn) {
            $cleanTargetSchool = School::normalizeSchoolName($user->school_name);
            $existingAllIn = Registration::where('package_type', 'Paket All-In')
                ->get()
                ->first(function ($r) use ($user, $cleanTargetSchool) {
                    if (empty($r->school_name)) return false;
                    if (strcasecmp(trim($r->school_name), trim($user->school_name)) === 0) return true;
                    return School::normalizeSchoolName($r->school_name) === $cleanTargetSchool;
                });
        }

        $isAllInVerified = false;
        if ($existingAllIn) {
            $pStatus = strtolower($existingAllIn->payment_status ?? '');
            $rStatus = strtolower($existingAllIn->registration_status ?? 'terverifikasi');
            $isAllInVerified = in_array($pStatus, ['terverifikasi', 'lunas', 'verified']) || $isAllInFromCode;
        }

        $packageType = $request->input('package_type') ?? ($isAllInVerified ? 'Paket All-In' : 'Paket Satuan');

        if ($isAllInVerified && $validated['competition_id'] !== 'all-in') {
            $totalFee = 0;
            $paymentStatus = 'Terverifikasi';
            $packageType = 'Paket All-In';
        } elseif ($packageType === 'Paket All-In') {
            $totalFee = (int)Setting::get('package_allin_price', 200000);
            $paymentStatus = 'Menunggu Verifikasi';
        } else {
            $totalFee = $competition ? (int)$competition->fee : 50000;
            $paymentStatus = 'Menunggu Verifikasi';
        }

        $regCode = 'SA26-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $fileDocuments = [];

        // Upload Berkas Dokumen Persyaratan
        if ($request->hasFile('file_documents')) {
            $files = $request->file('file_documents');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file->getClientOriginalName());
                    $path = $file->storeAs("uploads/registrations/{$regCode}", time() . "_{$safeOriginalName}", 'public');
                    $fileDocuments[] = [
                        'title' => 'Berkas Persyaratan Lomba',
                        'filename' => $file->getClientOriginalName(),
                        'url' => '/storage/' . $path,
                        'type' => $ext,
                        'uploaded_at' => now()->toIso8601String(),
                    ];
                }
            }
        }

        // Upload Bukti Pembayaran
        if ($request->hasFile('payment_proof')) {
            $proof = $request->file('payment_proof');
            if ($proof && $proof->isValid()) {
                $ext = strtolower($proof->getClientOriginalExtension());
                $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $proof->getClientOriginalName());
                $path = $proof->storeAs("uploads/registrations/{$regCode}", time() . "_bukti_{$safeOriginalName}", 'public');
                $fileDocuments[] = [
                    'title' => 'Bukti Transfer Pembayaran',
                    'filename' => $proof->getClientOriginalName(),
                    'url' => '/storage/' . $path,
                    'type' => $ext,
                    'uploaded_at' => now()->toIso8601String(),
                ];
            }
        }

        $customFields = $request->input('custom_fields') ?: [];

        // Upload Berkas Khusus Dinamis dari Form Builder (custom_files / custom_fields)
        if ($request->hasFile('custom_files') || $request->hasFile('custom_fields')) {
            $customUploads = array_merge(
                (array)$request->file('custom_files', []),
                (array)$request->file('custom_fields', [])
            );
            if (is_array($customUploads) && !empty($customUploads)) {
                $compCustomFields = ($competition && is_array($competition->custom_fields)) ? $competition->custom_fields : [];
                $compFieldMap = [];
                foreach ($compCustomFields as $cf) {
                    if (!empty($cf['name'])) $compFieldMap[$cf['name']] = $cf;
                }

                foreach ($customUploads as $fName => $uFile) {
                    $fieldDef = $compFieldMap[$fName] ?? [];
                    $maxFiles = !empty($fieldDef['max_files']) ? (int)$fieldDef['max_files'] : 1;
                    $fieldLabel = !empty($fieldDef['label']) ? $fieldDef['label'] : ucwords(str_replace('_', ' ', $fName));

                    $uFileList = is_array($uFile) 
                        ? array_values(array_filter($uFile, fn($f) => $f instanceof \Illuminate\Http\UploadedFile)) 
                        : ($uFile instanceof \Illuminate\Http\UploadedFile ? [$uFile] : []);

                    if ($maxFiles > 0 && count($uFileList) > $maxFiles) {
                        $uFileList = array_slice($uFileList, 0, $maxFiles);
                    }

                    $processedMetas = [];
                    foreach ($uFileList as $singleFile) {
                        if ($singleFile->isValid()) {
                            $ext = strtolower($singleFile->getClientOriginalExtension());
                            $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $singleFile->getClientOriginalName());
                            $path = $singleFile->storeAs("uploads/registrations/{$regCode}", time() . "_" . uniqid() . "_{$safeOriginalName}", 'public');
                            $fileUrl = '/storage/' . $path;
                            $fileMeta = [
                                'type' => 'file',
                                'label' => $fieldLabel,
                                'name' => $singleFile->getClientOriginalName(),
                                'url' => $fileUrl,
                                'size' => $singleFile->getSize(),
                                'extension' => $ext,
                            ];
                            $processedMetas[] = $fileMeta;
                            $fileDocuments[] = [
                                'title' => $fieldLabel,
                                'filename' => $singleFile->getClientOriginalName(),
                                'url' => $fileUrl,
                                'type' => $ext,
                                'uploaded_at' => now()->toIso8601String(),
                            ];
                        }
                    }

                    if (!empty($processedMetas)) {
                        if ($maxFiles > 1) {
                            $customFields[$fName] = $processedMetas;
                        } else {
                            $customFields[$fName] = $processedMetas[0] ?? null;
                        }
                    }
                }
            }
        }

        // Process participant repeater photos (photo_formal & photo_freestyle)
        $compCustomFields = ($competition && is_array($competition->custom_fields)) ? $competition->custom_fields : [];
        $repeaterDef = null;
        foreach ($compCustomFields as $f) {
            if (($f['type'] ?? '') === 'participant_repeater') {
                $repeaterDef = $f;
                break;
            }
        }
        $repeaterKey = $repeaterDef['name'] ?? 'biodata_peserta';

        if (!empty($customFields[$repeaterKey]) && is_array($customFields[$repeaterKey])) {
            $repeaterList = $customFields[$repeaterKey];
            $repeaterFiles = $request->file("custom_fields.{$repeaterKey}") ?: $request->file($repeaterKey);

            foreach ($repeaterList as $pIdx => &$pItem) {
                if (!is_array($pItem)) continue;
                $pName = trim($pItem['name'] ?? "Peserta #" . ($pIdx + 1));

                // 1. Photo Formal (Pas Foto 1 File)
                $formalFile = $request->file("custom_fields.{$repeaterKey}.{$pIdx}.photo_formal")
                    ?: ($request->file("{$repeaterKey}.{$pIdx}.photo_formal")
                    ?: ($repeaterFiles[$pIdx]['photo_formal'] ?? null));

                if ($formalFile instanceof \Illuminate\Http\UploadedFile && $formalFile->isValid()) {
                    $ext = strtolower($formalFile->getClientOriginalExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $formalFile->getClientOriginalName());
                        $path = $formalFile->storeAs("uploads/registrations/{$regCode}", time() . "_{$pIdx}_formal_{$safeName}", 'public');
                        $fileUrl = '/storage/' . $path;
                        $pItem['photo_formal'] = $fileUrl;
                        $pItem['photo'] = $fileUrl;
                        $fileDocuments[] = [
                            'title' => "Pas Foto Formal: {$pName}",
                            'filename' => $formalFile->getClientOriginalName(),
                            'url' => $fileUrl,
                            'type' => $ext,
                            'uploaded_at' => now()->toIso8601String(),
                        ];
                    }
                }

                // 2. Photo Freestyle (Foto Gaya Bebas - Bisa >1 File)
                $freestyleFiles = $request->file("custom_fields.{$repeaterKey}.{$pIdx}.photo_freestyle")
                    ?: ($request->file("{$repeaterKey}.{$pIdx}.photo_freestyle")
                    ?: ($repeaterFiles[$pIdx]['photo_freestyle'] ?? null));

                if (!empty($freestyleFiles)) {
                    if (!is_array($freestyleFiles)) {
                        $freestyleFiles = [$freestyleFiles];
                    }
                    $storedFreestyleUrls = [];
                    foreach ($freestyleFiles as $fIdx => $fFile) {
                        if ($fFile instanceof \Illuminate\Http\UploadedFile && $fFile->isValid()) {
                            $ext = strtolower($fFile->getClientOriginalExtension());
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fFile->getClientOriginalName());
                                $path = $fFile->storeAs("uploads/registrations/{$regCode}", time() . "_{$pIdx}_bebas_{$fIdx}_{$safeName}", 'public');
                                $fileUrl = '/storage/' . $path;
                                $storedFreestyleUrls[] = $fileUrl;
                                $fileDocuments[] = [
                                    'title' => "Foto Gaya Bebas: {$pName}" . (count($freestyleFiles) > 1 ? " (#" . ($fIdx + 1) . ")" : ""),
                                    'filename' => $fFile->getClientOriginalName(),
                                    'url' => $fileUrl,
                                    'type' => $ext,
                                    'uploaded_at' => now()->toIso8601String(),
                                ];
                            }
                        }
                    }
                    if (!empty($storedFreestyleUrls)) {
                        $pItem['photo_freestyle'] = count($storedFreestyleUrls) === 1 ? $storedFreestyleUrls[0] : $storedFreestyleUrls;
                        $pItem['photo_freestyle_urls'] = $storedFreestyleUrls;
                    }
                }
            }
            unset($pItem);
            $customFields[$repeaterKey] = $repeaterList;
        }

        if ($isAllInVerified && $existingAllIn) {
            $customFields['allin_code'] = $existingAllIn->reg_code;
            $customFields['linked_all_in_reg_code'] = $existingAllIn->reg_code;
        } elseif (!empty($allInCodeInput)) {
            $customFields['allin_code'] = $allInCodeInput;
        }

        $registration = Registration::create([
            'registrant_id' => $user->id,
            'user_id' => $user->id,
            'reg_code' => $regCode,
            'barcode' => $regCode,
            'school_name' => $user->school_name ?: '-',
            'mentor_name' => $user->name,
            'mentor_wa' => $user->phone,
            'package_type' => $packageType,
            'competition_id' => $validated['competition_id'],
            'competition_title' => $compTitle,
            'participants_names' => $validated['participants_names'],
            'notes' => $validated['notes'] ?? null,
            'total_fee' => $totalFee,
            'registration_status' => $isAllInVerified ? 'Terverifikasi' : 'Menunggu Verifikasi',
            'payment_status' => $paymentStatus,
            'checkin_status' => 'belum_hadir',
            'payment_method' => $validated['payment_method'] ?? ('Transfer ' . (Setting::getPrimaryBankAccount()['bank_name'] ?? Setting::get('payment_bank_name', 'Bank Syariah Indonesia (BSI)'))),
            'custom_fields' => !empty($customFields) ? $customFields : null,
            'file_documents' => !empty($fileDocuments) ? $fileDocuments : null,
        ]);

        if ($competition) {
            $competition->increment('current_quota');
            if ($competition->current_quota >= $competition->max_quota) {
                $competition->status = 'closed';
                $competition->save();
            }
        }

        try {
            WhatsAppService::sendNewRegistrationNotification($registration);
        } catch (\Throwable $e) {
            Log::warning('Notifikasi WA gagal: ' . $e->getMessage());
        }

        return redirect()->route('pendaftar.dashboard')->with('success', "Pendaftaran delegasi cabang [{$compTitle}] berhasil dikirim dan terhubung ke akun Anda!");
    }

    /**
     * Simpan gambar avatar pendaftar dari data URL base64.
     */
    protected function saveAvatarData(string $base64Data, ?string $oldAvatar = null): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $imageType = strtolower($matches[1]);
            if (!in_array($imageType, ['jpeg', 'jpg', 'png', 'webp'])) {
                $imageType = 'jpg';
            }
            $cleanBase64 = substr($base64Data, strpos($base64Data, ',') + 1);
            $decoded = base64_decode($cleanBase64);
            if ($decoded === false) {
                return null;
            }

            if (!empty($oldAvatar)) {
                $cleanOld = ltrim(str_replace(['/storage/', 'storage/'], '', $oldAvatar), '/');
                if (Storage::disk('public')->exists($cleanOld)) {
                    Storage::disk('public')->delete($cleanOld);
                }
            }

            $filename = 'avatars/pendaftar_' . uniqid() . '_' . time() . '.' . ($imageType === 'jpeg' ? 'jpg' : $imageType);
            Storage::disk('public')->put($filename, $decoded);
            return $filename;
        }

        return null;
    }

    /**
     * Update avatar foto pendaftar.
     */
    public function updateAvatar(Request $request)
    {
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $request->validate([
            'avatar_data' => ['required', 'string'],
        ]);

        $newPath = $this->saveAvatarData($request->input('avatar_data'), $user->avatar);
        if (!$newPath) {
            return redirect()->back()->with('error', 'Gagal memproses data gambar foto.');
        }

        $user->update(['avatar' => $newPath]);

        return redirect()->back()->with('success', 'Foto profil Anda berhasil disesuaikan dan diperbarui!');
    }

    /**
     * Hapus avatar foto pendaftar.
     */
    public function removeAvatar()
    {
        /** @var \App\Models\Registrant|\App\Models\User|null $user */
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        if (!empty($user->avatar)) {
            $cleanOld = ltrim(str_replace(['/storage/', 'storage/'], '', $user->avatar), '/');
            if (Storage::disk('public')->exists($cleanOld)) {
                Storage::disk('public')->delete($cleanOld);
            }
            $user->update(['avatar' => null]);
        }

        return redirect()->back()->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Cetak Nota Invoice / Kwitansi Pembayaran Resmi untuk Pendaftar.
     */
    public function printInvoice(int|string $id, Request $request)
    {
        $user = $this->getRegistrant();
        if (!$user) {
            return redirect()->route('pendaftar.login');
        }

        $cleanPhone = Setting::formatWaNumber($user->phone);

        $reg = Registration::with('competition')->where(function ($q) use ($id) {
            $q->where('id', $id)
              ->orWhere('reg_code', $id);
        })->firstOrFail();

        // Check ownership or assigned PIC
        $isOwner = ($reg->registrant_id === $user->id || $reg->user_id === $user->id || Setting::formatWaNumber($reg->mentor_wa) === $cleanPhone);
        $isPic = (!empty($reg->assigned_phone) && Setting::formatWaNumber($reg->assigned_phone) === $cleanPhone);

        if (!$isOwner && !$isPic && ($user->role ?? '') !== 'admin') {
            abort(403, 'Anda tidak memiliki hak akses untuk mencetak nota invoice pendaftaran ini.');
        }

        $isPaid = in_array(strtolower($reg->payment_status ?? ''), ['lunas', 'paid', 'verified', 'terverifikasi', 'diterima']);
        $mode = $request->input('mode');
        if (!$mode) {
            $mode = $isPaid ? 'receipt' : 'invoice';
        }

        $totalFee = (int) ($reg->total_fee ?? 0);
        $terbilang = Setting::terbilang($totalFee);
        $bankAccounts = Setting::getBankAccounts();

        $invoiceConfig = Setting::getInvoiceConfig();
        $theme = $request->input('theme', $invoiceConfig['theme'] ?? 'formal');
        if (!in_array($theme, ['formal', 'professional'])) {
            $theme = $invoiceConfig['theme'] ?? 'formal';
        }

        return view('admin.print-invoice', [
            'reg' => $reg,
            'defaultMode' => $mode,
            'defaultTheme' => $theme,
            'isPaid' => $isPaid,
            'terbilang' => $terbilang,
            'bankAccounts' => $bankAccounts,
            'treasurerName' => $invoiceConfig['treasurer_name'],
            'treasurerRole' => $invoiceConfig['treasurer_role'],
            'invoiceConfig' => $invoiceConfig,
        ]);
    }
}
