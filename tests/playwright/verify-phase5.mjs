// Phase 5 (Group/Admin/Notice/Link) verification
//
// Tests with BgataMatsumoto (admin_div=2):
//   P5-1 admin.php 200 + CSRF token
//   P5-2 notice.php 200 + CSRF token
//   P5-3 link.php 200 + CSRF token
//   P5-4 group.php for admin_div=2 -> NOT 200 (admin_div=1 only)
//   P5-5 admin_edit.php (GET) 200
//   P5-6 admin_edit.php POST with bad CSRF -> 302 error.php?reason=csrf
//
// Env: TEST_ADMIN_ID, TEST_ADMIN_PASSWORD

import { request } from 'playwright';

const BASE_URL = 'http://localhost/zaitakukanri_honbu_optimal';
const ADMIN_ID = process.env.TEST_ADMIN_ID;
const ADMIN_PASSWORD = process.env.TEST_ADMIN_PASSWORD;

if (!ADMIN_ID || !ADMIN_PASSWORD) {
  console.error('TEST_ADMIN_ID, TEST_ADMIN_PASSWORD required');
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

  // P5-1: admin.php
  const adminResp = await ctx.get(`${BASE_URL}/admin.php`);
  const adminHtml = await adminResp.text();
  record('P5-1 admin.php returns 200', adminResp.status() === 200, `Status: ${adminResp.status()}`);
  const adminToken = extractToken(adminHtml);
  // admin.php is a list page; no form, so token may be absent — check the title instead
  record(
    'P5-1 admin.php title contains 管理者一覧',
    adminHtml.includes('管理者一覧'),
    `Title check`
  );

  // P5-2: notice.php
  const noticeResp = await ctx.get(`${BASE_URL}/notice.php`);
  const noticeHtml = await noticeResp.text();
  record('P5-2 notice.php returns 200', noticeResp.status() === 200, `Status: ${noticeResp.status()}`);
  const noticeToken = extractToken(noticeHtml);
  record(
    'P5-2 notice.php contains CSRF token (form present)',
    Boolean(noticeToken),
    noticeToken ? `Token: ${noticeToken.substring(0, 12)}...` : 'No token'
  );

  // P5-3: link.php
  const linkResp = await ctx.get(`${BASE_URL}/link.php`);
  const linkHtml = await linkResp.text();
  record('P5-3 link.php returns 200', linkResp.status() === 200, `Status: ${linkResp.status()}`);
  const linkToken = extractToken(linkHtml);
  record(
    'P5-3 link.php contains CSRF token (form present)',
    Boolean(linkToken),
    linkToken ? `Token: ${linkToken.substring(0, 12)}...` : 'No token'
  );

  // P5-4: group.php should redirect or deny for admin_div=2
  const groupResp = await ctx.get(`${BASE_URL}/group.php`, { maxRedirects: 0 });
  const groupLoc = groupResp.headers()['location'] || '';
  record(
    'P5-4 group.php (admin_div=1 only) is denied for admin_div=2',
    groupResp.status() === 302 || (groupResp.status() === 200 && !await groupResp.text().then(t => t.includes('グループ'))),
    `Status: ${groupResp.status()}, Location: ${groupLoc}`
  );

  // P5-5: admin_edit.php (GET)
  const editGet = await ctx.get(`${BASE_URL}/admin_edit.php`);
  const editHtml = await editGet.text();
  record('P5-5 admin_edit.php GET returns 200', editGet.status() === 200, `Status: ${editGet.status()}`);
  const editToken = extractToken(editHtml);
  record(
    'P5-5 admin_edit.php contains CSRF token',
    Boolean(editToken),
    editToken ? `Token: ${editToken.substring(0, 12)}...` : 'No token'
  );

  // P5-6: admin_edit.php POST with BAD CSRF
  const badEditPost = await ctx.post(`${BASE_URL}/admin_edit.php`, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    form: { _token: 'BAD', admin_id: 'test', admin_name: 'test' },
    maxRedirects: 0,
  });
  const badEditLoc = badEditPost.headers()['location'] || '';
  record(
    'P5-6 admin_edit.php POST with bad CSRF -> 302 error.php?reason=csrf',
    badEditPost.status() === 302 && badEditLoc.includes('error.php') && badEditLoc.includes('reason=csrf'),
    `Status: ${badEditPost.status()}, Location: ${badEditLoc}`
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
