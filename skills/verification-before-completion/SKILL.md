---
name: verification-before-completion
description: Use when about to claim work is complete, fixed, or passing - requires running verification commands and confirming output before making any success claims; evidence before assertions always
---

# Verification Before Completion

## Overview

Claiming work is complete without verification is dishonesty, not efficiency.

**Core principle:** Evidence before claims, always.

## The Iron Law

```
NO COMPLETION CLAIMS WITHOUT FRESH VERIFICATION EVIDENCE
```

## The Gate Function

```
BEFORE claiming any status or expressing satisfaction:

1. IDENTIFY: What command proves this claim?
2. RUN: Execute the FULL command (fresh, complete)
3. READ: Full output, check exit code, count failures
4. VERIFY: Does output confirm the claim?
   - If NO: State actual status with evidence
   - If YES: State claim WITH evidence
5. ONLY THEN: Make the claim

Skip any step = lying, not verifying
```

## Verification Commands for This Project

### PHP 変更時

```bash
# シンタックスチェック（変更した全ファイル）
php -l app/Controllers/XxxController.php
php -l app/Services/XxxService.php
php -l bootstrap/container.php
php -l bootstrap/routes.php

# ルート登録確認（routes.php に新エンドポイントが追加されているか）
grep "/xxx.php" bootstrap/routes.php

# DI 登録確認（container.php に新クラスが登録されているか）
grep "XxxController" bootstrap/container.php
```

### DB 変更時

```bash
# 列の存在確認
C:/xampp/mysql/bin/mysql.exe -u root -p6648 jigyodan_zk_honbu \
  -e "SHOW COLUMNS FROM mst_xxx LIKE 'col_name';"
```

### 共通

```bash
git status --short
```

## Red Flags - STOP

- "should", "probably", "seems to" を使っている
- 確認コマンドを実行する前に "完了" / "OK" / "成功" と言う
- PHP のシンタックスエラーを確認せずに「動くはず」と言う
- コンテナ登録を確認せずに「接続できるはず」と言う

## Output Format

```
VERIFICATION: [PASS/FAIL/PARTIAL]

PHP syntax:    [OK / X errors / Skipped]
Route check:   [OK / Missing / Skipped]
Container:     [OK / Missing / Skipped]
Git status:    [summary]

Commands run:
- php -l ...
- grep ...

Ready: [YES/NO]
```
