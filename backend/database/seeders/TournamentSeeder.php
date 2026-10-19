<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\TournamentMatch;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Teams for Tournament Volleyball (Putra)
        $voliPutraTeams = [
            ['code' => 'SA26-VB01', 'school' => 'SMPN 1 Balung', 'mentor' => 'Coach Hariyanto, S.Pd', 'wa' => '081234567801'],
            ['code' => 'SA26-VB02', 'school' => 'MTs Baitul Hikmah', 'mentor' => 'Ustadz Farid Ridwan', 'wa' => '081234567802'],
            ['code' => 'SA26-VB03', 'school' => 'SMP Plus Al-Azhar', 'mentor' => 'Bambang Sudarsono, M.Pd', 'wa' => '081234567803'],
            ['code' => 'SA26-VB04', 'school' => 'SMPN 2 Wuluhan', 'mentor' => 'Agus Priyono, S.Or', 'wa' => '081234567804'],
            ['code' => 'SA26-VB05', 'school' => 'MTs SA Wahid Hasyim (Tuan Rumah)', 'mentor' => 'Ustadz Choirul Anam', 'wa' => '081234567805'],
            ['code' => 'SA26-VB06', 'school' => 'SMPN 1 Puger', 'mentor' => 'Dedi Iskandar, S.Pd', 'wa' => '081234567806'],
            ['code' => 'SA26-VB07', 'school' => 'SMP Islam Ambulu', 'mentor' => 'H. Suwarno, S.Pd', 'wa' => '081234567807'],
            ['code' => 'SA26-VB08', 'school' => 'MTsN 1 Jember', 'mentor' => 'Rahmat Hidayat, S.Or', 'wa' => '081234567808'],
        ];

        $regVoliPutra = [];
        foreach ($voliPutraTeams as $t) {
            $regVoliPutra[] = Registration::updateOrCreate(
                ['reg_code' => $t['code']],
                [
                    'school_name' => $t['school'],
                    'mentor_name' => $t['mentor'],
                    'mentor_wa' => $t['wa'],
                    'package_type' => 'Turnamen Beregu',
                    'competition_id' => 'volleyball-putra',
                    'competition_title' => 'Tournament Volleyball (Putra)',
                    'participants_names' => 'Tim Inti (6) + Cadangan (4)',
                    'payment_status' => 'Terverifikasi',
                    'checkin_status' => 'hadir',
                    'total_fee' => 50000
                ]
            );
        }

        // 2. Seed Teams for Mobile Legends (MSA S4)
        $mlbbTeams = [
            ['code' => 'SA26-ML01', 'school' => 'SMPN 1 Balung (Balung Esports)', 'mentor' => 'Kapten Kevin', 'wa' => '085235828601'],
            ['code' => 'SA26-ML02', 'school' => 'MTs Al-Qodiri Jember', 'mentor' => 'Kapten Dimas', 'wa' => '085235828602'],
            ['code' => 'SA26-ML03', 'school' => 'SMP Plus Darus Sholah', 'mentor' => 'Kapten Fajar', 'wa' => '085235828603'],
            ['code' => 'SA26-ML04', 'school' => 'SMPN 2 Ambulu (Ambulu Pride)', 'mentor' => 'Kapten Rizky', 'wa' => '085235828604'],
            ['code' => 'SA26-ML05', 'school' => 'MTs SA Wahid Hasyim (WH Cyber)', 'mentor' => 'Kapten Nabil', 'wa' => '085235828605'],
            ['code' => 'SA26-ML06', 'school' => 'SMPN 3 Semboro', 'mentor' => 'Kapten Rendy', 'wa' => '085235828606'],
            ['code' => 'SA26-ML07', 'school' => 'SMP Muhammadiyah 1 Jember', 'mentor' => 'Kapten Arya', 'wa' => '085235828607'],
            ['code' => 'SA26-ML08', 'school' => 'MTsN 2 Jember', 'mentor' => 'Kapten Ilham', 'wa' => '085235828608'],
        ];

        $regMlbb = [];
        foreach ($mlbbTeams as $t) {
            $regMlbb[] = Registration::updateOrCreate(
                ['reg_code' => $t['code']],
                [
                    'school_name' => $t['school'],
                    'mentor_name' => $t['mentor'],
                    'mentor_wa' => $t['wa'],
                    'package_type' => 'Turnamen E-Sport',
                    'competition_id' => 'mobile-legends',
                    'competition_title' => 'Tournament Mobile Legends (MSA S4)',
                    'participants_names' => 'Roster 5 Pemain + 1 Cadangan',
                    'payment_status' => 'Terverifikasi',
                    'checkin_status' => 'hadir',
                    'total_fee' => 50000
                ]
            );
        }

        // 3. Clear old matches if any
        TournamentMatch::whereIn('competition_id', ['volleyball-putra', 'mobile-legends'])->delete();

        // 4. Generate Bracket Matches for Volleyball Putra
        // Grand Final
        $finalMatch = TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'FINAL',
            'round_name' => 'Grand Final',
            'round_order' => 3,
            'team1_placeholder' => 'Pemenang Semi Final 1',
            'team2_placeholder' => 'Pemenang Semi Final 2',
            'status' => 'scheduled',
            'court' => 'Lapangan Utama (Center Court)',
            'scheduled_time' => '15:30 WIB',
            'referee_name' => 'Wasit Nasional PBVSI 1'
        ]);

        // Semi Finals
        $sf1 = TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'SF-1',
            'round_name' => 'Semi Final',
            'round_order' => 2,
            'team1_registration_id' => $regVoliPutra[0]->id, // SMPN 1 Balung (sudah lolos dari QF-1)
            'team2_registration_id' => $regVoliPutra[2]->id, // SMP Plus Al-Azhar (sudah lolos dari QF-2)
            'team1_score' => 0,
            'team2_score' => 0,
            'status' => 'scheduled',
            'next_match_id' => $finalMatch->id,
            'next_match_slot' => 'team1',
            'court' => 'Lapangan 1',
            'scheduled_time' => '13:00 WIB',
            'referee_name' => 'Wasit Wasito, S.Pd'
        ]);

        $sf2 = TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'SF-2',
            'round_name' => 'Semi Final',
            'round_order' => 2,
            'team1_placeholder' => 'Pemenang QF-3',
            'team2_placeholder' => 'Pemenang QF-4',
            'status' => 'scheduled',
            'next_match_id' => $finalMatch->id,
            'next_match_slot' => 'team2',
            'court' => 'Lapangan 2',
            'scheduled_time' => '13:45 WIB',
            'referee_name' => 'Wasit Sutrisno, M.Pd'
        ]);

        // Quarter Finals (Babak 8 Besar)
        // QF-1: SMPN 1 Balung (Win) vs MTs Baitul Hikmah
        TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'QF-1',
            'round_name' => 'Perempat Final (8 Besar)',
            'round_order' => 1,
            'team1_registration_id' => $regVoliPutra[0]->id,
            'team2_registration_id' => $regVoliPutra[1]->id,
            'team1_score' => 2,
            'team1_set_scores' => '25, 25',
            'team2_score' => 0,
            'team2_set_scores' => '19, 21',
            'winner_registration_id' => $regVoliPutra[0]->id,
            'next_match_id' => $sf1->id,
            'next_match_slot' => 'team1',
            'status' => 'completed',
            'court' => 'Lapangan 1',
            'scheduled_time' => '08:00 WIB',
            'referee_name' => 'Wasit Nasional 1',
            'notes' => 'SMPN 1 Balung menang straight set 2-0 dan lolos ke Semi Final.'
        ]);

        // QF-2: SMP Plus Al-Azhar (Win) vs SMPN 2 Wuluhan
        TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'QF-2',
            'round_name' => 'Perempat Final (8 Besar)',
            'round_order' => 1,
            'team1_registration_id' => $regVoliPutra[2]->id,
            'team2_registration_id' => $regVoliPutra[3]->id,
            'team1_score' => 2,
            'team1_set_scores' => '25, 21, 15',
            'team2_score' => 1,
            'team2_set_scores' => '22, 25, 12',
            'winner_registration_id' => $regVoliPutra[2]->id,
            'next_match_id' => $sf1->id,
            'next_match_slot' => 'team2',
            'status' => 'completed',
            'court' => 'Lapangan 2',
            'scheduled_time' => '09:00 WIB',
            'referee_name' => 'Wasit Nasional 2',
            'notes' => 'Pertandingan sengit 3 set (rubber set), dimenangkan Al-Azhar.'
        ]);

        // QF-3: MTs SA Wahid Hasyim vs SMPN 1 Puger (Currently Live/Ongoing!)
        TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'QF-3',
            'round_name' => 'Perempat Final (8 Besar)',
            'round_order' => 1,
            'team1_registration_id' => $regVoliPutra[4]->id,
            'team2_registration_id' => $regVoliPutra[5]->id,
            'team1_score' => 1,
            'team1_set_scores' => '25, 22',
            'team2_score' => 1,
            'team2_set_scores' => '20, 25',
            'next_match_id' => $sf2->id,
            'next_match_slot' => 'team1',
            'status' => 'ongoing',
            'court' => 'Lapangan 1 (Live Match)',
            'scheduled_time' => '10:00 WIB',
            'referee_name' => 'Wasit Utama PBVSI',
            'notes' => 'Sedang berlangsung Set Penentuan (Set ke-3).'
        ]);

        // QF-4: SMP Islam Ambulu vs MTsN 1 Jember
        TournamentMatch::create([
            'competition_id' => 'volleyball-putra',
            'match_number' => 'QF-4',
            'round_name' => 'Perempat Final (8 Besar)',
            'round_order' => 1,
            'team1_registration_id' => $regVoliPutra[6]->id,
            'team2_registration_id' => $regVoliPutra[7]->id,
            'team1_score' => 0,
            'team2_score' => 0,
            'next_match_id' => $sf2->id,
            'next_match_slot' => 'team2',
            'status' => 'scheduled',
            'court' => 'Lapangan 2',
            'scheduled_time' => '11:00 WIB',
            'referee_name' => 'Wasit Wasito, S.Pd',
            'notes' => 'Menunggu antrean setelah QF-3 selesai.'
        ]);

        // 5. Generate MLBB Matches (MSA Season 4)
        $mlbbFinal = TournamentMatch::create([
            'competition_id' => 'mobile-legends',
            'match_number' => 'GRAND FINAL',
            'round_name' => 'Grand Final (Bo5)',
            'round_order' => 3,
            'team1_placeholder' => 'Pemenang Semi Final 1',
            'team2_placeholder' => 'Pemenang Semi Final 2',
            'status' => 'scheduled',
            'court' => 'Main Stage Lab Komputer',
            'scheduled_time' => '16:00 WIB',
            'referee_name' => 'Caster & Marshal MSA'
        ]);

        $mlbbSf1 = TournamentMatch::create([
            'competition_id' => 'mobile-legends',
            'match_number' => 'SF-1',
            'round_name' => 'Semi Final (Bo3)',
            'round_order' => 2,
            'team1_registration_id' => $regMlbb[0]->id,
            'team2_registration_id' => $regMlbb[2]->id,
            'team1_score' => 1,
            'team2_score' => 0,
            'status' => 'ongoing',
            'next_match_id' => $mlbbFinal->id,
            'next_match_slot' => 'team1',
            'court' => 'Stage Arena A',
            'scheduled_time' => '13:00 WIB',
            'referee_name' => 'Admin Tournament MLBB 1',
            'notes' => 'Game 1 dimenangkan Balung Esports (Lord Steal menit 14).'
        ]);

        $mlbbSf2 = TournamentMatch::create([
            'competition_id' => 'mobile-legends',
            'match_number' => 'SF-2',
            'round_name' => 'Semi Final (Bo3)',
            'round_order' => 2,
            'team1_placeholder' => 'Pemenang QF-3 (WH Cyber / Semboro)',
            'team2_placeholder' => 'Pemenang QF-4',
            'status' => 'scheduled',
            'next_match_id' => $mlbbFinal->id,
            'next_match_slot' => 'team2',
            'court' => 'Stage Arena B',
            'scheduled_time' => '14:30 WIB',
            'referee_name' => 'Admin Tournament MLBB 2'
        ]);
    }
}
