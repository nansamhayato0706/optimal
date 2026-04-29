---
description: PHP・C#コードのセキュリティスキャンを実行する
argument-hint: [ファイルパス | blank で変更ファイル全体をスキャン]
---

# Security Scan

**Input**: $ARGUMENTS

## 手順

### Phase 1 — スキャン対象の決定

引数にファイルパスがある場合はそのファイルのみ。なければ変更ファイルを対象とする：

```bash
git diff --name-only HEAD
```

ファイルがない場合は「スキャン対象なし」で終了。

### Phase 2 — PHP スキャン

PHPファイルが含まれる場合：

```bash
# SQLインジェクション候補（prepared statement 未使用）
grep -n "query.*\$_POST\|query.*\$_GET\|\"\s*\.\s*\$" <ファイル>

# XSS候補（$h() なしのecho）
grep -n "echo.*\$_POST\|echo.*\$_GET\|echo.*\$_REQUEST" <ファイル>

# トークン検証漏れ（WPFエンドポイント）
grep -n "login_uuid\|value" <ファイル>

# ハードコード認証情報
grep -n "password\s*=\s*['\"]" <ファイル>

# CSRF 漏れ（ブラウザ向けPOST）
grep -rn "isPost()" <ファイル>  # csrf_verify_or_abort が直後に来るか確認

# Auth チェック漏れ
grep -n "requireAdminRoute\|requireUserRoute" <ファイル>
```

### Phase 3 — C# スキャン（WPF側変更がある場合）

```bash
# SSL検証無効化
grep -n "ServerCertificateValidationCallback\|DangerousAccept" <ファイル>

# ハードコードURL・パスワード
grep -n "http://\|https://\|[Pp]assword\s*=" <ファイル>

# トークンのログ出力
grep -n "login_uuid\|token" <ファイル>
```

### Phase 4 — security-reviewer エージェントで詳細分析

疑わしい箇所が見つかった場合は **security-reviewer** エージェントに詳細分析を依頼する。

### Phase 5 — レポート

```
## セキュリティスキャン結果

スキャン対象: <ファイル一覧>

### CRITICAL
<発見事項または「なし」>

### HIGH
<発見事項または「なし」>

### MEDIUM
<発見事項または「なし」>

### 判定
✅ コミット可 / ❌ 修正後にコミット
```

CRITICALまたはHIGHがある場合は修正完了まで次の作業を止めるよう勧告する。
