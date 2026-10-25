# Dokumentasi Akun Pengujian (Testing Accounts) - Semarak Aliyah XIV

Dokumen ini adalah referensi resmi untuk semua akun pengujian (Admin, Panitia, dan Pendaftar) yang digunakan selama proses pengembangan dan pengujian otomatis maupun manual.

> **PENTING UNTUK AI AGENT & DEVELOPER:**
> - **DILARANG KERAS** membuat akun dummy baru secara berulang setiap kali pengujian.
> - **WAJIB** menggunakan kredensial yang sudah tercatat di bawah ini.
> - Jika ada skenario khusus yang memerlukan akun baru, **WAJIB** langsung mencatat data akun baru tersebut ke dalam dokumen ini.

---

## 1. Akun Admin & Panitia (Portal Admin: Port 8001)

| No | Nama | NIP / Username | Email Login | Password Awal / SSO | Password Baru | Role | Keterangan |
|---|---|---|---|---|---|---|---|
| 1 | Panitia Semarak XII | `admin` | `admin@maswahas.sch.id` | `admin123` | - | `super_admin` | Akses penuh seluruh modul admin |
| 2 | Ust. Budi Santoso | `budi` | `budi@maswahas.sch.id` | `admin123` | - | `panitia` | Akun staf panitia lokal |
| 3 | Budi Santoso, S.Pd.I. | `198001012005011001` | `198001012005011001@sipresensi.local` | `12345678` (SSO Sipresensi) | `PanitiaBaru2026!` | `panitia` | Akun sinkronisasi Guru Sipresensi |
| 4 | AKHMAD MUHTADI | `1001` | `1001@sipresensi.local` | Sesuai Akun Sipresensi | Wajib Buat Sandi | `panitia` | Akun sinkronisasi Guru Sipresensi |

### Metode Login Panel Panitia (Port 8001):
1. **Login Default**: Username / NIP / Email + Password.
2. **Login SSO Sipresensi**: NIP + Password Akun Sipresensi (verifikasi langsung ke MySQL Sipresensi `D:\1Laravel\sipresensi`).
3. **Login OTP WhatsApp**: Masukkan Nomor WA atau NIP $\rightarrow$ Kirim OTP $\rightarrow$ Verifikasi kode 6-digit.
4. **Login Akun Google**: Tombol 1-klik Google OAuth atau Input Email Google terdaftar (fallback).
5. **Kebijakan Sandi Baru Pertama Kali**: Akun panitia hasil sinkronisasi Sipresensi kata sandinya kosong secara default (`password = null`), dan saat pertama kali login via SSO atau OTP WA akan langsung diarahkan ke halaman `/admin/buat-password` untuk membuat kata sandi mandiri.

---

## 2. Akun Pendaftar / Delegasi Sekolah (Portal Pendaftar: Port 8000)

Seluruh akun pendaftar dummy berikut dapat digunakan untuk login via **Email** atau **Nomor WhatsApp**:

| No | Nama Pendamping | Email Login | Nomor WhatsApp | Password | Asal Lembaga / Sekolah | Status / Keterangan |
|---|---|---|---|---|---|---|
| 1 | Ahmad Fauzi | `ahmad.fauzi@example.com` | `6281277778888` | `pendaftar123` | MTsN 1 Jember | Akun Utama Pengujian Delegasi (Aktif) |
| 2 | Ust. Ahmad Fauzi, S.Pd. | `pendaftar@semarak.id` | `081234567899` | `pendaftar123` | SMP Negeri 1 Balung | Akun Pendaftar Reguler (Aktif) |
| 3 | Ust. Budi Santoso | `budi@santoso.com` | `6281234567891` | `pendaftar123` | SMP Negeri 1 Balung | Akun Pendaftar Sekunder (Aktif) |
| 4 | Ahmad Fauzi, S.Pd. | `ahmad.fauzi@gmail.com` | `6281299997777` | `pendaftar123` | SMPN 1 Jember | Akun Pendaftar Cadangan (Aktif) |

---

## 3. Ketentuan Pemisahan Portal & Tabel Database
- **Tabel Database Panitia**: Tabel `users` murni hanya menyimpan akun Administrator & Panitia Pelaksana.
- **Tabel Database Pendaftar**: Tabel `registrants` dibuat khusus dan terpisah secara fisik untuk seluruh akun pendaftar / delegasi sekolah / peserta lomba.
- **Portal Web Publik & Pendaftar**: `http://127.0.0.1:8000` (Login di `/pendaftar/login` menggunakan guard `pendaftar` terhadap tabel `registrants`).
- **Portal Administrator & Panitia**: `http://127.0.0.1:8001` (Login di `/login` menggunakan guard `web` terhadap tabel `users`).
- **Isolasi Keamanan Penuh**:
  - Akun pendaftar (tabel `registrants`) **dilarang keras dan tidak dapat login** di halaman Admin Panel. Jika mencoba masuk, sistem otomatis menolak autentikasi dan memberikan pesan peringatan.
  - Akun panitia (tabel `users`) tidak dapat masuk ke portal pendaftar.
  - Sistem dilengkapi `PortalSeparationMiddleware`, `EnsureUserIsAdmin`, dan `EnsureUserIsRegistrant` untuk menjamin pemisahan mutlak di level HTTP request dan guard session.
