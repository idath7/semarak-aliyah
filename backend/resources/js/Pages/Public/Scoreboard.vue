<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { initEcho } from '@/echo';
import { useTheme } from '@/Composables/useTheme';

const { isDark, initTheme, toggleTheme } = useTheme();

const page = usePage();
const school = computed(() => page.props.schoolProfile || {});

const eventInfo = computed(() => {
  const name = (school.value.event_name || 'SEMARAK ALIYAH XIV 2026').trim();
  const match = name.match(/^(.*?)\s+([IVXLCDM]+|\d+)(?:\s+(\d{4}))?$/i);
  if (match) {
    return {
      base: match[1],
      edition: match[2].toUpperCase(),
      year: match[3] || '',
      full: name
    };
  }
  return {
    base: name,
    edition: '',
    year: '',
    full: name
  };
});

const props = defineProps({
  competitions: {
    type: Array,
    default: () => []
  },
  selectedCompetition: {
    type: Object,
    default: () => null
  },
  leaderboard: {
    type: Array,
    default: () => []
  },
  isTournament: {
    type: Boolean,
    default: false
  },
  activeMatch: {
    type: Object,
    default: () => null
  },
  recentMatches: {
    type: Array,
    default: () => []
  },
  topJuryScores: {
    type: Array,
    default: () => []
  },
});

const currentMatch = ref(props.activeMatch || null);
const recentList = ref(props.recentMatches || []);
const juryScoresList = ref(props.leaderboard?.length ? props.leaderboard : (props.topJuryScores || []));
const wsStatus = ref('connecting');
const currentTime = ref('');
const isFullscreen = ref(false);

watch(() => props.activeMatch, (newVal) => {
  currentMatch.value = newVal || null;
});

watch(() => props.recentMatches, (newVal) => {
  recentList.value = newVal || [];
});

watch(() => [props.leaderboard, props.topJuryScores], () => {
  juryScoresList.value = props.leaderboard?.length ? props.leaderboard : (props.topJuryScores || []);
});

watch(() => props.selectedCompetition, (newComp) => {
  if (newComp?.id) {
    scrollToActivePill(newComp.id);
  }
});

function updateTime() {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
}

function scrollToActivePill(compId) {
  nextTick(() => {
    setTimeout(() => {
      const id = compId || props.selectedCompetition?.id;
      const targetBtn = id ? document.getElementById('comp-pill-' + id) : document.querySelector('.comp-pill-btn.active');
      if (targetBtn) {
        targetBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }
    }, 60);
  });
}

function selectCompetition(comp) {
  if (typeof window !== 'undefined' && window.sessionStorage) {
    sessionStorage.setItem('scoreboard_selected_competition', comp.id);
  }
  router.get('/scoreboard', { competition: comp.id }, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      scrollToActivePill(comp.id);
    }
  });
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().then(() => {
      isFullscreen.value = true;
    }).catch(() => {});
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().then(() => {
        isFullscreen.value = false;
      }).catch(() => {});
    }
  }
}

function getCompIcon(comp) {
  if (!comp) return 'ri-trophy-line';
  const id = String(comp.id || '').toLowerCase();
  const title = String(comp.title || '').toLowerCase();
  if (id.includes('voli') || title.includes('voli') || title.includes('volley')) return 'ri-football-line';
  if (id.includes('ml') || id.includes('esport') || title.includes('mobile legends')) return 'ri-gamepad-line';
  if (id.includes('mipa') || title.includes('mipa')) return 'ri-microscope-line';
  if (id.includes('ips') || title.includes('ips')) return 'ri-earth-line';
  if (id.includes('story') || title.includes('story')) return 'ri-mic-line';
  if (id.includes('banjari') || title.includes('banjari')) return 'ri-music-2-line';
  if (id.includes('singer') || title.includes('singer')) return 'ri-mv-line';
  if (id.includes('tahfidz') || title.includes('tahfidz')) return 'ri-book-open-line';
  return 'ri-trophy-line';
}

function isTournamentComp(comp) {
  if (!comp) return false;
  if (typeof comp.is_tournament === 'boolean') return comp.is_tournament;
  const id = String(comp.id || '').toLowerCase();
  const title = String(comp.title || '').toLowerCase();
  const cat = String(comp.category || '').toLowerCase();
  return ['volleyball-putra', 'volleyball-putri', 'mobile-legends'].includes(id) 
    || cat === 'olahraga' 
    || title.includes('tournament');
}

let timerInterval = null;
let echoScoreboardChannel = null;

onMounted(() => {
  initTheme();

  updateTime();
  timerInterval = setInterval(updateTime, 1000);

  // Auto-scroll active pill into view so user doesn't have to scroll from the beginning
  scrollToActivePill();

  // If there's a saved competition in client sessionStorage and URL has none, sync it
  if (typeof window !== 'undefined' && window.sessionStorage) {
    const savedCompId = sessionStorage.getItem('scoreboard_selected_competition');
    const params = new URLSearchParams(window.location.search);
    if (!params.has('competition') && savedCompId && props.selectedCompetition?.id !== savedCompId) {
      const found = props.competitions?.find(c => c.id === savedCompId);
      if (found) {
        selectCompetition(found);
      }
    }
  }

  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = !!document.fullscreenElement;
  });

  try {
    const echo = initEcho();
    if (echo) {
      echoScoreboardChannel = echo.channel('scoreboard');
      
      // Match Updated Event
      echoScoreboardChannel.listen('.match.updated', (data) => {
        if (data?.match) {
          currentMatch.value = data.match;
          
          const idx = recentList.value.findIndex(m => m.id === data.match.id);
          if (idx !== -1) {
            recentList.value[idx] = data.match;
          } else {
            recentList.value.unshift(data.match);
          }
        }
      });

      // Jury Score Submitted Event
      echoScoreboardChannel.listen('.jury.score.submitted', (data) => {
        if (data?.score) {
          const idx = juryScoresList.value.findIndex(s => s.id === data.score.id);
          if (idx !== -1) {
            juryScoresList.value[idx] = data.score;
          } else {
            juryScoresList.value.unshift(data.score);
          }
          // Sort descending by total_score
          juryScoresList.value.sort((a, b) => Number(b.total_score || 0) - Number(a.total_score || 0));
        }
      });

      wsStatus.value = 'connected';
    } else {
      wsStatus.value = 'standby';
    }
  } catch (err) {
    console.warn('Reverb Scoreboard connection error:', err);
    wsStatus.value = 'error';
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (echoScoreboardChannel) {
    try {
      const echo = initEcho();
      echo.leaveChannel('scoreboard');
    } catch(e) {}
  }
});
</script>

