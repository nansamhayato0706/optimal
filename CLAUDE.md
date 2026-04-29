# CLAUDE.md

このファイルは、このリポジトリで作業するエージェント向けの実務メモです。

## 概要

在宅就労管理システム（optimal 版）。次の2系統で構成されます。

- PHP Webアプリ（本プロジェクト）
  - 管理画面、日報、チャット、各種マスター管理
- WPF デスクトップアプリ
  - 在宅就労者向けクライアント（別リポジトリ）

このプロジェクトでの最上位目標：
- 構成を効率的に整理する
- 実装を分かりやすく保つ
- 動作を速くする

判断に迷った場合は、この3点を優先する。

重要な実行制約：
- PHP 側は **PHP 7 で動作しなければならない**
- PHP 8 専用構文（match式・readonly・nullsafe演算子・enum・属性 #[] など）は使わない

ルート：
- PHP: `C:\xampp\htdocs\zaitakukanri_honbu_optimal`
- WPF: `C:\xampp\htdocs\zaitakukanri_honbu_high_speed\JigyodanWpf_latest_v4.0.3`

## 主要構成

### PHP 側

**アーキテクチャ（high_speed との主な違い）：**

- EntryPoints 層は存在しない。`index.php` が単一エントリーポイント
- ルーティングは `bootstrap/routes.php` の `RouteRegistry` で明示管理
- DI は `bootstrap/container.php` の `Container` クラスで管理
- Repository はインターフェース経由（`AdminRepositoryInterface` / `GroupRepositoryInterface` / `UserRepositoryInterface`）
- View は `View::render()` が全テンプレートに `$h()` ヘルパーを注入する（テンプレート内で `$h` を再定義しない）

**リクエストフロー：**
```
index.php
  → bootstrap/app.php（オートローダー・ヘルパー関数・DI初期化）
  → bootstrap/routes.php（RouteRegistry でルート定義）
  → app/Controllers/**
  → app/Services/**
  → app/Repositories/** (AbstractRepository 経由で PDO)
  → app/Views/**
```

**ディレクトリ構成：**
```
app/Auth/          認証ガード（AdminAuth / GroupAuth / UserAdminAuth / ReportAuth）
app/Controllers/   HTTP入出力・認証呼び出し・View呼び出し
app/Repositories/  DB アクセス（AbstractRepository を継承）
  Contracts/       Repository インターフェース
app/Services/      ビジネスロジック
app/Support/       横断ユーティリティ（Csrf/Esc/Uuid/Logger/Database/DivCache/SessionStore/...）
app/Views/         PHP テンプレート（$h() はView::render()が注入）
bootstrap/
  app.php          セッション・オートローダー・ヘルパー関数定義
  container.php    DI コンテナ定義
  routes.php       ルート定義
db/                マイグレーション SQL
docs/              設計メモ・確認記録
```

**主な DB テーブル：**
- `mst_user`：就労者マスター（`login_uuid` でセッション管理）
- `mst_admin`：管理者マスター
- `mst_group`：組織グループ
- `tbl_report`：日報
- `tbl_chat`：管理者↔就労者チャット
- `tbl_user_status_summary`：一覧系画面用の非正規化サマリー
- `mst_div`：区分マスター（汎用コード）

**admin_div によるルーティング：**
- `1`（スーパー管理者）→ `group.php` へリダイレクト
- `2`（グループ管理者）→ `admin.php` へリダイレクト
- `3`以上（ユーザー管理者）→ `user.php` へリダイレクト

**Auth クラスの責務：**
- `AdminAuth`：admin_div 1 または 2 向けルート保護・ヘッダーリンク生成
- `GroupAuth`：admin_div 1 向けルート保護
- `UserAdminAuth`：admin_div 3以上向けルート保護
- `ReportAuth`：日報系ルート保護

**セッション：**
- セッション名：`ZK_OPTIMAL_SESSID`
- ドット記法で管理：`login.id` / `login.auth` / `login.group_id` / `login.admin_uuid` / `login.admin_id`
- `SessionStore::get('login.auth')` → ログインユーザーの admin_div

**DI コンテナの使い方：**
- `app_container()->get(XxxClass::class)` でインスタンス取得
- 新機能を追加するときは `bootstrap/container.php` に `singleton` または `bind` を追加する
- Repository は Interface 経由で登録：`$container->singleton(XxxRepositoryInterface::class, fn() => new XxxRepository(...))`

**ルート追加の手順：**
1. `bootstrap/routes.php` に `$routes->add(['GET', 'POST'], '/xxx.php', ...)` を追加
2. `bootstrap/container.php` に Controller の `bind` を追加
3. Controller → Service → Repository の順に実装

