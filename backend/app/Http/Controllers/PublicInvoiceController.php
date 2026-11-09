<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicInvoiceController extends Controller
{
    /**
     * Tampilkan lembar cetak Nota Invoice / Kwitansi Pembayaran Resmi.
     * Mendukung akses publik melalui Cryptographic Signed URL (Laravel HMAC-SHA256),
     * serta akses langsung bagi admin panitia dan akun pendaftar pemilik data.
     *
     * @param Request $request
     * @param int|string $id ID numerik atau reg_code (misal: SA26-00123)
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, int|string $id)
    {
        $reg = Registration::findByIdOrCodeOrFail($id, ['competition']);

        // 1. Verifikasi Tanda Tangan Kriptografis (Cryptographic Signed URL)
        $hasValidSignature = $request->hasValidSignature();

        // 2. Verifikasi Akses Admin Panitia (auth guard web)
        $isAdmin = Auth::guard('web')->check();

        // 3. Verifikasi Akses Akun Pendaftar Pemilik / PIC yang Ditugaskan
        $isAuthorizedRegistrant = false;
        if (Auth::guard('pendaftar')->check()) {
            $user = Auth::guard('pendaftar')->user();
            if ($user) {
                $cleanPhone = Setting::formatWaNumber($user->phone ?? '');
                $isOwner = (
                    $reg->registrant_id === $user->id ||
                    $reg->user_id === $user->id ||
                    (!empty($user->phone) && Setting::formatWaNumber($reg->mentor_wa) === $cleanPhone)
                );
                $isPic = (
                    !empty($reg->assigned_phone) &&
                    !empty($user->phone) &&
                    Setting::formatWaNumber($reg->assigned_phone) === $cleanPhone
                );

                if ($isOwner || $isPic) {
                    $isAuthorizedRegistrant = true;
                }
            }
        }

        // Jika salah satu izin terpenuhi, sajikan lembar nota invoice
        if ($hasValidSignature || $isAdmin || $isAuthorizedRegistrant) {
            return $this->renderInvoiceView($reg, $request);
        }

        // Jika membawa parameter signature tetapi tidak valid atau telah diubah (tampered)
        if ($request->has('signature')) {
            abort(403, 'Tautan nota/invoice tidak valid atau tanda tangan keamanan telah dimodifikasi.');
        }

        // Jika akun pendaftar sedang login tetapi mencoba membuka pendaftaran milik sekolah lain
        if (Auth::guard('pendaftar')->check()) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat nota invoice pendaftaran ini.');
        }

        // Jika diakses oleh tamu biasa tanpa login dan tanpa signature:
        // Arahkan ke halaman login pendaftar dengan notifikasi panduan yang ramah
        return redirect()->route('pendaftar.login')->with(
            'info',
            'Silakan masuk ke akun pendaftar Anda untuk melihat nota invoice, atau buka tautan resmi yang dikirimkan panitia melalui WhatsApp.'
        );
    }

    /**
     * Render tampilan nota invoice / kwitansi resmi dengan data lengkap.
     *
     * @param Registration $reg
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    protected function renderInvoiceView(Registration $reg, Request $request)
    {
        $isPaid = in_array(strtolower($reg->payment_status ?? ''), ['lunas', 'paid', 'verified', 'terverifikasi', 'diterima']);
        $isAdmin = Auth::guard('web')->check();

        // Penguncian Mode Dokumen:
        // Pendaftar dan akses publik SELALU terkunci secara mutlak sesuai status pembayaran riil di database:
        // - Belum Lunas -> selalu 'invoice' (Nota Tagihan)
        // - Sudah Lunas / Terverifikasi -> selalu 'receipt' (Kwitansi Lunas Resmi)
        // Opsi kustom mode hanya dapat diakses oleh Admin Panitia (guard web).
        if (!$isAdmin) {
            $mode = $isPaid ? 'receipt' : 'invoice';
        } else {
            $mode = $request->input('mode');
            if (!$mode) {
                $mode = $isPaid ? 'receipt' : 'invoice';
            }
        }

        $totalFee = (int) ($reg->total_fee ?? 0);
        $terbilang = Setting::terbilang($totalFee);
        $bankAccounts = Setting::getBankAccounts();

        $invoiceConfig = Setting::getInvoiceConfig();
        $isThemeLocked = ($invoiceConfig['theme_mode'] ?? 'locked') === 'locked';

        if ($isThemeLocked) {
            $theme = $invoiceConfig['theme'] ?? 'formal';
        } else {
            $theme = $request->input('theme', $invoiceConfig['theme'] ?? 'formal');
        }

        if (!in_array($theme, ['formal', 'professional'])) {
            $theme = $invoiceConfig['theme'] ?? 'formal';
        }

        $signedUrl = $reg->getSignedInvoiceUrl();
        $suggestedFilename = $reg->getInvoiceFilename($mode);

        return view('admin.print-invoice', [
            'reg' => $reg,
            'defaultMode' => $mode,
            'defaultTheme' => $theme,
            'isThemeLocked' => $isThemeLocked,
            'isPaid' => $isPaid,
            'isAdmin' => false,
            'terbilang' => $terbilang,
            'bankAccounts' => $bankAccounts,
            'treasurerName' => $invoiceConfig['treasurer_name'],
            'treasurerRole' => $invoiceConfig['treasurer_role'],
            'invoiceConfig' => $invoiceConfig,
            'signedUrl' => $signedUrl,
            'suggestedFilename' => $suggestedFilename,
        ]);
    }
}