<template>
  <div class="scoreboard-fullwidth-app" :class="{ 'dark-theme': isDark }" :data-theme="isDark ? 'dark' : 'light'">
    <Head :title="'Live Scoreboard & Klasemen Resmi | ' + (school.event_name || 'Semarak Aliyah XIV 2026')" />

    <!-- Top Sticky Arena Bar with Pintasan Kembali ke Website -->
    <header class="scoreboard-topbar">
      <div class="scoreboard-topbar-fluid">
        <div class="topbar-brand-group">
          <!-- Pintasan Kembali ke Halaman Web -->
          <Link href="/" class="btn-back-website" title="Kembali ke Beranda Website Utama">
            <i class="ri-arrow-left-line"></i>
            <span>Kembali ke Website</span>
          </Link>

          <div class="topbar-divider"></div>

          <!-- Brand Identity -->
          <div class="topbar-identity">
            <img src="/assets/images/logo/logo-semarak.png" alt="Logo Semarak Aliyah" class="topbar-logo-img" />
            <div class="topbar-identity-text">
              <span class="identity-title">
                {{ eventInfo.base }}
                <span v-if="eventInfo.edition" class="badge-edition">{{ eventInfo.edition }}</span>
              </span>
              <span class="identity-sub">{{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }} • Live Arena</span>
            </div>
          </div>
        </div>

        <div class="topbar-controls-group">
          <!-- Mode Terang / Gelap Toggle -->
          <button 
            type="button" 
            class="btn-theme-toggle" 
            @click="toggleTheme" 
            :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
          >
            <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line'"></i>
            <span>{{ isDark ? 'Mode Terang' : 'Mode Gelap' }}</span>
          </button>

          <!-- Digital Clock Arena -->
          <div class="topbar-clock-box">
            <i class="ri-time-fill text-orange"></i>
            <span class="clock-label">WAKTU ARENA:</span>
            <span class="clock-val">{{ currentTime }}</span>
          </div>

          <!-- WebSocket Live Status Badge -->
          <div 
            class="topbar-ws-badge"
            :class="{
              'ws-connected': wsStatus === 'connected',
              'ws-connecting': wsStatus === 'connecting',
              'ws-standby': wsStatus === 'standby' || wsStatus === 'error'
            }"
          >
            <span class="ws-dot"></span>
            <span>{{ wsStatus === 'connected' ? 'WebSocket Aktif' : wsStatus === 'connecting' ? 'Menghubungkan...' : 'Live Polling' }}</span>
          </div>

          <!-- Mode Proyektor Fullscreen Toggle -->
          <button 
            type="button" 
            class="btn-projector-toggle" 
            @click="toggleFullscreen"
            :title="isFullscreen ? 'Keluar Mode Layar Penuh' : 'Buka Mode Proyektor Layar Penuh'"
          >
            <i :class="isFullscreen ? 'ri-fullscreen-exit-line' : 'ri-fullscreen-line'"></i>
            <span>{{ isFullscreen ? 'Keluar Layar Penuh' : 'Mode Proyektor' }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Fullwidth Scoreboard Body -->
    <main class="scoreboard-main-wrap">
      <div class="scoreboard-fluid-container">
        <!-- Top Toolbar & Header Banner -->
        <div class="scoreboard-header-card">
          <div class="scoreboard-header-left">
            <div class="scoreboard-tag">
              <span class="pulse-dot-green"></span>
              <span>LIVE ARENA • REALTIME SYNC</span>
            </div>
            <h1 class="scoreboard-main-title">
              Live Scoreboard & Klasemen Kejuaraan
            </h1>
            <p class="scoreboard-main-subtitle">
              Pantau perolehan skor pertandingan, nilai dewan juri, dan peringkat kontingen peserta secara langsung.
            </p>
          </div>

          <div class="scoreboard-header-right-badges">
            <div class="stat-mini-pill">
              <i class="ri-trophy-line text-orange"></i>
              <span>{{ competitions.length }} Cabang Lomba</span>
            </div>
            <div class="stat-mini-pill" v-if="selectedCompetition">
              <i class="ri-checkbox-circle-line text-green"></i>
              <span>Format: {{ isTournament ? 'Bagan Gugur (Tournament)' : 'Akumulasi Skor Juri' }}</span>
            </div>
          </div>
        </div>

        <!-- Competition Category Selector Bar (Pills) -->
        <div class="comp-selector-container">
          <div class="comp-selector-label">
            <i class="ri-filter-3-line"></i>
            <span>Pilih Cabang Lomba:</span>
          </div>

          <div class="comp-selector-scroll">
            <button 
              v-for="comp in competitions" 
              :key="comp.id"
              :id="'comp-pill-' + comp.id"
              type="button"
              class="comp-pill-btn"
              :class="{ 'active': selectedCompetition && selectedCompetition.id === comp.id }"
              @click="selectCompetition(comp)"
            >
              <i :class="getCompIcon(comp)"></i>
              <span>{{ comp.title }}</span>
              <span v-if="isTournamentComp(comp)" class="comp-type-badge badge-tournament">Tournament</span>
              <span v-else class="comp-type-badge badge-jury">Penjurian</span>
            </button>
          </div>
        </div>

        <!-- Active Competition Title Banner -->
        <div v-if="selectedCompetition" class="selected-comp-banner">
          <div class="selected-comp-left">
            <div class="selected-comp-icon">
              <i :class="getCompIcon(selectedCompetition)"></i>
            </div>
            <div>
              <div class="selected-comp-category">{{ selectedCompetition.category_label || selectedCompetition.category || 'Kompetisi Resmi' }}</div>
              <h2 class="selected-comp-title">{{ selectedCompetition.title }}</h2>
            </div>
          </div>

          <div class="selected-comp-meta">
            <div class="comp-meta-item">
              <span class="meta-label">Format:</span>
              <span class="meta-val">{{ isTournament ? 'Bagan Gugur (Tournament Match)' : 'Akumulasi Skor Juri (Leaderboard)' }}</span>
            </div>
            <div class="comp-meta-item" v-if="selectedCompetition.tm_date">
              <span class="meta-label">Technical Meeting:</span>
              <span class="meta-val">{{ selectedCompetition.tm_date }}</span>
            </div>
          </div>
        </div>

        <!-- ====================================================================
             CASE 1: TOURNAMENT COMPETITION (Volleyball, Mobile Legends, dsb.)
             ==================================================================== -->
        <div v-if="isTournament" class="tournament-arena-section">
          <!-- Active Stadium Electronic Scoreboard Card -->
          <div v-if="currentMatch" class="match-center-board">
            <div class="board-top-status">
              <div class="board-match-info">
                <span class="match-num-pill">{{ currentMatch.match_number }}</span>
                <span class="match-round-text">{{ currentMatch.round_name }} • {{ currentMatch.court || 'Lapangan Utama' }}</span>
              </div>
              <div class="board-live-indicator">
                <span class="status-pulse-dot" :class="{ 'pulse-active': currentMatch.status === 'ongoing' }"></span>
                <span class="status-text">
                  {{ currentMatch.status === 'ongoing' ? 'SEDANG BERTANDING' : currentMatch.status === 'completed' ? 'PERTANDINGAN SELESAI' : 'TERJADWAL' }}
                </span>
              </div>
            </div>

            <!-- Stadium Score Display -->
            <div class="stadium-score-grid">
              <!-- Team 1 Side -->
              <div class="team-side team-left">
                <div class="team-school-name truncate">
                  {{ currentMatch.team1?.school_name || currentMatch.team1_placeholder || 'TIM A' }}
                </div>
                <div class="team-delegates-text">
                  {{ currentMatch.team1?.participants_names ? currentMatch.team1.participants_names : 'Kontingen Resmi' }}
                </div>
                <div class="big-score-number number-orange">
                  {{ currentMatch.team1_score ?? 0 }}
                </div>
                <div class="set-score-info">
                  Poin Set: <strong>{{ currentMatch.team1_set_scores || '-' }}</strong>
                </div>
              </div>

              <!-- Center VS Divider -->
              <div class="team-vs-divider">
                <div class="vs-circle">VS</div>
                <div class="vs-subtitle">SET SCORE</div>
                <div class="vs-referee">
                  <i class="ri-user-voice-line"></i>
                  <span>Wasit: {{ currentMatch.referee_name || 'Dewan Wasit' }}</span>
                </div>
              </div>

              <!-- Team 2 Side -->
              <div class="team-side team-right">
                <div class="team-school-name truncate">
                  {{ currentMatch.team2?.school_name || currentMatch.team2_placeholder || 'TIM B' }}
                </div>
                <div class="team-delegates-text">
                  {{ currentMatch.team2?.participants_names ? currentMatch.team2.participants_names : 'Kontingen Resmi' }}
                </div>
                <div class="big-score-number number-purple">
                  {{ currentMatch.team2_score ?? 0 }}
                </div>
                <div class="set-score-info">
                  Poin Set: <strong>{{ currentMatch.team2_set_scores || '-' }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty Tournament Active Match State -->
          <div v-else class="empty-arena-card">
            <div class="empty-icon-wrap icon-purple">
              <i class="ri-trophy-line"></i>
            </div>
            <h3 class="empty-title">Menunggu Pertandingan Dimulai</h3>
            <p class="empty-desc">
              Skor dan status laga akan otomatis tampil di layar ini secara realtime saat dewan wasit memulai pertandingan di arena.
            </p>
          </div>

          <!-- Tournament Matches Schedule & Recent Results -->
          <div v-if="recentList && recentList.length > 0" class="recent-matches-box">
            <h3 class="recent-matches-title">
              <i class="ri-history-line"></i>
              <span>Daftar Pertandingan & Hasil Babak</span>
            </h3>

            <div class="recent-matches-grid">
              <div 
                v-for="m in recentList" 
                :key="m.id" 
                class="match-summary-card"
                :class="{ 'card-ongoing': m.status === 'ongoing' }"
              >
                <div class="summary-top">
                  <span class="summary-match-num">{{ m.match_number }}</span>
                  <span class="summary-round-name">{{ m.round_name }}</span>
                  <span class="summary-status-pill" :class="m.status">
                    {{ m.status === 'ongoing' ? 'Live' : m.status === 'completed' ? 'Selesai' : 'Jadwal' }}
                  </span>
                </div>

                <div class="summary-teams">
                  <div class="summary-team-row" :class="{ 'is-winner': m.winner_registration_id && m.winner_registration_id === m.team1_registration_id }">
                    <span class="team-name truncate">{{ m.team1?.school_name || m.team1_placeholder || 'TIM A' }}</span>
                    <span class="team-score-badge">{{ m.team1_score ?? '-' }}</span>
                  </div>
                  <div class="summary-team-row" :class="{ 'is-winner': m.winner_registration_id && m.winner_registration_id === m.team2_registration_id }">
                    <span class="team-name truncate">{{ m.team2?.school_name || m.team2_placeholder || 'TIM B' }}</span>
                    <span class="team-score-badge">{{ m.team2_score ?? '-' }}</span>
                  </div>
                </div>

                <div class="summary-footer" v-if="m.court || m.scheduled_time">
                  <span><i class="ri-map-pin-line"></i> {{ m.court || 'Arena' }}</span>
                  <span v-if="m.scheduled_time"><i class="ri-time-line"></i> {{ m.scheduled_time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ====================================================================
             CASE 2: JURY SCORES LEADERBOARD (Olimpiade, Albanjari, Singer, dll)
             ==================================================================== -->
        <div v-else class="jury-leaderboard-section">
          <!-- Top 3 Podium (if >= 3 participants scored) -->
          <div v-if="juryScoresList && juryScoresList.length >= 3" class="podium-grid">
            <!-- 2nd Place -->
            <div class="podium-card podium-silver">
              <div class="podium-rank-badge rank-2">
                <i class="ri-medal-fill"></i> Juara 2
              </div>
              <h4 class="podium-school-name">{{ juryScoresList[1]?.registration?.school_name || 'Kontingen Sekolah' }}</h4>
              <p class="podium-participants">{{ juryScoresList[1]?.registration?.participants_names || '-' }}</p>
              <div class="podium-score-val">{{ Number(juryScoresList[1]?.total_score || 0).toFixed(2) }}</div>
              <span class="podium-score-label">Total Poin</span>
            </div>

            <!-- 1st Place (Champion) -->
            <div class="podium-card podium-gold">
              <div class="crown-icon-wrap">
                <i class="ri-vip-crown-fill"></i>
              </div>
              <div class="podium-rank-badge rank-1">
                <i class="ri-trophy-fill"></i> Juara 1
              </div>
              <h4 class="podium-school-name">{{ juryScoresList[0]?.registration?.school_name || 'Kontingen Sekolah' }}</h4>
              <p class="podium-participants">{{ juryScoresList[0]?.registration?.participants_names || '-' }}</p>
              <div class="podium-score-val gold-score">{{ Number(juryScoresList[0]?.total_score || 0).toFixed(2) }}</div>
              <span class="podium-score-label">Total Poin Tertinggi</span>
            </div>

            <!-- 3rd Place -->
            <div class="podium-card podium-bronze">
              <div class="podium-rank-badge rank-3">
                <i class="ri-medal-fill"></i> Juara 3
              </div>
              <h4 class="podium-school-name">{{ juryScoresList[2]?.registration?.school_name || 'Kontingen Sekolah' }}</h4>
              <p class="podium-participants">{{ juryScoresList[2]?.registration?.participants_names || '-' }}</p>
              <div class="podium-score-val">{{ Number(juryScoresList[2]?.total_score || 0).toFixed(2) }}</div>
              <span class="podium-score-label">Total Poin</span>
            </div>
          </div>

          <!-- Full Leaderboard Table -->
          <div v-if="juryScoresList && juryScoresList.length > 0" class="leaderboard-table-card">
            <div class="table-header-bar">
              <h3 class="table-heading">
                <i class="ri-list-ordered"></i>
                <span>Peringkat Nilai Resmi Dewan Juri</span>
              </h3>
              <span class="table-count-badge">{{ juryScoresList.length }} Kontingen Ternilai</span>
            </div>

            <div class="table-responsive-box">
              <table class="scoreboard-table">
                <thead>
                  <tr>
                    <th style="width: 80px; text-align: center;">Peringkat</th>
                    <th>Asal Sekolah / Madrasah</th>
                    <th>Nama Delegasi / Peserta</th>
                    <th>Dewan Juri</th>
                    <th style="text-align: right; width: 140px;">Total Skor</th>
                    <th style="text-align: center; width: 130px;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(score, index) in juryScoresList" :key="score.id">
                    <td style="text-align: center;">
                      <span 
                        class="rank-pill"
                        :class="{
                          'rank-gold': index === 0,
                          'rank-silver': index === 1,
                          'rank-bronze': index === 2,
                          'rank-default': index > 2
                        }"
                      >
                        #{{ index + 1 }}
                      </span>
                    </td>
                    <td>
                      <div class="school-cell-name font-bold">{{ score.registration?.school_name || 'Delegasi Peserta' }}</div>
                      <div class="reg-code-sub text-muted font-mono">{{ score.registration?.reg_code || '-' }}</div>
                    </td>
                    <td>
                      <div class="participants-cell">{{ score.registration?.participants_names || '-' }}</div>
                    </td>
                    <td>
                      <div class="jury-cell-name">
                        <i class="ri-user-star-line text-purple"></i>
                        <span>{{ score.jury_name || 'Dewan Juri' }}</span>
                      </div>
                    </td>
                    <td style="text-align: right;">
                      <span class="total-score-badge">
                        {{ Number(score.total_score || 0).toFixed(2) }}
                      </span>
                    </td>
                    <td style="text-align: center;">
                      <span class="score-status-badge" :class="score.is_locked ? 'status-locked' : 'status-draft'">
                        <i :class="score.is_locked ? 'ri-lock-line' : 'ri-time-line'"></i>
                        {{ score.is_locked ? 'Nilai Sah' : 'Sementara' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Empty Jury Scores State -->
          <div v-else class="empty-arena-card">
            <div class="empty-icon-wrap icon-orange">
              <i class="ri-file-list-3-line"></i>
            </div>
            <h3 class="empty-title">Belum Ada Nilai yang Diterbitkan</h3>
            <p class="empty-desc">
              Peringkat dan nilai resmi dewan juri untuk cabang lomba ini akan otomatis tersinkronisasi saat sesi penilaian dimulai di panggung.
            </p>
          </div>
        </div>

        <!-- Footer Minimal Fullwidth -->
        <footer class="scoreboard-minimal-footer">
          <div class="footer-minimal-inner">
            <div class="footer-left-info">
              <img src="/assets/images/logo/logo-semarak.png" alt="Logo" class="footer-mini-logo" />
              <span>© {{ eventInfo.year || '2026' }} <strong>{{ school.event_name || 'SEMARAK ALIYAH XIV' }}</strong> • {{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }}. Papan Skor Resmi Kejuaraan.</span>
            </div>
            <Link href="/" class="footer-back-link">
              <i class="ri-arrow-left-line"></i> Kembali ke Beranda Website
            </Link>
          </div>
        </footer>

      </div>
    </main>
  </div>
</template>

<style scoped>
.scoreboard-fullwidth-app {
  min-height: 100vh;
  background-color: #f8fafc;
  display: flex;
  flex-direction: column;
}

/* Sticky Topbar */
.scoreboard-topbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: #ffffff;
  border-bottom: 1px solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}

.scoreboard-topbar-fluid {
  width: 100%;
  padding: 12px clamp(16px, 2.5vw, 40px);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.topbar-brand-group {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.btn-back-website {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 18px;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 9999px;
  color: #1e293b;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.btn-back-website:hover {
  background: #fff1f2;
  border-color: var(--primary-pink);
  color: var(--primary-pink);
  transform: translateX(-3px);
}

.topbar-divider {
  width: 1px;
  height: 28px;
  background: #e2e8f0;
}

.topbar-identity {
  display: flex;
  align-items: center;
  gap: 12px;
}

.topbar-logo-img {
  width: 38px;
  height: 38px;
  object-fit: contain;
}

.identity-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: var(--font-heading);
  font-size: 1rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.badge-edition {
  display: inline-block;
  padding: 1px 6px;
  background: var(--grad-pink-orange);
  color: #ffffff;
  font-size: 0.65rem;
  font-weight: 800;
  border-radius: 6px;
}

.identity-sub {
  display: block;
  font-size: 0.74rem;
  font-weight: 600;
  color: #64748b;
}

.topbar-controls-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.topbar-clock-box {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  padding: 6px 14px;
  border-radius: 9999px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #c2410c;
}

.clock-label {
  font-size: 0.72rem;
  color: #ea580c;
  font-weight: 800;
}

.clock-val {
  font-family: var(--font-heading);
  font-weight: 800;
}

.topbar-ws-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 14px;
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 700;
  transition: all 0.25s ease;
}

.topbar-ws-badge.ws-connected {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.topbar-ws-badge.ws-connected .ws-dot {
  background: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
}

.topbar-ws-badge.ws-connecting {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
}

.topbar-ws-badge.ws-connecting .ws-dot {
  background: #f59e0b;
}

.topbar-ws-badge.ws-standby {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #cbd5e1;
}

.topbar-ws-badge.ws-standby .ws-dot {
  background: #94a3b8;
}

.ws-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}

/* Fullwidth Main Wrap & Fluid Container */
.scoreboard-main-wrap {
  flex: 1;
  padding: 24px 0 48px;
}

.scoreboard-fluid-container {
  width: 100%;
  max-width: 100%;
  padding: 0 clamp(16px, 2.5vw, 40px);
}

.scoreboard-header-right-badges {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.stat-mini-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 8px 16px;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
}

/* Minimal Footer */
.scoreboard-minimal-footer {
  margin-top: 48px;
  padding: 24px 0 16px;
  border-top: 1px solid #e2e8f0;
}

.footer-minimal-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
}

.footer-left-info {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.85rem;
  color: #64748b;
}

.footer-mini-logo {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.footer-back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--primary-pink);
  text-decoration: none;
  transition: all 0.2s ease;
}

.footer-back-link:hover {
  color: var(--primary-orange);
  transform: translateX(-3px);
}

/* Header Card */
.scoreboard-header-card {
  background: #ffffff;
  border: 1px solid var(--border-glass);
  border-radius: var(--radius-xl);
  padding: 32px 36px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
  box-shadow: var(--shadow-sm);
  margin-bottom: 28px;
  position: relative;
  overflow: hidden;
}

.scoreboard-header-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--grad-full-spectrum);
}

.scoreboard-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 5px 14px;
  border-radius: 9999px;
  margin-bottom: 12px;
}

