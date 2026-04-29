---
description: PHPシンタックス・ルート登録・DIコンテナ・DB列の確認を実行する
argument-hint: [php | db | full | blank for auto-detect]
---

# Verify Command

Run practical verification for the current PHP repository state. Do not claim success without command output.

## Instructions

Choose only the checks that match the files being changed.

### PHP changes

1. Run syntax check for each changed PHP file:
```bash
php -l app/Controllers/XxxController.php
php -l app/Services/XxxService.php
php -l app/Repositories/XxxRepository.php
```

2. If a new Controller was added, verify route registration:
```bash
grep "XxxController" bootstrap/routes.php
grep "XxxController" bootstrap/container.php
```

3. If a new route was added, verify the path appears in routes.php:
```bash
grep "/xxx.php" bootstrap/routes.php
```

4. Confirm auth is called at the top of each new Controller's `handle()`:
- Browser controllers: `$this->auth->requireAdminRoute()` or `$this->auth->requireUserRoute()`
- WPF endpoints: token checked via `TrainingService`/`FirstService`, CSRF exempt

5. If data writes changed, verify:
- All SQL uses prepared statements (no string concatenation)
- State-changing browser POSTs call `csrf_verify_or_abort($this->request)`
- Writes that must be atomic use `$this->pdo->beginTransaction()`

### DB changes

If ALTER TABLE or new columns are involved:
```bash
C:/xampp/mysql/bin/mysql.exe -u root -p6648 jigyodan_zk_honbu \
  -e "SHOW COLUMNS FROM mst_xxx LIKE 'col_name';"
```

### Common checks

1. Show git status:
```bash
git status --short
```

2. Report commands actually run.
3. Report checks skipped and why.

## Output

Produce a concise report:

```
VERIFICATION: [PASS/FAIL/PARTIAL]

PHP syntax:    [OK / X errors / Skipped]
Route check:   [OK / Missing / Skipped]
Container:     [OK / Missing / Skipped]
Auth check:    [OK / Missing / Skipped]
DB check:      [OK / Issues found / Skipped]
Git status:    [summary]

Commands run:
- php -l ...
- grep ...

Skipped:
- ...

Ready: [YES/NO]
```

## Arguments

`$ARGUMENTS` can be:
- `php` — syntax + route/container checks only
- `db` — DB column existence only
- `full` — all applicable checks
- blank — auto-detect based on changed files
