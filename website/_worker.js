// _worker.js — CF Pages Advanced Mode worker
// Handles /api/* requests via D1 / API function, passes everything else to static assets
// Returns proper 404 for non-existent pages instead of serving index.html (Soft 404 fix)

// Known valid static file paths (exact and pattern-based)
const KNOWN_FILES = new Set([
  '/', '/index.html', '/get-runorb.html',
  '/privacy.html', '/terms.html', '/404.html',
  '/robots.txt', '/sitemap.xml',
  '/favicon.svg',
  '/BingSiteAuth.xml',
  '/googled201f3458aabc561.html',
  '/baidu_verify_codeva-PynWpAHt3M.html',
]);

const KNOWN_PREFIXES = [
  '/css/', '/js/', '/docs/', '/videos/', '/assets/',
];

function isValidPath(pathname) {
  if (KNOWN_FILES.has(pathname)) return true;
  for (const prefix of KNOWN_PREFIXES) {
    if (pathname.startsWith(prefix)) return true;
  }
  // Allow known verification files
  if (pathname.endsWith('.xml') || pathname.endsWith('.html') || pathname.endsWith('.mp4')) return true;
  // Allow known doc paths
  if (pathname.includes('/docs/')) return true;
  return false;
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);

    // API requests → handle with D1 / API function
    if (url.pathname.startsWith('/api/')) {
      return handleApi(request, env, url);
    }

    // Static files: only serve valid paths, return 404 for everything else
    if (!isValidPath(url.pathname)) {
      // Try to serve the custom 404 page
      try {
        const notFound = await env.ASSETS.fetch(new Request(new URL('/404.html', url)));
        return new Response(notFound.body, {
          status: 404,
          statusText: 'Not Found',
          headers: getSecurityHeaders(notFound.headers, 'text/html; charset=utf-8'),
        });
      } catch {
        return new Response('404 Not Found', {
          status: 404,
          headers: getSecurityHeaders(null, 'text/plain; charset=utf-8'),
        });
      }
    }

    // Serve valid static assets
    const response = await env.ASSETS.fetch(request);

    // Apply security headers
    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers: getSecurityHeaders(response.headers, response.headers.get('content-type')),
    });
  },
};

// ========== Security Headers ==========
function getSecurityHeaders(originalHeaders, contentType) {
  const headers = new Headers();

  // Preserve original headers
  if (originalHeaders) {
    for (const [key, value] of originalHeaders.entries()) {
      headers.set(key, value);
    }
  }

  // Override/add security headers
  if (contentType) headers.set('Content-Type', contentType);
  headers.set('X-Content-Type-Options', 'nosniff');
  headers.set('X-Frame-Options', 'DENY');
  headers.set('X-XSS-Protection', '1; mode=block');
  headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');
  headers.set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
  // Remove Cloudflare NEL/report-to headers that expose server info
  headers.delete('report-to');
  headers.delete('nel');

  return headers;
}

// ===================== API Router =====================
async function handleApi(request, env, url) {
  const path = url.pathname.replace('/api/', '');
  const method = request.method;

  try {
    // POST /api/login
    if (path === 'login' && method === 'POST') {
      return handleLogin(request, env);
    }
    // POST /api/register
    if (path === 'register' && method === 'POST') {
      return handleRegister(request, env);
    }
    // GET/POST /api/records
    if (path === 'records') {
      if (method === 'GET') return handleGetRecords(request, env, url);
      if (method === 'POST') return handleCreateRecord(request, env);
    }
    // POST /api/admin
    if (path === 'admin' && method === 'POST') {
      return handleAdmin(request, env, url);
    }
    // POST /api/ocr
    if (path === 'ocr' && method === 'POST') {
      return handleOCR(request, env);
    }

    return json({ success: false, error: 'Not found' }, 404);
  } catch (err) {
    console.error('API error:', err);
    return json({ success: false, error: err.message || 'Internal server error' }, 500);
  }
}

