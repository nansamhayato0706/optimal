// Phase 4 (Chat) verification
//
// Tests:
//   C-1 chat.php?i=<user_uuid> returns 200 + CSRF token
//   C-2 POST chat.php with BAD CSRF -> 302 error.php?reason=csrf (M-1 fix)
//   C-3 POST chat.php (history mode) with VALID CSRF -> 200
//   C-4 POST chat_send.php with BAD CSRF -> 302 error.php?reason=csrf
//   C-5 POST chat_send.php with VALID CSRF + empty message -> 200 with error message
//   C-6 POST chat_send.php with VALID CSRF + valid message -> 302 chat.php
//   C-7 Subsequent GET chat.php contains the sent message (delete_flg = 0 fix verified)
//
// NOTE: This test actually inserts a chat message into tbl_chat (with [E2E PhaseN] marker).
//       Clean up afterward: DELETE FROM tbl_chat WHERE chat_text LIKE '[E2E Phase4]%';
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

const TEST_MARKER = `[E2E Phase4 ${new Date().toISOString()}]`;
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

  // C-1: GET chat.php?i=<user_uuid>
  const chatGet = await ctx.get(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`);
  const chatHtml = await chatGet.text();
  record('C-1 chat.php returns 200', chatGet.status() === 200, `Status: ${chatGet.status()}`);
  const chatToken = extractToken(chatHtml);
  record(
    'C-1 chat.php contains CSRF token',
    Boolean(chatToken),
    chatToken ? `Token: ${chatToken.substring(0, 12)}...` : 'No token'
  );

  // C-2: POST chat.php with BAD CSRF
  const badPost = await ctx.post(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: 'BAD', history: '1' },
    maxRedirects: 0,
  });
  const badLoc = badPost.headers()['location'] || '';
  record(
    'C-2 POST chat.php with bad CSRF -> 302 error.php?reason=csrf (M-1 fix)',
    badPost.status() === 302 && badLoc.includes('error.php') && badLoc.includes('reason=csrf'),
    `Status: ${badPost.status()}, Location: ${badLoc}`
  );

  // Re-fetch token (session may have been impacted by error redirect)
  const refreshGet = await ctx.get(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`);
  const refreshToken = extractToken(await refreshGet.text());

  // C-3: POST chat.php (history mode) with VALID CSRF
  const histPost = await ctx.post(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: refreshToken, history: '1' },
    maxRedirects: 0,
  });
  record(
    'C-3 POST chat.php (history) with valid CSRF returns 200',
    histPost.status() === 200,
    `Status: ${histPost.status()}`
  );

  // C-4: POST chat_send.php with BAD CSRF
  const badSend = await ctx.post(`${BASE_URL}/chat_send.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: 'BAD', chat_text: 'should not be sent' },
    maxRedirects: 0,
  });
  const badSendLoc = badSend.headers()['location'] || '';
  record(
    'C-4 POST chat_send.php with bad CSRF -> 302 error.php?reason=csrf',
    badSend.status() === 302 && badSendLoc.includes('error.php') && badSendLoc.includes('reason=csrf'),
    `Status: ${badSend.status()}, Location: ${badSendLoc}`
  );

  // Re-fetch token + ensure current_user_uuid is set in session by visiting chat.php
  const reauth = await ctx.get(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`);
  const reauthToken = extractToken(await reauth.text());

  // C-5: POST chat_send.php with VALID CSRF + empty message
  const emptySend = await ctx.post(`${BASE_URL}/chat_send.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: reauthToken, chat_text: '' },
    maxRedirects: 0,
  });
  const emptyHtml = await emptySend.text();
  record(
    'C-5 chat_send.php with empty message returns 200 with error message',
    emptySend.status() === 200 && emptyHtml.includes('メッセージは必須'),
    `Status: ${emptySend.status()}, contains error: ${emptyHtml.includes('メッセージは必須')}`
  );

  // C-6: POST chat_send.php with VALID CSRF + valid message
  const message = `${TEST_MARKER} hello from Playwright`;
  const reauth2 = await ctx.get(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`);
  const reauth2Token = extractToken(await reauth2.text());

  const sendOk = await ctx.post(`${BASE_URL}/chat_send.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: reauth2Token, chat_text: message },
    maxRedirects: 0,
  });
  const sendLoc = sendOk.headers()['location'] || '';
  record(
    'C-6 chat_send.php with valid message returns 302 -> chat.php',
    sendOk.status() === 302 && sendLoc.startsWith('chat.php?i='),
    `Status: ${sendOk.status()}, Location: ${sendLoc}`
  );

  // C-7: GET chat.php contains the sent message
  const verifyGet = await ctx.get(`${BASE_URL}/chat.php?i=${encodeURIComponent(USER_UUID)}`);
  const verifyHtml = await verifyGet.text();
  record(
    'C-7 Sent message appears in chat history',
    verifyHtml.includes(TEST_MARKER),
    `Marker found: ${verifyHtml.includes(TEST_MARKER)} (search: ${TEST_MARKER})`
  );

  await ctx.dispose();

  // Summary
  const passed = results.filter(r => r.pass).length;
  const failed = results.filter(r => !r.pass).length;
  console.log('');
  console.log('=========================================');
  console.log(`SUMMARY: ${passed} passed, ${failed} failed`);
  console.log('=========================================');
  console.log(`\nTest marker for cleanup: ${TEST_MARKER}`);
  console.log(`Cleanup SQL: DELETE FROM tbl_chat WHERE chat_text LIKE '${TEST_MARKER}%';`);

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
