// SEMARAK ALIYAH XII 2026 - PRODUCTION SERVER
// Node.js + MySQL (Wamp64 Local & Docker Production)
require('dotenv').config();
const http = require('http');
const fs = require('fs');
const path = require('path');
const db = require('./db');

const START_PORT = parseInt(process.env.PORT || '8080', 10);
const BASE_DIR = __dirname;

const MIME_TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.pdf': 'application/pdf',
  '.csv': 'text/csv; charset=utf-8'
};

// Helper: Parse JSON Body
function parseJsonBody(req) {
  return new Promise((resolve, reject) => {
    let body = '';
    req.on('data', chunk => {
      body += chunk.toString();
      if (body.length > 1e6) { // 1MB limit
        req.destroy();
        reject(new Error('Payload too large'));
      }
    });
    req.on('end', () => {
      try {
        resolve(body ? JSON.parse(body) : {});
      } catch (err) {
        reject(err);
      }
    });
  });
}

// Helper: Send JSON Response
function sendJson(res, statusCode, data) {
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization'
  });
  res.end(JSON.stringify(data));
}

// Request Handler
const serverHandler = async (req, res) => {
  const parsedUrl = new URL(req.url, `http://${req.headers.host}`);
  const pathname = decodeURIComponent(parsedUrl.pathname);

  // Handle CORS Preflight
  if (req.method === 'OPTIONS') {
    res.writeHead(200, {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization'
    });
    res.end();
    return;
  }

  // ========================================================================
  // API ROUTES
  // ========================================================================

  // 1. GET /api/competitions (Daftar Lomba & Kuota Realtime)
  if (pathname === '/api/competitions' && req.method === 'GET') {
    try {
      const competitions = await db.getCompetitions();
      const formatted = competitions.map(c => {
        const isFull = (c.status === 'closed') || (c.current_quota >= c.max_quota);
        return {
          id: c.id,
          title: c.title,
          category: c.category,
          categoryLabel: c.category_label,
          icon: c.icon,
          quota: isFull ? 'Kuota Penuh' : `Tersedia (${c.max_quota - c.current_quota} sisa)`,
          maxQuota: c.max_quota,
          currentQuota: c.current_quota,
          isFull: isFull,
          fee: c.fee,
          desc: c.description,
          peserta: c.participants_rule,
          tmDate: c.tm_date,
          contactPerson: c.contact_person
        };
      });

      sendJson(res, 200, {
        status: 'success',
        driver: db.getActiveDriver(),
        data: formatted
      });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // 2. POST /api/register (Simpan Pendaftaran Baru ke Database)
  if (pathname === '/api/register' && req.method === 'POST') {
    try {
      const body = await parseJsonBody(req);
      if (!body.school_name || !body.mentor_name || !body.mentor_wa || !body.participants_names) {
        sendJson(res, 400, { status: 'error', message: 'Semua kolom wajib harus diisi lengkap!' });
        return;
      }

      const result = await db.registerParticipant(body);

      // Susun pesan WhatsApp konfirmasi
      const waMsg = 
`*PENDAFTARAN RESMI SEMARAK ALIYAH XII 2026*
━━━━━━━━━━━━━━━━━━━━
*Kode Registrasi:* ${result.regCode}
*Asal Sekolah:* ${body.school_name}
*Guru Pendamping:* ${body.mentor_name}
*Nomor WhatsApp:* ${body.mentor_wa}
*Pilihan Paket:* ${body.package_type}
*Cabang Lomba:* ${body.competition_title || body.competition_id}
*Nama Peserta:* 
${body.participants_names}
${body.notes ? `*Catatan Tambahan:* ${body.notes}\n` : ''}*Total Biaya:* Rp ${result.totalFee.toLocaleString('id-ID')}
━━━━━━━━━━━━━━━━━━━━
Halo Panitia Semarak Aliyah 2026, data registrasi di atas sudah tersimpan di sistem database website resmi. Mohon konfirmasi rekening transfer dan petunjuk verifikasi berikutnya. Terima kasih!`;

      const waUrl = `https://wa.me/6283850070767?text=${encodeURIComponent(waMsg)}`;

      sendJson(res, 201, {
        status: 'success',
        message: 'Pendaftaran berhasil disimpan ke database!',
        regCode: result.regCode,
        totalFee: result.totalFee,
        waUrl: waUrl
      });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // 3. GET /api/admin/overview (Dashboard Panitia: Statistik & Tabel Pendaftar)
  if (pathname === '/api/admin/overview' && req.method === 'GET') {
    try {
      const overview = await db.getAdminOverview();
      sendJson(res, 200, { status: 'success', data: overview });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // 4. POST /api/admin/verify (Ubah Status Verifikasi Pembayaran)
  if (pathname === '/api/admin/verify' && req.method === 'POST') {
    try {
      const body = await parseJsonBody(req);
      if (!body.id || !body.status) {
        sendJson(res, 400, { status: 'error', message: 'ID dan status wajib disertakan.' });
        return;
      }
      await db.updateRegistrationStatus(body.id, body.status);
      sendJson(res, 200, { status: 'success', message: 'Status verifikasi berhasil diperbarui!' });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // 5. POST /api/admin/toggle-quota (Buka / Tutup Kuota Lomba)
  if (pathname === '/api/admin/toggle-quota' && req.method === 'POST') {
    try {
      const body = await parseJsonBody(req);
      if (!body.id || !body.status) {
        sendJson(res, 400, { status: 'error', message: 'ID dan status lomba wajib disertakan.' });
        return;
      }
      await db.toggleCompetitionStatus(body.id, body.status);
      sendJson(res, 200, { status: 'success', message: 'Status kuota lomba berhasil diperbarui!' });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // 6. GET /api/admin/export (Export CSV Excel)
  if (pathname === '/api/admin/export' && req.method === 'GET') {
    try {
      const overview = await db.getAdminOverview();
      let csv = 'Kode Registrasi,Asal Sekolah,Guru Pendamping,Nomor WA,Cabang Lomba,Paket,Total Biaya,Status Pembayaran,Tanggal Daftar\n';
      
      overview.registrations.forEach(r => {
        const cleanNames = (r.participants_names || '').replace(/[\r\n]+/g, ' | ').replace(/"/g, '""');
        const cleanSchool = (r.school_name || '').replace(/"/g, '""');
        const cleanMentor = (r.mentor_name || '').replace(/"/g, '""');
        csv += `"${r.reg_code}","${cleanSchool}","${cleanMentor}","'${r.mentor_wa}'","${r.competition_title}","${r.package_type}","${r.total_fee}","${r.payment_status}","${r.created_at}"\n`;
      });

      res.writeHead(200, {
        'Content-Type': 'text/csv; charset=utf-8',
        'Content-Disposition': 'attachment; filename="data-pendaftar-semarak-2026.csv"'
      });
      res.end('\uFEFF' + csv); // BOM for Excel UTF-8
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // 7. OPERASIONAL APIS
  if (pathname === '/api/operasional/overview' && req.method === 'GET') {
    try {
      const data = await db.getOperationalOverview();
      sendJson(res, 200, { status: 'success', data });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  if (pathname === '/api/operasional/delegates' && req.method === 'GET') {
    try {
      const filter = {
        competition_id: parsedUrl.searchParams.get('competition_id') || '',
        checkin_status: parsedUrl.searchParams.get('checkin_status') || '',
        payment_status: parsedUrl.searchParams.get('payment_status') || '',
        q: parsedUrl.searchParams.get('q') || ''
      };
      const delegates = await db.getDelegates(filter);
      sendJson(res, 200, { status: 'success', delegates });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  if (pathname === '/api/operasional/gate/scan' && req.method === 'POST') {
    try {
      const body = await parseJsonBody(req);
      const result = await db.scanGate(body.code);
      sendJson(res, 200, result);
    } catch (err) {
      sendJson(res, 500, { success: false, message: err.message });
    }
    return;
  }

  if (pathname.startsWith('/api/operasional/gate/undo/') && req.method === 'POST') {
    try {
      const id = pathname.replace('/api/operasional/gate/undo/', '');
      const result = await db.undoGate(id);
      sendJson(res, 200, result);
    } catch (err) {
      sendJson(res, 500, { success: false, message: err.message });
    }
    return;
  }

  if (pathname.startsWith('/api/operasional/verification/') && req.method === 'POST') {
    try {
      const id = pathname.replace('/api/operasional/verification/', '');
      const body = await parseJsonBody(req);
      const result = await db.updateVerification(id, body.status, body.notes);
      sendJson(res, 200, result);
    } catch (err) {
      sendJson(res, 500, { success: false, message: err.message });
    }
    return;
  }

  if (pathname.startsWith('/api/operasional/jury/') && req.method === 'GET') {
    try {
      const compId = pathname.replace('/api/operasional/jury/', '');
      const data = await db.getJuryArenaData(compId);
      sendJson(res, 200, { status: 'success', data });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  if (pathname === '/api/operasional/jury/score' && req.method === 'POST') {
    try {
      const body = await parseJsonBody(req);
      const result = await db.saveJuryScore(body);
      sendJson(res, 200, result);
    } catch (err) {
      sendJson(res, 500, { success: false, message: err.message });
    }
    return;
  }

  if (pathname.startsWith('/api/operasional/tournament/generate/') && req.method === 'POST') {
    try {
      const compId = pathname.replace('/api/operasional/tournament/generate/', '');
      const result = await db.generateKnockoutBracket(compId);
      sendJson(res, 200, result);
    } catch (err) {
      sendJson(res, 500, { success: false, message: err.message });
    }
    return;
  }

  if (pathname.startsWith('/api/operasional/tournament/match/') && pathname.endsWith('/score') && req.method === 'POST') {
    try {
      const matchId = pathname.replace('/api/operasional/tournament/match/', '').replace('/score', '');
      const body = await parseJsonBody(req);
      const result = await db.updateTournamentMatch(matchId, body);
      sendJson(res, 200, result);
    } catch (err) {
      sendJson(res, 500, { success: false, message: err.message });
    }
    return;
  }

  if (pathname.startsWith('/api/operasional/tournament/') && req.method === 'GET') {
    try {
      const compId = pathname.replace('/api/operasional/tournament/', '');
      const data = await db.getTournamentArenaData(compId);
      sendJson(res, 200, { status: 'success', data });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  if (pathname === '/api/operasional/id-cards' && req.method === 'GET') {
    try {
      const filter = {
        competition_id: parsedUrl.searchParams.get('competition_id') || '',
        school_name: parsedUrl.searchParams.get('school_name') || ''
      };
      const cards = await db.getIdCardsData(filter);
      sendJson(res, 200, { status: 'success', cards });
    } catch (err) {
      sendJson(res, 500, { status: 'error', message: err.message });
    }
    return;
  }

  // ========================================================================
  // STATIC FILES SERVING
  // ========================================================================
  let targetPath = pathname;
  if (targetPath === '/' || targetPath === '') {
    targetPath = '/index.html';
  } else if (targetPath === '/layanan-registrasi' || targetPath === '/layanan-registrasi/') {
    targetPath = '/layanan-registrasi.html';
  } else if (targetPath === '/operasional' || targetPath === '/operasional/') {
    targetPath = '/frontend/views/operasional.html';
  } else if (targetPath === '/admin' || targetPath === '/admin/') {
    targetPath = '/admin.html';
  }

  const safePath = path.normalize(targetPath).replace(/^(\.\.[\/\\])+/, '');
  let filePath = path.join(BASE_DIR, safePath);

  try {
    const stat = fs.statSync(filePath);
    if (stat.isDirectory()) {
      filePath = path.join(filePath, 'index.html');
    }
  } catch (err) {}

  fs.stat(filePath, (err, stats) => {
    if (err || !stats.isFile()) {
      res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(`<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><title>404 - Halaman Tidak Ditemukan</title>
  <style>
    body { font-family: sans-serif; background: #060911; color: #fff; text-align: center; padding: 100px 20px; }
    h1 { font-size: 3rem; color: #f59e0b; }
    a { color: #38bdf8; text-decoration: none; font-weight: bold; }
  </style>
</head>
<body>
  <h1>404</h1>
  <p>Halaman atau file tidak ditemukan.</p>
  <p><a href="/">← Kembali ke Beranda Semarak Aliyah 2026</a></p>
</body>
</html>`);
      return;
    }

    const ext = path.extname(filePath).toLowerCase();
    const contentType = MIME_TYPES[ext] || 'application/octet-stream';

    res.writeHead(200, {
      'Content-Type': contentType,
      'Content-Length': stats.size,
      'Cache-Control': 'no-cache'
    });

    fs.createReadStream(filePath).pipe(res);
  });
};

// Start Server with Auto-Port Finding
function startServer(port) {
  const server = http.createServer(serverHandler);

  server.once('error', (err) => {
    if (err.code === 'EADDRINUSE') {
      console.log(`⚠️ Port ${port} terpakai, mencoba port ${port + 1}...`);
      startServer(port + 1);
    } else {
      console.error('Server error:', err);
    }
  });

  server.once('listening', async () => {
    console.log(`\n======================================================`);
    console.log(`🚀 Server Semarak Aliyah XII 2026 Berhasil Aktif!`);
    console.log(`📍 URL Beranda: http://localhost:${port}`);
    console.log(`📋 Layanan Registrasi: http://localhost:${port}/layanan-registrasi.html`);
    console.log(`🔐 Panel Admin Panitia: http://localhost:${port}/admin.html`);
    console.log(`======================================================`);

    // Inisialisasi Database
    await db.initDatabase();
    console.log(`⚡ Driver Aktif: ${db.getActiveDriver().toUpperCase()}\n`);
  });

  server.listen(port);
}

startServer(START_PORT);
