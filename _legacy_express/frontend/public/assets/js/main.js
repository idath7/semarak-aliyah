/**
 * SEMARAK ALIYAH XII 2026 - MAIN JAVASCRIPT
 * Official Interactive Features & Logic
 */

// Competition Dataset (Authentic based on Juknis Semarak Aliyah XII 2026)
const COMPETITIONS_DATA = [
  {
    id: "mipa",
    title: "Olimpiade MIPA",
    category: "akademik",
    categoryLabel: "Akademik",
    icon: "🔬",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Ajang uji kemampuan sains dan penalaran logika Matematika serta Ilmu Pengetahuan Alam untuk siswa/i tingkat SMP/MTs sederajat se-Kabupaten Jember.",
    peserta: "Maksimal 3 peserta per sekolah/madrasah",
    tmDate: "07 Januari 2026 • 08:00 WIB",
    contactPerson: "0838 5007 0767",
    rules: [
      "Peserta merupakan siswa aktif SMP/MTs kelas 7, 8, atau 9.",
      "Membawa surat delegasi resmi dari kepala sekolah/madrasah.",
      "Ujian meliputi materi Matematika dan IPA tingkat SMP/MTs.",
      "Babak penyisihan dan babak final dilaksanakan offline di MA Wahid Hasyim Balung."
    ]
  },
  {
    id: "ips",
    title: "Olimpiade IPS",
    category: "akademik",
    categoryLabel: "Akademik",
    icon: "🌍",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Kompetisi wawasan sosial, geografi, sejarah, sosiologi, dan ekonomi terpadu untuk mengasah kemampuan analitis generasi muda.",
    peserta: "Maksimal 3 peserta per sekolah/madrasah",
    tmDate: "07 Januari 2026 • 08:00 WIB",
    contactPerson: "0838 5007 0767",
    rules: [
      "Peserta merupakan siswa aktif SMP/MTs kelas 7, 8, atau 9.",
      "Materi meliputi Sejarah Nasional, Geografi, Ekonomi, dan Sosiologi SMP.",
      "Sistem penilaian transparansi tinggi dengan babak tes tertulis dan kuis cepat tepat."
    ]
  },
  {
    id: "storytelling",
    title: "Storytelling (Bahasa Inggris)",
    category: "seni",
    categoryLabel: "Bahasa & Seni",
    icon: "📖",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Lomba bercerita dalam bahasa Inggris untuk mengekspresikan bakat public speaking, intonasi, ekspresi, dan penguasaan panggung dengan tema cerita rakyat / moral story.",
    peserta: "Maksimal 2 peserta per sekolah/madrasah",
    tmDate: "07 Januari 2026 • 08:00 WIB",
    contactPerson: "0838 5007 0767",
    rules: [
      "Durasi penampilan 5 - 7 menit.",
      "Diperbolehkan menggunakan kostum dan properti yang mendukung alur cerita.",
      "Kriteria penilaian: Pronunciation, Fluency, Expression, Creativity, dan Time Management."
    ]
  },
  {
    id: "albanjari",
    title: "Festival Albanjari",
    category: "seni",
    categoryLabel: "Religi & Seni",
    icon: "🥁",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Festival shalawat dan hadrah Al-Banjari tingkat pelajar se-Kabupaten Jember. Menjunjung tinggi syiar Islam dan keindahan seni tabuhan tradisional.",
    peserta: "1 grup terdiri dari maksimal 10 personel (Vokal + Terbang)",
    tmDate: "07 Januari 2026 • 08:00 WIB",
    contactPerson: "0838 5007 0767",
    rules: [
      "Membawakan 1 lagu wajib dan 1 lagu pilihan.",
      "Menggunakan alat hadrah banjari standar (tanpa alat musik elektrik).",
      "Penilaian mencakup: Vokal, Variasi Pukulan Terbang, Adab, dan Kerapian Busana."
    ]
  },
  {
    id: "singer",
    title: "Singer Lagu Islami",
    category: "seni",
    categoryLabel: "Bahasa & Seni",
    icon: "🎤",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Kompetisi vokal solo lagu religi/Islami untuk menyalurkan bakat bernyanyi dengan nada merdu, penghayatan lirik, dan teknik vokal prima.",
    peserta: "Maksimal 2 peserta (Putra/Putri) per sekolah",
    tmDate: "07 Januari 2026 • 08:00 WIB",
    contactPerson: "0838 5007 0767",
    rules: [
      "Peserta menyanyikan lagu religi pilihan yang telah ditentukan panitia.",
      "Menggunakan iringan minus-one / karaoke.",
      "Penilaian: Kualitas Vokal, Artikulasi, Penghayatan, dan Penampilan Panggung."
    ]
  },
  {
    id: "volleyball-putra",
    title: "Tournament Volleyball (Putra)",
    category: "olahraga",
    categoryLabel: "Olahraga",
    icon: "🏐",
    quota: "Kuota Penuh",
    isFull: true,
    fee: 50000,
    desc: "Turnamen bola voli putra bergengsi antar SMP/MTs. Kuota peserta utama telah terpenuhi, pendaftaran baru masuk waiting list.",
    peserta: "1 Tim (6 pemain inti + 4 cadangan + 1 official)",
    tmDate: "Technical Meeting: 08:00 WIB - Selesai",
    contactPerson: "0838 5007 0767",
    rules: [
      "Sistem gugur (knockout) dengan reli poin 25 (two winning sets).",
      "Seluruh pemain wajib berseragam tim bernomor punggung rapi.",
      "Menjunjung tinggi sportivitas dan fair play selama turnamen berlangsung."
    ]
  },
  {
    id: "volleyball-putri",
    title: "Tournament Volleyball (Putri)",
    category: "olahraga",
    categoryLabel: "Olahraga",
    icon: "🏐",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Turnamen bola voli putri untuk mengasah ketangkasan, kekompakan tim, dan semangat kompetisi sehat tingkat pelajar putri.",
    peserta: "1 Tim (6 pemain inti + 4 cadangan + 1 official)",
    tmDate: "Technical Meeting: 08:00 WIB - Selesai",
    contactPerson: "0838 5007 0767",
    rules: [
      "Sistem gugur dengan reli poin resmi PBVSI.",
      "Wajib mengenakan seragam olahraga yang serasi dan sopan.",
      "Pemain wajib melampirkan fotokopi KTS / surat keterangan siswa aktif."
    ]
  },
  {
    id: "mobile-legends",
    title: "Tournament Mobile Legends (MSA S4)",
    category: "olahraga",
    categoryLabel: "E-Sport",
    icon: "🎮",
    quota: "Tersedia",
    isFull: false,
    fee: 50000,
    desc: "Ajang E-Sport Mobile Legends Bang Bang (MSA Season 4) resmi Semarak Aliyah. Uji strategi makro dan mikro, sinergi draft pick, dan kerjasama tim.",
    peserta: "1 Tim (5 pemain inti + 1 cadangan)",
    tmDate: "Technical Meeting: 14:00 WIB - Selesai",
    contactPerson: "0852 3582 8664 / 0813 3311 3204",
    rules: [
      "Mode Turnamen: Custom Draft Pick 5v5.",
      "All Tier / Rank diperbolehkan, wajib berstatus pelajar SMP/MTs aktif.",
      "Dilarang menggunakan cheat, skin script, atau bug dalam bentuk apapun.",
      "Koneksi internet dan device disiapkan mandiri oleh tim peserta."
    ]
  }
];

