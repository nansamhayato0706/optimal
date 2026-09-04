// Phase 7 (Contact / Log / First) verification
//
// Tests:
//   P7-1 contact.php?i=<contact_uuid> 200 OR 302 to error (depending on data)
//   P7-2 contact_detail.php?i=<contact_uuid> JSON response
//   P7-3 contact_update.php POST without CSRF -> 419 JSON error (not redirect, JSON API)
//   P7-4 log.php?i=<user_uuid> 200
//   P7-5 first.php POST WPF endpoint - JSON response
//
// Env: TEST_ADMIN_ID, TEST_ADMIN_PASSWORD, TEST_USER_UUID

import { request } from 'playwright';

const BASE_URL = 'http://localhost/zaitakukanri_honbu_new';
const ADMIN_ID = process.env.TEST_ADMIN_ID;
const ADMIN_PASSWORD = process.env.TEST_ADMIN_PASSWORD;
const USER_UUID = process.env.TEST_USER_UUID;

if (!ADMIN_ID || !ADMIN_PASSWORD || !USER_UUID) {
  console.error('TEST_ADMIN_ID, TEST_ADMIN_PASSWORD, TEST_USER_UUID required');
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
  await ctx.post(`${BASE_URL}/login.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: token, admin_id: ADMIN_ID, admin_password: ADMIN_PASSWORD, act: 'ログイン' },
    maxRedirects: 0,
  });
}

async function main() {
  const ctx = await request.newContext();
  await login(ctx);
  console.log('Logged in.');

  // P7-1: log.php?i=<user_uuid> 200
  const logResp = await ctx.get(`${BASE_URL}/log.php?i=${encodeURIComponent(USER_UUID)}`);
  const logHtml = await logResp.text();
  record(
    'P7-1 log.php returns 200',
    logResp.status() === 200,
    `Status: ${logResp.status()}`
  );
  record(
    'P7-1 log.php contains date_st/date_ed form fields',
    logHtml.includes('date_st') && logHtml.includes('date_ed'),
    'Form fields present'
  );

  // P7-2: contact_update.php POST without CSRF -> 419 JSON error
  const noCsrfUpdate = await ctx.post(`${BASE_URL}/contact_update.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { contact_uuid: 'test', _token: 'BAD' },
    maxRedirects: 0,
  });
  const updateBody = await noCsrfUpdate.text();
  let updateJson;
  try { updateJson = JSON.parse(updateBody); } catch { updateJson = null; }
  record(
    'P7-2 contact_update.php with bad CSRF returns 403 JSON (not redirect, HTTP-standard status)',
    noCsrfUpdate.status() === 403 && updateJson && updateJson.error === 'invalid_token',
    `Status: ${noCsrfUpdate.status()}, Body: ${updateBody.substring(0, 100)}`
  );

  // P7-3: contact_update.php with valid CSRF + invalid contact_uuid -> 404 not_found
  const reauth = await ctx.get(`${BASE_URL}/user.php`);
  const reauthToken = extractToken(await reauth.text());

  const notFoundUpdate = await ctx.post(`${BASE_URL}/contact_update.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: reauthToken, contact_uuid: '00000000-0000-0000-0000-000000000000' },
    maxRedirects: 0,
  });
  const notFoundBody = await notFoundUpdate.text();
  let notFoundJson;
  try { notFoundJson = JSON.parse(notFoundBody); } catch { notFoundJson = null; }
  record(
    'P7-3 contact_update.php with invalid contact_uuid returns 404 JSON',
    notFoundUpdate.status() === 404 && notFoundJson && notFoundJson.error === 'not_found',
    `Status: ${notFoundUpdate.status()}, Body: ${notFoundBody.substring(0, 100)}`
  );

  // P7-4: contact_update.php GET should be 404 (RouteRegistry only registers POST for this path)
  const getUpdate = await ctx.get(`${BASE_URL}/contact_update.php`);
  const getUpdateBody = await getUpdate.text();
  record(
    'P7-4 contact_update.php GET returns 404 (POST-only route)',
    getUpdate.status() === 404 && getUpdateBody.includes('Not Found'),
    `Status: ${getUpdate.status()}, Body: ${getUpdateBody.substring(0, 50)}`
  );

  // P7-5: first.php POST WPF endpoint without valid token -> some response
  const firstResp = await ctx.post(`${BASE_URL}/first.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { value: '00000000-0000-0000-0000-000000000000' },
    maxRedirects: 0,
  });
  const firstBody = await firstResp.text();
  let firstJson;
  try { firstJson = JSON.parse(firstBody); } catch { firstJson = null; }
  record(
    'P7-5 first.php with invalid token returns JSON (WPF endpoint)',
    firstResp.status() === 200 && firstJson !== null,
    `Status: ${firstResp.status()}, Body: ${firstBody.substring(0, 100)}`
  );

  // P7-6: first.php GET should return 404 (POST-only route)
  const firstGet = await ctx.get(`${BASE_URL}/first.php`, { maxRedirects: 0 });
  const firstGetBody = await firstGet.text();
  record(
    'P7-6 first.php GET returns 404 (POST-only route)',
    firstGet.status() === 404 && firstGetBody.includes('Not Found'),
    `Status: ${firstGet.status()}, Body: ${firstGetBody.substring(0, 50)}`
  );

  await ctx.dispose();

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
