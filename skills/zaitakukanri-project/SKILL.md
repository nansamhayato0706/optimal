---
name: zaitakukanri-project
description: Work effectively in the zaitakukanri_honbu_new PHP codebase and its adjacent WPF client. Use when Codex needs project-specific navigation, route and DI conventions, WPF training_* endpoint compatibility, hot-path investigation, verification commands, or comparison guidance against zaitakukanri_honbu_high_speed.
---

# Zaitakukanri Project

## Overview

Use this skill to rebuild project context before editing `zaitakukanri_honbu_new`.

The standing project goal is to make this codebase more efficient, easier to understand, and faster in runtime behavior. When choosing between implementation options, prefer the one that improves those three qualities without breaking compatibility.

PHP 7 compatibility is a hard requirement. Do not introduce PHP 8-only syntax or implementation choices that require PHP 8 in web execution paths.

Read [project-map.md](references/project-map.md) when you need the detailed file map, command list, endpoint list, or project-specific cautions.

Read `AGENTS.md` first when starting work in this repository.

## Quick Start

For PHP changes, work in `C:\xampp\htdocs\zaitakukanri_honbu_new`.

Use `C:\xampp\htdocs\zaitakukanri_honbu_high_speed` as the comparison source when checking behavior parity or migration gaps.

Follow the optimal request flow:

- `index.php`
- `bootstrap/app.php`
- `bootstrap/routes.php`
- `app/Controllers/**`
- `app/Services/**`
- `app/Repositories/**`
- `app/Views/**`
- `app/Support/**`

Do not look for `app/EntryPoints/**` in optimal; that is a high_speed pattern.

Do not change public PHP endpoint names that existing clients use. In particular, keep WPF endpoints such as `training_*.php` and `first.php` stable.

After making changes, always run the narrowest useful verification before finishing. For PHP changes, run `php -l` on changed PHP files at minimum. For WPF endpoint changes, also check HTTP response shapes against `docs/training_checks.md`.

## PHP Workflow

Keep controllers thin. Put business logic in `Services`, SQL in `Repositories`, and cross-cutting helpers in `Support`.

Add routes in `bootstrap/routes.php` and dependency registrations in `bootstrap/container.php`.

Prefer repository interfaces under `app/Repositories/Contracts` when adding new repository dependencies.

Use `AppConfig` for new configuration access. Do not spread new `define()` dependencies into app code.

Use `View::render()` conventions:

- `$h()` is injected by the renderer.
- Do not redefine `$h` in templates.
- Browser POST forms need `$_csrf_field`.
- WPF JSON endpoints are CSRF-exempt and token-authenticated.

Treat `training_update.php` and `TrainingService` / `TrainingRepository` as hot-path files when the task is about polling, chat, work tracking, or DB pressure.

Treat `user.php`, `user_status.php`, `UserRepository`, and `UserStatusSummaryRepository` as hot-path files when the task is about user list speed or status display.

Do not claim a missing MySQL index from code alone. Confirm with schema files, `SHOW INDEX`, or `EXPLAIN`.

Assume the web server PHP runtime may be older than the CLI runtime. The safe baseline is PHP 7 compatibility.

## WPF Endpoint Workflow

WPF uses these PHP endpoints:

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

Before changing response shapes, read `docs/training_checks.md`.

Preserve JSON compatibility:

- Login success returns `res`, `s`, `k`, and `m`.
- Login failure returns `{"res":"0"}`.
- Logout returns `{"res":""}`.
- Start/end/inquiry return `{"res":"<new_token>"}`.
- Middle/keyboard/mouse return `{"res":"<current_token>"}`.
- Chat insert/update return the chat array shape expected by WPF.

Image upload compatibility matters. Keep the high_speed storage format unless the user explicitly asks for a breaking change:

```text
img/<user_uuid>/<YYYYMMDD>/<HHIISS><event>.<ext>
```

## WPF Client Notes

The WPF client is adjacent to the PHP project. Inspect it only when a task explicitly involves client behavior, packaging, or endpoint calls.

Keep new `MainWindow` logic in the existing partial-class split by responsibility.

Move communication, DTO, and reusable logic into `Services/` and `Models/` when practical.

Avoid swallowing exceptions.

When reporting WPF verification, state exactly which executable you checked: Debug or publish output.

## Performance And Verification

For PHP performance work, estimate request volume from user count and interval before judging query cost.

Prioritize hot endpoints, SQL count, and repeated updates in loops.

For `training_*` work, verify:

- PHP syntax
- route registration
- DI registration
- JSON response shape
- token rotation behavior
- image upload behavior when touched

For UI work, verify the rendered HTML or browser output when practical, not only syntax.

