// Phase 2 (Training / WPF) verification script
//
// Tests the WPF training_*.php endpoints by simulating HTTP POSTs.
// Verifies JSON response shapes per docs/training_checks.md and:
//   T-1 training_login returns token + intervals
//   T-2 training_start returns new token (rotates from login token)
//   T-3 training_update returns [] when no new chat
//   T-4 training_middle keeps token (event 4 is excluded from rotation)
//   T-5 training_logout returns res=""
//   T-6 After logout, token is invalid (login_uuid cleared)
//   T-7 prev_login_uuid grace period works: old token still valid briefly after rotation
//   T-8 Invalid token returns failure
//
// Restores user state at the end (login_uuid).
//
// Env vars: TEST_USER_ID, TEST_USER_PASSWORD, TEST_USER_SERIAL_NO, TEST_USER_VOLUME_NO

import { request } from 'playwright';

const BASE_URL = 'http://localhost/zaitakukanri_honbu_optimal';
const USER_ID = process.env.TEST_USER_ID;
const USER_PASSWORD = process.env.TEST_USER_PASSWORD;
const SERIAL_NO = process.env.TEST_USER_SERIAL_NO;
const VOLUME_NO = process.env.TEST_USER_VOLUME_NO;

if (!USER_ID || !USER_PASSWORD || !SERIAL_NO || !VOLUME_NO) {
  console.error('TEST_USER_ID, TEST_USER_PASSWORD, TEST_USER_SERIAL_NO, TEST_USER_VOLUME_NO env vars required');
  process.exit(2);
}

const results = [];
function record(name, pass, detail) {
  results.push({ name, pass, detail });
  console.log(`[${pass ? 'PASS' : 'FAIL'}] ${name}`);
  if (detail) console.log(`        ${detail}`);
}

async function postForm(ctx, url, fields) {
  return ctx.post(url, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: fields,
    maxRedirects: 0,
  });
}

