<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Competition;
use App\Models\TournamentMatch;
use App\Models\JuryScore;

class OperasionalHubController extends Controller
{
    protected $defaultRubrics = [
        'albanjari' => [
            'Vokal & Keutuhan Lagu (Fashahah & Nada)' => 35,
            'Variasi Pukulan Terbang (Tempo, Ketukan & Variasi)' => 35,
            'Harmonisasi & Kekompakan Tim' => 20,
            'Adab, Busana & Kerapian Panggung' => 10,
        ],
        'singer' => [
            'Intonasi & Pitch Control (Ketepatan Nada)' => 30,
            'Teknik Vokal & Artikulasi (Pernafasan & Artikulasi)' => 30,
            'Penghayatan & Dinamika (Interpretasi Lagu)' => 25,
            'Stage Presence, Busana & Penampilan' => 15,
        ],
        'storytelling' => [
            'Pronunciation, Fluency & Accent' => 35,
            'Content, Grammar & Story Structure' => 25,
            'Facial Expression, Gesture & Stage Act' => 25,
            'Props, Costume & Time Management' => 15,
        ],
        'mipa' => [
            'Skor Babak Teori Matematika & Sains (CBT)' => 45,
            'Skor Babak Penalaran & Pemecahan Soal Esai' => 40,
            'Kecepatan & Ketepatan Penyelesaian Waktu' => 15,
        ],
        'ips' => [
            'Skor Babak Wawasan Geografi & Sosiologi' => 45,
            'Skor Babak Analisis Sejarah & Ekonomi Terpadu' => 40,
            'Daya Nalar & Ketepatan Logika Jawaban' => 15,
        ],
        'volleyball-putra' => [
            'Poin Kemenangan Set Pertandingan' => 50,
            'Teknik Serangan & Defense' => 25,
            'Kerjasama Tim & Taktik Lapangan' => 15,
            'Fair Play & Sportivitas' => 10,
        ],
        'volleyball-putri' => [
            'Poin Kemenangan Set Pertandingan' => 50,
            'Teknik Serangan & Defense' => 25,
            'Kerjasama Tim & Taktik Lapangan' => 15,
            'Fair Play & Sportivitas' => 10,
        ],
        'mobile-legends' => [
            'Poin Game / Turret / Objective' => 50,
            'Team Fight & Drafting Strategy' => 25,
            'Disiplin Waktu & Komunikasi' => 15,
            'Fair Play & Sportivitas' => 10,
        ],
    ];

    /**
     * Show Dark Operational Command Center (Inertia Vue 3 SPA)
     */
    public function index(Request $request)
    {
        $total = Registration::count();
        $hadir = Registration::where('checkin_status', 'hadir')->count();
        $belumHadir = Registration::where(function($q) {
            $q->where('checkin_status', '!=', 'hadir')->orWhereNull('checkin_status');
        })->count();
        $terverifikasi = Registration::where('payment_status', 'Terverifikasi')->count();
        $matchesLive = TournamentMatch::where('status', 'ongoing')->count();
        $matchesDone = TournamentMatch::where('status', 'completed')->count();
        $scoresCount = JuryScore::count();
        $scoresLocked = JuryScore::where('is_locked', true)->count();
        $persenKehadiran = $total > 0 ? round(($hadir / $total) * 100) : 0;

        $competitions = Competition::orderBy('id')->get();
        $recentCheckins = Registration::where('checkin_status', 'hadir')
            ->orderByDesc('checkin_at')
            ->limit(15)
            ->get();
        $registrations = Registration::orderByDesc('id')->get();
        $scores = JuryScore::with('registration')->get();
        $matches = TournamentMatch::with(['team1', 'team2', 'winner'])->orderBy('round_order')->get();

        $activeTab = $request->query('tab', 'overview');
        $activeComp = $request->query('comp', 'albanjari');

        return \Inertia\Inertia::render('Admin/Operasional', [
            'initialTab' => $activeTab,
            'initialComp' => $activeComp,
            'stats' => [
                'total' => $total,
                'hadir' => $hadir,
                'belumHadir' => $belumHadir,
                'terverifikasi' => $terverifikasi,
                'matchesLive' => $matchesLive,
                'matchesDone' => $matchesDone,
                'scoresCount' => $scoresCount,
                'scoresLocked' => $scoresLocked,
                'persenKehadiran' => $persenKehadiran,
            ],
            'competitions' => $competitions,
            'recentCheckins' => $recentCheckins,
            'registrations' => $registrations,
            'scores' => $scores,
            'matches' => $matches,
            'defaultRubrics' => $this->defaultRubrics,
        ]);
    }