// ===================== Auth Helpers =====================
function hashPassword(password) {
  // Simple hash for compatibility — in production use bcrypt via Wasm
  let hash = 0;
  for (let i = 0; i < password.length; i++) {
    const char = password.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return 'sha256_' + Math.abs(hash).toString(36);
}

function generateToken() {
  const arr = new Uint8Array(32);
  crypto.getRandomValues(arr);
  return Array.from(arr, b => b.toString(16).padStart(2, '0')).join('');
}

async function verifyToken(request, env) {
  const auth = request.headers.get('Authorization') || '';
  const token = auth.replace('Bearer ', '');
  if (!token) return null;
  const user = await env.DB.prepare(
    'SELECT u.*, t.token as _t FROM users u JOIN tokens t ON u.id = t.user_id WHERE t.token = ? AND t.expires_at > datetime("now")'
  ).bind(token).first();
  return user || null;
}

// ===================== Login =====================
async function handleLogin(request, env) {
  const { username, password } = await request.json();
  if (!username || !password) return json({ success: false, error: '请填写用户名和密码' });

  const user = await env.DB.prepare(
    'SELECT * FROM users WHERE username = ?'
  ).bind(username).first();

  if (!user) return json({ success: false, error: '用户不存在' });
  if (user.password_hash !== hashPassword(password)) return json({ success: false, error: '密码错误' });

  const token = generateToken();
  await env.DB.prepare(
    'INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, datetime("now", "+30 days"))'
  ).bind(user.id, token).run();

  return json({
    success: true,
    token,
    user: { id: user.id, username: user.username, nickname: user.nickname, role: user.role, gender: user.gender, age_group: user.age_group, country: user.country, city: user.city, avatar_color: user.avatar_color }
  });
}

// ===================== Register =====================
async function handleRegister(request, env) {
  const { username, password, nickname, gender, ageGroup, country, city } = await request.json();
  if (!username || !password) return json({ success: false, error: '请填写用户名和密码' });

  const existing = await env.DB.prepare('SELECT id FROM users WHERE username = ?').bind(username).first();
  if (existing) return json({ success: false, error: '用户名已存在' });

  const result = await env.DB.prepare(
    'INSERT INTO users (username, password_hash, nickname, gender, age_group, country, city, avatar_color, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime("now"))'
  ).bind(username, hashPassword(password), nickname || username, gender || 'unknown', ageGroup || 'adult', country || '中国', city || '', '#FF6B35').run();

  const userId = result.meta.last_row_id;
  const token = generateToken();
  await env.DB.prepare(
    'INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, datetime("now", "+30 days"))'
  ).bind(userId, token).run();

  return json({
    success: true,
    token,
    user: { id: userId, username, nickname: nickname || username, role: 'user', gender: gender || 'unknown', age_group: ageGroup || 'adult', country: country || '中国', city: city || '', avatar_color: '#FF6B35' }
  });
}

// ===================== GET Records =====================
async function handleGetRecords(request, env, url) {
  const params = url.searchParams;
  const action = params.get('action');

  // Group stats
  if (action === 'group-stats') {
    try {
      const row = await env.DB.prepare(
        `SELECT u.gender || '-' || u.age_group as grp, COUNT(DISTINCT r.user_id) as users
         FROM records r
         LEFT JOIN users u ON r.user_id = u.id
         WHERE r.status = 'approved' AND u.gender IS NOT NULL AND u.age_group IS NOT NULL
         GROUP BY u.gender, u.age_group`
      ).all();
      const stats = {};
      const groups = ['male-youth','male-adult','male-senior','female-youth','female-adult','female-senior'];
      for (const g of groups) stats[g] = { users: 0 };
      if (row.results) {
        for (const r of row.results) {
          if (stats[r.grp]) stats[r.grp] = { users: r.users };
        }
      }
      return json({ success: true, stats });
    } catch (e) {
      return json({ success: true, stats: {
        'male-youth': { users: 0 }, 'male-adult': { users: 0 }, 'male-senior': { users: 0 },
        'female-youth': { users: 0 }, 'female-adult': { users: 0 }, 'female-senior': { users: 0 }
      }});
    }
  }

  // My rank
  if (action === 'my-rank') {
    const userId = params.get('userId');
    const group = params.get('group') || 'overall';
    const sort = params.get('sort') || 'energy';
    const sortCol = sort === 'rpm' ? 'rpm' : sort === 'dist' ? 'distance' : 'energy';

    const myBest = await env.DB.prepare(
      `SELECT user_id, MAX(${sortCol}) as best FROM records WHERE user_id = ? AND status = 'approved' GROUP BY user_id`
    ).bind(userId).first();

    if (!myBest) return json({ success: true, rank: null });

    const rank = await env.DB.prepare(
      `SELECT COUNT(*) + 1 as rank FROM (SELECT user_id, MAX(${sortCol}) as best FROM records WHERE status = 'approved' GROUP BY user_id HAVING best > ?) sub`
    ).bind(myBest.best).first();

    return json({ success: true, rank: rank?.rank || 1, best: myBest.best });
  }

  // My best
  if (action === 'my-best') {
    const userId = params.get('userId');
    const row = await env.DB.prepare(
      "SELECT * FROM records WHERE user_id = ? AND status = 'approved' ORDER BY energy DESC LIMIT 1"
    ).bind(userId).first();
    return json({ success: true, record: row || null });
  }

  // My history
  if (action === 'my-history') {
    const userId = params.get('userId');
    const records = await env.DB.prepare(
      "SELECT * FROM records WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 50"
    ).bind(userId).all();
    return json({ success: true, records: records.results || [] });
  }

  // Leaderboard (default)
  const group = params.get('group') || 'overall';
  const page = parseInt(params.get('page')) || 1;
  const pageSize = parseInt(params.get('pageSize')) || 200;
  const sort = params.get('sort') || 'energy';
  const country = params.get('country') || '';
  const city = params.get('city') || '';

  const sortCol = sort === 'rpm' ? 'r.rpm' : sort === 'dist' ? 'r.distance' : 'r.energy';
  const sortDir = 'DESC';

  // Build query with user best scores
  let whereClause = "r.status = 'approved'";
  const binds = [];

  // Group filter
  if (group && group !== 'overall') {
    const parts = group.split('-');
    if (parts.length === 2) {
      whereClause += ' AND u.gender = ? AND u.age_group = ?';
      binds.push(parts[0] === 'male' ? 'male' : 'female', parts[1]);
    }
  }

  // Country filter
  if (country) {
    whereClause += ' AND (u.country = ? OR u.country IS NULL)';
    binds.push(country);
  }

  // City filter
  if (city) {
    whereClause += ' AND (u.city = ? OR u.city IS NULL)';
    binds.push(city);
  }

  const offset = (page - 1) * pageSize;

  // Get leaderboard with user info
  const query = `
    SELECT u.nickname as name, u.username, u.city, u.country, u.avatar_color,
           MAX(r.energy) as energy, MAX(r.rpm) as rpm, MAX(r.distance) as dist
    FROM records r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE ${whereClause}
    GROUP BY r.user_id
    ORDER BY ${sortCol} ${sortDir}
    LIMIT ? OFFSET ?
  `;

  const result = await env.DB.prepare(query).bind(...binds, pageSize, offset).all();

  // Get total count
  const countQuery = `
    SELECT COUNT(DISTINCT r.user_id) as total
    FROM records r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE ${whereClause}
  `;
  const countResult = await env.DB.prepare(countQuery).bind(...binds).first();

  const leaderboard = (result.results || []).map(r => ({
    name: r.name || r.username || '匿名',
    username: r.username,
    city: r.city || '-',
    country: r.country || '中国',
    avatar_color: r.avatar_color,
    energy: r.energy != null ? Number(r.energy) : 0,
    rpm: r.rpm != null ? Number(r.rpm) : 0,
    dist: r.dist != null ? Number(r.dist) : 0,
  }));

  return json({
    success: true,
    leaderboard,
    total: countResult?.total || 0,
    page,
    pageSize,
  });
}

// ===================== POST Records =====================
async function handleCreateRecord(request, env) {
  const user = await verifyToken(request, env);
  if (!user) return json({ success: false, error: '请先登录' });

  const { energy, rpm, distance, device } = await request.json();

  // Auto-approve for now (admin can review later)
  const status = user.role === 'admin' ? 'approved' : 'approved';

  // Determine age_group from user
  const ageGroup = user.age_group || 'adult';
  const gender = user.gender || 'unknown';

  await env.DB.prepare(
    `INSERT INTO records (user_id, energy, rpm, distance, device, status, gender, age_group, country, city, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"))`
  ).bind(user.id, energy, rpm, distance, device || 'manual', status, gender, ageGroup, user.country || '中国', user.city || '').run();

  return json({ success: true, pending: status === 'pending' });
}

// ===================== Admin =====================
async function handleAdmin(request, env, url) {
  const user = await verifyToken(request, env);
  if (!user || user.role !== 'admin') return json({ success: false, error: '无权限' });

  const action = url.searchParams.get('action');
  if (action === 'updateProfile') {
    const { userId, nickname, gender, ageGroup, country, city } = await request.json();
    await env.DB.prepare(
      'UPDATE users SET nickname = ?, gender = ?, age_group = ?, country = ?, city = ? WHERE id = ?'
    ).bind(nickname, gender, ageGroup, country, city, userId).run();
    return json({ success: true });
  }

  return json({ success: false, error: 'Unknown action' }, 400);
}

// ===================== OCR =====================
async function handleOCR(request, env) {
  // Placeholder — OCR would need AI binding
  return json({ success: false, error: 'OCR not available' }, 501);
}

// ===================== Helpers =====================
function json(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' },
  });
}
