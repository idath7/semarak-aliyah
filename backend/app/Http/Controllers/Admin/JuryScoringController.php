<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\Competition;
use App\Models\JuryScore;

class JuryScoringController extends Controller
{
    /**
     * Rubric criteria presets per category according to official Juknis Semarak Aliyah.
     */
    protected $rubrics = [
        // 1. Festival Al-Banjari (Religi & Seni Hadrah)
        'albanjari' => [
            'Vokal & Keutuhan Lagu (Fashahah & Nada)' => 35,
            'Variasi Pukulan Terbang (Tempo, Ketukan & Variasi)' => 35,
            'Harmonisasi & Kekompakan Tim' => 20,
            'Adab, Busana & Kerapian Panggung' => 10,
        ],

        // 2. Singer Solo Lagu Islami (Bahasa & Seni)
        'singer' => [
            'Intonasi & Pitch Control (Ketepatan Nada)' => 30,
            'Teknik Vokal & Artikulasi (Pernafasan & Artikulasi)' => 30,
            'Penghayatan & Dinamika (Interpretasi Lagu)' => 25,
            'Stage Presence, Busana & Penampilan' => 15,
        ],

        // 3. Storytelling Bahasa Inggris (Bahasa & Seni)
        'storytelling' => [
            'Pronunciation, Fluency & Accent' => 35,
            'Content, Grammar & Story Structure' => 25,
            'Facial Expression, Gesture & Stage Act' => 25,
            'Props, Costume & Time Management' => 15,
        ],

        // 4. Olimpiade MIPA (Akademik Sains)
        'mipa' => [
            'Skor Babak Teori Matematika & Sains (CBT)' => 45,
            'Skor Babak Penalaran & Pemecahan Soal Esai' => 40,
            'Kecepatan & Ketepatan Penyelesaian Waktu' => 15,
        ],

        // 5. Olimpiade IPS (Akademik Sosial)
        'ips' => [
            'Skor Babak Wawasan Geografi & Sosiologi' => 45,
            'Skor Babak Analisis Sejarah & Ekonomi Terpadu' => 40,
            'Daya Nalar & Ketepatan Logika Jawaban' => 15,
        ],

        // 6. Turnamen Bola Voli Putra (Olahraga Beregu)
        'volleyball-putra' => [
            'Poin Kemenangan Set Pertandingan (Set 1 & 2/3)' => 50,
            'Teknik Serangan & Defense (Spike, Servis, Block)' => 25,
            'Kerjasama Tim, Taktik & Komunikasi Lapangan' => 15,
            'Fair Play, Sportivitas & Kedisiplinan Tim' => 10,
        ],

        // 7. Turnamen Bola Voli Putri (Olahraga Beregu)
        'volleyball-putri' => [
            'Poin Kemenangan Set Pertandingan (Set 1 & 2/3)' => 50,
            'Teknik Serangan & Defense (Spike, Servis, Block)' => 25,
            'Kerjasama Tim, Taktik & Komunikasi Lapangan' => 15,
            'Fair Play, Sportivitas & Kedisiplinan Tim' => 10,
        ],

        // 8. Turnamen E-Sport Mobile Legends MSA S4 (E-Sport)
        'mobile-legends' => [
            'Skor Match / Game Point (Sistem Bo3 Win/Loss)' => 45,
            'Kontrol Objektif (Lord, Turtle, Turret & Gold Lead)' => 25,
            'Teamfight Synergy, KDA & Macro Play' => 20,
            'Sportivitas, Respect & Disiplin Turnamen' => 10,
        ],

        // Fallback Umum
        'default' => [
            'Penguasaan Materi & Teknik' => 40,
            'Kreativitas & Estetika Penampilan' => 30,
            'Kerapian, Adab & Kedisiplinan' => 20,
            'Ketepatan Waktu Pelaksanaan' => 10,
        ]
    ];