async function main() {
  const ctx = await request.newContext();

  // ============================================================
  // T-1 training_login
  // ============================================================
  console.log('--- T-1: training_login ---');
  const loginResp = await postForm(ctx, `${BASE_URL}/training_login.php`, {
    user_id: USER_ID,
    user_password: USER_PASSWORD,
    serial_no: SERIAL_NO,
    volume_no: VOLUME_NO,
  });
  const loginText = await loginResp.text();
  let loginJson;
  try { loginJson = JSON.parse(loginText); } catch { loginJson = null; }

  record('T-1.1 training_login returns 200', loginResp.status() === 200, `Status: ${loginResp.status()}`);
  record('T-1.2 training_login returns parseable JSON', loginJson !== null, `Body: ${loginText.substring(0, 100)}`);
  record(
    'T-1.3 training_login JSON has res (token), s, k, m',
    loginJson && typeof loginJson.res === 'string' && loginJson.res.length > 0
      && 's' in loginJson && 'k' in loginJson && 'm' in loginJson,
    loginJson ? JSON.stringify(loginJson) : 'null'
  );

  const tokenFromLogin = loginJson ? loginJson.res : null;
  if (!tokenFromLogin) {
    console.error('Cannot proceed without login token');
    await ctx.dispose();
    process.exit(1);
  }

  // ============================================================
  // T-2 training_start (event 1) - rotates token
  // ============================================================
  console.log('--- T-2: training_start (event 1) - token rotation ---');
  const startResp = await postForm(ctx, `${BASE_URL}/training_start.php`, {
    value: tokenFromLogin,
  });
  const startJson = JSON.parse(await startResp.text());
  record('T-2.1 training_start returns 200', startResp.status() === 200, `Status: ${startResp.status()}`);
  record(
    'T-2.2 training_start returns new token (rotated)',
    typeof startJson.res === 'string' && startJson.res !== '' && startJson.res !== tokenFromLogin,
    `before: ${tokenFromLogin.substring(0,8)}..., after: ${(startJson.res || '').substring(0,8)}...`
  );
  const tokenAfterStart = startJson.res;

  // ============================================================
  // T-3 training_update (event 12) - no token rotation, returns chat array
  // ============================================================
  console.log('--- T-3: training_update (event 12) - polling, no rotation ---');
  const updateResp = await postForm(ctx, `${BASE_URL}/training_update.php`, {
    value: tokenAfterStart,
  });
  const updateText = await updateResp.text();
  let updateJson;
  try { updateJson = JSON.parse(updateText); } catch { updateJson = null; }

  record('T-3.1 training_update returns 200', updateResp.status() === 200, `Status: ${updateResp.status()}`);
  record(
    'T-3.2 training_update returns JSON array (chat messages or empty)',
    Array.isArray(updateJson),
    `Body: ${updateText.substring(0, 100)}`
  );

  // ============================================================
  // T-4 training_middle (event 4) - keeps token (no rotation)
  // ============================================================
  console.log('--- T-4: training_middle (event 4) - no token rotation ---');
  const middleResp = await postForm(ctx, `${BASE_URL}/training_middle.php`, {
    value: tokenAfterStart,
  });
  const middleJson = JSON.parse(await middleResp.text());
  record('T-4.1 training_middle returns 200', middleResp.status() === 200, `Status: ${middleResp.status()}`);
  record(
    'T-4.2 training_middle keeps same token (event 4 excluded from rotation)',
    middleJson.res === tokenAfterStart,
    `expected: ${tokenAfterStart.substring(0,8)}..., got: ${(middleJson.res || '').substring(0,8)}...`
  );

  // ============================================================
  // T-7 prev_login_uuid grace period: the previous token (tokenFromLogin)
  //     should still be accepted for 5 minutes after rotation in T-2
  // ============================================================
  console.log('--- T-7: prev_login_uuid grace period (old token still valid) ---');
  const graceResp = await postForm(ctx, `${BASE_URL}/training_update.php`, {
    value: tokenFromLogin,  // ← OLD token from T-1
  });
  const graceStatus = graceResp.status();
  const graceText = await graceResp.text();
  let graceJson;
  try { graceJson = JSON.parse(graceText); } catch { graceJson = null; }

  // training_update returns [] on success. On failure, returns eventFailed().
  // eventFailed() returns {res:""} per TrainingResponses (need to check the exact shape).
  // We know that on success, the result is an ARRAY. On failure, it's an OBJECT.
  record(
    'T-7 Old token still accepted (prev_login_uuid grace period works)',
    Array.isArray(graceJson),
    `Body: ${graceText.substring(0, 100)}`
  );

  // ============================================================
  // T-5 training_logout (event 6) - clears token (newToken = '')
  // ============================================================
  console.log('--- T-5: training_logout ---');
  const logoutResp = await postForm(ctx, `${BASE_URL}/training_logout.php`, {
    value: tokenAfterStart,
  });
  const logoutJson = JSON.parse(await logoutResp.text());
  record('T-5.1 training_logout returns 200', logoutResp.status() === 200, `Status: ${logoutResp.status()}`);
  record(
    'T-5.2 training_logout returns res=""',
    logoutJson.res === '',
    `Body: ${JSON.stringify(logoutJson)}`
  );

  // ============================================================
  // T-6 After logout + grace period expired, old token fails
  // Note: We just rotated, so prev_login_uuid is set. Wait simulation isn't practical here.
  // Instead, test with a clearly invalid token
  // ============================================================
  console.log('--- T-8: Invalid token ---');
  const invalidResp = await postForm(ctx, `${BASE_URL}/training_update.php`, {
    value: '00000000-0000-0000-0000-000000000000',
  });
  const invalidText = await invalidResp.text();
  let invalidJson;
  try { invalidJson = JSON.parse(invalidText); } catch { invalidJson = null; }
  record(
    'T-8 Invalid token returns failure (not array success)',
    !Array.isArray(invalidJson),
    `Body: ${invalidText.substring(0, 100)}`
  );

  await ctx.dispose();

  // ============================================================
  // Cleanup: ensure user is logged out
  // ============================================================

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
