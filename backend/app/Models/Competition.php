<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $table = 'competitions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'category',
        'category_label',
        'icon',
        'fee',
        'max_quota',
        'current_quota',
        'status',
        'participant_type',
        'max_teams',
        'max_participants',
        'registration_deadline',
        'participants_rule',
        'tm_date',
        'contact_person',
        'coordinator_id',
        'coordinators',
        'description',
        'scoring_rubrics',
        'custom_fields',
        'form_settings',
        'tm_settings',
        'registration_type',
        'direct_registration_note',
        'juknis_file',
        'juknis_original_name',
        'juknis_size',
    ];

    protected $casts = [
        'fee' => 'integer',
        'max_quota' => 'integer',
        'current_quota' => 'integer',
        'max_teams' => 'integer',
        'max_participants' => 'integer',
        'juknis_size' => 'integer',
        'coordinators' => 'array',
        'scoring_rubrics' => 'array',
        'custom_fields' => 'array',
        'form_settings' => 'array',
        'tm_settings' => 'array'
    ];

    protected $appends = [
        'is_deadline_passed',
        'is_quota_full',
        'is_registration_closed',
        'is_manually_closed',
        'quota_status_text',
        'is_tournament',
        'is_direct_registration',
        'direct_contact_wa_url',
        'primary_coordinator',
        'resolved_tm_settings',
        'juknis_url',
        'juknis_filename',
    ];

    public function isDeadlinePassed(): bool
    {
        if (empty($this->registration_deadline) || $this->registration_deadline === '-') {
            return false;
        }

        try {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $this->registration_deadline, $m)) {
                $deadline = \Carbon\Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->endOfDay();
            } else {
                $deadline = \Carbon\Carbon::parse($this->registration_deadline)->endOfDay();
            }
            return \Carbon\Carbon::now()->greaterThan($deadline);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isQuotaFull(): bool
    {
        return $this->max_quota > 0 && $this->current_quota >= $this->max_quota;
    }

    public function isRegistrationClosed(): bool
    {
        return $this->status === 'closed' || $this->isDeadlinePassed() || $this->isQuotaFull();
    }

    public function isManuallyClosed(): bool
    {
        return $this->status === 'closed' && !$this->isDeadlinePassed() && !$this->isQuotaFull();
    }

    public function getIsDeadlinePassedAttribute(): bool
    {
        return $this->isDeadlinePassed();
    }

    public function getIsQuotaFullAttribute(): bool
    {
        return $this->isQuotaFull();
    }

    public function getIsRegistrationClosedAttribute(): bool
    {
        return $this->isRegistrationClosed();
    }

    public function getIsManuallyClosedAttribute(): bool
    {
        return $this->isManuallyClosed();
    }

    public function getQuotaStatusTextAttribute(): string
    {
        if (!$this->isRegistrationClosed() && $this->status === 'open') {
            return 'Buka Pendaftaran';
        }

        if ($this->isQuotaFull()) {
            return 'Kuota Penuh';
        }

        if ($this->isDeadlinePassed()) {
            return 'Waktu Habis';
        }

        // Tanggal masih berlaku dan kuota masih tersedia, tapi pendaftaran ditutup secara manual
        return 'Ditutup Panitia';
    }

    public function getIsTournamentAttribute(): bool
    {
        return $this->isTournament();
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'competition_id', 'id');
    }

    public function tournamentMatches()
    {
        return $this->hasMany(TournamentMatch::class, 'competition_id', 'id')->orderBy('round_order')->orderBy('id');
    }

    public function getResolvedTmSettingsAttribute(): array
    {
        $settings = $this->tm_settings ?? [];

        // 1. Resolve date and time from tm_date if not explicitly set in tm_settings
        $parsedDate = $settings['tm_date'] ?? null;
        $parsedTime = $settings['tm_time'] ?? null;

        if (empty($parsedDate) || empty($parsedTime)) {
            $raw = $this->tm_date ?? '';
            
            // Extract time (e.g. 08:00, 14:00)
            if (empty($parsedTime) && preg_match('/(\d{1,2})[:.](\d{2})/', $raw, $m)) {
                $parsedTime = sprintf('%02d:%02d WIB', (int)$m[1], (int)$m[2]);
            }

            // Extract date (e.g. 07/01/2026, 12/10/2026, 07 Januari 2026)
            if (empty($parsedDate)) {
                if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $raw, $m)) {
                    $parsedDate = sprintf('%02d/%02d/%04d', (int)$m[1], (int)$m[2], (int)$m[3]);
                } elseif (preg_match('/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/', $raw, $m)) {
                    $months = [
                        'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
                        'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
                        'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
                        'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
                        'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
                        'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12'
                    ];
                    $mName = strtolower($m[2]);
                    $monthNum = $months[$mName] ?? '10';
                    $parsedDate = sprintf('%02d/%s/%04d', (int)$m[1], $monthNum, (int)$m[3]);
                } else {
                    $parsedDate = '26/10/2026'; // Default TM date for Semarak XIV
                }
            }
        }

        // 2. Resolve PIC and contact from coordinators or contact_person
        $picName = $settings['pic_name'] ?? null;
        $picContact = $settings['pic_contact'] ?? null;

        if (!empty($picName) && preg_match('/^[0-9\s\+\-]+$/', trim($picName))) {
            if (empty($picContact)) $picContact = trim($picName);
            $picName = null;
        }

        if (empty($picName) || empty($picContact)) {
            $firstCoord = !empty($this->coordinators) && is_array($this->coordinators) ? $this->coordinators[0] : null;
            if ($firstCoord) {
                if (empty($picName)) $picName = $firstCoord['name'] ?? null;
                if (empty($picContact)) $picContact = $firstCoord['contact'] ?? null;
            } elseif (!empty($this->contact_person)) {
                if (preg_match('/^(.*?)\((.*?)\)$/', $this->contact_person, $m)) {
                    if (empty($picName)) $picName = trim($m[1]);
                    if (empty($picContact)) $picContact = trim($m[2]);
                } elseif (preg_match('/^[0-9\s\+\-]+$/', trim($this->contact_person))) {
                    if (empty($picContact)) $picContact = trim($this->contact_person);
                    if (empty($picName)) $picName = 'Koordinator ' . $this->title;
                } else {
                    if (empty($picName)) $picName = trim($this->contact_person);
                }
            }
        }

        // 3. Resolve location contextual to competition category
        $location = $settings['location'] ?? null;
        if (empty($location)) {
            if ($this->category === 'olahraga') {
                $location = 'Gedung Olahraga / Lapangan Utama Madrasah';
            } elseif ($this->id === 'mobile-legends') {
                $location = 'Lab Komputer Multimedia (Online Hybrid)';
            } elseif ($this->category === 'seni') {
                $location = 'Panggung Kesenian / Aula Pertemuan Lt. 2';
            } else {
                $location = 'Aula Utama Madrasah Lt. 2';
            }
        }

        // 4. Resolve rules_summary from participants_rule and description
        $rulesSummary = $settings['rules_summary'] ?? null;
        if (empty($rulesSummary)) {
            $parts = [];
            if (!empty($this->participants_rule)) {
                $parts[] = "Aturan Delegasi: " . $this->participants_rule;
            }
            if (!empty($this->description)) {
                $parts[] = $this->description;
            }
            $rulesSummary = implode("\n", $parts);
        }

        // 5. Resolve TM Agenda
        $agenda = $settings['agenda'] ?? null;
        if (empty($agenda)) {
            if ($this->id === 'mobile-legends') {
                $agenda = 'Penentuan bagan bracket turnamen, aturan draft pick tournament mode, check-in ID & verifikasi kartu pelajar.';
            } elseif ($this->category === 'olahraga' || str_contains($this->id, 'volleyball')) {
                $agenda = 'Drawing bagan pertandingan sistem gugur, verifikasi berkas pemain, penegasan regulasi resmi PBVSI.';
            } elseif ($this->category === 'akademik') {
                $agenda = 'Tata tertib ujian berbasis CBT, kisi-kisi babak penyisihan, pembagian sesi waktu & teknis babak final.';
            } elseif ($this->id === 'albanjari') {
                $agenda = 'Pengundian nomor urut tampil, penetapan maqra\' & lagu, pembagian jadwal check-sound panggung.';
            } elseif ($this->id === 'singer') {
                $agenda = 'Pengundian urutan peserta, penyerahan minus-one/partitur lagu, uji coba tata suara panggung teater.';
            } elseif ($this->id === 'storytelling') {
                $agenda = 'Pengundian nomor urut tampil, penyerahan sinopsis naskah, teknis penilaian dewan juri.';
            } else {
                $agenda = 'Penjelasan petunjuk teknis (Juknis), tata tertib kompetisi, verifikasi berkas delegasi, dan pengundian nomor urut.';
            }
        }

        return [
            'tm_date' => $parsedDate ?: '26/10/2026',
            'tm_time' => $parsedTime ?: ($this->id === 'mobile-legends' ? '14:00 WIB' : '08:00 WIB'),
            'location' => $location,
            'meeting_type' => $settings['meeting_type'] ?? ($this->id === 'mobile-legends' ? 'hybrid' : 'offline'),
            'meeting_link' => $settings['meeting_link'] ?? '',
            'whatsapp_group' => $settings['whatsapp_group'] ?? '',
            'status' => $settings['status'] ?? 'upcoming',
            'pic_name' => $picName ?: 'Koordinator ' . $this->title,
            'pic_contact' => $picContact ?: '0838 5007 0767',
            'notes' => $settings['notes'] ?? '',
            'agenda' => $agenda,
            'rules_summary' => $rulesSummary,
        ];
    }

    public function isTournament(): bool
    {
        return in_array($this->id, ['volleyball-putra', 'volleyball-putri', 'mobile-legends']) 
            || $this->category === 'olahraga' 
            || str_contains(strtolower($this->title), 'tournament');
    }

    public function getJuknisUrlAttribute(): ?string
    {
        if (!empty($this->juknis_file)) {
            return asset($this->juknis_file);
        }
        if (!empty($this->tm_settings['juknis_file'])) {
            return asset($this->tm_settings['juknis_file']);
        }
        return null;
    }

    public function getJuknisFilenameAttribute(): ?string
    {
        if (!empty($this->juknis_original_name)) {
            return $this->juknis_original_name;
        }
        if (!empty($this->tm_settings['juknis_filename'])) {
            return $this->tm_settings['juknis_filename'];
        }
        if (!empty($this->juknis_file)) {
            return basename($this->juknis_file);
        }
        return null;
    }

    public function isDirectRegistration(): bool
    {
        return ($this->registration_type ?? 'online') === 'direct_contact';
    }

    public function getIsDirectRegistrationAttribute(): bool
    {
        return $this->isDirectRegistration();
    }

    public function getPrimaryCoordinatorAttribute(): ?array
    {
        if (!empty($this->coordinators) && is_array($this->coordinators) && count($this->coordinators) > 0) {
            foreach ($this->coordinators as $c) {
                if (!empty($c['name']) || !empty($c['contact'])) {
                    return $c;
                }
            }
        }

        $tm = $this->resolved_tm_settings ?? [];
        if (!empty($tm['pic_name']) || !empty($tm['pic_contact'])) {
            return [
                'name' => $tm['pic_name'] ?? ('Koordinator ' . $this->title),
                'contact' => $tm['pic_contact'] ?? '0838 5007 0767',
                'role' => 'Koordinator Lomba',
            ];
        }

        return null;
    }

    public function getDirectContactWaUrlAttribute(): ?string
    {
        $primary = $this->primary_coordinator;
        $phone = $primary['contact'] ?? null;
        if (empty($phone)) {
            $phone = '0838 5007 0767';
        }

        $cleanPhone = preg_replace('/\D/', '', (string)$phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (!str_starts_with($cleanPhone, '62')) {
            $cleanPhone = '62' . $cleanPhone;
        }

        $coordName = $primary['name'] ?? ('Koordinator ' . $this->title);
        $text = "Halo {$coordName}, saya ingin mendaftarkan delegasi sekolah kami untuk cabang lomba {$this->title} di Semarak Aliyah XIV 2026. Mohon informasi petunjuk teknis pendaftaran dan kuota cabang ini. Terima kasih.";

        return "https://wa.me/{$cleanPhone}?text=" . rawurlencode($text);
    }

    public function coordinator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function assignedCoordinators(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'competition_id');
    }
}
