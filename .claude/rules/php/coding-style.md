# PHP コーディング規約（optimal プロジェクト固有）

## 基本方針

- `declare(strict_types=1);` を全PHPファイルに記述する
- スカラー型ヒント・返り値型・型付きプロパティを使用する
- PHP 7 互換構文のみ使用する（`match`・`readonly`・`?->`・`enum`・`#[]` 属性は NG）

## クラス責務

| 層 | クラス | 責務 |
|---|---|---|
| Controller | `app/Controllers/` | 認証チェック → バリデーション → Service委譲 → レスポンス。ビジネスロジックを持たない |
| Service | `app/Services/` | ビジネスロジック。Repository を呼び出す |
| Repository | `app/Repositories/` | SQL と DB 操作のみ。ビジネスロジックを持たない |
| View | `app/Views/` | 表示のみ。`$h()` で全変数をエスケープ |

## DI ルール

- 新クラスは必ず `bootstrap/container.php` に登録する
- ステートレスなサービスは `singleton`、リクエストスコープは `bind`
- `new ClassName()` を直接呼び出さず、必ず DI 経由で取得する

## ルーティング

- 新エンドポイントは `bootstrap/routes.php` の `RouteRegistry` に登録する
- `index.php` や個別の `*.php` ファイルをエントリーポイントとして追加しない

## 認証

- ブラウザ向けコントローラーの `handle()` 冒頭で `requireAdminRoute()` または `requireUserRoute()` を呼ぶ
- ブラウザ向けの state-changing POST には `csrf_verify_or_abort($this->request)` を呼ぶ
- WPF 向けエンドポイントは CSRF 免除（token 認証のみ）

## セキュリティ

- SQL は必ず prepared statement を使用する
- View の出力は必ず `$h($value)` でエスケープする（`echo $var` 直接は NG）
- ファイルアップロードの拡張子は `jpg/jpeg/png/bmp` のみ許可する

## 命名

- クラス名：`PascalCase`
- メソッド名・変数名：`camelCase`
- DB カラム名は snake_case のまま配列キーで扱う
