# AGENTS.md

このファイルは、このリポジトリで作業する Codex / エージェント向けの実務メモです。

## 概要

在宅就労管理システムの optimal 版です。

- PHP Web アプリ
  - 管理画面、日報、チャット、各種マスター管理
- WPF デスクトップアプリ
  - 在宅就労者向けクライアント
  - PHP 側の `training_*.php` / `first.php` を利用する

このプロジェクトでの最上位目標:

- 構成を効率的に整理する
- 実装を分かりやすく保つ
- 動作を速くする

判断に迷った場合は、この3点を優先する。

重要な実行制約:

- PHP 側は PHP 7 で動作しなければならない
- 本番サーバは PHP 7 系のため、実装・修正・レビューでは PHP 7 互換を最優先で確認する
- PHP 8 専用構文や PHP 8 前提の実装は導入しない
- 型宣言、戻り値型、null 合体演算子などを使う場合も、対象 PHP 7 系で利用可能か確認してから使う
- 公開 URL の互換性を壊さない

ルート:

- PHP: `C:\xampp\htdocs\zaitakukanri_honbu_new`
- 比較元 PHP: `C:\xampp\htdocs\zaitakukanri_honbu_high_speed_BK`
- WPF: `C:\xampp\htdocs\Wpf`

## 主要構成

### PHP 側

optimal は `index.php` と明示ルーティングを中心にした構成です。

- 公開入口: `index.php`
- ルーティング: `bootstrap/routes.php`
- DI コンテナ: `bootstrap/container.php`
- コントローラー: `app/Controllers/**`
- サービス: `app/Services/**`
- リポジトリ: `app/Repositories/**`
- リポジトリ契約: `app/Repositories/Contracts/**`
- ビュー: `app/Views/**`
- 共通基盤: `app/Support/**`
- 認証ガード: `app/Auth/**`
- DB 差分: `db/**`
- 確認メモ: `docs/**`

基本フロー:

```text
index.php
  -> bootstrap/app.php
  -> bootstrap/routes.php
  -> app/Controllers/**
  -> app/Services/**
  -> app/Repositories/**
  -> app/Views/**
```

high_speed との大きな違い:

- `app/EntryPoints/**` は使わない
- root 直下の多数の `*.php` ではなく、`RouteRegistry` で URL を維持する
- Repository はインターフェース経由の DI を優先する
- View は `View::render()` が `$h()` / `$_csrf_field` / asset base を注入する

主な DB テーブル:

- `mst_user`: 就労者マスター
- `mst_admin`: 管理者マスター
- `mst_group`: 組織グループ
- `tbl_report`: 日報
- `tbl_chat`: 管理者と就労者のチャット
- `tbl_user_status_summary`: `user.php` など一覧系画面で使う非正規化サマリ
- `mst_div`: 区分マスター

## ルーティング方針

- 既存利用者や WPF が使う公開 URL は維持する
- `training_*.php` と `first.php` の endpoint 名は変更しない
- 追加ルートは `bootstrap/routes.php` に明示する
- Controller を追加したら `bootstrap/container.php` に DI 登録する
- WPF 連携 endpoint は CSRF 対象外にし、トークン認証と JSON 互換を優先する

WPF 連携 endpoint:

- `training_login.php`: ログイン
- `training_logout.php`: ログアウト
- `first.php`: 初期データ取得
- `training_insert.php`: チャット送信
- `training_update.php`: チャット取得
- `training_start.php` / `training_end.php` / `training_inquiry.php` / `training_middle.php`: 勤務状態
- `training_keyboard.php` / `training_mouse.php`: 入力活動報告

`training_*` を触った後は `docs/training_checks.md` の期待レスポンスを基準に HTTP レベルで確認する。

## 実装方針

### Controller

- 認証、入力取り出し、CSRF 確認、Service 呼び出し、View/JSON 返却に絞る
- DB 操作や複雑な業務判断を置かない

### Service

- 業務ロジックを置く
- Controller から見た操作単位にまとめる
- WPF のレスポンス互換など、意図が追いにくい箇所には短いコメントを残す

### Repository

- SQL と永続化処理を置く
- `AbstractRepository` を継承する
- 新規依存はできるだけ `Contracts` の interface 経由で DI する
- SQL は PDO prepared statement を使う
- コードだけで DB インデックス有無を断定しない

### View

- `$h()` は `View::render()` が注入するため、テンプレート内で再定義しない
- HTML 出力は `$h()` でエスケープする
- POST フォームには `$_csrf_field` を埋め込む
- `user` / `contact` / `chat` / `report` の大きい画面は partial へ分けて本体を薄く保つ

