<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Comprehensive dataset of SMP & MTs across Kabupaten Jember.
     */
    public function run(): void
    {
        $schools = self::getSchoolList();

        foreach ($schools as $item) {
            School::updateOrCreate(
                [
                    'name' => $item['name'],
                    'kecamatan' => $item['kecamatan']
                ],
                [
                    'npsn' => $item['npsn'] ?? null,
                    'jenjang' => $item['jenjang'],
                    'status' => $item['status'],
                    'kabupaten' => 'Kabupaten Jember',
                    'alamat' => $item['alamat'] ?? ('Kecamatan ' . $item['kecamatan'] . ', Kabupaten Jember'),
                    'phone' => $item['phone'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Return list of SMP and MTs in Kabupaten Jember across 31 Kecamatan.
     */
    public static function getSchoolList(): array
    {
        return [
            // ==========================================
            // 1. BALUNG (Tuan Rumah Semarak Aliyah)
            // ==========================================
            ['name' => 'MTs Unggulan Wahid Hasyim', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Balung', 'npsn' => '20581001', 'alamat' => 'Jl. Yos Sudarso No. 1, Balung Lor'],
            ['name' => 'MTs SA Wahid Hasyim Balung', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Balung', 'npsn' => '20581002', 'alamat' => 'Jl. Wahid Hasyim, Balung'],
            ['name' => 'SMPN 1 Balung', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Balung', 'npsn' => '20523821', 'alamat' => 'Jl. Puger No. 24, Balung'],
            ['name' => 'SMPN 2 Balung', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Balung', 'npsn' => '20523822', 'alamat' => 'Jl. Rambipuji No. 17, Balung Kulon'],
            ['name' => 'MTs Al-Ma\'arif Balung', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Balung', 'npsn' => '20581003', 'alamat' => 'Balung Kidul, Balung'],
            ['name' => 'MTs Zainul Hasan Balung', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Balung', 'npsn' => '20581004', 'alamat' => 'Balung Lor, Balung'],
            ['name' => 'SMP PGRI 1 Balung', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Balung', 'npsn' => '20523789', 'alamat' => 'Jl. Ambulu, Balung'],
            ['name' => 'SMP Islam Balung', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Balung', 'npsn' => '20523790', 'alamat' => 'Curahlele, Balung'],

            // ==========================================
            // 2. WULUHAN
            // ==========================================
            ['name' => 'SMPN 1 Wuluhan', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Wuluhan', 'npsn' => '20523823', 'alamat' => 'Jl. Puger No. 9, Dukuhdempok'],
            ['name' => 'SMPN 2 Wuluhan', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Wuluhan', 'npsn' => '20523824', 'alamat' => 'Jl. Ki Hajar Dewantara, Kesilir'],
            ['name' => 'MTsN 6 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Wuluhan', 'npsn' => '20581106', 'alamat' => 'Jl. Manggar No. 45, Wuluhan'],
            ['name' => 'MTs Miftahul Huda Wuluhan', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Wuluhan', 'npsn' => '20581011', 'alamat' => 'Lojejer, Wuluhan'],
            ['name' => 'MTs Al-Ihsan Wuluhan', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Wuluhan', 'npsn' => '20581012', 'alamat' => 'Dukuhdempok, Wuluhan'],
            ['name' => 'SMP Muhammadiyah Wuluhan', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Wuluhan', 'npsn' => '20523785', 'alamat' => 'Jl. Suyitman, Glundengan'],

            // ==========================================
            // 3. PUGER
            // ==========================================
            ['name' => 'SMPN 1 Puger', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Puger', 'npsn' => '20523825', 'alamat' => 'Jl. Raya Puger No. 101, Puger Kulon'],
            ['name' => 'SMPN 2 Puger', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Puger', 'npsn' => '20523826', 'alamat' => 'Jl. Kasiyan No. 15, Kasiyan Timur'],
            ['name' => 'SMPN 3 Puger', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Puger', 'npsn' => '20523827', 'alamat' => 'Bagon, Puger'],
            ['name' => 'MTsN 7 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Puger', 'npsn' => '20581107', 'alamat' => 'Jl. Raya Kasiyan No. 88, Puger'],
            ['name' => 'MTs Nurul Huda Puger', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Puger', 'npsn' => '20581015', 'alamat' => 'Mlokorejo, Puger'],
            ['name' => 'MTs Baiturrohmah Puger', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Puger', 'npsn' => '20581016', 'alamat' => 'Grenden, Puger'],
            ['name' => 'SMP PGRI Puger', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Puger', 'npsn' => '20523786', 'alamat' => 'Puger Kulon, Puger'],

            // ==========================================
            // 4. AMBULU
            // ==========================================
            ['name' => 'SMPN 1 Ambulu', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ambulu', 'npsn' => '20523828', 'alamat' => 'Jl. Manggar No. 12, Ambulu'],
            ['name' => 'SMPN 2 Ambulu', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ambulu', 'npsn' => '20523829', 'alamat' => 'Jl. Suyitman No. 2, Sabrang'],
            ['name' => 'SMPN 3 Ambulu', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ambulu', 'npsn' => '20523830', 'alamat' => 'Jl. Sumberan No. 5, Karanganyar'],
            ['name' => 'MTsN 5 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Ambulu', 'npsn' => '20581105', 'alamat' => 'Jl. Hasanuddin No. 34, Ambulu'],
            ['name' => 'SMP Islam Ambulu', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Ambulu', 'npsn' => '20523781', 'alamat' => 'Jl. Pendidikan No. 7, Ambulu'],
            ['name' => 'MTs Nurul Islam Ambulu', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Ambulu', 'npsn' => '20581020', 'alamat' => 'Pontang, Ambulu'],
            ['name' => 'SMP Muhammadiyah Ambulu', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Ambulu', 'npsn' => '20523782', 'alamat' => 'Tegalsari, Ambulu'],

            // ==========================================
            // 5. RAMBIPUJI
            // ==========================================
            ['name' => 'SMPN 1 Rambipuji', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Rambipuji', 'npsn' => '20523831', 'alamat' => 'Jl. Gajah Mada No. 80, Rambipuji'],
            ['name' => 'SMPN 2 Rambipuji', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Rambipuji', 'npsn' => '20523832', 'alamat' => 'Nogosari, Rambipuji'],
            ['name' => 'MTsN 9 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Rambipuji', 'npsn' => '20581109', 'alamat' => 'Jl. Dharmawangsa No. 21, Rambipuji'],
            ['name' => 'MTs Fathul Ulum Rambipuji', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Rambipuji', 'npsn' => '20581025', 'alamat' => 'Rambigundam, Rambipuji'],
            ['name' => 'SMP PGRI Rambipuji', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Rambipuji', 'npsn' => '20523780', 'alamat' => 'Jl. PB Sudirman, Rambipuji'],

            // ==========================================
            // 6. KENCONG
            // ==========================================
            ['name' => 'SMPN 1 Kencong', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kencong', 'npsn' => '20523833', 'alamat' => 'Jl. Diponegoro No. 14, Kencong'],
            ['name' => 'SMPN 2 Kencong', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kencong', 'npsn' => '20523834', 'alamat' => 'Jl. Krakatau No. 3, Kencong'],
            ['name' => 'MTs Assunniyyah Kencong', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Kencong', 'npsn' => '20581030', 'alamat' => 'Jl. Pondok Pesantren Assunniyyah, Kencong'],
            ['name' => 'MTs Diponegoro Kencong', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Kencong', 'npsn' => '20581031', 'alamat' => 'Cakru, Kencong'],
            ['name' => 'SMP PGRI 1 Kencong', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Kencong', 'npsn' => '20523775', 'alamat' => 'Jl. Pemuda No. 10, Kencong'],

            // ==========================================
            // 7. GUMUKMAS
            // ==========================================
            ['name' => 'SMPN 1 Gumukmas', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Gumukmas', 'npsn' => '20523835', 'alamat' => 'Jl. Ahmad Yani No. 55, Gumukmas'],
            ['name' => 'SMPN 2 Gumukmas', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Gumukmas', 'npsn' => '20523836', 'alamat' => 'Menampu, Gumukmas'],
            ['name' => 'MTs Al-Falah Gumukmas', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Gumukmas', 'npsn' => '20581035', 'alamat' => 'Karangrejo, Gumukmas'],
            ['name' => 'MTs Nurul Huda Menampu', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Gumukmas', 'npsn' => '20581036', 'alamat' => 'Menampu, Gumukmas'],

            // ==========================================
            // 8. UMBULSARI
            // ==========================================
            ['name' => 'SMPN 1 Umbulsari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Umbulsari', 'npsn' => '20523837', 'alamat' => 'Jl. A. Yani No. 32, Umbulsari'],
            ['name' => 'SMPN 2 Umbulsari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Umbulsari', 'npsn' => '20523838', 'alamat' => 'Paleran, Umbulsari'],
            ['name' => 'MTs Al-Ittihad Umbulsari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Umbulsari', 'npsn' => '20581040', 'alamat' => 'Jl. Semboro No. 4, Umbulsari'],
            ['name' => 'MTs Paleran Umbulsari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Umbulsari', 'npsn' => '20581041', 'alamat' => 'Paleran, Umbulsari'],

            // ==========================================
            // 9. JOMBANG
            // ==========================================
            ['name' => 'SMPN 1 Jombang', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Jombang', 'npsn' => '20523839', 'alamat' => 'Jl. Kencong No. 18, Jombang'],
            ['name' => 'SMPN 2 Jombang', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Jombang', 'npsn' => '20523840', 'alamat' => 'Padomasan, Jombang'],
            ['name' => 'MTs Bustanul Ulum Jombang', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Jombang', 'npsn' => '20581045', 'alamat' => 'Wringagung, Jombang'],
            ['name' => 'MTs Miftahul Falah Jombang', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Jombang', 'npsn' => '20581046', 'alamat' => 'Sarimulyo, Jombang'],

            // ==========================================
            // 10. SEMBORO
            // ==========================================
            ['name' => 'SMPN 1 Semboro', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Semboro', 'npsn' => '20523841', 'alamat' => 'Jl. PB Sudirman No. 40, Semboro'],
            ['name' => 'SMPN 2 Semboro', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Semboro', 'npsn' => '20523842', 'alamat' => 'Sidomekar, Semboro'],
            ['name' => 'MTs Al-Fatah Semboro', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Semboro', 'npsn' => '20581050', 'alamat' => 'Semboro Lor, Semboro'],

            // ==========================================
            // 11. TANGGUL
            // ==========================================
            ['name' => 'SMPN 1 Tanggul', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Tanggul', 'npsn' => '20523843', 'alamat' => 'Jl. PB Sudirman No. 102, Tanggul Kulon'],
            ['name' => 'SMPN 2 Tanggul', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Tanggul', 'npsn' => '20523844', 'alamat' => 'Jl. Salak No. 12, Tanggul Wetan'],
            ['name' => 'SMPN 3 Tanggul', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Tanggul', 'npsn' => '20523845', 'alamat' => 'Klatakan, Tanggul'],
            ['name' => 'MTsN 3 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Tanggul', 'npsn' => '20581103', 'alamat' => 'Jl. Merak No. 20, Tanggul'],
            ['name' => 'MTs Al-Hidayah Tanggul', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Tanggul', 'npsn' => '20581055', 'alamat' => 'Tanggul Kulon, Tanggul'],
            ['name' => 'SMP Islam Tanggul', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Tanggul', 'npsn' => '20523760', 'alamat' => 'Jl. Hayam Wuruk, Tanggul'],

            // ==========================================
            // 12. BANGSALSARI
            // ==========================================
            ['name' => 'SMPN 1 Bangsalsari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Bangsalsari', 'npsn' => '20523846', 'alamat' => 'Jl. Raya Bangsalsari No. 67'],
            ['name' => 'SMPN 2 Bangsalsari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Bangsalsari', 'npsn' => '20523847', 'alamat' => 'Tisnogambar, Bangsalsari'],
            ['name' => 'SMPN 3 Bangsalsari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Bangsalsari', 'npsn' => '20523848', 'alamat' => 'Badean, Bangsalsari'],
            ['name' => 'MTs Miftahul Ulum Bangsalsari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Bangsalsari', 'npsn' => '20581060', 'alamat' => 'Bangsalsari'],
            ['name' => 'MTs Baitul Makmur Bangsalsari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Bangsalsari', 'npsn' => '20581061', 'alamat' => 'Petung, Bangsalsari'],

            // ==========================================
            // 13. SUMBERBARU
            // ==========================================
            ['name' => 'SMPN 1 Sumberbaru', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumberbaru', 'npsn' => '20523849', 'alamat' => 'Jl. PB Sudirman No. 25, Yosorati'],
            ['name' => 'SMPN 2 Sumberbaru', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumberbaru', 'npsn' => '20523850', 'alamat' => 'Pringgowirawan, Sumberbaru'],
            ['name' => 'MTs Nurul Qarnain Sumberbaru', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sumberbaru', 'npsn' => '20581065', 'alamat' => 'Baletbaru, Sumberbaru'],
            ['name' => 'MTs Al-Khoiriyah Sumberbaru', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sumberbaru', 'npsn' => '20581066', 'alamat' => 'Karangbayat, Sumberbaru'],

            // ==========================================
            // 14. PANTI
            // ==========================================
            ['name' => 'SMPN 1 Panti', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Panti', 'npsn' => '20523851', 'alamat' => 'Jl. PB Sudirman No. 15, Panti'],
            ['name' => 'SMPN 2 Panti', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Panti', 'npsn' => '20523852', 'alamat' => 'Suci, Panti'],
            ['name' => 'MTs Al-Hasan Panti', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Panti', 'npsn' => '20581070', 'alamat' => 'Kemuningsari Lor, Panti'],

            // ==========================================
            // 15. SUKORAMBI
            // ==========================================
            ['name' => 'SMPN 1 Sukorambi', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sukorambi', 'npsn' => '20523853', 'alamat' => 'Jl. Mujahir No. 5, Sukorambi'],
            ['name' => 'MTs Al-Azhar Sukorambi', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sukorambi', 'npsn' => '20581075', 'alamat' => 'Jubung, Sukorambi'],

            // ==========================================
            // 16. JENGGAWAH
            // ==========================================
            ['name' => 'SMPN 1 Jenggawah', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Jenggawah', 'npsn' => '20523854', 'alamat' => 'Jl. Tempurejo No. 8, Wonojati'],
            ['name' => 'SMPN 2 Jenggawah', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Jenggawah', 'npsn' => '20523855', 'alamat' => 'Kertonegoro, Jenggawah'],
            ['name' => 'MTsN 8 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Jenggawah', 'npsn' => '20581108', 'alamat' => 'Jl. Cendrawasih No. 12, Jenggawah'],
            ['name' => 'MTs Al-Ishlah Jenggawah', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Jenggawah', 'npsn' => '20581080', 'alamat' => 'Wonojati, Jenggawah'],
            ['name' => 'SMP Islam Jenggawah', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Jenggawah', 'npsn' => '20523755', 'alamat' => 'Jenggawah'],

            // ==========================================
            // 17. AJUNG
            // ==========================================
            ['name' => 'SMPN 1 Ajung', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ajung', 'npsn' => '20523856', 'alamat' => 'Jl. Otista No. 100, Mangaran'],
            ['name' => 'SMPN 2 Ajung', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ajung', 'npsn' => '20523857', 'alamat' => 'Klompangan, Ajung'],
            ['name' => 'MTs Nurul Jadid Ajung', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Ajung', 'npsn' => '20581085', 'alamat' => 'Pancakarya, Ajung'],
            ['name' => 'SMP IT Al-Ghozali', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Ajung', 'npsn' => '20523750', 'alamat' => 'Jl. Stadion Jember Sport Garden, Ajung'],

            // ==========================================
            // 18. TEMPUREJO
            // ==========================================
            ['name' => 'SMPN 1 Tempurejo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Tempurejo', 'npsn' => '20523858', 'alamat' => 'Jl. KH Wahid Hasyim No. 4, Tempurejo'],
            ['name' => 'SMPN 2 Tempurejo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Tempurejo', 'npsn' => '20523859', 'alamat' => 'Curahnongko, Tempurejo'],
            ['name' => 'MTs Baitul Hikmah', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Tempurejo', 'npsn' => '20581090', 'alamat' => 'Kranjingan, Tempurejo'],
            ['name' => 'MTs Mambaul Ulum Tempurejo', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Tempurejo', 'npsn' => '20581091', 'alamat' => 'Sanenrejo, Tempurejo'],

            // ==========================================
            // 19. MUMBULSARI
            // ==========================================
            ['name' => 'SMPN 1 Mumbulsari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Mumbulsari', 'npsn' => '20523860', 'alamat' => 'Jl. Karanganyar No. 2, Mumbulsari'],
            ['name' => 'MTsN 11 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Mumbulsari', 'npsn' => '20581111', 'alamat' => 'Jl. Raya Suco, Mumbulsari'],
            ['name' => 'MTs Raudlatul Ulum Mumbulsari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Mumbulsari', 'npsn' => '20581095', 'alamat' => 'Lengkong, Mumbulsari'],

            // ==========================================
            // 20. MAYANG
            // ==========================================
            ['name' => 'SMPN 1 Mayang', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Mayang', 'npsn' => '20523861', 'alamat' => 'Jl. Raung No. 78, Mayang'],
            ['name' => 'MTsN 4 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Mayang', 'npsn' => '20581104', 'alamat' => 'Jl. Kalisat No. 12, Mayang'],
            ['name' => 'MTs Al-Amien Mayang', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Mayang', 'npsn' => '20581100', 'alamat' => 'Tegalwaru, Mayang'],

            // ==========================================
            // 21. SILO
            // ==========================================
            ['name' => 'SMPN 1 Silo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Silo', 'npsn' => '20523862', 'alamat' => 'Jl. Sempolan No. 5, Sempolan'],
            ['name' => 'SMPN 2 Silo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Silo', 'npsn' => '20523863', 'alamat' => 'Mulyorejo, Silo'],
            ['name' => 'SMPN 3 Silo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Silo', 'npsn' => '20523864', 'alamat' => 'Garahan, Silo'],
            ['name' => 'MTs Al-Falah Silo', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Silo', 'npsn' => '20581120', 'alamat' => 'Sempolan, Silo'],
            ['name' => 'MTs Nurul Hidayah Silo', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Silo', 'npsn' => '20581121', 'alamat' => 'Pace, Silo'],

            // ==========================================
            // 22. KALISAT
            // ==========================================
            ['name' => 'SMPN 1 Kalisat', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kalisat', 'npsn' => '20523865', 'alamat' => 'Jl. Diponegoro No. 89, Kalisat'],
            ['name' => 'SMPN 2 Kalisat', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kalisat', 'npsn' => '20523866', 'alamat' => 'Glagahwero, Kalisat'],
            ['name' => 'SMPN 3 Kalisat', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kalisat', 'npsn' => '20523867', 'alamat' => 'Ajung, Kalisat'],
            ['name' => 'MTsN 2 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Kalisat', 'npsn' => '20581102', 'alamat' => 'Jl. Cokroaminoto No. 34, Kalisat'],
            ['name' => 'SMP Katolik Kalisat', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Kalisat', 'npsn' => '20523740', 'alamat' => 'Kalisat'],

            // ==========================================
            // 23. LEDOKOMBO
            // ==========================================
            ['name' => 'SMPN 1 Ledokombo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ledokombo', 'npsn' => '20523868', 'alamat' => 'Jl. Sumberlesung No. 1, Ledokombo'],
            ['name' => 'SMPN 2 Ledokombo', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Ledokombo', 'npsn' => '20523869', 'alamat' => 'Slateng, Ledokombo'],
            ['name' => 'MTs Al-Imam Ledokombo', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Ledokombo', 'npsn' => '20581125', 'alamat' => 'Sumberlesung, Ledokombo'],

            // ==========================================
            // 24. SUKOWONO
            // ==========================================
            ['name' => 'SMPN 1 Sukowono', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sukowono', 'npsn' => '20523870', 'alamat' => 'Jl. PB Sudirman No. 22, Sukowono'],
            ['name' => 'SMPN 2 Sukowono', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sukowono', 'npsn' => '20523871', 'alamat' => 'Sukorejo, Sukowono'],
            ['name' => 'MTs Al-Hikmah Sukowono', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sukowono', 'npsn' => '20581130', 'alamat' => 'Sukowono'],

            // ==========================================
            // 25. SUMBERJAMBE
            // ==========================================
            ['name' => 'SMPN 1 Sumberjambe', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumberjambe', 'npsn' => '20523872', 'alamat' => 'Jl. Cempaka No. 8, Sumberjambe'],
            ['name' => 'SMPN 2 Sumberjambe', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumberjambe', 'npsn' => '20523873', 'alamat' => 'Cumedak, Sumberjambe'],
            ['name' => 'MTs Bustanul Ulum Sumberjambe', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sumberjambe', 'npsn' => '20581135', 'alamat' => 'Sumberjambe'],

            // ==========================================
            // 26. ARJASA
            // ==========================================
            ['name' => 'SMPN 1 Arjasa', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Arjasa', 'npsn' => '20523874', 'alamat' => 'Jl. Supriyadi No. 4, Arjasa'],
            ['name' => 'SMPN 2 Arjasa', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Arjasa', 'npsn' => '20523875', 'alamat' => 'Kemuning Lor, Arjasa'],
            ['name' => 'MTs Darul Muqomah Arjasa', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Arjasa', 'npsn' => '20581140', 'alamat' => 'Sukowiryo, Arjasa'],

            // ==========================================
            // 27. PAKUSARI
            // ==========================================
            ['name' => 'SMPN 1 Pakusari', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Pakusari', 'npsn' => '20523876', 'alamat' => 'Jl. PB Sudirman No. 11, Pakusari'],
            ['name' => 'MTs Bustanul Ulum Pakusari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Pakusari', 'npsn' => '20581145', 'alamat' => 'Kertosari, Pakusari'],
            ['name' => 'MTs Al-Misri Pakusari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Pakusari', 'npsn' => '20581146', 'alamat' => 'Pakusari'],

            // ==========================================
            // 28. JELBUK
            // ==========================================
            ['name' => 'SMPN 1 Jelbuk', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Jelbuk', 'npsn' => '20523877', 'alamat' => 'Jl. PB Sudirman No. 5, Jelbuk'],
            ['name' => 'MTs Al-Fattah Jelbuk', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Jelbuk', 'npsn' => '20581150', 'alamat' => 'Sucopangepok, Jelbuk'],

            // ==========================================
            // 29. KALIWATES (Wilayah Kota)
            // ==========================================
            ['name' => 'SMPN 1 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523801', 'alamat' => 'Jl. Dewi Sartika No. 18, Jember Kidul'],
            ['name' => 'SMPN 2 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523802', 'alamat' => 'Jl. PB Sudirman No. 28, Kaliwates'],
            ['name' => 'SMPN 3 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523803', 'alamat' => 'Jl. Majapahit No. 5, Kaliwates'],
            ['name' => 'SMPN 4 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523804', 'alamat' => 'Jl. Hayam Wuruk No. 129, Sempusari'],
            ['name' => 'SMPN 5 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523805', 'alamat' => 'Jl. Imam Bonjol No. 37, Kaliwates'],
            ['name' => 'SMPN 6 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523806', 'alamat' => 'Jl. Gajah Mada No. 195, Kaliwates'],
            ['name' => 'SMPN 7 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523807', 'alamat' => 'Jl. Cempaka No. 42, Gebang'],
            ['name' => 'SMPN 8 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20523808', 'alamat' => 'Jl. Sultan Agung No. 54, Kaliwates'],
            ['name' => 'MTsN 1 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Kaliwates', 'npsn' => '20581101', 'alamat' => 'Jl. Imam Bonjol No. 50, Kaliwates'],
            ['name' => 'MTs Nurul Islam (Nuris) Jember', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Kaliwates', 'npsn' => '20581155', 'alamat' => 'Jl. Pangandaran No. 48, Antirogo'],
            ['name' => 'SMP Plus Al-Azhar Jember', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Kaliwates', 'npsn' => '20523730', 'alamat' => 'Jl. Danau Toba No. 1, Jember Kidul'],
            ['name' => 'SMP Muhammadiyah 1 Jember', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Kaliwates', 'npsn' => '20523731', 'alamat' => 'Jl. PB Sudirman No. 10, Kaliwates'],
            ['name' => 'SMP Katolik Maria Fatima Jember', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Kaliwates', 'npsn' => '20523732', 'alamat' => 'Jl. Kartini No. 24, Kepatihan'],
            ['name' => 'MTs Al-Qodiri Jember', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Kaliwates', 'npsn' => '20581156', 'alamat' => 'Jl. Manggar No. 139, Gebang'],
            ['name' => 'SMP Al-Furqan Jember', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Kaliwates', 'npsn' => '20523733', 'alamat' => 'Jl. S. Parman No. 2, Kaliwates'],

            // ==========================================
            // 30. SUMBERSARI (Wilayah Kampus / Kota)
            // ==========================================
            ['name' => 'SMPN 9 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumbersari', 'npsn' => '20523809', 'alamat' => 'Jl. Mastrip No. 1, Krajan Timur'],
            ['name' => 'SMPN 10 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumbersari', 'npsn' => '20523810', 'alamat' => 'Jl. Letjen Sutoyo No. 34, Sumbersari'],
            ['name' => 'SMPN 11 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumbersari', 'npsn' => '20523811', 'alamat' => 'Jl. PB Sudirman No. 88, Wirolegi'],
            ['name' => 'SMPN 12 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Sumbersari', 'npsn' => '20523812', 'alamat' => 'Jl. S. Parman No. 8, Sumbersari'],
            ['name' => 'MTs Al-Ghazali Sumbersari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sumbersari', 'npsn' => '20581160', 'alamat' => 'Karangrejo, Sumbersari'],
            ['name' => 'MTs Miftahul Ulum Sumbersari', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Sumbersari', 'npsn' => '20581161', 'alamat' => 'Wirolegi, Sumbersari'],
            ['name' => 'SMP Kartika IV-9 Jember', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Sumbersari', 'npsn' => '20523720', 'alamat' => 'Jl. PB Sudirman No. 50, Sumbersari'],

            // ==========================================
            // 31. PATRANG (Wilayah Kota Utara)
            // ==========================================
            ['name' => 'SMPN 13 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Patrang', 'npsn' => '20523813', 'alamat' => 'Jl. Dr. Soebandi No. 15, Patrang'],
            ['name' => 'SMPN 14 Jember', 'jenjang' => 'SMP', 'status' => 'Negeri', 'kecamatan' => 'Patrang', 'npsn' => '20523814', 'alamat' => 'Jl. Slamet Riyadi No. 90, Baratan'],
            ['name' => 'MTsN 10 Jember', 'jenjang' => 'MTs', 'status' => 'Negeri', 'kecamatan' => 'Patrang', 'npsn' => '20581110', 'alamat' => 'Jl. Srikoyo No. 8, Patrang'],
            ['name' => 'MTs Darussalam Patrang', 'jenjang' => 'MTs', 'status' => 'Swasta', 'kecamatan' => 'Patrang', 'npsn' => '20581165', 'alamat' => 'Bintoro, Patrang'],
            ['name' => 'SMP PGRI 1 Patrang', 'jenjang' => 'SMP', 'status' => 'Swasta', 'kecamatan' => 'Patrang', 'npsn' => '20523710', 'alamat' => 'Jl. dr. Soebandi No. 4, Patrang'],
        ];
    }
}
