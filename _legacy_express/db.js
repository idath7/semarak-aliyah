/**
 * SEMARAK ALIYAH XII 2026 - DATABASE ADAPTER & OPERATIONAL DATA ACCESS
 * Mendukung MySQL (Wamp64 Port 3307/3306 Lokal & Docker Production) dengan Fallback SQLite Cerdas
 */

require('dotenv').config();
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const DB_CONFIG = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'semarak_db',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

let pool = null;
let activeDriver = 'none'; // 'mysql' | 'sqlite'
let activePort = null;
let sqliteDb = null;

// Initial Competitions Seed Data
const SEED_COMPETITIONS = [
  ['mipa', 'Olimpiade MIPA', 'akademik', 'Akademik & Sains', '🔬', 50000, 40, 14, 'open', 'Maksimal 3 peserta per sekolah/madrasah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Ajang uji kemampuan sains dan penalaran logika Matematika serta IPA SMP/MTs se-Kabupaten Jember.'],
  ['ips', 'Olimpiade IPS', 'akademik', 'Akademik & Sains', '🌍', 50000, 40, 12, 'open', 'Maksimal 3 peserta per sekolah/madrasah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Kompetisi wawasan sosial, geografi, sejarah, sosiologi, dan ekonomi terpadu tingkat SMP/MTs.'],
  ['storytelling', 'Storytelling (Bahasa Inggris)', 'seni', 'Bahasa & Seni', '📖', 50000, 30, 9, 'open', 'Maksimal 2 peserta per sekolah/madrasah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Lomba bercerita dalam bahasa Inggris untuk mengasah bakat public speaking, ekspresi, dan intonasi.'],
  ['albanjari', 'Festival Albanjari', 'seni', 'Religi & Seni', '🥁', 50000, 25, 11, 'open', '1 grup terdiri dari maksimal 10 personel (Vokal + Terbang)', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Festival shalawat hadrah Al-Banjari tingkat pelajar se-Kabupaten Jember dengan syiar seni Islami.'],
  ['singer', 'Singer Lagu Islami', 'seni', 'Bahasa & Seni', '🎤', 50000, 30, 8, 'open', 'Maksimal 2 peserta (Putra/Putri) per sekolah', '07 Januari 2026 • 08:00 WIB', '0838 5007 0767', 'Kompetisi vokal solo lagu religi/Islami untuk menyalurkan bakat bernyanyi dengan nada merdu dan penghayatan.'],
  ['volleyball-putra', 'Tournament Volleyball (Putra)', 'olahraga', 'Olahraga', '🏐', 50000, 16, 16, 'closed', '1 Tim (6 pemain inti + 4 cadangan + official)', 'Technical Meeting: 08:00 WIB - Selesai', '0838 5007 0767', 'Turnamen bola voli putra bergengsi antar SMP/MTs. Kuota peserta utama telah terpenuhi (waiting list).'],
  ['volleyball-putri', 'Tournament Volleyball (Putri)', 'olahraga', 'Olahraga', '🏐', 50000, 16, 10, 'open', '1 Tim (6 pemain inti + 4 cadangan + official)', 'Technical Meeting: 08:00 WIB - Selesai', '0838 5007 0767', 'Turnamen bola voli putri untuk mengasah ketangkasan, kekompakan tim, dan sportivitas pelajar putri.'],
  ['mobile-legends', 'Tournament Mobile Legends (MSA S4)', 'olahraga', 'E-Sport', '🎮', 50000, 32, 19, 'open', '1 Tim (5 pemain inti + 1 cadangan)', 'Technical Meeting: 14:00 WIB - Selesai', '0852 3582 8664 / 0813 3311 3204', 'Turnamen E-Sport MLBB (MSA Season 4) resmi Semarak Aliyah tingkat pelajar SMP/MTs.']
];

// Presets Rubrik Penjurian
const DEFAULT_RUBRICS = {
  albanjari: {
    'Vokal & Keutuhan Lagu (Fashahah & Nada)': 35,
    'Variasi Pukulan Terbang (Tempo, Ketukan & Variasi)': 35,
    'Harmonisasi & Kekompakan Tim': 20,
    'Adab, Busana & Kerapian Panggung': 10
  },
  singer: {
    'Intonasi & Pitch Control (Ketepatan Nada)': 30,
    'Teknik Vokal & Artikulasi': 30,
    'Penghayatan & Dinamika': 25,
    'Stage Presence, Busana & Penampilan': 15
  },
  storytelling: {
    'Pronunciation, Fluency & Accent': 35,
    'Content, Grammar & Story Structure': 25,
    'Facial Expression, Gesture & Stage Act': 25,
    'Props, Costume & Time Management': 15
  },
  mipa: {
    'Skor Babak Teori Matematika & Sains (CBT)': 45,
    'Skor Babak Penalaran & Pemecahan Soal Esai': 40,
    'Kecepatan & Ketepatan Penyelesaian Waktu': 15
  },
  ips: {
    'Skor Babak Wawasan Geografi & Sosiologi': 45,
    'Skor Babak Analisis Sejarah & Ekonomi Terpadu': 40,
    'Daya Nalar & Ketepatan Logika Jawaban': 15
  },
  'volleyball-putra': {
    'Poin Kemenangan Set Pertandingan': 50,
    'Teknik Serangan & Defense (Spike, Servis, Block)': 25,
    'Kerjasama Tim & Komunikasi Lapangan': 15,
    'Sportivitas & Kedisiplinan': 10
  },
  'volleyball-putri': {
    'Poin Kemenangan Set Pertandingan': 50,
    'Teknik Serangan & Defense (Spike, Servis, Block)': 25,
    'Kerjasama Tim & Komunikasi Lapangan': 15,
    'Sportivitas & Kedisiplinan': 10
  },
  'mobile-legends': {
    'Skor Match / Game Point (Bo3)': 45,
    'Kontrol Objektif (Lord, Turtle, Turret)': 25,
    'Teamfight Synergy, KDA & Macro Play': 20,
    'Sportivitas & Disiplin Turnamen': 10
  }
};

async function initDatabase() {
  const candidatePorts = [
    parseInt(process.env.DB_PORT || '3307', 10),
    3307,
    3306
  ];
  const uniquePorts = [...new Set(candidatePorts)];

  for (const port of uniquePorts) {
    try {
      const initConn = await mysql.createConnection({
        host: DB_CONFIG.host,
        port: port,
        user: DB_CONFIG.user,
        password: DB_CONFIG.password,
        connectTimeout: 1500
      });

      await initConn.query(`CREATE DATABASE IF NOT EXISTS \`${DB_CONFIG.database}\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`);
      await initConn.end();

      pool = mysql.createPool({ ...DB_CONFIG, port });
      await createMysqlTables();
      activeDriver = 'mysql';
      activePort = port;

      console.log(`✅ [Database] Berhasil terhubung ke MariaDB (${DB_CONFIG.host}:${port}/${DB_CONFIG.database})`);
      return activeDriver;
    } catch (err) {
      // Continue to next port candidate
    }
  }

  // Fallback SQLite
  console.log(`ℹ️ [Database] MySQL lokal belum aktif pada port 3307/3306. Menggunakan penyimpanan SQLite lokal...`);
  try {
    initSqliteFallback();
    activeDriver = 'sqlite';
    console.log(`✅ [Database] Berhasil aktif menggunakan SQLite Lokal (database/semarak.db)`);
  } catch (e) {
    console.log(`ℹ️ SQLite fallback tidak aktif (${e.message}). Menunggu MySQL.`);
    activeDriver = 'none';
  }
  return activeDriver;
}

async function createMysqlTables() {
  // 1. Competitions
  await pool.query(`
    CREATE TABLE IF NOT EXISTS \`competitions\` (
      \`id\` VARCHAR(50) NOT NULL PRIMARY KEY,
      \`title\` VARCHAR(150) NOT NULL,
      \`category\` VARCHAR(50) NOT NULL DEFAULT 'akademik',
      \`category_label\` VARCHAR(100) NOT NULL,
      \`icon\` VARCHAR(20) NOT NULL DEFAULT '🎯',
      \`fee\` INT NOT NULL DEFAULT 50000,
      \`max_quota\` INT NOT NULL DEFAULT 30,
      \`current_quota\` INT NOT NULL DEFAULT 0,
      \`status\` VARCHAR(20) NOT NULL DEFAULT 'open',
      \`participants_rule\` VARCHAR(255) NOT NULL,
      \`tm_date\` VARCHAR(150) NOT NULL,
      \`contact_person\` VARCHAR(100) NOT NULL,
      \`description\` TEXT,
      \`scoring_rubrics\` TEXT NULL,
      \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  // 2. Registrations
  await pool.query(`
    CREATE TABLE IF NOT EXISTS \`registrations\` (
      \`id\` INT AUTO_INCREMENT PRIMARY KEY,
      \`reg_code\` VARCHAR(30) NOT NULL UNIQUE,
      \`barcode\` VARCHAR(100) NULL,
      \`school_name\` VARCHAR(200) NOT NULL,
      \`mentor_name\` VARCHAR(150) NOT NULL,
      \`mentor_wa\` VARCHAR(30) NOT NULL,
      \`package_type\` VARCHAR(100) NOT NULL,
      \`competition_id\` VARCHAR(50) NOT NULL,
      \`competition_title\` VARCHAR(150) NOT NULL,
      \`participants_names\` TEXT NOT NULL,
      \`notes\` TEXT NULL,
      \`file_documents\` TEXT NULL,
      \`verification_notes\` TEXT NULL,
      \`total_fee\` INT NOT NULL DEFAULT 50000,
      \`payment_status\` VARCHAR(50) NOT NULL DEFAULT 'Menunggu Verifikasi',
      \`checkin_status\` VARCHAR(50) NOT NULL DEFAULT 'belum_hadir',
      \`checkin_at\` TIMESTAMP NULL,
      \`payment_method\` VARCHAR(50) DEFAULT 'Transfer Bank / Tunai',
      \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  // Ensure operational columns exist on registrations
  const columnsToAdd = [
    { name: 'barcode', def: 'VARCHAR(100) NULL AFTER reg_code' },
    { name: 'checkin_status', def: "VARCHAR(50) NOT NULL DEFAULT 'belum_hadir' AFTER payment_status" },
    { name: 'checkin_at', def: 'TIMESTAMP NULL AFTER checkin_status' },
    { name: 'file_documents', def: 'TEXT NULL AFTER notes' },
    { name: 'verification_notes', def: 'TEXT NULL AFTER file_documents' }
  ];

  for (const col of columnsToAdd) {
    try {
      const [chk] = await pool.query(
        `SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'registrations' AND COLUMN_NAME = ?`,
        [DB_CONFIG.database, col.name]
      );
      if (chk.length === 0) {
        await pool.query(`ALTER TABLE \`registrations\` ADD COLUMN \`${col.name}\` ${col.def}`);
      }
    } catch(e) {}
  }

  // Auto-fill empty barcode with reg_code
  await pool.query(`UPDATE \`registrations\` SET \`barcode\` = \`reg_code\` WHERE \`barcode\` IS NULL OR \`barcode\` = ''`);

  // 3. Jury Scores
  await pool.query(`
    CREATE TABLE IF NOT EXISTS \`jury_scores\` (
      \`id\` INT AUTO_INCREMENT PRIMARY KEY,
      \`registration_id\` INT NOT NULL,
      \`competition_id\` VARCHAR(50) NOT NULL,
      \`jury_name\` VARCHAR(100) NOT NULL DEFAULT 'Dewan Juri',
      \`criteria_scores\` TEXT NULL,
      \`total_score\` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
      \`performance_duration\` VARCHAR(50) NULL,
      \`is_locked\` TINYINT(1) NOT NULL DEFAULT 0,
      \`notes\` TEXT NULL,
      \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      \`updated_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX \`idx_comp_score\` (\`competition_id\`, \`total_score\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  // 4. Tournament Matches
  await pool.query(`
    CREATE TABLE IF NOT EXISTS \`tournament_matches\` (
      \`id\` INT AUTO_INCREMENT PRIMARY KEY,
      \`competition_id\` VARCHAR(50) NOT NULL,
      \`match_number\` VARCHAR(30) NULL,
      \`round_name\` VARCHAR(50) NOT NULL DEFAULT 'Babak Penyisihan',
      \`round_order\` INT NOT NULL DEFAULT 1,
      \`team1_registration_id\` INT NULL,
      \`team1_placeholder\` VARCHAR(100) NULL,
      \`team1_score\` INT NOT NULL DEFAULT 0,
      \`team1_set_scores\` TEXT NULL,
      \`team2_registration_id\` INT NULL,
      \`team2_placeholder\` VARCHAR(100) NULL,
      \`team2_score\` INT NOT NULL DEFAULT 0,
      \`team2_set_scores\` TEXT NULL,
      \`winner_registration_id\` INT NULL,
      \`next_match_id\` INT NULL,
      \`next_match_slot\` VARCHAR(10) NULL,
      \`status\` VARCHAR(20) NOT NULL DEFAULT 'scheduled',
      \`court\` VARCHAR(50) NULL,
      \`scheduled_time\` VARCHAR(50) NULL,
      \`referee_name\` VARCHAR(100) NULL,
      \`notes\` TEXT NULL,
      \`created_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      \`updated_at\` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  // Seed default competitions if empty
  const [rows] = await pool.query(`SELECT COUNT(*) as count FROM \`competitions\``);
  if (rows[0].count === 0) {
    for (const comp of SEED_COMPETITIONS) {
      await pool.query(
        `INSERT INTO \`competitions\` (id, title, category, category_label, icon, fee, max_quota, current_quota, status, participants_rule, tm_date, contact_person, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        comp
      );
    }
  }

  // Seed default registrations if empty
  const [regCount] = await pool.query(`SELECT COUNT(*) as count FROM \`registrations\``);
  if (regCount[0].count === 0) {
    await pool.query(`
      INSERT INTO \`registrations\` (reg_code, barcode, school_name, mentor_name, mentor_wa, package_type, competition_id, competition_title, participants_names, notes, total_fee, payment_status, checkin_status) VALUES
      ('SA26-102941', 'SA26-102941', 'MTs Negeri 1 Jember', 'Ustadz Abdullah, S.Pd', '081234567890', 'Paket Satuan', 'mipa', 'Olimpiade MIPA', '1. Muhammad Rizky (Kls 9)\n2. Anisa Rahmawati (Kls 8)', 'Pembayaran transfer bank BSI', 50000, 'Terverifikasi', 'hadir'),
      ('SA26-492018', 'SA26-492018', 'SMP Plus Al-Azhar Balung', 'Ibu Nurul Hidayati, M.Pd', '085298765432', 'Paket Satuan', 'albanjari', 'Festival Albanjari', 'Grup Shalawat Syauqul Habib (10 Personel)', 'Konfirmasi via WhatsApp', 50000, 'Terverifikasi', 'belum_hadir'),
      ('SA26-881203', 'SA26-881203', 'SMP Negeri 1 Balung', 'Bapak Bambang Sutrisno, S.Or', '081399887766', 'Paket Satuan', 'volleyball-putra', 'Tournament Volleyball (Putra)', 'Tim Putra Inti (10 Pemain)', 'Berkas Surat Tugas Komplit', 50000, 'Terverifikasi', 'hadir'),
      ('SA26-773192', 'SA26-773192', 'MTs Baitul Hikmah Puger', 'Ustadz Fathurrahman, S.Pd', '085732114455', 'Paket Satuan', 'volleyball-putra', 'Tournament Volleyball (Putra)', 'Tim Putra Baitul Hikmah', 'Menunggu Verifikasi Bukti', 50000, 'Terverifikasi', 'belum_hadir'),
      ('SA26-662301', 'SA26-662301', 'SMP Negeri 2 Wuluhan', 'Ibu Siti Aminah, S.Pd', '082143658709', 'Paket Satuan', 'storytelling', 'Storytelling (Bahasa Inggris)', 'Aisyah Putri Maharani', 'Judul Cerita: The Legend of Papuma', 50000, 'Terverifikasi', 'hadir'),
      ('SA26-551408', 'SA26-551408', 'MTs Al-Ihsan Kencong', 'Ustadz Hilman, S.Sn', '083850070767', 'Paket Satuan', 'singer', 'Singer Lagu Islami', 'Nabila Shofia Ramadhani', 'Lagu Wajib: Deen Assalam', 50000, 'Terverifikasi', 'belum_hadir')
    `);
  }
}

function initSqliteFallback() {
  const { DatabaseSync } = require('node:sqlite');
  const dbDir = path.join(__dirname, 'database');
  if (!fs.existsSync(dbDir)) fs.mkdirSync(dbDir, { recursive: true });

  const dbPath = path.join(dbDir, 'semarak.db');
  sqliteDb = new DatabaseSync(dbPath);

  sqliteDb.exec(`
    CREATE TABLE IF NOT EXISTS competitions (
      id TEXT PRIMARY KEY,
      title TEXT NOT NULL,
      category TEXT NOT NULL DEFAULT 'akademik',
      category_label TEXT NOT NULL,
      icon TEXT NOT NULL DEFAULT '🎯',
      fee INTEGER NOT NULL DEFAULT 50000,
      max_quota INTEGER NOT NULL DEFAULT 30,
      current_quota INTEGER NOT NULL DEFAULT 0,
      status TEXT NOT NULL DEFAULT 'open',
      participants_rule TEXT NOT NULL,
      tm_date TEXT NOT NULL,
      contact_person TEXT NOT NULL,
      description TEXT,
      scoring_rubrics TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS registrations (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      reg_code TEXT NOT NULL UNIQUE,
      barcode TEXT,
      school_name TEXT NOT NULL,
      mentor_name TEXT NOT NULL,
      mentor_wa TEXT NOT NULL,
      package_type TEXT NOT NULL,
      competition_id TEXT NOT NULL,
      competition_title TEXT NOT NULL,
      participants_names TEXT NOT NULL,
      notes TEXT,
      file_documents TEXT,
      verification_notes TEXT,
      total_fee INTEGER NOT NULL DEFAULT 50000,
      payment_status TEXT NOT NULL DEFAULT 'Menunggu Verifikasi',
      checkin_status TEXT NOT NULL DEFAULT 'belum_hadir',
      checkin_at DATETIME,
      payment_method TEXT DEFAULT 'Transfer Bank / Tunai',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS jury_scores (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      registration_id INTEGER NOT NULL,
      competition_id TEXT NOT NULL,
      jury_name TEXT NOT NULL DEFAULT 'Dewan Juri',
      criteria_scores TEXT,
      total_score REAL NOT NULL DEFAULT 0.0,
      performance_duration TEXT,
      is_locked INTEGER NOT NULL DEFAULT 0,
      notes TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS tournament_matches (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      competition_id TEXT NOT NULL,
      match_number TEXT,
      round_name TEXT NOT NULL DEFAULT 'Babak Penyisihan',
      round_order INTEGER NOT NULL DEFAULT 1,
      team1_registration_id INTEGER,
      team1_placeholder TEXT,
      team1_score INTEGER NOT NULL DEFAULT 0,
      team1_set_scores TEXT,
      team2_registration_id INTEGER,
      team2_placeholder TEXT,
      team2_score INTEGER NOT NULL DEFAULT 0,
      team2_set_scores TEXT,
      winner_registration_id INTEGER,
      next_match_id INTEGER,
      next_match_slot TEXT,
      status TEXT NOT NULL DEFAULT 'scheduled',
      court TEXT,
      scheduled_time TEXT,
      referee_name TEXT,
      notes TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
  `);

  const countRow = sqliteDb.prepare("SELECT COUNT(*) as count FROM competitions").get();
  if (countRow.count === 0) {
    const insertStmt = sqliteDb.prepare(
      "INSERT INTO competitions (id, title, category, category_label, icon, fee, max_quota, current_quota, status, participants_rule, tm_date, contact_person, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    for (const comp of SEED_COMPETITIONS) {
      insertStmt.run(...comp);
    }
  }

  const regCount = sqliteDb.prepare("SELECT COUNT(*) as count FROM registrations").get();
  if (regCount.count === 0) {
    const regStmt = sqliteDb.prepare(
      "INSERT INTO registrations (reg_code, barcode, school_name, mentor_name, mentor_wa, package_type, competition_id, competition_title, participants_names, notes, total_fee, payment_status, checkin_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    regStmt.run('SA26-102941', 'SA26-102941', 'MTs Negeri 1 Jember', 'Ustadz Abdullah, S.Pd', '081234567890', 'Paket Satuan', 'mipa', 'Olimpiade MIPA', '1. Muhammad Rizky (Kls 9)\n2. Anisa Rahmawati (Kls 8)', 'Pembayaran transfer bank BSI', 50000, 'Terverifikasi', 'hadir');
    regStmt.run('SA26-492018', 'SA26-492018', 'SMP Plus Al-Azhar Balung', 'Ibu Nurul Hidayati, M.Pd', '085298765432', 'Paket Satuan', 'albanjari', 'Festival Albanjari', 'Grup Shalawat Syauqul Habib (10 Personel)', 'Konfirmasi via WhatsApp', 50000, 'Terverifikasi', 'belum_hadir');
    regStmt.run('SA26-881203', 'SA26-881203', 'SMP Negeri 1 Balung', 'Bapak Bambang Sutrisno, S.Or', '081399887766', 'Paket Satuan', 'volleyball-putra', 'Tournament Volleyball (Putra)', 'Tim Putra Inti (10 Pemain)', 'Berkas Surat Tugas Komplit', 50000, 'Terverifikasi', 'hadir');
    regStmt.run('SA26-662301', 'SA26-662301', 'SMP Negeri 2 Wuluhan', 'Ibu Siti Aminah, S.Pd', '082143658709', 'Paket Satuan', 'storytelling', 'Storytelling (Bahasa Inggris)', 'Aisyah Putri Maharani', 'Judul Cerita: The Legend of Papuma', 50000, 'Terverifikasi', 'hadir');
  }
}

// ==========================================================================
// OPERATIONAL DATA ACCESS FUNCTIONS
// ==========================================================================

async function getOperationalOverview() {
  let registrations = [];
  let competitions = [];
  let juryScores = [];
  let matches = [];

  if (activeDriver === 'mysql') {
    const [regs] = await pool.query("SELECT * FROM registrations ORDER BY id DESC");
    const [comps] = await pool.query("SELECT * FROM competitions ORDER BY id ASC");
    const [scores] = await pool.query("SELECT * FROM jury_scores ORDER BY id DESC");
    const [mtchs] = await pool.query("SELECT * FROM tournament_matches ORDER BY id ASC");
    registrations = regs;
    competitions = comps;
    juryScores = scores;
    matches = mtchs;
  } else {
    registrations = sqliteDb.prepare("SELECT * FROM registrations ORDER BY id DESC").all();
    competitions = sqliteDb.prepare("SELECT * FROM competitions ORDER BY id ASC").all();
    juryScores = sqliteDb.prepare("SELECT * FROM jury_scores ORDER BY id DESC").all();
    matches = sqliteDb.prepare("SELECT * FROM tournament_matches ORDER BY id ASC").all();
  }

  const total = registrations.length;
  const hadir = registrations.filter(r => r.checkin_status === 'hadir').length;
  const belumHadir = total - hadir;
  const terverifikasi = registrations.filter(r => r.payment_status === 'Terverifikasi').length;
  const matchesLive = matches.filter(m => m.status === 'ongoing').length;
  const matchesDone = matches.filter(m => m.status === 'completed').length;
  const scoresLocked = juryScores.filter(s => s.is_locked == 1).length;

  const recentCheckins = registrations
    .filter(r => r.checkin_status === 'hadir')
    .slice(0, 8);

  return {
    driver: activeDriver,
    port: activePort,
    stats: {
      total,
      hadir,
      belumHadir,
      terverifikasi,
      matchesLive,
      matchesDone,
      scoresCount: juryScores.length,
      scoresLocked,
      persenKehadiran: total > 0 ? Math.round((hadir / total) * 100) : 0
    },
    competitions,
    recentCheckins,
    registrations
  };
}

async function getDelegates(filter = {}) {
  let query = "SELECT * FROM registrations WHERE 1=1";
  const params = [];

  if (filter.competition_id) {
    query += " AND competition_id = ?";
    params.push(filter.competition_id);
  }
  if (filter.checkin_status) {
    query += " AND checkin_status = ?";
    params.push(filter.checkin_status);
  }
  if (filter.payment_status) {
    query += " AND payment_status = ?";
    params.push(filter.payment_status);
  }
  if (filter.q) {
    query += " AND (school_name LIKE ? OR reg_code LIKE ? OR mentor_name LIKE ? OR participants_names LIKE ?)";
    const wildcard = `%${filter.q}%`;
    params.push(wildcard, wildcard, wildcard, wildcard);
  }
  query += " ORDER BY id DESC";

  if (activeDriver === 'mysql') {
    const [rows] = await pool.query(query, params);
    return rows;
  } else {
    return sqliteDb.prepare(query).all(...params);
  }
}

async function scanGate(barcodeOrCode) {
  const code = (barcodeOrCode || '').trim();
  if (!code) {
    return { success: false, message: 'Kode barcode kosong.' };
  }

  let delegate = null;
  if (activeDriver === 'mysql') {
    const [rows] = await pool.query(
      "SELECT * FROM registrations WHERE barcode = ? OR reg_code = ? LIMIT 1",
      [code, code]
    );
    if (rows.length > 0) delegate = rows[0];
  } else {
    delegate = sqliteDb.prepare("SELECT * FROM registrations WHERE barcode = ? OR reg_code = ? LIMIT 1").get(code, code);
  }

  if (!delegate) {
    return {
      success: false,
      message: `Barcode/Kode [${code}] tidak ditemukan dalam database delegasi.`
    };
  }

  const alreadyCheckedIn = delegate.checkin_status === 'hadir';
  const nowStr = new Date().toISOString().slice(0, 19).replace('T', ' ');

  if (!alreadyCheckedIn) {
    if (activeDriver === 'mysql') {
      await pool.query(
        "UPDATE registrations SET checkin_status = 'hadir', checkin_at = NOW() WHERE id = ?",
        [delegate.id]
      );
    } else {
      sqliteDb.prepare("UPDATE registrations SET checkin_status = 'hadir', checkin_at = ? WHERE id = ?")
        .run(nowStr, delegate.id);
    }
    delegate.checkin_status = 'hadir';
    delegate.checkin_at = nowStr;
  }

  return {
    success: true,
    alreadyCheckedIn,
    message: alreadyCheckedIn
      ? `Peserta ${delegate.school_name} SUDAH check-in sebelumnya!`
      : `Check-in SUKSES! Selamat bertanding kontingen ${delegate.school_name}.`,
    delegate
  };
}

async function undoGate(id) {
  if (activeDriver === 'mysql') {
    await pool.query(
      "UPDATE registrations SET checkin_status = 'belum_hadir', checkin_at = NULL WHERE id = ?",
      [id]
    );
  } else {
    sqliteDb.prepare("UPDATE registrations SET checkin_status = 'belum_hadir', checkin_at = NULL WHERE id = ?").run(id);
  }
  return { success: true, message: 'Status kehadiran berhasil dibatalkan.' };
}

async function updateVerification(id, status, notes = '') {
  if (activeDriver === 'mysql') {
    await pool.query(
      "UPDATE registrations SET payment_status = ?, verification_notes = ? WHERE id = ?",
      [status, notes, id]
    );
  } else {
    sqliteDb.prepare("UPDATE registrations SET payment_status = ?, verification_notes = ? WHERE id = ?")
      .run(status, notes, id);
  }
  return { success: true, message: `Status verifikasi berkas diperbarui menjadi [${status}].` };
}

async function getJuryArenaData(competitionId) {
  let competition = null;
  let delegates = [];
  let scores = [];

  if (activeDriver === 'mysql') {
    const [compRows] = await pool.query("SELECT * FROM competitions WHERE id = ?", [competitionId]);
    competition = compRows[0] || null;
    const [delRows] = await pool.query("SELECT * FROM registrations WHERE competition_id = ? ORDER BY checkin_status DESC, school_name ASC", [competitionId]);
    delegates = delRows;
    const [scoreRows] = await pool.query("SELECT * FROM jury_scores WHERE competition_id = ?", [competitionId]);
    scores = scoreRows;
  } else {
    competition = sqliteDb.prepare("SELECT * FROM competitions WHERE id = ?").get(competitionId) || null;
    delegates = sqliteDb.prepare("SELECT * FROM registrations WHERE competition_id = ? ORDER BY checkin_status DESC, school_name ASC").all(competitionId);
    scores = sqliteDb.prepare("SELECT * FROM jury_scores WHERE competition_id = ?").all(competitionId);
  }

  // Parse rubrics
  let criteria = DEFAULT_RUBRICS[competitionId] || DEFAULT_RUBRICS.albanjari;
  if (competition && competition.scoring_rubrics) {
    try {
      const parsed = typeof competition.scoring_rubrics === 'string'
        ? JSON.parse(competition.scoring_rubrics)
        : competition.scoring_rubrics;
      if (parsed && parsed.criteria) criteria = parsed.criteria;
    } catch(e) {}
  }

  return {
    competition,
    criteria,
    delegates,
    scores
  };
}

async function saveJuryScore(data) {
  const { registration_id, competition_id, jury_name, criteria_scores, total_score, performance_duration, is_locked, notes } = data;
  const criteriaJson = typeof criteria_scores === 'object' ? JSON.stringify(criteria_scores) : (criteria_scores || '{}');

  if (activeDriver === 'mysql') {
    // Check if score exists
    const [chk] = await pool.query(
      "SELECT id FROM jury_scores WHERE registration_id = ? AND competition_id = ?",
      [registration_id, competition_id]
    );

    if (chk.length > 0) {
      await pool.query(`
        UPDATE jury_scores 
        SET jury_name = ?, criteria_scores = ?, total_score = ?, performance_duration = ?, is_locked = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
      `, [jury_name || 'Dewan Juri', criteriaJson, total_score || 0, performance_duration || '', is_locked ? 1 : 0, notes || '', chk[0].id]);
    } else {
      await pool.query(`
        INSERT INTO jury_scores (registration_id, competition_id, jury_name, criteria_scores, total_score, performance_duration, is_locked, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      `, [registration_id, competition_id, jury_name || 'Dewan Juri', criteriaJson, total_score || 0, performance_duration || '', is_locked ? 1 : 0, notes || '']);
    }
  } else {
    const existing = sqliteDb.prepare("SELECT id FROM jury_scores WHERE registration_id = ? AND competition_id = ?").get(registration_id, competition_id);
    if (existing) {
      sqliteDb.prepare(`
        UPDATE jury_scores
        SET jury_name = ?, criteria_scores = ?, total_score = ?, performance_duration = ?, is_locked = ?, notes = ?
        WHERE id = ?
      `).run(jury_name || 'Dewan Juri', criteriaJson, total_score || 0, performance_duration || '', is_locked ? 1 : 0, notes || '', existing.id);
    } else {
      sqliteDb.prepare(`
        INSERT INTO jury_scores (registration_id, competition_id, jury_name, criteria_scores, total_score, performance_duration, is_locked, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      `).run(registration_id, competition_id, jury_name || 'Dewan Juri', criteriaJson, total_score || 0, performance_duration || '', is_locked ? 1 : 0, notes || '');
    }
  }

  return { success: true, message: 'Nilai dewan juri berhasil disimpan dan diarsipkan.' };
}

async function getTournamentArenaData(competitionId) {
  let matches = [];
  let teams = [];

  if (activeDriver === 'mysql') {
    const [m] = await pool.query(`
      SELECT tm.*, 
             r1.school_name as team1_name, r1.reg_code as team1_code,
             r2.school_name as team2_name, r2.reg_code as team2_code,
             rw.school_name as winner_name
      FROM tournament_matches tm
      LEFT JOIN registrations r1 ON tm.team1_registration_id = r1.id
      LEFT JOIN registrations r2 ON tm.team2_registration_id = r2.id
      LEFT JOIN registrations rw ON tm.winner_registration_id = rw.id
      WHERE tm.competition_id = ?
      ORDER BY tm.round_order ASC, tm.id ASC
    `, [competitionId]);
    matches = m;

    const [t] = await pool.query(
      "SELECT id, reg_code, school_name, participants_names FROM registrations WHERE competition_id = ? ORDER BY school_name ASC",
      [competitionId]
    );
    teams = t;
  } else {
    matches = sqliteDb.prepare(`
      SELECT tm.*, 
             r1.school_name as team1_name, r1.reg_code as team1_code,
             r2.school_name as team2_name, r2.reg_code as team2_code,
             rw.school_name as winner_name
      FROM tournament_matches tm
      LEFT JOIN registrations r1 ON tm.team1_registration_id = r1.id
      LEFT JOIN registrations r2 ON tm.team2_registration_id = r2.id
      LEFT JOIN registrations rw ON tm.winner_registration_id = rw.id
      WHERE tm.competition_id = ?
      ORDER BY tm.round_order ASC, tm.id ASC
    `).all(competitionId);

    teams = sqliteDb.prepare(
      "SELECT id, reg_code, school_name, participants_names FROM registrations WHERE competition_id = ? ORDER BY school_name ASC"
    ).all(competitionId);
  }

  // If matches empty for this tournament competition, auto-generate standard bracket
  if (matches.length === 0 && (competitionId.includes('volleyball') || competitionId === 'mobile-legends')) {
    await generateKnockoutBracket(competitionId);
    return getTournamentArenaData(competitionId);
  }

  return { competitionId, matches, teams };
}

async function updateTournamentMatch(matchId, data) {
  const { team1_score, team2_score, team1_set_scores, team2_set_scores, status, winner_registration_id, court, referee_name, notes } = data;

  let newWinnerId = winner_registration_id || null;
  const isCompleted = status === 'completed';

  if (activeDriver === 'mysql') {
    const [curRows] = await pool.query("SELECT * FROM tournament_matches WHERE id = ?", [matchId]);
    if (curRows.length === 0) return { success: false, message: 'Pertandingan tidak ditemukan.' };
    const cur = curRows[0];

    // Auto-detect winner if completed and not explicitly set
    if (!newWinnerId && isCompleted) {
      if (parseInt(team1_score) > parseInt(team2_score)) newWinnerId = cur.team1_registration_id;
      else if (parseInt(team2_score) > parseInt(team1_score)) newWinnerId = cur.team2_registration_id;
    }

    await pool.query(`
      UPDATE tournament_matches
      SET team1_score = ?, team2_score = ?, team1_set_scores = ?, team2_set_scores = ?,
          status = ?, winner_registration_id = ?, court = ?, referee_name = ?, notes = ?
      WHERE id = ?
    `, [
      parseInt(team1_score || 0), parseInt(team2_score || 0),
      team1_set_scores || '', team2_set_scores || '',
      status || 'scheduled', newWinnerId,
      court || cur.court || '', referee_name || cur.referee_name || '', notes || '',
      matchId
    ]);

    // Propagate winner to next match in bracket
    if (cur.next_match_id && cur.next_match_slot) {
      const col = cur.next_match_slot === 'team1' ? 'team1_registration_id' : 'team2_registration_id';
      await pool.query(`UPDATE tournament_matches SET ${col} = ? WHERE id = ?`, [newWinnerId, cur.next_match_id]);
    }
  } else {
    const cur = sqliteDb.prepare("SELECT * FROM tournament_matches WHERE id = ?").get(matchId);
    if (!cur) return { success: false, message: 'Pertandingan tidak ditemukan.' };

    if (!newWinnerId && isCompleted) {
      if (parseInt(team1_score) > parseInt(team2_score)) newWinnerId = cur.team1_registration_id;
      else if (parseInt(team2_score) > parseInt(team1_score)) newWinnerId = cur.team2_registration_id;
    }

    sqliteDb.prepare(`
      UPDATE tournament_matches
      SET team1_score = ?, team2_score = ?, team1_set_scores = ?, team2_set_scores = ?,
          status = ?, winner_registration_id = ?, court = ?, referee_name = ?, notes = ?
      WHERE id = ?
    `).run(
      parseInt(team1_score || 0), parseInt(team2_score || 0),
      team1_set_scores || '', team2_set_scores || '',
      status || 'scheduled', newWinnerId,
      court || cur.court || '', referee_name || cur.referee_name || '', notes || '',
      matchId
    );

    if (cur.next_match_id && cur.next_match_slot) {
      const col = cur.next_match_slot === 'team1' ? 'team1_registration_id' : 'team2_registration_id';
      sqliteDb.prepare(`UPDATE tournament_matches SET ${col} = ? WHERE id = ?`).run(newWinnerId, cur.next_match_id);
    }
  }

  return { success: true, message: 'Skor dan status pertandingan turnamen berhasil diperbarui!' };
}

async function saveMatchTeams(matchId, team1_id, team2_id) {
  const t1 = team1_id ? parseInt(team1_id) : null;
  const t2 = team2_id ? parseInt(team2_id) : null;

  if (activeDriver === 'mysql') {
    await pool.query(
      "UPDATE tournament_matches SET team1_registration_id = ?, team2_registration_id = ? WHERE id = ?",
      [t1, t2, matchId]
    );
  } else {
    sqliteDb.prepare("UPDATE tournament_matches SET team1_registration_id = ?, team2_registration_id = ? WHERE id = ?").run(t1, t2, matchId);
  }

  return { success: true, message: 'Penetapan tim pertandingan berhasil disimpan.' };
}

async function generateKnockoutBracket(competitionId) {
  let teams = [];
  if (activeDriver === 'mysql') {
    const [t] = await pool.query(
      "SELECT id, school_name FROM registrations WHERE competition_id = ? ORDER BY id ASC LIMIT 8",
      [competitionId]
    );
    teams = t;
    await pool.query("DELETE FROM tournament_matches WHERE competition_id = ?", [competitionId]);
  } else {
    teams = sqliteDb.prepare("SELECT id, school_name FROM registrations WHERE competition_id = ? ORDER BY id ASC LIMIT 8").all(competitionId);
    sqliteDb.prepare("DELETE FROM tournament_matches WHERE competition_id = ?").run(competitionId);
  }

  // Create Standard 8-Bracket: 4 QF, 2 SF, 1 Final
  // We insert Final first to get next_match_id, or build and link
  // Let's create:
  // Match 7: Final (Order 3)
  // Match 5: Semi Final 1 -> Final team1 (Order 2)
  // Match 6: Semi Final 2 -> Final team2 (Order 2)
  // Match 1: QF 1 -> SF 1 team1 (Order 1)
  // Match 2: QF 2 -> SF 1 team2 (Order 1)
  // Match 3: QF 3 -> SF 2 team1 (Order 1)
  // Match 4: QF 4 -> SF 2 team2 (Order 1)

  const insertMatch = async (matchNum, roundName, roundOrder, court, t1Id, t1Place, t2Id, t2Place, nextId, nextSlot) => {
    if (activeDriver === 'mysql') {
      const [res] = await pool.query(`
        INSERT INTO tournament_matches (competition_id, match_number, round_name, round_order, court, team1_registration_id, team1_placeholder, team2_registration_id, team2_placeholder, next_match_id, next_match_slot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `, [competitionId, matchNum, roundName, roundOrder, court, t1Id, t1Place, t2Id, t2Place, nextId, nextSlot]);
      return res.insertId;
    } else {
      const res = sqliteDb.prepare(`
        INSERT INTO tournament_matches (competition_id, match_number, round_name, round_order, court, team1_registration_id, team1_placeholder, team2_registration_id, team2_placeholder, next_match_id, next_match_slot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `).run(competitionId, matchNum, roundName, roundOrder, court, t1Id, t1Place, t2Id, t2Place, nextId, nextSlot);
      return Number(res.lastInsertRowid);
    }
  };

  const finalId = await insertMatch('FINAL', 'Grand Final', 3, 'Lapangan Utama A', null, 'Pemenang SF-1', null, 'Pemenang SF-2', null, null);
  const sf1Id = await insertMatch('SF-1', 'Babak Semi Final', 2, 'Lapangan Utama A', null, 'Pemenang QF-1', null, 'Pemenang QF-2', finalId, 'team1');
  const sf2Id = await insertMatch('SF-2', 'Babak Semi Final', 2, 'Lapangan B', null, 'Pemenang QF-3', null, 'Pemenang QF-4', finalId, 'team2');

  await insertMatch('QF-1', 'Babak Perempat Final', 1, 'Lapangan Utama A', teams[0]?.id || null, 'Unggulan 1', teams[1]?.id || null, 'Penantang 1', sf1Id, 'team1');
  await insertMatch('QF-2', 'Babak Perempat Final', 1, 'Lapangan B', teams[2]?.id || null, 'Unggulan 2', teams[3]?.id || null, 'Penantang 2', sf1Id, 'team2');
  await insertMatch('QF-3', 'Babak Perempat Final', 1, 'Lapangan Utama A', teams[4]?.id || null, 'Unggulan 3', teams[5]?.id || null, 'Penantang 3', sf2Id, 'team1');
  await insertMatch('QF-4', 'Babak Perempat Final', 1, 'Lapangan B', teams[6]?.id || null, 'Unggulan 4', teams[7]?.id || null, 'Penantang 4', sf2Id, 'team2');

  return { success: true, message: 'Bagan 8 Besar Sistem Gugur resmi berhasil digenerate!' };
}

async function getIdCardsData(filter = {}) {
  let query = "SELECT id, reg_code, barcode, school_name, mentor_name, mentor_wa, competition_id, competition_title, participants_names, checkin_status FROM registrations WHERE 1=1";
  const params = [];

  if (filter.competition_id) {
    query += " AND competition_id = ?";
    params.push(filter.competition_id);
  }
  if (filter.school_name) {
    query += " AND school_name LIKE ?";
    params.push(`%${filter.school_name}%`);
  }
  query += " ORDER BY school_name ASC, id ASC";

  if (activeDriver === 'mysql') {
    const [rows] = await pool.query(query, params);
    return rows;
  } else {
    return sqliteDb.prepare(query).all(...params);
  }
}

// ==========================================================================
// REGISTRATION FUNCTIONS
// ==========================================================================

async function getCompetitions() {
  if (activeDriver === 'mysql') {
    const [rows] = await pool.query("SELECT * FROM competitions ORDER BY id ASC");
    return rows;
  } else {
    return sqliteDb.prepare("SELECT * FROM competitions ORDER BY id ASC").all();
  }
}

async function registerParticipant(data) {
  const regCode = "SA26-" + Math.floor(100000 + Math.random() * 900000);
  const totalFee = data.package_type && data.package_type.includes('200.000') ? 200000 : 50000;

  if (activeDriver === 'mysql') {
    await pool.query(
      `INSERT INTO registrations (reg_code, barcode, school_name, mentor_name, mentor_wa, package_type, competition_id, competition_title, participants_names, notes, total_fee, payment_status, checkin_status)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Verifikasi', 'belum_hadir')`,
      [regCode, regCode, data.school_name, data.mentor_name, data.mentor_wa, data.package_type, data.competition_id || '', data.competition_title || '', data.participants_names, data.notes || '', totalFee]
    );

    if (data.competition_id && data.competition_id !== 'all-in') {
      await pool.query(`UPDATE competitions SET current_quota = current_quota + 1 WHERE id = ?`, [data.competition_id]);
    }
  } else {
    const stmt = sqliteDb.prepare(
      `INSERT INTO registrations (reg_code, barcode, school_name, mentor_name, mentor_wa, package_type, competition_id, competition_title, participants_names, notes, total_fee, payment_status, checkin_status)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Verifikasi', 'belum_hadir')`
    );
    stmt.run(regCode, regCode, data.school_name, data.mentor_name, data.mentor_wa, data.package_type, data.competition_id || '', data.competition_title || '', data.participants_names, data.notes || '', totalFee);

    if (data.competition_id && data.competition_id !== 'all-in') {
      sqliteDb.prepare(`UPDATE competitions SET current_quota = current_quota + 1 WHERE id = ?`).run(data.competition_id);
    }
  }

  return { regCode, totalFee };
}

async function getAdminOverview() {
  let registrations = [];
  let competitions = [];

  if (activeDriver === 'mysql') {
    const [regRows] = await pool.query("SELECT * FROM registrations ORDER BY id DESC");
    const [compRows] = await pool.query("SELECT * FROM competitions ORDER BY id ASC");
    registrations = regRows;
    competitions = compRows;
  } else {
    registrations = sqliteDb.prepare("SELECT * FROM registrations ORDER BY id DESC").all();
    competitions = sqliteDb.prepare("SELECT * FROM competitions ORDER BY id ASC").all();
  }

  const totalPendaftar = registrations.length;
  const terverifikasi = registrations.filter(r => r.payment_status === 'Terverifikasi').length;
  const pending = registrations.filter(r => r.payment_status === 'Menunggu Verifikasi').length;
  const totalDana = registrations
    .filter(r => r.payment_status === 'Terverifikasi')
    .reduce((acc, curr) => acc + (curr.total_fee || 0), 0);

  return {
    driver: activeDriver,
    port: activePort,
    stats: {
      totalPendaftar,
      terverifikasi,
      pending,
      totalDana
    },
    competitions,
    registrations
  };
}

async function updateRegistrationStatus(id, newStatus) {
  if (activeDriver === 'mysql') {
    await pool.query("UPDATE registrations SET payment_status = ? WHERE id = ?", [newStatus, id]);
  } else {
    sqliteDb.prepare("UPDATE registrations SET payment_status = ? WHERE id = ?").run(newStatus, id);
  }
}

async function toggleCompetitionStatus(id, newStatus) {
  if (activeDriver === 'mysql') {
    await pool.query("UPDATE competitions SET status = ? WHERE id = ?", [newStatus, id]);
  } else {
    sqliteDb.prepare("UPDATE competitions SET status = ? WHERE id = ?").run(newStatus, id);
  }
}

module.exports = {
  initDatabase,
  getCompetitions,
  registerParticipant,
  getAdminOverview,
  updateRegistrationStatus,
  toggleCompetitionStatus,
  getActiveDriver: () => activeDriver,
  getActivePort: () => activePort,
  // Operational Exports
  getOperationalOverview,
  getDelegates,
  scanGate,
  undoGate,
  updateVerification,
  getJuryArenaData,
  saveJuryScore,
  getTournamentArenaData,
  updateTournamentMatch,
  saveMatchTeams,
  generateKnockoutBracket,
  getIdCardsData
};