.pulse-dot-green {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: pulseGreen 1.8s infinite;
}

@keyframes pulseGreen {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.scoreboard-main-title {
  font-family: var(--font-heading);
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin-bottom: 8px;
}

.scoreboard-main-subtitle {
  font-size: 0.92rem;
  color: var(--text-secondary);
  line-height: 1.55;
  max-width: 600px;
}



.btn-projector-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 9999px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-projector-toggle:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* Competition Category Tabs */
.comp-selector-container {
  margin-bottom: 24px;
}

.comp-selector-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.comp-selector-scroll {
  display: flex;
  align-items: center;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 10px;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f8fafc;
}

.comp-selector-scroll::-webkit-scrollbar {
  height: 6px;
}

.comp-selector-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 9999px;
}

.comp-selector-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}

.comp-selector-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.comp-pill-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 9999px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}

.comp-pill-btn:hover {
  border-color: #cbd5e1;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

.comp-pill-btn.active {
  background: linear-gradient(135deg, #ec4899 0%, #f97316 100%);
  color: #ffffff;
  border-color: transparent;
  box-shadow: 0 6px 18px rgba(236, 72, 153, 0.35);
  transform: translateY(-2px);
}

.comp-type-badge {
  font-size: 0.68rem;
  padding: 2px 7px;
  border-radius: 9999px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.badge-tournament {
  background: #fdf2f8;
  color: #db2777;
}

.badge-jury {
  background: #f5f3ff;
  color: #7c3aed;
}

.comp-pill-btn.active .comp-type-badge {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff;
}

/* Selected Competition Banner */
.selected-comp-banner {
  background: linear-gradient(135deg, #ffffff 0%, #fdf2f8 50%, #fff7ed 100%);
  border: 1px solid #fed7aa;
  border-radius: var(--radius-lg);
  padding: 20px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 28px;
  box-shadow: 0 4px 16px rgba(249, 115, 22, 0.06);
}

.selected-comp-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.selected-comp-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #ec4899 0%, #f97316 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  box-shadow: 0 4px 12px rgba(236, 72, 153, 0.25);
}

.selected-comp-category {
  font-size: 0.76rem;
  font-weight: 800;
  text-transform: uppercase;
  color: #ea580c;
  letter-spacing: 0.06em;
}

.selected-comp-title {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.25;
}

.selected-comp-meta {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.comp-meta-item {
  font-size: 0.85rem;
}

.comp-meta-item .meta-label {
  color: #64748b;
  margin-right: 5px;
}

.comp-meta-item .meta-val {
  color: #0f172a;
  font-weight: 700;
}

/* Stadium Electronic Scoreboard Card */
.match-center-board {
  background: #ffffff;
  border: 2px solid transparent;
  background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, #ec4899 0%, #f97316 50%, #8b5cf6 100%);
  background-origin: border-box;
  background-clip: padding-box, border-box;
  border-radius: 28px;
  padding: 36px 32px;
  box-shadow: 0 16px 40px rgba(236, 72, 153, 0.12), 0 4px 16px rgba(139, 92, 246, 0.08);
  margin-bottom: 35px;
}

.board-top-status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 18px;
  margin-bottom: 24px;
}

.board-match-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.match-num-pill {
  background: #0f172a;
  color: #ffffff;
  font-size: 0.8rem;
  font-weight: 800;
  padding: 4px 12px;
  border-radius: 8px;
  letter-spacing: 0.04em;
}

.match-round-text {
  font-family: var(--font-heading);
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
}

.board-live-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-pulse-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #10b981;
}

.pulse-active {
  background: #ef4444;
  box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
  animation: pulseRed 1.6s infinite;
}

@keyframes pulseRed {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.status-text {
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: #0f172a;
}

.stadium-score-grid {
  display: grid;
  grid-template-columns: 1fr 140px 1fr;
  align-items: center;
  gap: 20px;
  text-align: center;
}

.team-school-name {
  font-family: var(--font-heading);
  font-size: 1.6rem;
  font-weight: 900;
  color: #0f172a;
  letter-spacing: -0.01em;
  margin-bottom: 4px;
}

.team-delegates-text {
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 12px;
}

.big-score-number {
  font-family: var(--font-heading);
  font-size: 5rem;
  font-weight: 900;
  line-height: 1;
  letter-spacing: -0.03em;
  margin-bottom: 12px;
}

.number-orange {
  color: #ea580c;
  text-shadow: 0 6px 20px rgba(249, 115, 22, 0.25);
}

.number-purple {
  color: #7c3aed;
  text-shadow: 0 6px 20px rgba(139, 92, 246, 0.25);
}

.set-score-info {
  font-size: 0.85rem;
  color: #475569;
}

.team-vs-divider {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.vs-circle {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-heading);
  font-weight: 900;
  font-size: 1.15rem;
  color: #64748b;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 8px;
}

.vs-subtitle {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #94a3b8;
  margin-bottom: 4px;
}

.vs-referee {
  font-size: 0.78rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Empty Arena Card */
.empty-arena-card {
  background: #ffffff;
  border: 1px dashed #cbd5e1;
  border-radius: var(--radius-xl);
  padding: 50px 24px;
  text-align: center;
  margin-bottom: 35px;
}

.empty-icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin-bottom: 16px;
}

.empty-icon-wrap.icon-purple {
  background: #f5f3ff;
  color: #7c3aed;
  border: 1px solid #ddd6fe;
}

.empty-icon-wrap.icon-orange {
  background: #fff7ed;
  color: #ea580c;
  border: 1px solid #fed7aa;
}

.empty-title {
  font-family: var(--font-heading);
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 8px;
}

.empty-desc {
  font-size: 0.9rem;
  color: #64748b;
  max-width: 520px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Recent Matches Grid */
.recent-matches-box {
  margin-top: 20px;
}

.recent-matches-title {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 18px;
}

.recent-matches-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
  gap: 18px;
}

.match-summary-card {
  background: #ffffff;
  border: 1px solid var(--border-glass);
  border-radius: 16px;
  padding: 16px 18px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  transition: all 0.2s ease;
}

.match-summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.match-summary-card.card-ongoing {
  border-color: #f97316;
  box-shadow: 0 4px 16px rgba(249, 115, 22, 0.15);
}

.summary-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.summary-match-num {
  font-size: 0.75rem;
  font-weight: 800;
  background: #f1f5f9;
  color: #475569;
  padding: 2px 8px;
  border-radius: 6px;
}

.summary-round-name {
  font-size: 0.8rem;
  font-weight: 700;
  color: #0f172a;
}

.summary-status-pill {
  font-size: 0.7rem;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 9999px;
  text-transform: uppercase;
}

.summary-status-pill.ongoing {
  background: #fee2e2;
  color: #dc2626;
}

.summary-status-pill.completed {
  background: #ecfdf5;
  color: #059669;
}

.summary-status-pill.scheduled {
  background: #f1f5f9;
  color: #64748b;
}

.summary-teams {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.summary-team-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 10px;
  border-radius: 8px;
  background: #f8fafc;
  font-size: 0.86rem;
  font-weight: 600;
  color: #334155;
}

.summary-team-row.is-winner {
  background: #f5f3ff;
  color: #6d28d9;
  font-weight: 800;
}

.team-score-badge {
  font-family: var(--font-heading);
  font-weight: 900;
  font-size: 1rem;
}

.summary-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.76rem;
  color: #94a3b8;
  border-top: 1px solid #f1f5f9;
  padding-top: 8px;
}

/* ==========================================================================
   JURY SCORES & PODIUM STYLES
   ========================================================================== */
.podium-grid {
  display: grid;
  grid-template-columns: 1fr 1.15fr 1fr;
  align-items: flex-end;
  gap: 20px;
  margin-bottom: 35px;
}

.podium-card {
  background: #ffffff;
  border: 1px solid var(--border-glass);
  border-radius: 20px;
  padding: 24px 20px;
  text-align: center;
  box-shadow: var(--shadow-sm);
  position: relative;
  transition: all 0.25s ease;
}

.podium-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}

.podium-gold {
  border: 2px solid #fde68a;
  background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);
  padding-top: 32px;
  box-shadow: 0 12px 35px rgba(245, 158, 11, 0.15);
}