document.addEventListener("DOMContentLoaded", () => {
  initCountdown();
  initHeader();
  renderCompetitions();
  initCalculator();
  initFAQ();
  initModals();
  initRegistrationLogic();
});

/* ==========================================================================
   1. COUNTDOWN TIMER
   ========================================================================== */
function initCountdown() {
  // Set target date for Semarak Aliyah 2026
  let targetDate = new Date("2026-01-15T08:00:00+07:00").getTime();
  const now = new Date().getTime();

  // If target date in the past during testing, set to 45 days ahead dynamically
  if (now > targetDate) {
    targetDate = now + (45 * 24 * 60 * 60 * 1000);
  }

  const daysEl = document.getElementById("timer-days");
  const hoursEl = document.getElementById("timer-hours");
  const minsEl = document.getElementById("timer-minutes");
  const secsEl = document.getElementById("timer-seconds");

  if (!daysEl) return;

  function update() {
    const currentTime = new Date().getTime();
    const distance = targetDate - currentTime;

    if (distance < 0) {
      daysEl.textContent = "00";
      hoursEl.textContent = "00";
      minsEl.textContent = "00";
      secsEl.textContent = "00";
      return;
    }

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    daysEl.textContent = String(days).padStart(2, "0");
    hoursEl.textContent = String(hours).padStart(2, "0");
    minsEl.textContent = String(minutes).padStart(2, "0");
    secsEl.textContent = String(seconds).padStart(2, "0");
  }

  update();
  setInterval(update, 1000);
}

