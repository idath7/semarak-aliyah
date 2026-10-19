/**
 * SEMARAK ALIYAH XII 2026 - FRONTEND & OPERATIONAL SERVER (EXPRESS.JS)
 * Menangani UI Beranda, Layanan Registrasi, Panel Operasional Hari-H Mandiri, dan Proxy API
 */

const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../.env') });
const express = require('express');
const cors = require('cors');
const db = require('../db');

const app = express();
const PORT = parseInt(process.env.PORT || '8080', 10);
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000';

// Middlewares
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve Static Assets (CSS, JS, Images, Documents)
app.use(express.static(path.join(__dirname, 'public')));
app.use('/assets', express.static(path.join(__dirname, 'public/assets')));
// Also serve root assets if referenced
app.use('/root-assets', express.static(path.join(__dirname, '../assets')));

// ==========================================================================
// FRONTEND & OPERATIONAL PAGE ROUTES
// ==========================================================================

// 1. Beranda Utama
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'views', 'index.html'));
});

// 2. Portal Layanan Registrasi
app.get('/layanan-registrasi', (req, res) => {
  res.sendFile(path.join(__dirname, 'views', 'layanan-registrasi.html'));
});

// 3. Pengalihan Operasional Langsung ke Laravel Velzon (Port 8000)
// Menghilangkan duplikasi panel dan iframe (Opsi 3 Terpadu)
app.get('/operasional', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin');
});
app.get('/operasional/turnamen', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin/tournament/arena/volleyball-putra');
});
app.get('/operasional/turnamen/:comp', (req, res) => {
  res.redirect(`http://127.0.0.1:8000/admin/tournament/arena/${req.params.comp || 'volleyball-putra'}`);
});
app.get('/operasional/gate', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin/gate-checkin');
});
app.get('/operasional/juri', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin/jury');
});
app.get('/operasional/print', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin/print-studio');
});
app.get('/operasional/verifikasi', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin/verification');
});

// 4. Panel Admin Panitia Pendaftaran (Laravel Port 8000)
app.get('/admin', (req, res) => {
  res.redirect('http://127.0.0.1:8000/admin');
});

// 5. Login Panitia
app.get('/login', (req, res) => {
  res.redirect('http://127.0.0.1:8000/login');
});

// ==========================================================================
// OPERATIONAL API ENDPOINTS (STANDALONE EXPRESS.JS)
// ==========================================================================

// 1. Dashboard Overview Stats & Recent Activity
app.get('/api/operasional/overview', async (req, res) => {
  try {
    const data = await db.getOperationalOverview();
    res.json({ status: 'success', data });
  } catch (err) {
    res.status(500).json({ status: 'error', message: err.message });
  }
});

// 2. Delegate List (Searchable & Filterable)
app.get('/api/operasional/delegates', async (req, res) => {
  try {
    const delegates = await db.getDelegates(req.query);
    res.json({ status: 'success', delegates });
  } catch (err) {
    res.status(500).json({ status: 'error', message: err.message });
  }
});