    /**
     * Get active rubric details for a competition.
     * Returns associative array: ['type' => 'percentage'|'point', 'criteria' => array]
     */
    public function getCompetitionRubricDetails(Competition $competition): array
    {
        $raw = $competition->scoring_rubrics;
        if (!empty($raw) && is_array($raw)) {
            if (isset($raw['criteria']) && is_array($raw['criteria'])) {
                return [
                    'type' => $raw['type'] ?? 'percentage',
                    'criteria' => $raw['criteria'],
                ];
            }
            // Legacy / plain array format
            return [
                'type' => 'percentage',
                'criteria' => $raw,
            ];
        }

        $preset = $this->rubrics[$competition->id] ?? $this->rubrics['default'];
        return [
            'type' => 'percentage',
            'criteria' => $preset,
        ];
    }

    /**
     * Get active rubric criteria dictionary for a competition.
     */
    public function getCompetitionRubric(Competition $competition): array
    {
        return $this->getCompetitionRubricDetails($competition)['criteria'];
    }

    /**
     * Show list of arenas/competitions to judge.
     */
    public function index()
    {
        $competitions = Competition::orderBy('title')->get()->map(function ($comp) {
            $details = $this->getCompetitionRubricDetails($comp);
            $comp->active_rubric = $details['criteria'];
            $comp->rubric_type = $details['type'];
            $comp->is_custom_rubric = !empty($comp->scoring_rubrics);

            if ($comp->isTournament()) {
                $comp->matches_count = \App\Models\TournamentMatch::where('competition_id', $comp->id)->count();
                $comp->completed_matches_count = \App\Models\TournamentMatch::where('competition_id', $comp->id)->where('status', 'completed')->count();
                $comp->ongoing_matches_count = \App\Models\TournamentMatch::where('competition_id', $comp->id)->where('status', 'ongoing')->count();
            }
            return $comp;
        });

        $recentScores = JuryScore::with(['registration', 'competition'])
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('admin.jury-scoring-index', compact('competitions', 'recentScores'));
    }

    /**
     * Judging Arena Screen for a specific competition.
     */
    public function arena($competitionId, Request $request)
    {
        $competition = Competition::findOrFail($competitionId);

        // Turnamen Sistem Gugur (Knockout: Voli, E-Sport) diarahkan ke Tournament Match Arena khusus
        if ($competition->isTournament()) {
            return redirect()->route('admin.tournament.arena', $competitionId);
        }

        $registrations = Registration::where('competition_id', $competitionId)
            ->with('juryScores')
            ->orderBy('id')
            ->get();

        $selectedParticipantId = $request->input('participant', $registrations->first()?->id);
        $selectedParticipant = $selectedParticipantId ? Registration::find($selectedParticipantId) : null;

        // Existing score if any
        $existingScore = null;
        if ($selectedParticipant) {
            $existingScore = JuryScore::where('registration_id', $selectedParticipant->id)
                ->where('competition_id', $competitionId)
                ->first();
        }

        $details = $this->getCompetitionRubricDetails($competition);
        $criteria = $details['criteria'];
        $rubricType = $details['type'];

        if ($existingScore && !empty($existingScore->criteria_scores)) {
            foreach ($existingScore->criteria_scores as $criterionKey => $val) {
                if (!isset($criteria[$criterionKey])) {
                    $criteria[$criterionKey] = ($rubricType === 'point' ? 100 : 50);
                }
            }
        }

        $isCustomRubric = !empty($competition->scoring_rubrics);
        $defaultRubric = $this->rubrics[$competition->id] ?? $this->rubrics['default'];
        $allRubrics = $this->rubrics;

        return view('admin.jury-scoring-arena', compact(
            'competition',
            'registrations',
            'selectedParticipant',
            'existingScore',
            'criteria',
            'rubricType',
            'isCustomRubric',
            'defaultRubric',
            'allRubrics'
        ));
    }

    /**
     * Get JSON data of rubric for a competition (for modal customizer).
     */
    public function getRubric($competitionId)
    {
        $competition = Competition::findOrFail($competitionId);
        $details = $this->getCompetitionRubricDetails($competition);
        $isCustom = !empty($competition->scoring_rubrics);
        $defaultRubric = $this->rubrics[$competition->id] ?? $this->rubrics['default'];

        return response()->json([
            'success' => true,
            'competition_id' => $competition->id,
            'competition_title' => $competition->title,
            'is_custom' => $isCustom,
            'rubric_type' => $details['type'],
            'active_rubric' => $details['criteria'],
            'default_rubric' => $defaultRubric,
        ]);
    }

