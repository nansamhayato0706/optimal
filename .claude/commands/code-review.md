---
description: Code review — local uncommitted changes or GitHub PR (pass PR number/URL for PR mode)
argument-hint: [pr-number | pr-url | blank for local review]
---

# Code Review

**Input**: $ARGUMENTS

---

## Mode Selection

If `$ARGUMENTS` contains a PR number, PR URL, or `--pr`:
→ Jump to **PR Review Mode** below.

Otherwise:
→ Use **Local Review Mode**.

---

## Local Review Mode

Comprehensive review of local changes in this PHP repository.

### Phase 1 — GATHER

```bash
git status --short
git diff --name-only
```

If no changed files, stop: "Nothing to review."

### Phase 2 — REVIEW

Read each changed file in full. Check for:

**Security Issues (CRITICAL):**
- Hardcoded credentials, API keys, tokens
- SQL injection vulnerabilities (no prepared statements)
- XSS vulnerabilities (`$h()` escaping missing in Views)
- Missing CSRF check on browser POST endpoints
- Missing auth (`requireAdminRoute`/`requireUserRoute`) at top of `handle()`
- Path traversal in file uploads

**Correctness / Reliability (HIGH):**
- Route not registered in `bootstrap/routes.php`
- Service/Repository not registered in `bootstrap/container.php`
- Missing transaction for multi-step writes
- Controller doing business logic (should be in Service)
- `prev_login_uuid_expires_at > NOW()` missing in token lookup queries

**Best Practices (MEDIUM):**
- PHP: raw SQL concatenation, unescaped output in Views
- Views redefining `$h` (override of the injected escape function)
- SELECT * instead of explicit column list in hot paths
- N+1 queries in loops
- Missing `declare(strict_types=1)` in new PHP files

### Phase 3 — REPORT

Generate report with:
- Severity: CRITICAL, HIGH, MEDIUM, LOW
- File location and line numbers
- Issue description
- Suggested fix

Block commit if CRITICAL or HIGH issues found. Never approve code with security vulnerabilities.

---

## PR Review Mode

### Phase 1 — FETCH

```bash
gh pr view <NUMBER> --json number,title,body,author,baseRefName,headRefName,changedFiles,additions,deletions
gh pr diff <NUMBER>
```

### Phase 2 — CONTEXT

1. Read `CLAUDE.md`, `AGENTS.md`, and relevant `.claude/rules/**`
2. Check `docs/` for relevant guides
3. Parse PR description for goals and linked issues

### Phase 3 — REVIEW

Read each changed file in full. Apply the same checklist as Local Review Mode, plus:

| Category | What to Check |
|---|---|
| **Correctness** | Logic errors, null handling, edge cases |
| **Pattern Compliance** | DI wiring, route registration, thin controller |
| **Security** | Auth gaps, CSRF, injection, path traversal, XSS |
| **Performance** | N+1 queries, missing indexes, `tbl_user_status_summary` over-update |
| **Completeness** | Missing DB migration, missing route, missing container binding |

### Phase 4 — VALIDATE

```bash
php -l <changed-php-files>
git status --short
```

### Phase 5 — DECIDE

| Condition | Decision |
|---|---|
| Zero CRITICAL/HIGH, validation passes | **APPROVE** |
| Only MEDIUM/LOW, validation passes | **APPROVE** with comments |
| Any HIGH or validation failures | **REQUEST CHANGES** |
| Any CRITICAL | **BLOCK** |

### Phase 6 — REPORT

```markdown
# PR Review: #<NUMBER> — <TITLE>

**Decision**: APPROVE | REQUEST CHANGES | BLOCK

## Findings

### CRITICAL
### HIGH
### MEDIUM
### LOW

## Validation Results
| Check | Result |
|---|---|
| PHP syntax | ✅ / ❌ / ⏭️ |
| DB check | ✅ / ❌ / ⏭️ |
```

### Phase 7 — PUBLISH

```bash
gh pr review <NUMBER> --approve --body "<summary>"
# or
gh pr review <NUMBER> --request-changes --body "<required fixes>"
```