/* ==========================================================================
   2. HEADER & NAVIGATION SCROLL
   ========================================================================== */
function initHeader() {
  const header = document.querySelector(".main-header");
  const backToTop = document.querySelector(".back-to-top");
  const mobileToggle = document.querySelector(".mobile-toggle");
  const mobileDrawer = document.querySelector(".mobile-drawer");
  const drawerLinks = document.querySelectorAll(".mobile-drawer .nav-link");

  window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
      header?.classList.add("scrolled");
    } else {
      header?.classList.remove("scrolled");
    }

    if (window.scrollY > 400) {
      backToTop?.classList.add("show");
    } else {
      backToTop?.classList.remove("show");
    }
  });

  backToTop?.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  if (mobileToggle && mobileDrawer) {
    mobileToggle.addEventListener("click", () => {
      mobileToggle.classList.toggle("active");
      mobileDrawer.classList.toggle("active");
      document.body.style.overflow = mobileDrawer.classList.contains("active") ? "hidden" : "";
    });

    drawerLinks.forEach(link => {
      link.addEventListener("click", () => {
        mobileToggle.classList.remove("active");
        mobileDrawer.classList.remove("active");
        document.body.style.overflow = "";
      });
    });
  }
}

/* ==========================================================================
   3. COMPETITIONS RENDERING & FILTER (DYNAMIC DATABASE)
   ========================================================================== */
let activeCompetitionsData = COMPETITIONS_DATA;

async function renderCompetitions() {
  const container = document.getElementById("competitions-grid");
  const filterBtns = document.querySelectorAll(".filter-btn");

  if (!container) return;

  // Coba ambil dari Database API
  try {
    const res = await fetch('/api/competitions');
    const json = await res.json();
    if (json.status === 'success' && json.data && json.data.length > 0) {
      activeCompetitionsData = json.data;
    }
  } catch (err) {
    console.warn('Menggunakan dataset lokal (offline/fallback):', err);
  }

  function display(category = "all") {
    const filtered = category === "all" 
      ? activeCompetitionsData 
      : activeCompetitionsData.filter(item => item.category === category);

    container.innerHTML = filtered.map(comp => `
      <article class="comp-card ${comp.isFull ? 'full-quota' : ''}" data-category="${comp.category}">
        <div>
          <div class="comp-header">
            <span class="comp-category-badge">${comp.categoryLabel}</span>
            <span class="comp-status-badge ${comp.isFull ? 'status-closed' : 'status-open'}">
              ${comp.isFull ? '⛔ ' + comp.quota : '✅ ' + comp.quota}
            </span>
          </div>

          <div class="comp-icon-box">${comp.icon}</div>
          <h3 class="comp-title">${comp.title}</h3>
          <p class="comp-desc">${comp.desc}</p>

          <div class="comp-meta-list">
            <div class="comp-meta-item">
              <span>👥</span>
              <span><strong>Ketentuan:</strong> ${comp.peserta}</span>
            </div>
            <div class="comp-meta-item">
              <span>📅</span>
              <span><strong>TM:</strong> ${comp.tmDate}</span>
            </div>
            <div class="comp-meta-item">
              <span>💵</span>
              <span><strong>Biaya:</strong> Rp ${comp.fee.toLocaleString("id-ID")}</span>
            </div>
          </div>
        </div>

        <div class="comp-actions">
          <button type="button" class="btn-card-detail" onclick="openCompModal('${comp.id}')">
            ℹ️ Detail Juknis
          </button>
          ${comp.isFull 
            ? `<button type="button" class="btn-card-register disabled" disabled>Kuota Penuh</button>`
            : `<button type="button" class="btn-card-register" onclick="openRegisterModal('${comp.id}')">⚡ Daftar</button>`
          }
        </div>
      </article>
    `).join("");
  }

  display("all");

  filterBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      filterBtns.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      const filter = btn.getAttribute("data-filter");
      display(filter);
    });
  });
}

/* ==========================================================================
   4. MODALS (COMPETITION DETAIL & REGISTRATION)
   ========================================================================== */
function initModals() {
  const overlays = document.querySelectorAll(".modal-overlay");
  overlays.forEach(overlay => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        overlay.classList.remove("active");
        document.body.style.overflow = "";
      }
    });

    const closeBtn = overlay.querySelector(".modal-close");
    closeBtn?.addEventListener("click", () => {
      overlay.classList.remove("active");
      document.body.style.overflow = "";
    });
  });
}

