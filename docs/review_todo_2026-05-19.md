# PHPアプリ コードレビュー対応メモ

対象: `JigyodanWpf_latest_multiple` 以外の PHP アプリ
日付: 2026-05-19

## 優先対応

1. 管理者編集の権限チェック修正
   - `admin_uuid` / `group_uuid` / `admin_div` を POST hidden 値に任せない
   - ログイン権限と所属グループで編集可否をサーバ側検証する

2. ユーザー編集の権限チェック修正
   - 担当外ユーザーを `i` や hidden `user_uuid` で編集できないようにする
   - GET / POST / complete 保存時の全段で検証する

3. complete 系を POST + CSRF に変更
   - `group_complete.php`
   - `admin_complete.php`
   - `user_complete.php`

4. パスワード扱いの改善方針を決める
   - 平文表示をやめる
   - `password_hash()` / `password_verify()` への段階移行を検討する

## 関連ファイル

- `app/Auth/AdminAuth.php`
- `app/Auth/UserAdminAuth.php`
- `app/Services/AdminFormService.php`
- `app/Services/UserFormService.php`
- `app/Repositories/AdminRepository.php`
- `app/Repositories/UserRepository.php`
- `app/Controllers/GroupCompleteController.php`
- `app/Controllers/AdminCompleteController.php`
- `app/Controllers/UserCompleteController.php`
- `app/Views/group/confirm.php`
- `app/Views/admin/confirm.php`
- `app/Views/user/confirm.php`

## 注意

- PHP 7 互換を維持する
- 公開 URL は壊さない
- まず権限境界を直し、見た目の整理は後回し