    /**
     * Overview stats & recent checkins
     */
    public function overview()
    {
        $total = Registration::count();
        $hadir = Registration::where('checkin_status', 'hadir')->count();
        $belumHadir = Registration::where(function($q) {
            $q->where('checkin_status', '!=', 'hadir')->orWhereNull('checkin_status');
        })->count();
        $terverifikasi = Registration::where('payment_status', 'Terverifikasi')->count();
        $matchesLive = TournamentMatch::where('status', 'ongoing')->count();
        $matchesDone = TournamentMatch::where('status', 'completed')->count();
        $scoresCount = JuryScore::count();
        $scoresLocked = JuryScore::where('is_locked', true)->count();
        $persenKehadiran = $total > 0 ? round(($hadir / $total) * 100) : 0;

        $competitions = Competition::orderBy('id')->get();
        $recentCheckins = Registration::where('checkin_status', 'hadir')
            ->orderByDesc('checkin_at')
            ->limit(8)
            ->get();
        $registrations = Registration::orderByDesc('id')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver' => 'MariaDB',
                'port' => '3307',
                'stats' => [
                    'total' => $total,
                    'hadir' => $hadir,
                    'belumHadir' => $belumHadir,
                    'terverifikasi' => $terverifikasi,
                    'matchesLive' => $matchesLive,
                    'matchesDone' => $matchesDone,
                    'scoresCount' => $scoresCount,
                    'scoresLocked' => $scoresLocked,
                    'persenKehadiran' => $persenKehadiran,
                ],
                'competitions' => $competitions,
                'recentCheckins' => $recentCheckins,
                'registrations' => $registrations
            ]
        ]);
    }

    /**
     * Filtered delegates
     */
    public function delegates(Request $request)
    {
        $query = Registration::orderByDesc('id');

        if ($request->filled('competition_id')) {
            $query->where('competition_id', $request->competition_id);
        }
        if ($request->filled('checkin_status')) {
            $query->where('checkin_status', $request->checkin_status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(function($q) use ($s) {
                $q->where('school_name', 'like', "%{$s}%")
                  ->orWhere('reg_code', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%")
                  ->orWhere('mentor_name', 'like', "%{$s}%")
                  ->orWhere('participants_names', 'like', "%{$s}%");
            });
        }

        $delegates = $query->get();

        return response()->json([
            'status' => 'success',
            'delegates' => $delegates
        ]);
    }

    /**
     * Gate Barcode Scan
     */
    public function gateScan(Request $request)
    {
        $code = trim($request->input('code', ''));
        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Kode barcode kosong.'], 400);
        }

        $delegate = Registration::where('barcode', $code)
            ->orWhere('reg_code', $code)
            ->first();

        if (!$delegate) {
            return response()->json([
                'success' => false,
                'message' => "Barcode/Kode [{$code}] tidak ditemukan dalam database delegasi."
            ]);
        }

        $alreadyCheckedIn = ($delegate->checkin_status === 'hadir');

        if (!$alreadyCheckedIn) {
            $delegate->checkin_status = 'hadir';
            $delegate->checkin_at = now();
            $delegate->save();
        }

        return response()->json([
            'success' => true,
            'alreadyCheckedIn' => $alreadyCheckedIn,
            'message' => $alreadyCheckedIn 
                ? "Peserta {$delegate->school_name} SUDAH check-in sebelumnya!" 
                : "Check-in SUKSES! Selamat bertanding kontingen {$delegate->school_name}.",
            'delegate' => $delegate
        ]);
    }

    /**
     * Undo Gate Check-in
     */
    public function gateUndo($id)
    {
        $delegate = Registration::find($id);
        if ($delegate) {
            $delegate->checkin_status = 'belum_hadir';
            $delegate->checkin_at = null;
            $delegate->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Status kehadiran berhasil dibatalkan.'
        ]);
    }

    /**
     * Update Verification Decision
     */
    public function verificationUpdate($id, Request $request)
    {
        $delegate = Registration::findOrFail($id);
        if ($request->filled('status')) {
            $delegate->payment_status = $request->status;
        }
        if ($request->has('notes')) {
            $delegate->verification_notes = $request->notes;
        }
        $delegate->save();

        return response()->json([
            'success' => true,
            'message' => "Status verifikasi berkas diperbarui menjadi [{$delegate->payment_status}]."
        ]);
    }

    /**
     * Get Jury Arena Data
     */
    public function juryData($compId)
    {
        $competition = Competition::find($compId);
        $delegates = Registration::where('competition_id', $compId)
            ->orderByRaw("CASE WHEN checkin_status = 'hadir' THEN 0 ELSE 1 END")
            ->orderBy('school_name')
            ->get();
        $scores = JuryScore::where('competition_id', $compId)->get();

        $defaultCriteria = $this->defaultRubrics[$compId] ?? ($this->defaultRubrics['default'] ?? [
            'Penguasaan Materi & Teknik' => 40,
            'Kreativitas & Estetika Penampilan' => 30,
            'Kerapian, Adab & Kedisiplinan' => 20,
            'Ketepatan Waktu Pelaksanaan' => 10,
        ]);

        $criteria = $defaultCriteria;
        $rubricType = 'percentage';
        $isCustom = false;

        if ($competition && !empty($competition->scoring_rubrics)) {
            $rubricData = is_array($competition->scoring_rubrics) 
                ? $competition->scoring_rubrics 
                : json_decode($competition->scoring_rubrics, true);

            if (isset($rubricData['criteria']) && is_array($rubricData['criteria'])) {
                $criteria = $rubricData['criteria'];
                $rubricType = $rubricData['type'] ?? 'percentage';
                $isCustom = true;
            } elseif (is_array($rubricData)) {
                $criteria = $rubricData;
                $rubricType = 'percentage';
                $isCustom = true;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'competition' => $competition,
                'criteria' => $criteria,
                'rubric_type' => $rubricType,
                'is_custom' => $isCustom,
                'default_criteria' => $defaultCriteria,
                'delegates' => $delegates,
                'scores' => $scores
            ]
        ]);
    }

    /**
     * Get Rubric Details for Customizer Modal
     */
    public function getRubric($compId)
    {
        $competition = Competition::findOrFail($compId);
        $defaultCriteria = $this->defaultRubrics[$compId] ?? ($this->defaultRubrics['default'] ?? [
            'Penguasaan Materi & Teknik' => 40,
            'Kreativitas & Estetika Penampilan' => 30,
            'Kerapian, Adab & Kedisiplinan' => 20,
            'Ketepatan Waktu Pelaksanaan' => 10,
        ]);

        $criteria = $defaultCriteria;
        $rubricType = 'percentage';
        $isCustom = false;

        if (!empty($competition->scoring_rubrics)) {
            $rubricData = is_array($competition->scoring_rubrics) 
                ? $competition->scoring_rubrics 
                : json_decode($competition->scoring_rubrics, true);

            if (isset($rubricData['criteria']) && is_array($rubricData['criteria'])) {
                $criteria = $rubricData['criteria'];
                $rubricType = $rubricData['type'] ?? 'percentage';
                $isCustom = true;
            } elseif (is_array($rubricData)) {
                $criteria = $rubricData;
                $rubricType = 'percentage';
                $isCustom = true;
            }
        }

        return response()->json([
            'success' => true,
            'competition_id' => $competition->id,
            'competition_title' => $competition->title,
            'is_custom' => $isCustom,
            'rubric_type' => $rubricType,
            'active_rubric' => $criteria,
            'default_rubric' => $defaultCriteria,
        ]);
    }

    /**
     * Update/Customize Rubric Criteria and Scoring Mode
     */
    public function updateRubric(Request $request, $compId)
    {
        $competition = Competition::findOrFail($compId);
        $rubricType = in_array($request->input('rubric_type'), ['point', 'score']) ? 'point' : 'percentage';

        $criteria = [];

        // Support both array of objects [{name, weight}] or associative array or parallel names & weights
        if ($request->has('criteria_names') && $request->has('criteria_weights')) {
            $names = (array) $request->input('criteria_names', []);
            $weights = (array) $request->input('criteria_weights', []);
            foreach ($names as $idx => $n) {
                $nTrim = trim($n);
                if ($nTrim !== '') {
                    $criteria[$nTrim] = floatval($weights[$idx] ?? ($rubricType === 'point' ? 100 : 10));
                }
            }
        } elseif ($request->has('criteria')) {
            $input = $request->input('criteria');
            if (is_array($input)) {
                foreach ($input as $k => $v) {
                    if (is_array($v) && isset($v['name'])) {
                        $nTrim = trim($v['name']);
                        if ($nTrim !== '') {
                            $criteria[$nTrim] = floatval($v['weight'] ?? 10);
                        }
                    } else {
                        $nTrim = trim($k);
                        if ($nTrim !== '') {
                            $criteria[$nTrim] = floatval($v);
                        }
                    }
                }
            }
        }

        if (empty($criteria)) {
            return response()->json(['success' => false, 'message' => 'Kriteria penilaian tidak boleh kosong! Minimal harus ada 1 kriteria.'], 422);
        }

        $competition->scoring_rubrics = [
            'type' => $rubricType,
            'criteria' => $criteria,
        ];
        $competition->save();

        $typeLabel = $rubricType === 'point' ? 'Mode Skor / Poin Maksimal' : 'Mode Nilai Persentase (%)';

        return response()->json([
            'success' => true,
            'message' => "Rubrik kriteria ({$typeLabel}) untuk cabang {$competition->title} berhasil diperbarui!",
            'rubric_type' => $rubricType,
            'criteria' => $criteria,
        ]);
    }

    /**
     * Reset Rubric to official Juknis Defaults
     */
    public function resetRubric($compId)
    {
        $competition = Competition::findOrFail($compId);
        $competition->scoring_rubrics = null;
        $competition->save();

        $defaultCriteria = $this->defaultRubrics[$compId] ?? ($this->defaultRubrics['default'] ?? [
            'Penguasaan Materi & Teknik' => 40,
            'Kreativitas & Estetika Penampilan' => 30,
            'Kerapian, Adab & Kedisiplinan' => 20,
            'Ketepatan Waktu Pelaksanaan' => 10,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Rubrik kriteria cabang {$competition->title} berhasil direset ke standar resmi Juknis (Mode Persentase)!",
            'rubric_type' => 'percentage',
            'criteria' => $defaultCriteria,
        ]);
    }

    /**
     * Save Jury Score
     */
    public function juryScore(Request $request)
    {
        $data = $request->validate([
            'registration_id' => 'required',
            'competition_id' => 'required',
            'jury_name' => 'nullable|string',
            'criteria_scores' => 'nullable',
            'total_score' => 'required|numeric',
            'performance_duration' => 'nullable|string',
            'is_locked' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        $score = JuryScore::updateOrCreate(
            [
                'registration_id' => $data['registration_id'],
                'competition_id' => $data['competition_id']
            ],
            [
                'jury_name' => $data['jury_name'] ?? 'Dewan Juri',
                'criteria_scores' => is_array($data['criteria_scores']) ? $data['criteria_scores'] : json_decode($data['criteria_scores'] ?? '{}', true),
                'total_score' => $data['total_score'],
                'performance_duration' => $data['performance_duration'] ?? '',
                'is_locked' => !empty($data['is_locked']),
                'notes' => $data['notes'] ?? '',
            ]
        );

        $score->load('registration', 'competition');

        // Broadcast to Reverb WebSocket channel instantly
        try {
            broadcast(new \App\Events\JuryScoreSubmitted($score));
        } catch (\Throwable $e) {
            // Reverb broadcast fallback
        }

        return response()->json([
            'success' => true,
            'message' => 'Nilai dewan juri berhasil disimpan dan diarsipkan.',
            'score' => $score
        ]);
    }

    /**
     * Get Tournament Arena Data
     */
    public function tournamentData($compId)
    {
        $matches = TournamentMatch::where('competition_id', $compId)
            ->with(['team1', 'team2', 'winner'])
            ->orderBy('round_order')
            ->orderBy('id')
            ->get();

        $teams = Registration::where('competition_id', $compId)
            ->orderBy('school_name')
            ->get();

        // Auto generate if empty for volleyball or mobile-legends
        if ($matches->isEmpty() && (str_contains($compId, 'volleyball') || str_contains($compId, 'mobile-legends'))) {
            $this->tournamentGenerate($compId);
            return $this->tournamentData($compId);
        }

        $formattedMatches = $matches->map(function($m) {
            return [
                'id' => $m->id,
                'match_number' => $m->match_number,
                'round_name' => $m->round_name,
                'round_order' => $m->round_order,
                'court' => $m->court,
                'status' => $m->status,
                'team1_registration_id' => $m->team1_registration_id,
                'team1_placeholder' => $m->team1_placeholder,
                'team1_name' => $m->team1 ? $m->team1->school_name : $m->team1_placeholder,
                'team1_score' => $m->team1_score,
                'team1_set_scores' => $m->team1_set_scores,
                'team2_registration_id' => $m->team2_registration_id,
                'team2_placeholder' => $m->team2_placeholder,
                'team2_name' => $m->team2 ? $m->team2->school_name : $m->team2_placeholder,
                'team2_score' => $m->team2_score,
                'team2_set_scores' => $m->team2_set_scores,
                'winner_registration_id' => $m->winner_registration_id,
                'winner_name' => $m->winner ? $m->winner->school_name : null,
                'next_match_id' => $m->next_match_id,
                'next_match_slot' => $m->next_match_slot,
                'referee_name' => $m->referee_name,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'competitionId' => $compId,
                'matches' => $formattedMatches,
                'teams' => $teams
            ]
        ]);
    }

    /**
     * Update Tournament Match Score
     */
    public function tournamentMatchScore($matchId, Request $request)
    {
        $match = TournamentMatch::findOrFail($matchId);

        $t1Score = intval($request->input('team1_score', $match->team1_score));
        $t2Score = intval($request->input('team2_score', $match->team2_score));
        $status = $request->input('status', $match->status);

        $winnerId = $request->input('winner_registration_id', $match->winner_registration_id);
        if ($status === 'completed' && !$winnerId) {
            if ($t1Score > $t2Score) {
                $winnerId = $match->team1_registration_id;
            } elseif ($t2Score > $t1Score) {
                $winnerId = $match->team2_registration_id;
            }
        }

        $match->update([
            'team1_score' => $t1Score,
            'team2_score' => $t2Score,
            'team1_set_scores' => $request->input('team1_set_scores', $match->team1_set_scores),
            'team2_set_scores' => $request->input('team2_set_scores', $match->team2_set_scores),
            'status' => $status,
            'winner_registration_id' => $winnerId,
            'court' => $request->input('court', $match->court),
            'referee_name' => $request->input('referee_name', $match->referee_name),
            'notes' => $request->input('notes', $match->notes),
        ]);

        // Propagate winner to next match in bracket
        if ($match->next_match_id && $match->next_match_slot && $winnerId) {
            $nextMatch = TournamentMatch::find($match->next_match_id);
            if ($nextMatch) {
                if ($match->next_match_slot === 'team1') {
                    $nextMatch->team1_registration_id = $winnerId;
                } else {
                    $nextMatch->team2_registration_id = $winnerId;
                }
                $nextMatch->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Skor dan status pertandingan turnamen berhasil diperbarui!'
        ]);
    }

    /**
     * Generate standard 8-bracket
     */
    public function tournamentGenerate($compId)
    {
        $teams = Registration::where('competition_id', $compId)
            ->orderBy('id')
            ->limit(8)
            ->get();

        TournamentMatch::where('competition_id', $compId)->delete();

        // 1. Grand Final
        $final = TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'FINAL',
            'round_name' => 'Grand Final',
            'round_order' => 3,
            'court' => 'Lapangan Utama A',
            'team1_placeholder' => 'Pemenang SF-1',
            'team2_placeholder' => 'Pemenang SF-2',
            'status' => 'scheduled'
        ]);

        // 2. Semi Finals
        $sf1 = TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'SF-1',
            'round_name' => 'Babak Semi Final',
            'round_order' => 2,
            'court' => 'Lapangan Utama A',
            'team1_placeholder' => 'Pemenang QF-1',
            'team2_placeholder' => 'Pemenang QF-2',
            'next_match_id' => $final->id,
            'next_match_slot' => 'team1',
            'status' => 'scheduled'
        ]);

        $sf2 = TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'SF-2',
            'round_name' => 'Babak Semi Final',
            'round_order' => 2,
            'court' => 'Lapangan B',
            'team1_placeholder' => 'Pemenang QF-3',
            'team2_placeholder' => 'Pemenang QF-4',
            'next_match_id' => $final->id,
            'next_match_slot' => 'team2',
            'status' => 'scheduled'
        ]);

        // 3. Quarter Finals
        TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'QF-1',
            'round_name' => 'Babak Perempat Final',
            'round_order' => 1,
            'court' => 'Lapangan Utama A',
            'team1_registration_id' => $teams->get(0)?->id,
            'team1_placeholder' => 'Unggulan 1',
            'team2_registration_id' => $teams->get(1)?->id,
            'team2_placeholder' => 'Penantang 1',
            'next_match_id' => $sf1->id,
            'next_match_slot' => 'team1',
            'status' => 'scheduled'
        ]);

        TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'QF-2',
            'round_name' => 'Babak Perempat Final',
            'round_order' => 1,
            'court' => 'Lapangan B',
            'team1_registration_id' => $teams->get(2)?->id,
            'team1_placeholder' => 'Unggulan 2',
            'team2_registration_id' => $teams->get(3)?->id,
            'team2_placeholder' => 'Penantang 2',
            'next_match_id' => $sf1->id,
            'next_match_slot' => 'team2',
            'status' => 'scheduled'
        ]);

        TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'QF-3',
            'round_name' => 'Babak Perempat Final',
            'round_order' => 1,
            'court' => 'Lapangan Utama A',
            'team1_registration_id' => $teams->get(4)?->id,
            'team1_placeholder' => 'Unggulan 3',
            'team2_registration_id' => $teams->get(5)?->id,
            'team2_placeholder' => 'Penantang 3',
            'next_match_id' => $sf2->id,
            'next_match_slot' => 'team1',
            'status' => 'scheduled'
        ]);

        TournamentMatch::create([
            'competition_id' => $compId,
            'match_number' => 'QF-4',
            'round_name' => 'Babak Perempat Final',
            'round_order' => 1,
            'court' => 'Lapangan B',
            'team1_registration_id' => $teams->get(6)?->id,
            'team1_placeholder' => 'Unggulan 4',
            'team2_registration_id' => $teams->get(7)?->id,
            'team2_placeholder' => 'Penantang 4',
            'next_match_id' => $sf2->id,
            'next_match_slot' => 'team2',
            'status' => 'scheduled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bagan 8 Besar Sistem Gugur resmi berhasil digenerate!'
        ]);
    }

    /**
     * ID Cards Data
     */
    public function idCards(Request $request)
    {
        $query = Registration::orderBy('school_name')->orderBy('id');

        if ($request->filled('competition_id')) {
            $query->where('competition_id', $request->competition_id);
        }
        if ($request->filled('school_name')) {
            $query->where('school_name', 'like', "%{$request->school_name}%");
        }

        $cards = $query->get();

        return response()->json([
            'status' => 'success',
            'cards' => $cards
        ]);
    }
}