window.openCompModal = function(id) {
  const comp = COMPETITIONS_DATA.find(c => c.id === id);
  if (!comp) return;

  const modal = document.getElementById("comp-modal");
  const titleEl = document.getElementById("comp-modal-title");
  const bodyEl = document.getElementById("comp-modal-body");
  const ctaBtn = document.getElementById("comp-modal-cta");

  if (!modal || !titleEl || !bodyEl) return;

  titleEl.innerHTML = `${comp.icon} ${comp.title}`;
  bodyEl.innerHTML = `
    <div style="margin-bottom: 20px;">
      <span class="comp-category-badge" style="margin-right: 8px;">${comp.categoryLabel}</span>
      <span class="comp-status-badge ${comp.isFull ? 'status-closed' : 'status-open'}">${comp.quota}</span>
    </div>

    <p style="color: var(--text-secondary); margin-bottom: 20px; line-height: 1.6;">${comp.desc}</p>

    <div style="background: rgba(11, 17, 32, 0.7); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
      <h4 style="font-size: 1rem; color: var(--accent-gold-light); margin-bottom: 10px;">📋 Informasi Pelaksanaan & Ketentuan:</h4>
      <ul style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: var(--text-secondary);">
        <li><strong>Jumlah Peserta:</strong> ${comp.peserta}</li>
        <li><strong>Jadwal TM:</strong> ${comp.tmDate}</li>
        <li><strong>Biaya Pendaftaran:</strong> Rp ${comp.fee.toLocaleString("id-ID")} (Termasuk seluruh fasilitas lomba)</li>
        <li><strong>Contact Person:</strong> ${comp.contactPerson}</li>
      </ul>
    </div>

    <h4 style="font-size: 1rem; color: #fff; margin-bottom: 12px;">Ketentuan Utama:</h4>
    <ul style="padding-left: 20px; list-style-type: disc; color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 24px;">
      ${comp.rules.map(r => `<li>${r}</li>`).join("")}
    </ul>
  `;

  if (ctaBtn) {
    if (comp.isFull) {
      ctaBtn.style.display = "none";
    } else {
      ctaBtn.style.display = "inline-flex";
      ctaBtn.onclick = () => {
        modal.classList.remove("active");
        openRegisterModal(comp.id);
      };
    }
  }

  modal.classList.add("active");
  document.body.style.overflow = "hidden";
};

window.openRegisterModal = function(defaultCompId = "") {
  const modal = document.getElementById("register-modal");
  if (!modal) return;

  const compSelect = document.getElementById("reg-competition");
  if (compSelect && defaultCompId) {
    compSelect.value = defaultCompId;
  }

  modal.classList.add("active");
  document.body.style.overflow = "hidden";
};

/* ==========================================================================
   5. INTERACTIVE FEE CALCULATOR
   ========================================================================== */
function initCalculator() {
  const container = document.getElementById("calc-comp-list");
  const totalValEl = document.getElementById("calc-total-val");
  const discountBadge = document.getElementById("calc-discount-badge");
  const allInNotice = document.getElementById("calc-allin-notice");

  if (!container || !totalValEl) return;

  container.innerHTML = COMPETITIONS_DATA.filter(c => !c.isFull).map(c => `
    <label class="calc-item-label">
      <input type="checkbox" name="calc-check" value="${c.fee}" data-id="${c.id}" data-name="${c.title}">
      <div class="calc-item-info">
        <span>${c.icon} ${c.title}</span>
        <span class="calc-item-price">Rp 50.000</span>
      </div>
    </label>
  `).join("");

  const checkboxes = container.querySelectorAll("input[type='checkbox']");

  function calculate() {
    let count = 0;
    checkboxes.forEach(cb => {
      if (cb.checked) count++;
    });

    let total = count * 50000;
    let isAllInRecommended = count >= 4;

    if (count === 0) {
      totalValEl.textContent = "Rp 0";
      if (discountBadge) discountBadge.style.display = "none";
      if (allInNotice) allInNotice.style.display = "none";
      return;
    }

    if (isAllInRecommended) {
      // All In is only 200.000
      totalValEl.innerHTML = `Rp 200.000 <small style="font-size: 1rem; color: var(--accent-emerald-light); font-weight: normal;">(Paket All-In Komplit!)</small>`;
      if (discountBadge) {
        discountBadge.style.display = "inline-block";
        const saved = (count * 50000) - 200000;
        discountBadge.textContent = saved > 0 ? `Hemat Rp ${saved.toLocaleString("id-ID")}!` : "Harga Paket Termurah!";
      }
      if (allInNotice) {
        allInNotice.style.display = "block";
      }
    } else {
      totalValEl.textContent = `Rp ${total.toLocaleString("id-ID")}`;
      if (discountBadge) discountBadge.style.display = "none";
      if (allInNotice) allInNotice.style.display = "none";
    }
  }

  checkboxes.forEach(cb => cb.addEventListener("change", calculate));
  calculate();
}

