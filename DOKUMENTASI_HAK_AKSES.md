# Dokumentasi Fitur Manajemen Hak Akses & Peran (RBAC)
**Sistem Operasional SEMARAK ALIYAH XIV 2026**  
*MAS Wahid Hasyim Balung, Jember*

---

## 📌 1. Pendahuluan & Ringkasan Fitur

Fitur **Manajemen Hak Akses & Peran (Role-Based Access Control / RBAC)** adalah sistem kontrol akses terpadu dan dinamis untuk mengatur wewenang seluruh pengguna panitia dan administrator pada Portal Admin.

Sistem ini dirancang untuk:
1. **Membatasi & Mengamankan Wewenang**: Memastikan setiap panitia hanya dapat melihat menu dan mengeksekusi aksi yang sesuai dengan tugasnya (misal: Bendahara hanya fokus pada keuangan, Juri/Wasit hanya fokus pada penilaian).
2. **Fleksibilitas Tanpa Hardcode**: Hak akses peran dapat diubah langsung melalui UI Matriks tanpa perlu memodifikasi kode program atau melakukan deploy ulang.
3. **Dukungan Kustomisasi Individual (*Permission Override*)**: Memungkinkan panitia tertentu memiliki izin tambahan atau pembatasan khusus tanpa harus mengubah peran dasarnya.
4. **Proteksi Sistem Inti**: Peran bawaan (*System Roles*) dan wewenang *Super Admin* diproteksi secara ketat dari penghapusan atau penguncian yang tidak disengaja.

---

## 🗄️ 2. Struktur Database & Model Data