**View テンプレートのルール：**
- `$h()` は `View::render()` が注入するため、テンプレート冒頭で再定義しない
- `$_csrf_field` はフォームへの埋め込み用（`View::render()` が注入）
- `$cssBase` / `$jsBase` / `$imgBase` も同様に注入済み

**WPF 連携エンドポイント（JSON レスポンス、CSRF 対象外）：**
- `training_login.php`：ログイン
- `training_logout.php`：ログアウト（イベント6）
- `training_start/end/inquiry/middle.php`：勤務状態（イベント1/2/3/4）
- `training_keyboard/mouse.php`：入力活動（イベント7/8）
- `training_insert/update.php`：チャット送受信（イベント11/12）
- `first.php`：初期データ取得

JSON レスポンス形式の期待値は `docs/training_checks.md` を参照。

### WPF 側

WPF アプリは別ディレクトリに存在します。詳細は高速版プロジェクトの CLAUDE.md を参照。

## コーディング方針

### PHP 全般

- `declare(strict_types=1)` を全ファイル冒頭に記述する
- 型ヒント・戻り値型を新規コードに必ず付ける
- SQLは全て PDO prepared statement（`PDO::ATTR_EMULATE_PREPARES=false`）
- HTML 出力は必ず `$h()` でエスケープ（XSS 防止）
- ブラウザ向け state-changing POST には CSRF 必須：
  - View の `<form method="post">` 内で `<?= $_csrf_field ?>`
  - Controller の POST 分岐冒頭で `csrf_verify_or_abort($this->request)`
- WPF 連携エンドポイントは CSRF 対象外（トークン認証のみ）

### 新機能追加の手順

1. Repository Interface に必要なメソッドを追加
2. AbstractRepository を継承した Repository クラスに実装
3. Service クラスにビジネスロジックを実装
4. Controller は薄く（認証・バリデーション・View 呼び出しのみ）
5. `bootstrap/container.php` に DI 登録
6. `bootstrap/routes.php` にルート追加
7. `app/Views/**` にテンプレート作成

### 設定

- DB 接続・アプリ URL などは `.env.local` または本番 `env.production` の `ZAITAKU_*` に記述（git 管理外）
- 新しい設定値は `AppConfig` クラスに追加してから使う
- `define()` を増やして app 側で直接参照しない

### パフォーマンス

- `training_update.php` は高頻度ホットパス（ポーリング）
- `user.php` / `user_status.php` は一覧系ホットパス
- `tbl_user_status_summary` を適切に使って一覧取得の SQL を削減する
- ホットパスに N+1 クエリを置かない

## 調査の着眼点

### 不具合調査

- まず `php -l <file>` でシンタックスエラーを確認する
- ルーティングの問題は `bootstrap/routes.php` と `RequestContext::path()` を確認する
- DI エラーは `bootstrap/container.php` の登録を確認する
- セッション問題は `login.*` キーと `SessionStore` のドット記法を確認する

### パフォーマンス調査

- ホットエンドポイント → `TrainingService` / `TrainingRepository` を確認
- 一覧系 → `UserRepository` / `UserStatusSummaryRepository` を確認
- インデックスの有無はコードから断定せず EXPLAIN で確認する

## よく使うコマンド

```bash
# PHP シンタックスチェック（変更ファイルを確認）
php -l app/Controllers/XxxController.php

# MySQL 接続確認
C:/xampp/mysql/bin/mysql.exe -u root -p6648 jigyodan_zk_honbu

# Apache ログ確認
tail -f C:/xampp/apache/logs/error.log
```

## ローカル環境前提

- XAMPP 上で PHP が動作
- ローカル URL：`http://localhost/zaitakukanri_honbu_optimal/`
- MySQL DB 名：`jigyodan_zk_honbu`
- ローカル設定：`.env.local`
- セッション名：`ZK_OPTIMAL_SESSID`

DB スキーマ：`db/` 配下のファイルを参照
- `migration_v1_prev_login_uuid.sql`：`mst_user` に prev_login_uuid 列を追加（新規環境で必要）

参照用の隣接プロジェクト：
- `C:\xampp\htdocs\zaitakukanri_honbu_high_speed`（移植元）

## 優先利用するローカル skills

- `skills/architecture-guard`：PHP 画面の追加・改修・バグ修正で、DI/Router/責務構成を崩したくないときに使う
- `skills/encoding-guard`：日本語を含む PHP / HTML / JS / CSS / SQL を編集・生成・保存するときに使う
- `skills/systematic-debugging`：不具合調査・性能問題・予期しない挙動の原因切り分けで使う
- `skills/verification-before-completion`：修正完了・成功・通過を報告する前に確認コマンドで根拠を確認するときに使う
