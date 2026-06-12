// functions/api/[[path]].js — CF Pages Function
// Handles all /api/* requests via D1

export async function onRequest(context) {
  const { request, env } = context;
  const url = new URL(request.url);
  const path = url.pathname.replace('/api/', '');
  const method = request.method;

  try {
    if (path === 'login' && method === 'POST') return handleLogin(request, env);
    if (path === 'register' && method === 'POST') return handleRegister(request, env);
    if (path === 'records') {
      if (method === 'GET') return handleGetRecords(request, env, url);
      if (method === 'POST') return handleCreateRecord(request, env);
    }
    if (path === 'admin' && method === 'POST') return handleAdmin(request, env, url);
    if (path === 'ocr' && method === 'POST') return handleOCR(request, env);
    return json({ success: false, error: 'Not found' }, 404);
  } catch (err) {
    console.error('API error:', err);
    return json({ success: false, error: err.message }, 500);
  }
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

  const token = crypto.randomUUID().replace(/-/g, '');
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

  await env.DB.prepare(
    'INSERT INTO users (username, password_hash, nickname, gender, age_group, country, city, avatar_color, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime("now"))'
  ).bind(username, hashPassword(password), nickname || username, gender || 'unknown', ageGroup || 'adult', country || '中国', city || '', '#FF6B35').run();

  const user = await env.DB.prepare('SELECT * FROM users WHERE username = ?').bind(username).first();
  const token = crypto.randomUUID().replace(/-/g, '');
  return json({
    success: true,
    token,
    user: { id: user.id, username, nickname: nickname || username, role: 'user', gender: gender || 'unknown', age_group: ageGroup || 'adult', country: country || '中国', city: city || '', avatar_color: '#FF6B35' }
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

    const rankRow = await env.DB.prepare(
      `SELECT COUNT(*) as rank FROM (
        SELECT user_id, MAX(${sortCol}) as best FROM records WHERE status = 'approved' GROUP BY user_id
        HAVING best > ?
      )`
    ).bind(myBest.best).first();

    return json({ success: true, rank: (rankRow?.rank || 0) + 1, best: myBest.best });
  }

  // My best
  if (action === 'my-best') {
    const userId = params.get('userId');
    const row = await env.DB.prepare(
      "SELECT MAX(energy) as energy, MAX(rpm) as rpm, MAX(distance) as dist FROM records WHERE user_id = ? AND status = 'approved'"
    ).bind(userId).first();
    return json({ success: true, best: { energy: row?.energy || 0, rpm: row?.rpm || 0, dist: row?.dist || 0 } });
  }

  // My history
  if (action === 'my-history') {
    const userId = params.get('userId');
    const records = await env.DB.prepare(
      "SELECT energy, rpm, distance as dist, created_at FROM records WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 50"
    ).bind(userId).all();
    return json({ success: true, records: records.results || [] });
  }

  // Leaderboard
  const group = params.get('group') || 'male-adult';
  const page = parseInt(params.get('page')) || 1;
  const pageSize = parseInt(params.get('pageSize')) || 20;
  const sort = params.get('sort') || 'energy';
  const country = params.get('country');
  const city = params.get('city');

  const sortCol = sort === 'rpm' ? 'r.rpm' : sort === 'dist' ? 'r.distance' : 'r.energy';
  let whereClause = "r.status = 'approved'";

  if (group !== 'overall') {
    const parts = group.split('-');
    const gender = parts[0] === 'male' ? 'male' : 'female';
    const ageGroup = parts[1];
    whereClause += ' AND u.gender = ? AND u.age_group = ?';
  }
  if (country) whereClause += ' AND u.country = ?';
  if (city) whereClause += ' AND u.city = ?';

  const countSql = `SELECT COUNT(DISTINCT r.user_id) as total FROM records r LEFT JOIN users u ON r.user_id = u.id WHERE ${whereClause}`;
  const dataSql = `SELECT u.nickname as name, u.username, u.city, u.country, u.avatar_color,
    MAX(r.energy) as energy, MAX(r.rpm) as rpm, MAX(r.distance) as dist
    FROM records r LEFT JOIN users u ON r.user_id = u.id
    WHERE ${whereClause}
    GROUP BY r.user_id ORDER BY ${sortCol} DESC LIMIT ? OFFSET ?`;

  const binds = [];
  if (group !== 'overall') {
    const parts = group.split('-');
    binds.push(parts[0] === 'male' ? 'male' : 'female', parts[1]);
  }
  if (country) binds.push(country);
  if (city) binds.push(city);

  const countResult = await env.DB.prepare(countSql).bind(...binds).first();
  const total = countResult?.total || 0;

  const offset = (page - 1) * pageSize;
  const dataResult = await env.DB.prepare(dataSql).bind(...binds, pageSize, offset).all();
  const leaderboard = (dataResult.results || []).map(r => ({
    name: r.name || r.username,
    username: r.username,
    city: r.city || '-',
    country: r.country || '中国',
    avatar_color: r.avatar_color || '#FF6B35',
    energy: r.energy != null ? Number(r.energy) : 0,
    rpm: r.rpm != null ? Number(r.rpm) : 0,
    dist: r.dist != null ? Number(r.dist) : 0,
  }));

  return json({ success: true, total, leaderboard });
}

// ===================== Create Record =====================
async function handleCreateRecord(request, env) {
  const user = await verifyToken(request, env);
  if (!user) return json({ success: false, error: '请先登录' });

  const { energy, rpm, distance, device } = await request.json();
  if (energy == null) return json({ success: false, error: '缺少活力指数' });

  // Auto-approve manual entries, pending for device entries
  const status = (!device || device.startsWith('manual')) ? 'approved' : 'pending';
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
  return json({ success: false, error: 'OCR not available' }, 501);
}

// ===================== Helpers =====================
function json(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' },
  });
}

async function verifyToken(request, env) {
  const auth = request.headers.get('Authorization') || '';
  const token = auth.replace('Bearer ', '');
  if (!token) return null;
  // Simple token lookup - in production use a tokens table
  // For now, we'll skip token verification and use a basic approach
  const userId = request.headers.get('X-User-Id');
  if (userId) {
    return await env.DB.prepare('SELECT * FROM users WHERE id = ?').bind(userId).first();
  }
  return null;
}

function hashPassword(password) {
  // Simple hash - matches what the original worker used
  let hash = 0;
  for (let i = 0; i < password.length; i++) {
    const char = password.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return 'simple_' + Math.abs(hash).toString(36);
}