### 2.1. Tabel-Tabel Database
Migrasi database didefinisikan dalam berkas:  
[`database/migrations/2026_10_26_000000_create_roles_and_permissions_tables.php`](file:///d:/wp/semarak/backend/database/migrations/2026_10_26_000000_create_roles_and_permissions_tables.php)

1. **`roles`**:
   - `id` (bigint, PK)
   - `name` (varchar 50, unique) - slug identifier (contoh: `admin`, `bendahara`, `panitia`)
   - `label` (varchar 100) - Nama resmi peran (contoh: *Bendahara & Keuangan*)
   - `description` (text, nullable) - Keterangan tugas peran
   - `badge_color` (varchar 30) - Warna aksen badge (contoh: `primary`, `success`, `warning`, `info`, `danger`, `secondary`, `dark`)
   - `is_system` (boolean, default false) - Menandakan peran bawaan sistem yang tidak boleh dihapus.

2. **`permissions`**:
   - `id` (bigint, PK)
   - `name` (varchar 80, unique) - Format dot-notation (contoh: `lomba.create`, `keuangan.verify`)
   - `label` (varchar 120) - Label ramah pengguna (contoh: *Tambah & Buat Lomba Baru*)
   - `group` (varchar 60) - Pengelompokan kategori fitur (contoh: `Lomba & Kategori`)
   - `description` (text, nullable)

3. **`role_permission`** (Pivot):
   - `role_id` (FK -> `roles.id`, onDelete cascade)
   - `permission_id` (FK -> `permissions.id`, onDelete cascade)
   - Primary Key gabungan `(role_id, permission_id)`

4. **Kolom Baru pada Tabel `users`**:
   - `role_id` (foreignId nullable, indexed -> `roles.id`, onDelete set null)
   - `custom_permissions` (JSON / JSONB, nullable) - Menyimpan pemetaan izin khusus per pengguna dalam bentuk objek key-value boolean:
     ```json
     {
       "sertifikat.generate": true,
       "pengguna.delete": false
     }
     ```

---

### 2.2. Peran Bawaan Sistem (*Default System Roles*)

| Slug Peran | Nama / Label Peran | Badge | Keterangan & Batasan Akses |
| :--- | :--- | :--- | :--- |
| `super_admin` | **Super Administrator** | `danger` | Akses mutlak ke seluruh sistem & konfigurasi (Bypass otomatis). |
| `admin` | **Administrator Utama** | `primary` | Akses penuh operasional, pengguna, pendaftaran, dan sistem. |
| `kesekretariatan`| **Kesekretariatan** | `info` | Kelola peserta, verifikasi formulir, surat menyurat, dan sertifikat. |
| `bendahara` | **Bendahara & Keuangan** | `success` | Verifikasi bukti pembayaran registrasi, invoice, dan laporan keuangan. |
| `panitia` | **Panitia Lapangan** | `secondary` | Presensi gate check-in, visualisasi bagan, dan pendataan juara. |
| `juri` | **Dewan Juri** | `warning` | Akses lembar input nilai dan rekapitulasi penilaian lomba. |
| `wasit` | **Wasit Pertandingan** | `dark` | Input skor arena langsung dan stopwatch pertandingan. |

---

### 2.3. Daftar 25 Hak Akses Riil Sistem (9 Kategori Fungsional)

Tabel berikut mencantumkan seluruh kode permission yang aktif dan dapat dikelola langsung di tab **Matriks Hak Akses Peran**:

| Kategori Fitur | Kode Permission | Label Izin & Cakupan Wewenang |
| :--- | :--- | :--- |
| **1. Dashboard & Ringkasan** | `dashboard.view` | Melihat Dashboard Utama, statistik pendaftar, dan grafik ringkasan. |
| **2. Administrasi Pendaftaran** | `registrants.view` | Melihat Data Pendaftar Masuk, biodata peserta, dan berkas persyaratan. |
| | `registrants.verify` | Verifikasi kelengkapan berkas & validasi status pendaftaran peserta. |
| | `registrants.delete` | Menghapus data pendaftaran peserta dari database. |
| | `registrants.export` | Ekspor data seluruh delegasi ke berkas spreadsheet Excel / CSV. |
| **3. Database Sekolah** | `schools.view` | Melihat master data sekolah dan daftar sekolah yang telah mendaftar. |
| | `schools.manage` | Menambah, menyunting, menghapus, serta impor/ekspor database sekolah. |
| **4. Manajemen Lomba & TM** | `competitions.manage` | Mengelola cabang lomba, kuota pendaftar, biaya dasar, dan PJ lomba. |
| | `tm.manage` | Mengelola Technical Meeting, tautan pengundian nomor urut peserta. |
| | `form_builder.manage` | Mengonfigurasi Formulir Builder dinamis dan kolom khusus per lomba. |
| | `juknis.manage` | Mengunggah & memperbarui berkas PDF buku panduan juknis lomba. |
| **5. Keuangan & Biaya** | `finance.view` | Melihat laporan rekapitulasi keuangan, arus kas, dan bukti pembayaran. |
| | `finance.manage` | Mengatur tarif biaya pendaftaran, paket registrasi, diskon, dan rekening. |
| **6. Kelola Web Publik** | `content.sponsor` | Mengelola mitra & logo sponsorship di landing page. |
| | `content.gallery` | Mengunggah foto dokumentasi kegiatan & galeri slider 2-baris. |
| | `content.faq` | Menambah dan mengubah daftar pertanyaan umum & jawaban (FAQ). |
| | `content.customization` | **Kelola Pengumuman Banner, Pop-up Modal Pengumuman, Teks Berjalan (Ticker), & Countdown.** |
| **7. Manajemen Pengguna & Akses**| `users.view` | Melihat daftar akun panitia, kontak, peran, dan status aktif. |
| | `users.manage` | Menambah akun panitia baru, reset kata sandi, dan sinkron Sipresensi. |
| | `roles.manage` | Mengelola Matriks Hak Akses (RBAC), peran kustom, dan izin khusus panitia. |
| **8. Pengaturan Sistem** | `settings.system` | Mengelola profil madrasah, mode maintenance, SEO, login admin, & reCAPTCHA. |
| | `settings.integrations` | Mengonfigurasi WhatsApp Gateway (OpenWA) dan API SI-Presensi. |
| **9. Operasional Lapangan** | `operational.gate` | Akses scanner barcode / QR code Gate Check-in kehadiran kontingen. |
| | `operational.verification` | Akses meja registrasi ulang fisik dan verifikasi berkas lapangan hari H. |
| | `operational.jury` | Akses arena penilaian digital & input lembar nilai dewan juri. |
| | `operational.tournament` | Akses arena wasit, stopwatch otomatis, dan live score bagan gugur. |
| | `operational.print` | Akses Studio Cetak untuk cetak massal ID Card peserta & piagam sertifikat. |

---

## ⚙️ 3. Arsitektur Backend

### 3.1. Model Eloquent
- **[`App\Models\Role`](file:///d:/wp/semarak/backend/app/Models/Role.php)**:
  - Relasi `permissions()` (`belongsToMany`)
  - Relasi `users()` (`hasMany`)
  - Helper `hasPermission($permissionName)`
  - Helper `syncPermissions(array $permissionIds)`
- **[`App\Models\Permission`](file:///d:/wp/semarak/backend/app/Models/Permission.php)**:
  - Relasi `roles()` (`belongsToMany`)
- **[`App\Models\User`](file:///d:/wp/semarak/backend/app/Models/User.php)**:
  - Relasi `roleRelation()` (`belongsTo(Role::class, 'role_id')`)
  - Casts: `'custom_permissions' => 'array'`
  - Method `allPermissions()`:
    1. Jika `super_admin`, otomatis me-return seluruh nama permission dalam database.
    2. Mengambil seluruh permission dari peran pengguna (`roleRelation->permissions`).
    3. Menerapkan *override* dari `custom_permissions`:
       - Nilai `true` akan ditambahkan ke daftar izin.
       - Nilai `false` akan dicabut/dihapus dari daftar izin.
  - Method `hasPermission($permission)`:
    - Mengecek apakah izin spesifik dimiliki pengguna.

### 3.2. Middleware Proteksi Rute
- **[`App\Http\Middleware\CheckPermission`](file:///d:/wp/semarak/backend/app/Http/Middleware/CheckPermission.php)**:
  - Didaftarkan sebagai alias `'permission'` di [`bootstrap/app.php`](file:///d:/wp/semarak/backend/bootstrap/app.php).
  - Mendukung pengecekan single izin atau multi-izin (OR) dengan pemisah pipa `|`:
    ```php
    Route::middleware(['auth', 'permission:lomba.create|lomba.edit'])->group(function () {
        // ...
    });
    ```
  - Jika pengguna tidak memiliki izin, otomatis mengembalikan response `403 Forbidden` dengan pesan deskriptif.

### 3.3. Sharing State ke Inertia.js
Pada [`App\Http\Middleware\HandleInertiaRequests`](file:///d:/wp/semarak/backend/app/Http/Middleware/HandleInertiaRequests.php), data wewenang pengguna disuntikkan secara global ke properti `auth.user`:
```php
'auth' => [
    'user' => $user ? [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'role_label' => $user->roleRelation?->label ?? ucfirst($user->role ?? 'Pengguna'),
        'role_badge' => $user->roleRelation?->badge_color ?? 'secondary',
        'permissions' => $user->allPermissions(),
    ] : null,
],
```

### 3.4. Rute & Controller
Dikelola oleh [`App\Http\Controllers\Admin\RolePermissionController`](file:///d:/wp/semarak/backend/app/Http/Controllers/Admin/RolePermissionController.php) di bawah prefix `/admin/pengguna/hak-akses`:

| Method | Endpoint URI | Nama Rute | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/pengguna/hak-akses` | `admin.roles.index` | Halaman utama Matriks, Daftar Peran & Hak Khusus |
| `GET` | `/admin/hak-akses` | `admin.roles.index.shortcut` | Pintasan cepat ke halaman hak akses |
| `POST` | `/admin/pengguna/hak-akses/matriks` | `admin.roles.matrix.update` | Simpan seluruh toggle matriks peran & hak akses |
| `POST` | `/admin/pengguna/hak-akses/peran` | `admin.roles.store` | Buat peran kustom panitia baru |
| `PUT` | `/admin/pengguna/hak-akses/peran/{role}` | `admin.roles.update` | Perbarui nama, label, deskripsi, warna peran |
| `DELETE`| `/admin/pengguna/hak-akses/peran/{role}` | `admin.roles.destroy` | Hapus peran kustom (Peran sistem diproteksi) |
| `POST` | `/admin/pengguna/hak-akses/peran/reset-defaults` | `admin.roles.reset-defaults` | Pulihkan pemetaan hak akses standar sistem |
| `POST` | `/admin/pengguna/hak-akses/pengguna/{user}/permissions` | `admin.roles.user-permissions.update` | Simpan hak akses khusus (*override*) individual |

---

## 💻 4. Frontend & Antarmuka Pengguna (UI/UX)

### 4.1. Halaman Manajemen Hak Akses (`Admin/Roles/Index.vue`)
Terletak di [`resources/js/Pages/Admin/Roles/Index.vue`](file:///d:/wp/semarak/backend/resources/js/Pages/Admin/Roles/Index.vue) dengan 3 tab interaktif:

```
[Tab 1: Matriks Hak Akses Peran] ─── [Tab 2: Kelola & Daftar Peran] ─── [Tab 3: Hak Akses Khusus Anggota]
```

#### Tab 1: Matriks Hak Akses Peran
- Mengelompokkan 27 wewenang ke dalam 9 kartu kategori (*Accordion-style grouping*).
- Setiap izin memiliki toggle switch untuk masing-masing peran.
- Super Admin ditandai dengan badge khusus "Akses Penuh".
- Fitur pencarian instan (*realtime search filter*) berdasarkan nama atau kode izin.
- Aksi massal per kolom peran: tombol **Pilih Semua** dan **Kosongkan**.
- Deteksi otomatis perubahan dengan tombol melayang *Simpan Perubahan Matriks*.

#### Tab 2: Kelola & Daftar Peran
- Grid kartu peran dengan informasi jumlah anggota, izin aktif, dan badge warna.
- Tombol **Tambah Peran Baru** yang membuka modal pembuatan peran.
- Pemilihan warna badge menggunakan komponen kustom [`<CustomSelect>`](file:///d:/wp/semarak/backend/resources/js/Components/CustomSelect.vue) yang mematuhi aturan strict UI/UX (tanpa select native).
- Proteksi penghapusan untuk peran bertanda `is_system = true`.

#### Tab 3: Hak Akses Khusus Anggota Panitia
- Menampilkan daftar akun panitia, email, peran utama, dan jumlah izin kustom aktif.
- Modal **Atur Hak Akses Khusus** dengan kontrol 3-state per izin:
  - ⚪ **Ikuti Peran**: Mengikuti default dari peran yang disandang.
  - 🟢 **Izinkan Khusus**: Memberikan izin secara paksa.
  - 🔴 **Larang Khusus**: Mencabut izin secara spesifik meskipun peran aslinya memiliki izin tersebut.

---

### 4.2. Composable Frontend (`usePermission.js`)
Terletak di [`resources/js/Composables/usePermission.js`](file:///d:/wp/semarak/backend/resources/js/Composables/usePermission.js):
```javascript
import { usePermission } from '@/Composables/usePermission';

const { can, canAny, hasRole, isSuperAdmin } = usePermission();

// Contoh penggunaan:
if (can('lomba.create')) {
    // Tombol tambah lomba diaktifkan
}
```

### 4.3. Dynamic Sidebar Navigation (`VelzonLayout.vue`)
Seluruh item menu pada sidebar Portal Admin ([`resources/js/Layouts/VelzonLayout.vue`](file:///d:/wp/semarak/backend/resources/js/Layouts/VelzonLayout.vue)) telah dilindungi secara kondisional menggunakan `v-if="can('...')"`:
- Menu **Dashboard** -> `can('dashboard.view')`
- Menu **Pengguna & Panitia** -> `can('users.view')`
- Menu **Hak Akses & Peran** -> `can('roles.manage')`
- Menu **Data Pendaftar Masuk** -> `can('registrants.view')`
- Menu **Database Sekolah** -> `can('schools.view')`
- Menu **Manajemen Lomba** -> `can('competitions.manage')`
- Menu **Biaya & Keuangan** -> `can('finance.view')`
- Menu **Kelola Web Publik & Pengumuman** -> `can('content.customization') || can('content.sponsor') || can('content.gallery') || can('content.faq')`
- Menu **Pengaturan Sistem** -> `can('settings.system')`

---

## 🛡️ 5. Standar & Kepatuhan UI / UX Proyek

Fitur ini dirancang mematuhi **5 Aturan Ketat UI/UX Proyek Semarak Aliyah XIV**:
1. **Dropdown Bebas Elemen Native**: Seluruh dropdown (pemilihan warna peran, filter peran) menggunakan komponen `<CustomSelect>` dengan visual checkmark, chevron animasi, dan keyboard navigation.
2. **Kontras Teks Berstandar WCAG AA**:
   - Mode Terang: Warna teks pekat (`#0f172a`, `#1e293b`).
   - Mode Gelap: Latar `#1a1d21` dengan teks terang berkontras tajam (`#f8fafc`, `#e2e8f0`).
3. **Kontras Badge**: Badge peran dan izin menggunakan styling adaptif agar teks terbaca jelas di mode terang maupun mode gelap.
4. **Format Waktu & Tanggal**: Seluruh tampilan tanggal menggunakan format `dd/mm/yyyy` dan format jam 24 jam (`HH:mm`).
5. **Full Lokal Offline (Offline-First)**: Seluruh font, stylesheet, dan icon (RemixIcon) tersaji secara lokal tanpa memanggil CDN eksternal.

---

## 📖 6. Panduan Penggunaan Bagi Pengembang

### 6.1. Menambahkan Hak Akses Baru ke Sistem
Untuk menambahkan kode hak akses baru di masa mendatang:
1. Buat migrasi baru atau tambahkan record ke tabel `permissions`:
   ```php
   use App\Models\Permission;

   Permission::create([
       'name' => 'laporan.export_pdf',
       'label' => 'Ekspor Laporan dalam Format PDF',
       'group' => 'Laporan & Statistik',
       'description' => 'Mengunduh laporan rekapitulasi PDF resmi.',
   ]);
   ```
2. Izin baru akan otomatis muncul di Matriks UI Hak Akses pada Portal Admin tanpa perlu mengubah kode Vue.

### 6.2. Memasang Proteksi pada Controller Baru
Pasang middleware pada rute di [`routes/web.php`](file:///d:/wp/semarak/backend/routes/web.php):
```php
Route::get('/admin/laporan/pdf', [LaporanController::class, 'exportPdf'])
    ->middleware(['auth', 'permission:laporan.export_pdf']);
```
Atau di dalam method Controller:
```php
public function destroy(Lomba $lomba)
{
    if (!auth()->user()->canAccess('lomba.delete')) {
        abort(403, 'Anda tidak memiliki hak akses untuk menghapus cabang lomba.');
    }
    // ...
}
```

### 6.3. Menyembunyikan Elemen Tombol di Template Vue
Gunakan composable `usePermission`:
```html
<script setup>
import { usePermission } from '@/Composables/usePermission';
const { can } = usePermission();
</script>

<template>
  <button v-if="can('lomba.create')" class="btn btn-primary" @click="openCreateModal">
    <i class="ri-add-line me-1"></i> Tambah Lomba
  </button>
</template>
```

---

## 🧪 7. Kredensial Pengujian

Pengujian dapat dilakukan dengan akun resmi yang tercatat di [`DOKUMENTASI_AKUN_TESTING.md`](file:///d:/wp/semarak/DOKUMENTASI_AKUN_TESTING.md):
- **URL Akses**: [http://127.0.0.1:8001/admin/pengguna/hak-akses](http://127.0.0.1:8001/admin/pengguna/hak-akses)
- **Akun Super Admin**:
  - Email: `admin@maswahas.sch.id`
  - Password: `admin123`

---

## 🚀 8. Rencana Pengembangan Mendatang (Future Roadmap / Backlog)

Berdasarkan tinjauan kebutuhan operasional, berikut modul yang dicatat sebagai rencana pengembangan tahap berikutnya:

### 8.1. Modul Berita & Warta Acara Terpadu (*News & Article Engine*)
- **Status**: ⏳ *Tersimpan dalam Rencana Kedepan (Pending Roadmap)*
- **Latar Belakang**: Memfasilitasi tim Humas & Publikasi untuk merilis liputan lomba, pengumuman artikel panjang, dokumentasi wawancara pemenang, dan warta harian Semarak Aliyah tanpa bergantung pada banner ribbon / pop-up singkat.
- **Rancangan Fitur**:
  1. **Backend & Skema**: Tabel `articles` (slug, title, excerpt, body_html, featured_image, author_id, published_at, is_published, views_count).
  2. **Hak Akses RBAC**:
     - `news.view` (Melihat daftar artikel dan draf berita)
     - `news.create` (Menulis dan mengunggah artikel baru)
     - `news.edit` (Menyunting isi berita dan memperbarui foto sampul)
     - `news.delete` (Menghapus artikel yang ditarik dari peredaran)
  3. **Antarmuka Admin**: Editor teks kaya (Rich Text / Markdown editor lokal) dengan pratinjau langsung, manajemen tag/kategori, dan upload gambar lokal.
  4. **Antarmuka Publik**: Halaman indeks berita `/berita` dan halaman baca `/berita/{slug}` terintegrasi dengan SEO OpenGraph.