.crown-icon-wrap {
  position: absolute;
  top: -18px;
  left: 50%;
  transform: translateX(-50%);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f59e0b;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
}

.podium-silver {
  border: 2px solid #e2e8f0;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.podium-bronze {
  border: 2px solid #fed7aa;
  background: linear-gradient(180deg, #ffffff 0%, #fff7ed 100%);
}

.podium-rank-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.74rem;
  font-weight: 800;
  padding: 4px 12px;
  border-radius: 9999px;
  text-transform: uppercase;
  margin-bottom: 12px;
}

.rank-1 {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}

.rank-2 {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.rank-3 {
  background: #ffedd5;
  color: #c2410c;
  border: 1px solid #fed7aa;
}

.podium-school-name {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 4px;
}

.podium-participants {
  font-size: 0.82rem;
  color: #64748b;
  margin-bottom: 16px;
  line-height: 1.4;
}

.podium-score-val {
  font-family: var(--font-heading);
  font-size: 2.2rem;
  font-weight: 900;
  color: #0f172a;
  line-height: 1;
}

.gold-score {
  color: #d97706;
  font-size: 2.6rem;
}

.podium-score-label {
  font-size: 0.74rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 4px;
  display: block;
}

/* Leaderboard Table Card */
.leaderboard-table-card {
  background: #ffffff;
  border: 1px solid var(--border-glass);
  border-radius: var(--radius-xl);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.table-header-bar {
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.table-heading {
  font-family: var(--font-heading);
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
}

.table-count-badge {
  font-size: 0.78rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #475569;
  padding: 4px 12px;
  border-radius: 9999px;
}

.table-responsive-box {
  overflow-x: auto;
}

.scoreboard-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
  text-align: left;
}

.scoreboard-table th {
  background: #f8fafc;
  padding: 14px 20px;
  font-size: 0.78rem;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e2e8f0;
}

.scoreboard-table td {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.scoreboard-table tr:hover td {
  background: #fdf2f8;
}

.rank-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  font-family: var(--font-heading);
  font-weight: 900;
  font-size: 0.88rem;
}

.rank-gold {
  background: #fef3c7;
  color: #b45309;
  border: 2px solid #fde68a;
}

.rank-silver {
  background: #f1f5f9;
  color: #475569;
  border: 2px solid #cbd5e1;
}

.rank-bronze {
  background: #ffedd5;
  color: #c2410c;
  border: 2px solid #fed7aa;
}

.rank-default {
  background: #f8fafc;
  color: #64748b;
}

.school-cell-name {
  color: #0f172a;
  font-size: 0.95rem;
}

.reg-code-sub {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 2px;
}

.participants-cell {
  color: #475569;
  font-size: 0.88rem;
  line-height: 1.4;
}

.jury-cell-name {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  color: #6d28d9;
  font-weight: 600;
  background: #f5f3ff;
  padding: 3px 10px;
  border-radius: 8px;
}

.total-score-badge {
  font-family: var(--font-heading);
  font-weight: 900;
  font-size: 1.25rem;
  color: #0f172a;
}

.score-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 9999px;
}

.status-locked {
  background: #ecfdf5;
  color: #059669;
}

.status-draft {
  background: #fff7ed;
  color: #ea580c;
}

@media (max-width: 900px) {
  .scoreboard-header-card {
    flex-direction: column;
    align-items: flex-start;
  }
  .scoreboard-header-right {
    align-items: flex-start;
    width: 100%;
  }
  .stadium-score-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .podium-grid {
    grid-template-columns: 1fr;
  }
}

/* ==========================================================================
   SCOREBOARD DARK THEME OVERRIDES
   ========================================================================== */
.btn-theme-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 9999px;
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-theme-toggle:hover {
  background: #e2e8f0;
  color: var(--primary-purple);
}

.scoreboard-fullwidth-app.dark-theme {
  background-color: #0b0f19 !important;
  color: #f8fafc;
}

/* 1. Sticky Arena Topbar */
.scoreboard-fullwidth-app.dark-theme .scoreboard-topbar {
  background: #0f172a !important;
  border-bottom: 1px solid rgba(51, 65, 85, 0.8) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .btn-back-website {
  background: #1e293b !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .btn-back-website:hover {
  background: #334155 !important;
  border-color: var(--theme-pink) !important;
  color: var(--theme-pink) !important;
}

.scoreboard-fullwidth-app.dark-theme .topbar-divider {
  background: #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .identity-title {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .identity-sub {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .topbar-clock-box {
  background: rgba(249, 115, 22, 0.15) !important;
  border-color: rgba(249, 115, 22, 0.35) !important;
  color: #fb923c !important;
}

.scoreboard-fullwidth-app.dark-theme .topbar-clock-box .clock-label {
  color: #f97316 !important;
}

.scoreboard-fullwidth-app.dark-theme .btn-theme-toggle,
.scoreboard-fullwidth-app.dark-theme .btn-projector-toggle {
  background: #1e293b !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .btn-theme-toggle:hover,
.scoreboard-fullwidth-app.dark-theme .btn-projector-toggle:hover {
  background: #334155 !important;
  border-color: #64748b !important;
  color: #ffffff !important;
}

.scoreboard-fullwidth-app.dark-theme .topbar-ws-badge.ws-connected {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #34d399 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

.scoreboard-fullwidth-app.dark-theme .topbar-ws-badge.ws-connecting {
  background: rgba(245, 158, 11, 0.15) !important;
  color: #fbbf24 !important;
  border-color: rgba(245, 158, 11, 0.35) !important;
}

.scoreboard-fullwidth-app.dark-theme .topbar-ws-badge.ws-standby {
  background: rgba(51, 65, 85, 0.5) !important;
  color: #cbd5e1 !important;
  border-color: rgba(71, 85, 105, 0.6) !important;
}

/* 2. Scoreboard Header Card */
.scoreboard-fullwidth-app.dark-theme .scoreboard-header-card {
  background: #151d2e !important;
  border-color: rgba(51, 65, 85, 0.8) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .scoreboard-tag {
  background: rgba(16, 185, 129, 0.15) !important;
  color: #34d399 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

.scoreboard-fullwidth-app.dark-theme .scoreboard-main-title {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .scoreboard-main-subtitle {
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .stat-mini-pill {
  background: #0f172a !important;
  border-color: #334155 !important;
  color: #e2e8f0 !important;
}

/* 3. Competition Category Selector */
.scoreboard-fullwidth-app.dark-theme .comp-selector-label {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-selector-scroll {
  scrollbar-color: #334155 #0f172a !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-selector-scroll::-webkit-scrollbar-track {
  background: #0f172a !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-selector-scroll::-webkit-scrollbar-thumb {
  background: #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-pill-btn {
  background: #151d2e !important;
  border-color: #334155 !important;
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-pill-btn:hover {
  background: #1e293b !important;
  color: #ffffff !important;
  border-color: #475569 !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-pill-btn.active {
  background: linear-gradient(135deg, #ec4899 0%, #f97316 100%) !important;
  color: #ffffff !important;
  border-color: transparent !important;
  box-shadow: 0 6px 18px rgba(236, 72, 153, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .badge-tournament {
  background: rgba(236, 72, 153, 0.18) !important;
  color: #f472b6 !important;
  border: 1px solid rgba(236, 72, 153, 0.3) !important;
}

.scoreboard-fullwidth-app.dark-theme .badge-jury {
  background: rgba(139, 92, 246, 0.18) !important;
  color: #c4b5fd !important;
  border: 1px solid rgba(139, 92, 246, 0.3) !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-pill-btn.active .comp-type-badge {
  background: rgba(255, 255, 255, 0.25) !important;
  color: #ffffff !important;
  border: none !important;
}

/* 4. Active Competition Banner */
.scoreboard-fullwidth-app.dark-theme .selected-comp-banner {
  background: #151d2e !important;
  border-color: rgba(51, 65, 85, 0.8) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
}

.scoreboard-fullwidth-app.dark-theme .selected-comp-category {
  color: #fb923c !important;
}

.scoreboard-fullwidth-app.dark-theme .selected-comp-title {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-meta-item .meta-label {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .comp-meta-item .meta-val {
  color: #f8fafc !important;
}

/* 5. Stadium Electronic Scoreboard Card */
.scoreboard-fullwidth-app.dark-theme .match-center-board {
  background: #111827 !important;
  background-image: linear-gradient(#111827, #111827), linear-gradient(135deg, #ec4899 0%, #f97316 50%, #8b5cf6 100%) !important;
  border-color: transparent !important;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6), 0 4px 16px rgba(139, 92, 246, 0.15) !important;
}

.scoreboard-fullwidth-app.dark-theme .board-top-status {
  border-bottom: 1px solid rgba(51, 65, 85, 0.6) !important;
}

.scoreboard-fullwidth-app.dark-theme .board-match-info {
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .match-num-pill {
  background: #1e293b !important;
  border: 1px solid #475569 !important;
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .match-round-text,
.scoreboard-fullwidth-app.dark-theme .status-text {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .team-side {
  background: #151d2e !important;
  border: 1px solid rgba(51, 65, 85, 0.8) !important;
  border-radius: 16px;
  padding: 18px 14px;
}

.scoreboard-fullwidth-app.dark-theme .team-school-name {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .team-delegates-text {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .number-orange {
  color: #fb923c !important;
  text-shadow: 0 0 25px rgba(249, 115, 22, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .number-purple {
  color: #a78bfa !important;
  text-shadow: 0 0 25px rgba(167, 139, 250, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .set-score-info {
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .set-score-info strong {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .vs-circle {
  background: #151d2e !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .vs-subtitle {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .vs-referee {
  background: #0f172a !important;
  color: #94a3b8 !important;
  border: 1px solid #334155;
  padding: 4px 12px;
  border-radius: 9999px;
}

/* 6. Recent Matches Schedule & Results */
.scoreboard-fullwidth-app.dark-theme .recent-matches-title {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .match-summary-card {
  background: #151d2e !important;
  border: 1px solid #334155 !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
}

.scoreboard-fullwidth-app.dark-theme .match-summary-card.card-ongoing {
  border-color: #f97316 !important;
  box-shadow: 0 4px 16px rgba(249, 115, 22, 0.25) !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-round-name {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-match-num {
  background: #0f172a !important;
  color: #cbd5e1 !important;
  border: 1px solid #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-status-pill.ongoing {
  background: rgba(239, 68, 68, 0.2) !important;
  color: #f87171 !important;
  border: 1px solid rgba(239, 68, 68, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-status-pill.completed {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #34d399 !important;
  border: 1px solid rgba(16, 185, 129, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-status-pill.scheduled {
  background: rgba(51, 65, 85, 0.5) !important;
  color: #cbd5e1 !important;
  border: 1px solid rgba(71, 85, 105, 0.6) !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-team-row {
  background: #0f172a !important;
  color: #cbd5e1 !important;
  border: 1px solid rgba(51, 65, 85, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-team-row.is-winner {
  background: rgba(139, 92, 246, 0.2) !important;
  color: #c4b5fd !important;
  border-color: rgba(139, 92, 246, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-team-row .team-name {
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .summary-team-row.is-winner .team-name {
  color: #f8fafc !important;
  font-weight: 800;
}

.scoreboard-fullwidth-app.dark-theme .summary-team-row .team-score-badge {
  background: #151d2e !important;
  color: #f8fafc !important;
  padding: 2px 8px;
  border-radius: 6px;
}

.scoreboard-fullwidth-app.dark-theme .summary-footer {
  color: #94a3b8 !important;
  border-top: 1px solid #334155 !important;
}

/* 7. Empty Arena State */
.scoreboard-fullwidth-app.dark-theme .empty-arena-card {
  background: #151d2e !important;
  border: 1px dashed #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .empty-icon-wrap.icon-purple {
  background: rgba(139, 92, 246, 0.18) !important;
  color: #c4b5fd !important;
  border: 1px solid rgba(139, 92, 246, 0.35) !important;
}

.scoreboard-fullwidth-app.dark-theme .empty-icon-wrap.icon-orange {
  background: rgba(249, 115, 22, 0.18) !important;
  color: #fb923c !important;
  border: 1px solid rgba(249, 115, 22, 0.35) !important;
}

.scoreboard-fullwidth-app.dark-theme .empty-title {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .empty-desc {
  color: #94a3b8 !important;
}

/* 8. Jury Scores & Podium */
.scoreboard-fullwidth-app.dark-theme .podium-card {
  background: #151d2e !important;
  border-color: #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-card.podium-gold {
  background: linear-gradient(180deg, #151d2e 0%, rgba(245, 158, 11, 0.15) 100%) !important;
  border-color: #b45309 !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-card.podium-silver {
  background: linear-gradient(180deg, #151d2e 0%, rgba(148, 163, 184, 0.15) 100%) !important;
  border-color: #475569 !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-card.podium-bronze {
  background: linear-gradient(180deg, #151d2e 0%, rgba(249, 115, 22, 0.15) 100%) !important;
  border-color: #c2410c !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-rank-badge.rank-1 {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fbbf24 !important;
  border: 1px solid rgba(245, 158, 11, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-rank-badge.rank-2 {
  background: rgba(148, 163, 184, 0.2) !important;
  color: #e2e8f0 !important;
  border: 1px solid rgba(148, 163, 184, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-rank-badge.rank-3 {
  background: rgba(249, 115, 22, 0.2) !important;
  color: #fb923c !important;
  border: 1px solid rgba(249, 115, 22, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-school-name {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-participants {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-score-val {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .gold-score {
  color: #fbbf24 !important;
}

.scoreboard-fullwidth-app.dark-theme .podium-score-label {
  color: #94a3b8 !important;
}

/* 9. Leaderboard Table */
.scoreboard-fullwidth-app.dark-theme .leaderboard-table-card {
  background: #151d2e !important;
  border-color: rgba(51, 65, 85, 0.8) !important;
}

.scoreboard-fullwidth-app.dark-theme .table-header-bar {
  border-bottom: 1px solid rgba(51, 65, 85, 0.6) !important;
}

.scoreboard-fullwidth-app.dark-theme .table-heading {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .table-count-badge {
  background: #0f172a !important;
  color: #cbd5e1 !important;
  border: 1px solid #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .scoreboard-table thead th {
  background: #0f172a !important;
  color: #cbd5e1 !important;
  border-bottom: 1px solid #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .scoreboard-table td {
  border-bottom: 1px solid rgba(51, 65, 85, 0.6) !important;
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .scoreboard-table tbody tr:hover td {
  background: rgba(30, 41, 59, 0.7) !important;
}

.scoreboard-fullwidth-app.dark-theme .rank-gold {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fbbf24 !important;
  border: 2px solid rgba(245, 158, 11, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .rank-silver {
  background: rgba(148, 163, 184, 0.2) !important;
  color: #e2e8f0 !important;
  border: 2px solid rgba(148, 163, 184, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .rank-bronze {
  background: rgba(249, 115, 22, 0.2) !important;
  color: #fb923c !important;
  border: 2px solid rgba(249, 115, 22, 0.5) !important;
}

.scoreboard-fullwidth-app.dark-theme .rank-default {
  background: #1e293b !important;
  border: 1px solid #334155 !important;
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .school-cell-name {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .reg-code-sub {
  color: #64748b !important;
}

.scoreboard-fullwidth-app.dark-theme .participants-cell {
  color: #cbd5e1 !important;
}

.scoreboard-fullwidth-app.dark-theme .jury-cell-name {
  background: rgba(139, 92, 246, 0.2) !important;
  color: #c4b5fd !important;
  border: 1px solid rgba(139, 92, 246, 0.35) !important;
}

.scoreboard-fullwidth-app.dark-theme .total-score-badge {
  background: rgba(139, 92, 246, 0.2) !important;
  border: 1px solid rgba(139, 92, 246, 0.4) !important;
  color: #c4b5fd !important;
  padding: 4px 10px;
  border-radius: 8px;
}

.scoreboard-fullwidth-app.dark-theme .score-status-badge.status-locked {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #34d399 !important;
  border: 1px solid rgba(16, 185, 129, 0.4) !important;
}

.scoreboard-fullwidth-app.dark-theme .score-status-badge.status-draft {
  background: rgba(249, 115, 22, 0.2) !important;
  color: #fb923c !important;
  border: 1px solid rgba(249, 115, 22, 0.4) !important;
}

/* 10. Minimal Scoreboard Footer */
.scoreboard-fullwidth-app.dark-theme .scoreboard-minimal-footer {
  border-top: 1px solid #334155 !important;
}

.scoreboard-fullwidth-app.dark-theme .footer-left-info {
  color: #94a3b8 !important;
}

.scoreboard-fullwidth-app.dark-theme .footer-left-info strong {
  color: #f8fafc !important;
}

.scoreboard-fullwidth-app.dark-theme .footer-back-link {
  color: var(--theme-pink-light) !important;
}

.scoreboard-fullwidth-app.dark-theme .footer-back-link:hover {
  color: var(--theme-orange-light) !important;
}
</style>
