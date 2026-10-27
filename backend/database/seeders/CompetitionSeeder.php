<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competition;
use App\Models\Registration;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $competitions = [
            [
                'id' => 'mipa',
                'title' => 'Olimpiade MIPA',
                'category' => 'akademik',
                'category_label' => 'Akademik & Sains',
                'icon' => '🔬',
                'fee' => 50000,
                'max_quota' => 40,
                'current_quota' => 14,
                'status' => 'open',
                'participant_type' => 'individu',
                'max_teams' => 1,
                'max_participants' => 3,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => 'Maksimal 3 peserta per sekolah/madrasah',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Olimpiade MIPA (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Olimpiade MIPA', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Ajang uji kemampuan sains dan penalaran logika Matematika serta IPA SMP/MTs se-Kabupaten Jember.'
            ],
            [
                'id' => 'ips',
                'title' => 'Olimpiade IPS',
                'category' => 'akademik',
                'category_label' => 'Akademik & Sains',
                'icon' => '🌍',
                'fee' => 50000,
                'max_quota' => 40,
                'current_quota' => 12,
                'status' => 'open',
                'participant_type' => 'individu',
                'max_teams' => 1,
                'max_participants' => 3,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => 'Maksimal 3 peserta per sekolah/madrasah',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Olimpiade IPS (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Olimpiade IPS', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Kompetisi wawasan sosial, geografi, sejarah, sosiologi, dan ekonomi terpadu tingkat SMP/MTs.'
            ],
            [
                'id' => 'storytelling',
                'title' => 'Storytelling (Bahasa Inggris)',
                'category' => 'seni',
                'category_label' => 'Bahasa & Seni',
                'icon' => '📖',
                'fee' => 50000,
                'max_quota' => 30,
                'current_quota' => 9,
                'status' => 'open',
                'participant_type' => 'individu',
                'max_teams' => 1,
                'max_participants' => 2,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => 'Maksimal 2 peserta per sekolah/madrasah',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Storytelling (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Storytelling', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Lomba bercerita dalam bahasa Inggris untuk mengasah bakat public speaking, ekspresi, dan intonasi.'
            ],
            [
                'id' => 'albanjari',
                'title' => 'Festival Albanjari',
                'category' => 'keagamaan',
                'category_label' => "Bidang Keagamaan & Da'wah",
                'icon' => '🥁',
                'fee' => 50000,
                'max_quota' => 25,
                'current_quota' => 11,
                'status' => 'open',
                'participant_type' => 'tim',
                'max_teams' => 1,
                'max_participants' => 10,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => '1 grup terdiri dari maksimal 10 personel (Vokal + Terbang)',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Festival Albanjari (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Festival Albanjari', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Festival shalawat hadrah Al-Banjari tingkat pelajar se-Kabupaten Jember dengan syiar seni Islami.'
            ],
            [
                'id' => 'singer',
                'title' => 'Singer Lagu Islami',
                'category' => 'seni',
                'category_label' => 'Bahasa & Seni',
                'icon' => '🎤',
                'fee' => 50000,
                'max_quota' => 30,
                'current_quota' => 8,
                'status' => 'open',
                'participant_type' => 'individu',
                'max_teams' => 1,
                'max_participants' => 2,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => 'Maksimal 2 peserta (Putra/Putri) per sekolah',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Singer Lagu Islami (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Singer Lagu Islami', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Kompetisi vokal solo lagu religi/Islami untuk menyalurkan bakat bernyanyi dengan nada merdu dan penghayatan.'
            ],
            [
                'id' => 'volleyball-putra',
                'title' => 'Tournament Volleyball (Putra)',
                'category' => 'olahraga',
                'category_label' => 'Olahraga',
                'icon' => '🏐',
                'fee' => 50000,
                'max_quota' => 16,
                'current_quota' => 16,
                'status' => 'closed',
                'participant_type' => 'tim',
                'max_teams' => 1,
                'max_participants' => 10,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => '1 Tim (6 pemain inti + 4 cadangan + official)',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Tournament Volleyball Putra (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Tournament Volleyball Putra', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Turnamen bola voli putra bergengsi antar SMP/MTs. Kuota peserta utama telah terpenuhi (waiting list).'
            ],
            [
                'id' => 'volleyball-putri',
                'title' => 'Tournament Volleyball (Putri)',
                'category' => 'olahraga',
                'category_label' => 'Olahraga',
                'icon' => '🏐',
                'fee' => 50000,
                'max_quota' => 16,
                'current_quota' => 10,
                'status' => 'open',
                'participant_type' => 'tim',
                'max_teams' => 1,
                'max_participants' => 10,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => '1 Tim (6 pemain inti + 4 cadangan + official)',
                'tm_date' => '26/10/2026 • 08:00 WIB',
                'contact_person' => 'Koordinator Tournament Volleyball Putri (0838 5007 0767)',
                'coordinators' => [
                    ['name' => 'Koordinator Tournament Volleyball Putri', 'contact' => '0838 5007 0767', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Turnamen bola voli putri untuk mengasah ketangkasan, kekompakan tim, dan sportivitas pelajar putri.'
            ],
            [
                'id' => 'mobile-legends',
                'title' => 'Tournament Mobile Legends (MSA S4)',
                'category' => 'teknologi',
                'category_label' => "Bidang Teknologi & E-Sport",
                'icon' => '🎮',
                'fee' => 50000,
                'max_quota' => 32,
                'current_quota' => 19,
                'status' => 'open',
                'participant_type' => 'tim',
                'max_teams' => 1,
                'max_participants' => 6,
                'registration_deadline' => '25/10/2026',
                'participants_rule' => '1 Tim (5 pemain inti + 1 cadangan)',
                'tm_date' => '26/10/2026 • 14:00 WIB',
                'contact_person' => 'Koordinator E-Sport MLBB (0852 3582 8664)',
                'coordinators' => [
                    ['name' => 'Koordinator E-Sport MLBB', 'contact' => '0852 3582 8664', 'role' => 'Koordinator Lomba']
                ],
                'description' => 'Turnamen E-Sport MLBB (MSA Season 4) resmi Semarak Aliyah tingkat pelajar SMP/MTs.'
            ]
        ];

        foreach ($competitions as $comp) {
            Competition::updateOrCreate(['id' => $comp['id']], $comp);
        }

        Registration::updateOrCreate(
            ['reg_code' => 'SA26-102941'],
            [
                'school_name' => 'MTs Negeri 1 Jember',
                'mentor_name' => 'Ustadz Abdullah, S.Pd',
                'mentor_wa' => '081234567890',
                'package_type' => 'Paket Satuan (Rp 50.000 / Cabang)',
                'competition_id' => 'mipa',
                'competition_title' => 'Olimpiade MIPA',
                'participants_names' => "1. Muhammad Rizky (Kls 9)\n2. Anisa Rahmawati (Kls 8)",
                'notes' => 'Pembayaran transfer bank BSI',
                'total_fee' => 50000,
                'payment_status' => 'Terverifikasi'
            ]
        );

        Registration::updateOrCreate(
            ['reg_code' => 'SA26-492018'],
            [
                'school_name' => 'SMP Plus Al-Azhar Balung',
                'mentor_name' => 'Ibu Nurul Hidayati, M.Pd',
                'mentor_wa' => '085298765432',
                'package_type' => 'Paket All-In Komplit (Rp 200.000)',
                'competition_id' => 'all-in',
                'competition_title' => 'Semua Cabang Lomba (Paket All In)',
                'participants_names' => 'Delegasi lengkap 8 cabang lomba kontingen SMP Plus Al-Azhar',
                'notes' => 'Konfirmasi via WhatsApp Panitia',
                'total_fee' => 200000,
                'payment_status' => 'Terverifikasi'
            ]
        );
    }
}