    /**
     * Update/Customize rubric criteria and weights for a competition.
     */
    public function updateRubric(Request $request, $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);

        $request->validate([
            'rubric_type' => 'nullable|in:percentage,point,score',
            'criteria_names' => 'required|array|min:1',
            'criteria_names.*' => 'required|string|max:150',
            'criteria_weights' => 'required|array|min:1',
            'criteria_weights.*' => 'required|numeric|min:0.5|max:1000',
        ]);

        $rubricType = in_array($request->rubric_type, ['point', 'score']) ? 'point' : 'percentage';

        $criteria = [];
        $names = $request->criteria_names;
        $weights = $request->criteria_weights;

        foreach ($names as $idx => $name) {
            $nameTrimmed = trim($name);
            if ($nameTrimmed !== '') {
                $criteria[$nameTrimmed] = floatval($weights[$idx] ?? ($rubricType === 'point' ? 100 : 10));
            }
        }

        $competition->scoring_rubrics = [
            'type' => $rubricType,
            'criteria' => $criteria,
        ];
        $competition->save();

        $typeLabel = $rubricType === 'point' ? 'Skor/Poin Maksimal' : 'Prosentase (%)';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Rubrik kriteria ({$typeLabel}) untuk cabang {$competition->title} berhasil disimpan & disesuaikan!",
                'rubric_type' => $rubricType,
                'rubric' => $criteria
            ]);
        }

        return redirect()->back()->with('success', "Rubrik kriteria ({$typeLabel}) untuk cabang {$competition->title} berhasil disimpan & disesuaikan!");
    }

    /**
     * Reset competition rubric back to official Juknis defaults.
     */
    public function resetRubric(Request $request, $competitionId)
    {
        $competition = Competition::findOrFail($competitionId);
        $competition->scoring_rubrics = null;
        $competition->save();

        $defaultRubric = $this->rubrics[$competition->id] ?? $this->rubrics['default'];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Rubrik kriteria untuk cabang {$competition->title} berhasil direset ke standar resmi Juknis (Prosentase)!",
                'rubric_type' => 'percentage',
                'rubric' => $defaultRubric
            ]);
        }

        return redirect()->back()->with('success', "Rubrik kriteria untuk cabang {$competition->title} berhasil direset ke standar resmi Juknis!");
    }

    /**
     * Store or update digital jury score.
     */
    public function storeScore(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'competition_id' => 'required|exists:competitions,id',
            'jury_name' => 'required|string|max:100',
            'criteria_scores' => 'nullable|array',
            'scores' => 'nullable|array',
            'performance_duration' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $scores = $request->input('criteria_scores') ?? $request->input('scores', []);
        $competition = Competition::findOrFail($request->competition_id);
        $rubrics = $this->getCompetitionRubricDetails($competition)['criteria'];

        $total = 0;
        foreach ($scores as $criterion => $val) {
            $weight = $rubrics[$criterion] ?? 0;
            $total += (floatval($val) * floatval($weight)) / 100;
        }

        $score = JuryScore::updateOrCreate(
            [
                'registration_id' => $request->registration_id,
                'competition_id' => $request->competition_id,
            ],
            [
                'jury_name' => $request->jury_name,
                'criteria_scores' => $scores,
                'total_score' => round($total, 2),
                'performance_duration' => $request->input('performance_duration', '00:00'),
                'is_locked' => $request->boolean('is_locked', true),
                'notes' => $request->notes
            ]
        );

        $score->load('registration', 'competition');

        // Broadcast to Reverb WebSocket channel instantly
        broadcast(new \App\Events\JuryScoreSubmitted($score));

        return redirect()->back()->with('success', "Penilaian juri untuk {$score->registration->school_name} berhasil disimpan! Total Skor: {$score->total_score}");
    }
}