// 3. Gate Barcode Check-in
app.post('/api/operasional/gate/scan', async (req, res) => {
  try {
    const { code } = req.body;
    const result = await db.scanGate(code);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 4. Gate Check-in Undo
app.post('/api/operasional/gate/undo/:id', async (req, res) => {
  try {
    const result = await db.undoGate(req.params.id);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 5. File Verification Update
app.post('/api/operasional/verification/:id', async (req, res) => {
  try {
    const { status, notes } = req.body;
    const result = await db.updateVerification(req.params.id, status, notes);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 6. Jury Arena Data & Rubrics
app.get('/api/operasional/jury/:competitionId', async (req, res) => {
  try {
    const data = await db.getJuryArenaData(req.params.competitionId);
    res.json({ status: 'success', data });
  } catch (err) {
    res.status(500).json({ status: 'error', message: err.message });
  }
});

// 7. Save Jury Score
app.post('/api/operasional/jury/score', async (req, res) => {
  try {
    const result = await db.saveJuryScore(req.body);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 8. Tournament Bracket & Match Data
app.get('/api/operasional/tournament/:competitionId', async (req, res) => {
  try {
    const data = await db.getTournamentArenaData(req.params.competitionId);
    res.json({ status: 'success', data });
  } catch (err) {
    res.status(500).json({ status: 'error', message: err.message });
  }
});

// 9. Update Tournament Match Score
app.post('/api/operasional/tournament/match/:matchId/score', async (req, res) => {
  try {
    const result = await db.updateTournamentMatch(req.params.matchId, req.body);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 10. Set Match Teams
app.post('/api/operasional/tournament/match/:matchId/teams', async (req, res) => {
  try {
    const { team1_id, team2_id } = req.body;
    const result = await db.saveMatchTeams(req.params.matchId, team1_id, team2_id);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 11. Generate Knockout Bracket
app.post('/api/operasional/tournament/generate/:competitionId', async (req, res) => {
  try {
    const result = await db.generateKnockoutBracket(req.params.competitionId);
    res.json(result);
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// 12. ID Cards Data
app.get('/api/operasional/id-cards', async (req, res) => {
  try {
    const cards = await db.getIdCardsData(req.query);
    res.json({ status: 'success', cards });
  } catch (err) {
    res.status(500).json({ status: 'error', message: err.message });
  }
});

// ==========================================================================
// API GATEWAY / PROXY TO LARAVEL BACKEND (REGISTRATION SYSTEM)
// ==========================================================================

async function forwardToLaravel(req, res, targetPath, method = 'GET', body = null) {
  try {
    const url = `${LARAVEL_API_URL}${targetPath}`;
    const options = {
      method: method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    };

    if (body && ['POST', 'PUT', 'PATCH'].includes(method)) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const data = await response.json().catch(() => null);

    res.status(response.status).json(data || { status: 'error', message: 'Respon Laravel tidak valid' });
  } catch (err) {
    // Fallback direct to local database adapter
    console.warn(`[Express Gateway] Gagal menghubungi Laravel API (${targetPath}):`, err.message);
    if (targetPath === '/api/competitions') {
      const competitions = await db.getCompetitions();
      return res.json(competitions);
    }
    if (targetPath === '/api/register' && method === 'POST') {
      const regResult = await db.registerParticipant(body);
      return res.json({ status: 'success', ...regResult });
    }

    res.status(503).json({
      status: 'fallback',
      message: 'Backend Laravel sedang offline/memuat. Data dilayani langsung oleh Express.js DB adapter.',
      error: err.message
    });
  }
}

// 1. GET /api/competitions
app.get('/api/competitions', async (req, res) => {
  await forwardToLaravel(req, res, '/api/competitions', 'GET');
});

// 2. POST /api/register
app.post('/api/register', async (req, res) => {
  await forwardToLaravel(req, res, '/api/register', 'POST', req.body);
});

// 3. GET /api/admin/overview
app.get('/api/admin/overview', async (req, res) => {
  await forwardToLaravel(req, res, '/api/admin/overview', 'GET');
});

// 4. POST /api/admin/verify
app.post('/api/admin/verify', async (req, res) => {
  await forwardToLaravel(req, res, '/api/admin/verify', 'POST', req.body);
});

// 5. POST /api/admin/toggle-quota
app.post('/api/admin/toggle-quota', async (req, res) => {
  await forwardToLaravel(req, res, '/api/admin/toggle-quota', 'POST', req.body);
});

// 6. GET /api/admin/export
app.get('/api/admin/export', async (req, res) => {
  try {
    const url = `${LARAVEL_API_URL}/api/admin/export`;
    const response = await fetch(url);
    if (!response.ok) throw new Error('Gagal mengambil export dari Laravel');
    
    const csvData = await response.text();
    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename="data-pendaftar-semarak-2026.csv"');
    res.send(csvData);
  } catch (err) {
    // Generate CSV locally from database
    const overview = await db.getAdminOverview();
    let csv = "ID,Kode Pendaftaran,Nama Sekolah,Nama Pembina,WhatsApp Pembina,Cabang Lomba,Nama Peserta,Biaya,Status Pembayaran\n";
    for (const r of overview.registrations) {
      csv += `"${r.id}","${r.reg_code}","${(r.school_name || '').replace(/"/g, '""')}","${(r.mentor_name || '').replace(/"/g, '""')}","${r.mentor_wa}","${(r.competition_title || '').replace(/"/g, '""')}","${(r.participants_names || '').replace(/"/g, '""').replace(/\n/g, ' ')}","${r.total_fee}","${r.payment_status}"\n`;
    }
    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename="data-pendaftar-semarak-2026.csv"');
    res.send(csv);
  }
});

// Catch-all 404
app.use((req, res) => {
  res.status(404).send(`<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>404 - Halaman Tidak Ditemukan</title>
<style>body{font-family:sans-serif;background:#060911;color:#fff;text-align:center;padding:100px 20px;}h1{color:#f59e0b;}a{color:#38bdf8;}</style>
</head><body><h1>404</h1><p>Halaman tidak ditemukan di server Express.js.</p><a href="/">← Kembali ke Beranda</a></body></html>`);
});

// Auto-port finding & DB initialization
async function startListening(port) {
  try {
    await db.initDatabase();
  } catch (dbErr) {
    console.error('Database init error:', dbErr.message);
  }

  const server = app.listen(port, () => {
    console.log(`\n======================================================`);
    console.log(`🚀 Frontend & Operasional Express.js Semarak Aliyah 2026 Aktif!`);
    console.log(`📍 URL Beranda        : http://localhost:${port}`);
    console.log(`📋 Layanan Registrasi : http://localhost:${port}/layanan-registrasi`);
    console.log(`⚡ Panel Operasional  : http://localhost:${port}/operasional (MANDIRI)`);
    console.log(`🔐 Panel Pendaftaran  : http://localhost:8000/admin (LARAVEL)`);
    console.log(`🔗 Database Driver    : ${db.getActiveDriver()} (Port ${db.getActivePort() || 'sqlite'})`);
    console.log(`======================================================\n`);
  });

  server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
      console.log(`⚠️ Port ${port} terpakai, mencoba port ${port + 1}...`);
      startListening(port + 1);
    } else {
      console.error('Express server error:', err);
    }
  });
}

startListening(PORT);