/* ==========================================================================
   6. FAQ ACCORDION
   ========================================================================== */
function initFAQ() {
  const faqItems = document.querySelectorAll(".faq-item");
  faqItems.forEach(item => {
    const question = item.querySelector(".faq-question");
    const answer = item.querySelector(".faq-answer");

    question?.addEventListener("click", () => {
      const isActive = item.classList.contains("active");

      // Close all others
      faqItems.forEach(other => {
        other.classList.remove("active");
        const otherAnswer = other.querySelector(".faq-answer");
        if (otherAnswer) otherAnswer.style.maxHeight = null;
      });

      if (!isActive) {
        item.classList.add("active");
        answer.style.maxHeight = answer.scrollHeight + 30 + "px";
      }
    });
  });
}

/* ==========================================================================
   7. REGISTRATION FORM & DATABASE PERSISTENCE (POST /api/register)
   ========================================================================== */
function initRegistrationLogic() {
  const form = document.getElementById("main-register-form");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const sekolah = document.getElementById("reg-sekolah")?.value.trim();
    const pendamping = document.getElementById("reg-pendamping")?.value.trim();
    const wa = document.getElementById("reg-wa")?.value.trim();
    const competition = document.getElementById("reg-competition")?.value;
    const paket = document.getElementById("reg-paket")?.value;
    const peserta = document.getElementById("reg-peserta")?.value.trim();
    const catatan = document.getElementById("reg-catatan")?.value.trim();

    if (!sekolah || !pendamping || !wa || !peserta) {
      showToast("⚠️ Mohon lengkapi seluruh kolom formulir wajib!", "warning");
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '⏳ Menyimpan ke Database...';
    }

    try {
      const response = await fetch('/api/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          school_name: sekolah,
          mentor_name: pendamping,
          mentor_wa: wa,
          package_type: paket,
          competition_id: competition,
          competition_title: competition,
          participants_names: peserta,
          notes: catatan
        })
      });

      const result = await response.json();
      if (result.status !== 'success') {
        throw new Error(result.message || 'Gagal menyimpan pendaftaran');
      }

      showToast(`✅ Tersimpan di Database! (ID: ${result.regCode}) Mengarahkan ke WhatsApp...`, "success");

      setTimeout(() => {
        document.getElementById("register-modal")?.classList.remove("active");
        document.body.style.overflow = "";
        form.reset();
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
        window.open(result.waUrl, "_blank");
      }, 1500);

    } catch (err) {
      console.error('Registration API Error:', err);
      // Fallback direct WhatsApp if server offline
      const regCode = "SA26-" + Math.floor(100000 + Math.random() * 900000);
      const fallbackWa = `https://wa.me/6283850070767?text=${encodeURIComponent('Pendaftaran ' + sekolah + ' - ' + competition + ' (ID: ' + regCode + ')')}`;
      showToast(`⚠️ Server offline, mengarahkan via WhatsApp langsung...`, "warning");
      setTimeout(() => {
        document.getElementById("register-modal")?.classList.remove("active");
        window.open(fallbackWa, "_blank");
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      }, 1500);
    }
  });
}

/* ==========================================================================
   8. TOAST NOTIFICATION UTILITY
   ========================================================================== */
window.showToast = function(message, type = "info") {
  let toast = document.querySelector(".toast-msg");
  if (!toast) {
    toast = document.createElement("div");
    toast.className = "toast-msg";
    document.body.appendChild(toast);
  }

  if (type === "warning") {
    toast.style.borderColor = "var(--accent-rose)";
  } else if (type === "success") {
    toast.style.borderColor = "var(--accent-emerald)";
  } else {
    toast.style.borderColor = "var(--primary-blue-light)";
  }

  toast.innerHTML = message;
  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 4000);
};
