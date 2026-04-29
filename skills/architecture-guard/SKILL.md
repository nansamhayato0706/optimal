---
name: architecture-guard
description: "【発動条件】PHPアプリの画面追加/改修/バグ修正で、DI Container・Router・Auth・Repository Interface・責務分離・既存フォルダ構成を維持する必要があるときに適用する。【発動しない】単発の小さな文言修正や雑談。"
---

# ガード範囲

- **責務分離**（View にビジネスロジック混入・Controller 肥大化・Repository の直叩きを抑止）
- **DI Container ルール**（`bootstrap/container.php` 以外での new Controller/Service は原則禁止）
- **Repository Interface 準拠**（`Contracts/` 内の Interface 経由で登録・注入する）
- **ルーティング規則**（`bootstrap/routes.php` に明示登録。`index.php` 以外のエントリーポイントを増やさない）
- **View テンプレートルール**（`$h()` は View::render() が注入。テンプレート内で再定義しない）

# 責務の境界

| 層 | 役割 | やってはいけないこと |
|---|---|---|
| Controller | 認証・バリデーション・Service 呼び出し・View 呼び出し | DB 直接操作・ビジネスロジック |
| Service | ビジネスロジック・集計・判定 | HTTP 知識・View 知識 |
| Repository | DB アクセスのみ（AbstractRepository を継承） | ビジネスロジック |
| View | 表示のみ（`$h()` でエスケープ必須） | DB 操作・Service 呼び出し |
| Auth | セッション検証・リダイレクト | ビジネスロジック |

# 新機能追加の順序

1. Repository Interface にメソッド追加（`app/Repositories/Contracts/`）
2. Repository クラスに実装
3. Service クラスにビジネスロジック実装
4. Controller を薄く実装
5. `bootstrap/container.php` に DI 登録
6. `bootstrap/routes.php` にルート追加
7. View テンプレート作成

# 設計変更が必要な場合

- いきなり実装しない
- 先に「提案→理由→影響範囲→代替案」を提示して合意後に実装
- 既存の Interface を変更する場合は全実装クラスへの影響を確認してから行う
