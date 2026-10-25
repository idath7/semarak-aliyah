# Standar & Aturan Ketat UI/UX Aplikasi Semarak Aliyah

Dokumen ini memuat aturan wajib (*strictly enforced guidelines*) yang harus dipatuhi oleh seluruh developer dan agen AI setiap kali melakukan penambahan, perubahan, atau penyesuaian UI/UX pada seluruh halaman aplikasi ini (Admin Panel, Pusat Komando Operasional, serta Web Publik).

---

## 1. Larangan Penggunaan Dropdown Bawaan Browser
- **DILARANG KERAS** menggunakan tag `<select>` native bawaan browser di seluruh modul dan komponen aplikasi.
- **WAJIB** menggunakan komponen custom dropdown terdaftar:
  - `<CustomSelect v-model="..." :options="..." />` atau `<CustomSelect v-model="..."><option ...>...</option></CustomSelect>`.
- **Fitur Wajib pada Setiap Dropdown**:
  1. **Hover State**: Opsi berubah warna secara halus saat kursor melayang (`#f1f5f9` di mode terang / `#1e293b` di mode gelap).
  2. **Active Focus Background**: Opsi yang sedang dipilih memiliki latar belakang fokus kontras (`#eef2ff` / `rgba(79, 70, 229, 0.28)`) dilengkapi garis aksen penanda di sisi kiri (*border-left accent* `#4f46e5` / `#818cf8`).
  3. **Tanda Centang**: Menampilkan ikon centang aktif (`ri-check-line`) di sisi kanan opsi yang sedang terpilih.
  4. **Rotasi Chevron**: Ikon panah berputar 180° mulus saat menu terbuka.
  5. **Auto Search**: Menyediakan input filter pencarian otomatis jika jumlah opsi melebihi 7 item.
  6. **Dual Theme**: Mendukung kontras penuh baik pada tema terang maupun tema gelap (*Dark Theme*).

---

## 2. Kontras Visibilitas Teks (Mode Terang & Mode Gelap)
- Setiap teks antarmuka harus lulus standar rasio kontras visual (minimal WCAG AA, rasio 4.5:1 untuk teks biasa dan 3:1 untuk teks besar/bold).
- **Mode Terang (*Light Mode*)**:
  - Warna latar belakang: putih (`#ffffff`), abu-abu sangat muda (`#f8fafc`, `#f3f3f9`).
  - Warna teks: Wajib menggunakan warna gelap tegas (`#0f172a`, `#1e293b`, `#334155`, `#495057`).
  - Dilarang menggunakan teks abu-abu yang terlalu pucat/redup (*faint gray*).
- **Mode Gelap (*Dark Mode*)**:
  - Warna latar belakang: slate gelap / navy (`#0b0f19`, `#111827`, `#162032`, `#1e293b`).
  - Warna teks: Wajib menggunakan warna terang berdaya baca tinggi (`#ffffff`, `#f8fafc`, `#f1f5f9`, `#cbd5e1`).
  - **Dilarang keras** membiarkan teks berwarna gelap (`#212529`, `text-dark`, `#495057`) berada di atas container/kartu mode gelap.

---

## 3. Kontras Visibilitas Teks di Dalam Badge (Mode Terang & Mode Gelap)
- **Badge Subtle (`bg-*-subtle`)**:
  - *Light Mode*: Background lembut dengan warna teks pekat/jenuh (`.text-primary`, `.text-success`, `.text-warning`, `.text-danger`, `.text-info`).
  - *Dark Mode*: Background semi-transparan (`rgba(..., 0.25 - 0.35)`) dengan warna teks terang berpendar (*high luminescence* seperti `#93c5fd` untuk primary, `#86efac` untuk success, `#fde047` untuk warning, `#fca5a5` untuk danger, `#7dd3fc` untuk info). Teks tidak boleh pudar atau tenggelam di latar gelap.
- **Badge Solid (`bg-primary`, `bg-success`, `bg-danger`, dll)**:
  - Teks wajib memiliki kontras tajam penuh (`#ffffff` untuk background gelap/sedang, atau `#000000` jika background terang).
- **Badge Status di Tabel & Card**:
  - Selalu lengkapi dengan ikon penjelas yang selaras dan padding proporsional (`px-2 py-1` atau `px-2.5 py-0.5`).

---

## 4. Format Tanggal & Jam Baku
- **Format Tanggal Tampilan**:
  - **Selalu `dd/mm/yyyy`** (contoh: `25/10/2026`).
  - Dilarang menampilkan format Amerika (`mm/dd/yyyy`) atau format ISO mentah (`yyyy-mm-dd`) ke antarmuka pengguna tanpa pemformatan.
- **Komponen Datepicker**:
  - **Wajib menggunakan Flatpickr** yang telah dilokalisasi ke Bahasa Indonesia (komponen `<IndoDatePicker>` atau `<IndoDateTimePicker>`).
- **Format Jam**:
  - **Selalu format 24 Jam** (`HH:mm`, contoh: `08:00`, `13:30`, `23:59`).
  - Dilarang menggunakan format 12 jam dengan embel-embel AM/PM.
- **Konversi Data**:
  - Tampilan UI: `dd/mm/yyyy` dan `HH:mm WIB`.
  - Penyimpanan Database: Konversi standar backend `YYYY-MM-DD` atau `YYYY-MM-DD HH:mm:ss`.

---

## 5. Kebijakan Aset Full Lokal Offline (Offline-First)
- **Seluruh aset antarmuka wajib tersimpan secara lokal**:
  - File CSS, JS, library vendor (Bootstrap, Tailwind, Simplebar, Flatpickr, dll).
  - Font tipografi (Poppins, HK Grotesk, Inter) wajib tersedia secara lokal di `resources/fonts/` dan `public/fonts/`.
  - Pustaka ikon (Remixicon, Boxicons) wajib dilayani dari bundle lokal (`resources/` / `public/build/`).
  - Gambar, logo, thumbnail, dan panduan PDF wajib disajikan dari storage/public lokal (`/assets/`).
- **Aturan Pengecualian Aset Online / CDN**:
  - **Dilarang menambahkan CDN online baru** (seperti `cdn.jsdelivr.net`, `cdnjs.cloudflare.com`, `unpkg.com`) tanpa konfirmasi.
  - Jika terdapat kebutuhan khusus yang mutlak memerlukan aset online atau integrasi eksternal (misal: API eksternal atau resource pihak ketiga), **wajib meminta izin atau konfirmasi terlebih dahulu kepada pengguna** sebelum menambahkan tautan tersebut.

---

## 6. Prosedur Wajib Setelah Perubahan UI
Setiap kali melakukan penyesuaian styling, template, atau komponen Vue:
1. Jalankan `npm run build` di direktori `backend/` untuk memverifikasi tidak ada error build pada Vite.
2. Periksa tampilan di browser pada kedua tema (Terang dan Gelap) untuk memastikan kontras teks, badge, dan komponen dropdown tampil sempurna.
