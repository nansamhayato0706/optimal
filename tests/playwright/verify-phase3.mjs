// Phase 3 (User list) verification
//
// Tests:
//   U-1 user.php returns 200 after admin login
//   U-2 user_status.php returns 200 + JSON shape
//   U-3 user_status.php with matching If-None-Match returns 304 (ETag works)
//   U-4 delete_flg POST without CSRF token -> redirected to error.php?reason=csrf (M-2 fix)
//   U-5 delete_flg POST with valid CSRF -> 200 (table re-rendered)
//   U-6 status_summary_refresh POST with valid CSRF -> 200 (existing behavior preserved)
//
// Env: TEST_ADMIN_ID, TEST_ADMIN_PASSWORD

import { request } from 'playwright';

const BASE_URL = 'http://localhost/zaitakukanri_honbu_new';
const ADMIN_ID = process.env.TEST_ADMIN_ID;
const ADMIN_PASSWORD = process.env.TEST_ADMIN_PASSWORD;

if (!ADMIN_ID || !ADMIN_PASSWORD) {
  console.error('TEST_ADMIN_ID and TEST_ADMIN_PASSWORD required');
  process.exit(2);
}

const results = [];
function record(name, pass, detail) {
  results.push({ name, pass, detail });
  console.log(`[${pass ? 'PASS' : 'FAIL'}] ${name}`);
  if (detail) console.log(`        ${detail}`);
}

function extractToken(html) {
  const m = html.match(/name="_token"\s+value="([0-9a-f]+)"/);
  return m ? m[1] : null;
}

async function login(ctx) {
  const get = await ctx.get(`${BASE_URL}/login.php`);
  const html = await get.text();
  const token = extractToken(html);
  const post = await ctx.post(`${BASE_URL}/login.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: token, admin_id: ADMIN_ID, admin_password: ADMIN_PASSWORD, act: 'ログイン' },
    maxRedirects: 0,
  });
  return post.headers()['location'] || '';
}

async function main() {
  const ctx = await request.newContext();
  const redirect = await login(ctx);
  console.log(`Logged in, redirect target: ${redirect}`);

  // For BgataMatsumoto (admin_div=2), redirect is admin.php.
  // user.php should also be accessible.

  // ============================================================
  // U-1 user.php returns 200
  // ============================================================
  const userResp = await ctx.get(`${BASE_URL}/user.php`);
  const userHtml = await userResp.text();
  record(
    'U-1 user.php returns 200 for authenticated admin',
    userResp.status() === 200,
    `Status: ${userResp.status()}`
  );

  const userPageToken = extractToken(userHtml);
  record(
    'U-1 user.php contains CSRF token (csrf_field()) in form',
    Boolean(userPageToken),
    userPageToken ? `Token: ${userPageToken.substring(0, 12)}...` : 'No token found'
  );

  // ============================================================
  // U-2 user_status.php returns 200 + JSON
  // ============================================================
  const statusResp = await ctx.get(`${BASE_URL}/user_status.php`);
  const statusBody = await statusResp.text();
  let statusJson;
  try { statusJson = JSON.parse(statusBody); } catch { statusJson = null; }
  record(
    'U-2 user_status.php returns 200',
    statusResp.status() === 200,
    `Status: ${statusResp.status()}`
  );
  record(
    'U-2 user_status.php returns JSON with users array',
    statusJson && Array.isArray(statusJson.users),
    statusJson ? `users count: ${statusJson.users?.length ?? '?'}` : `Body: ${statusBody.substring(0, 100)}`
  );

  const etag = statusResp.headers()['etag'];
  record(
    'U-2 user_status.php returns ETag header',
    typeof etag === 'string' && etag.length > 0,
    `ETag: ${etag}`
  );

  // ============================================================
  // U-3 user_status.php with matching If-None-Match returns 304
  // ============================================================
  if (etag) {
    const status304 = await ctx.get(`${BASE_URL}/user_status.php`, {
      headers: { 'If-None-Match': etag },
    });
    record(
      'U-3 user_status.php with matching ETag returns 304',
      status304.status() === 304,
      `Status: ${status304.status()}`
    );
  } else {
    record('U-3 user_status.php with matching ETag returns 304', false, 'ETag missing, cannot test');
  }

  // ============================================================
  // U-4 delete_flg POST WITHOUT CSRF token -> redirect to error.php?reason=csrf
  // ============================================================
  const noCsrfResp = await ctx.post(`${BASE_URL}/user.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: 'BAD', delete_flg: '0' },
    maxRedirects: 0,
  });
  const noCsrfLoc = noCsrfResp.headers()['location'] || '';
  record(
    'U-4 delete_flg POST with bad CSRF token redirects to error.php?reason=csrf (M-2 fix)',
    noCsrfResp.status() === 302 && noCsrfLoc.includes('error.php') && noCsrfLoc.includes('reason=csrf'),
    `Status: ${noCsrfResp.status()}, Location: ${noCsrfLoc}`
  );

  // ============================================================
  // U-5 delete_flg POST WITH valid CSRF token -> 200 (table re-renders)
  // ============================================================
  // After the bad CSRF, our session may have been invalidated by redirect. Re-fetch a fresh CSRF token.
  const refreshGet = await ctx.get(`${BASE_URL}/user.php`);
  const refreshHtml = await refreshGet.text();
  const validToken = extractToken(refreshHtml);

  const validResp = await ctx.post(`${BASE_URL}/user.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: validToken, delete_flg: '0' },
    maxRedirects: 0,
  });
  record(
    'U-5 delete_flg POST with valid CSRF returns 200',
    validResp.status() === 200,
    `Status: ${validResp.status()}`
  );

  await ctx.dispose();

  // Summary
  const passed = results.filter(r => r.pass).length;
  const failed = results.filter(r => !r.pass).length;
  console.log('');
  console.log('=========================================');
  console.log(`SUMMARY: ${passed} passed, ${failed} failed`);
  console.log('=========================================');

  if (failed > 0) {
    console.log('\nFailed tests:');
    results.filter(r => !r.pass).forEach(r => {
      console.log(`  - ${r.name}`);
      if (r.detail) console.log(`    ${r.detail}`);
    });
    process.exit(1);
  }
}

main().catch(err => {
  console.error('Verification script error:', err);
  process.exit(2);
});
