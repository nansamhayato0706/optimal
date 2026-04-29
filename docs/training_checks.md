# Training Endpoint Checks

WPF クライアントが呼び出す `training_*.php` / `first.php` の確認メモ。

## Endpoints

| ファイル | event | 認証 |
|---|---|---|
| `training_login.php` | POST (独自) | user_id + password |
| `training_logout.php` | POST event=6 | token |
| `training_start.php` | POST event=1 | token |
| `training_end.php` | POST event=2 | token |
| `training_inquiry.php` | POST event=3 | token |
| `training_middle.php` | POST event=4 | token |
| `training_keyboard.php` | POST event=7 | token |
| `training_mouse.php` | POST event=8 | token |
| `training_insert.php` | POST event=11 | token |
| `training_update.php` | POST event=12 | token |
| `first.php` | POST | token |

## Expected Response Shapes

- `training_login.php`
  - `200`
  - `{"res":"<token>","s":"<send_interval>","k":"<keyboard_interval>","m":"<mouse_interval>"}`
- `training_logout.php`
  - `200`
  - `{"res":""}`
- `training_start.php`
  - `200`
  - `{"res":"<new_token>"}`
- `training_end.php`
  - `200`
  - `{"res":"<new_token>"}`
- `training_inquiry.php`
  - `200`
  - `{"res":"<new_token>"}`
- `training_middle.php`
  - `200`
  - `{"res":"<new_token>"}`
- `training_keyboard.php`
  - `200`
  - `{"res":"<new_token>"}`
- `training_mouse.php`
  - `200`
  - `{"res":"<new_token>"}`
- `training_insert.php`
  - `200`
  - `[]`
- `training_update.php`
  - `200`
  - `[]` (新着チャットがない場合)
- `first.php`
  - `200`
  - `{"group":"<グループ名>","notice":"<お知らせ>","link":[...]}`

## Verification Notes

- 確認時は実在ユーザーで `training_login.php` から token を取得して順に呼び出す
- token を更新する endpoint を連続確認するときは、返ってきた token を次の呼び出しへ引き継ぐ
- 確認後は対象ユーザーの `mst_user.login_uuid` を元の値へ戻す
- CSRF チェックは WPF エンドポイントには適用されない（token 認証のみ）
- `prev_login_uuid` + `prev_login_uuid_expires_at` による5分猶予ローテーションが機能しているか確認する場合は別途テスト手順が必要