### 設定

- 新しい設定参照は `AppConfig` 経由を優先する
- app 側へ新しい `define()` 依存を増やさない
- ローカル環境設定は `.env.local` の `ZAITAKU_*` を使う
- 本番環境設定はプロジェクト一つ上の `env.production` の `ZAITAKU_*` を使う

## 変更時の注意

### PHP

- 本番 PHP 7 で動作する書き方を前提にする
- PHP 8 以降で追加された構文・標準関数・挙動には依存しない
- `training_update.php` は高頻度ホットパス
- `user.php` / `user_status.php` は一覧系ホットパス
- 一覧系画面では `tbl_user_status_summary` の利用有無を先に確認する
- 旧 `v_chat` / `v_user` などの DB VIEW 依存は増やさない
- Apache と CLI で PHP バージョンが異なる可能性がある
- `php -l` が通っても CLI 側だけ PHP 8 の可能性があるため、本番互換の判断は PHP 7 基準で行う
- Web 実行経路では PHP 8 専用構文を使わない

日報まわりでは、視覚障害者向けのアクセシブル画面も確認する。

- `app/Views/report/edit_accessible.php`
- `app/Views/report/confirm_accessible.php`
- `app/Views/report/detail_accessible.php`

利用者の `visual_impairment_flg = 1` のとき、本人向け日報画面はアクセシブル版へ切り替わる。

### WPF

- WPF 側は別ディレクトリにある
- `MainWindow` は partial class 分割を維持する
- 認証、チャット、作業記録は `Services/` と `Models/` 側へ寄せる
- 例外を握りつぶす変更は避ける
- 確認時は Debug と publish 出力のどちらを見たか明示する

## 調査の着眼点

### 不具合

- まず `php -l` で変更ファイルの構文を確認する
- ルーティング問題は `bootstrap/routes.php` と `RequestContext::path()` を確認する
- DI エラーは `bootstrap/container.php` の登録を確認する
- セッション問題は `SessionStore` と `login.*` キーを確認する

### パフォーマンス

- ユーザー数とポーリング間隔から req/s を先に概算する
- PHP ではホット endpoint、SQL 回数、ループ内 UPDATE を優先確認する
- `training_*` は `app/Services/TrainingService.php` と `app/Repositories/TrainingRepository.php` を先に見る
- `user.php` は `app/Repositories/UserRepository.php` と `app/Repositories/UserStatusSummaryRepository.php` を先に見る

## よく使うコマンド

PHP 構文確認:

```bash
/mnt/c/xampp/php/php.exe -l app/Controllers/XxxController.php
```

MySQL 確認:

```bash
/mnt/c/xampp/mysql/bin/mysql.exe -uroot -p6648 jigyodan_zk_honbu
```

ローカル URL:

```text
http://localhost/zaitakukanri_honbu_new/
```

## ローカル環境前提

- XAMPP 上で PHP が動作
- ローカル URL: `http://localhost/zaitakukanri_honbu_new/`
- MySQL DB 名: `jigyodan_zk_honbu`
- ローカル設定: `.env.local`
- セッション名: `ZK_OPTIMAL_SESSID`

比較用の隣接プロジェクト:

- `C:\xampp\htdocs\zaitakukanri_honbu_high_speed_BK`

## 現在の到達点

- optimal は `index.php` + `RouteRegistry` + DI コンテナ構成
- WPF endpoint は `TrainingController` / `TrainingService` / `TrainingRepository` へ集約
- `prev_login_uuid` / `prev_login_uuid_expires_at` は `db/migration_v1_prev_login_uuid.sql` に記録済み
- JSON レスポンス互換は `docs/training_checks.md` を基準に確認する

## 優先利用するローカル skills

- `skills/zaitakukanri-project`: プロジェクト構成、WPF 連携、hot path、検証コマンドを確認するときに使う
- `skills/architecture-guard`: PHP 画面の追加、改修、バグ修正で DI/Router/責務構成を崩したくないときに使う
- `skills/encoding-guard`: 日本語を含む PHP / HTML / JS / CSS / SQL を編集、生成、保存するときに使う
- `skills/systematic-debugging`: 不具合調査、性能問題、予期しない挙動、テスト失敗の原因切り分けで使う
- `skills/verification-before-completion`: 修正完了、成功、通過を報告する前に確認コマンドで根拠を確認するときに使う
