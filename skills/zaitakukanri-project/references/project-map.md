# Project Map

## PHP

- Workspace root: `C:\xampp\htdocs\zaitakukanri_honbu_new`
- Local URL: `http://localhost/zaitakukanri_honbu_new/`
- Local DB: `jigyodan_zk_honbu`
- Local config: `.env.local`
- Session name: `ZK_OPTIMAL_SESSID`

Related adjacent workspaces:

- Comparison source: `C:\xampp\htdocs\zaitakukanri_honbu_high_speed`
- WPF client: `C:\xampp\htdocs\zaitakukanri_honbu_high_speed\JigyodanWpf_latest_v4.0.3`

Trace request flow in this order:

- `index.php`
- `bootstrap/app.php`
- `bootstrap/routes.php`
- `bootstrap/container.php`
- `app/Controllers/**`
- `app/Services/**`
- `app/Repositories/**`
- `app/Repositories/Contracts/**`
- `app/Views/**`
- `app/Support/**`
- `app/Auth/**`

Important references:

- `AGENTS.md`
- `CLAUDE.md`
- `docs/training_checks.md`
- `db/migration_v1_prev_login_uuid.sql`

High-value PHP files and areas:

- `bootstrap/routes.php`
- `bootstrap/container.php`
- `app/Controllers/TrainingController.php`
- `app/Services/TrainingService.php`
- `app/Repositories/TrainingRepository.php`
- `app/Repositories/UserRepository.php`
- `app/Repositories/UserStatusSummaryRepository.php`
- `app/Views/user/index.php`

WPF endpoints:

- `training_login.php`
- `training_logout.php`
- `first.php`
- `training_insert.php`
- `training_update.php`
- `training_start.php`
- `training_end.php`
- `training_inquiry.php`
- `training_middle.php`
- `training_keyboard.php`
- `training_mouse.php`

Accessible report support:

- `app/Views/report/edit_accessible.php`
- `app/Views/report/confirm_accessible.php`
- `app/Views/report/detail_accessible.php`
- `visual_impairment_flg` controls whether the end-user report flow switches to accessible views.

Compatibility caution:

- Do not assume Apache and CLI use the same PHP version.
- The required baseline for the PHP application is PHP 7 compatibility.
- Do not introduce PHP 8-only syntax in web execution paths.
- Do not rename WPF endpoint URLs.
- Do not change WPF JSON response shapes without checking `docs/training_checks.md`.

## Commands

PHP syntax check:

```bash
/mnt/c/xampp/php/php.exe -l app/Controllers/XxxController.php
```

MySQL:

```bash
/mnt/c/xampp/mysql/bin/mysql.exe -uroot -p6648 jigyodan_zk_honbu
```

Column check example:

```bash
/mnt/c/xampp/mysql/bin/mysql.exe -uroot -p6648 -e "SHOW COLUMNS FROM jigyodan_zk_honbu.mst_user LIKE 'prev_login_uuid';"
```

## Project-Specific Cautions

- `training_update.php` is a polling hot path.
- `user.php` and `user_status.php` are list hot paths.
- Prefer `tbl_user_status_summary` for list status aggregation.
- Avoid restoring dependency on legacy DB views such as `v_chat` or `v_user`.
- `prev_login_uuid` and `prev_login_uuid_expires_at` are required for token rotation tolerance.
- Migration files may be one-time files; check whether a column already exists before applying `ADD COLUMN`.
