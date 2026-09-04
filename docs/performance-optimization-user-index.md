---
name: user/index.php パフォーマンス改善ロードマップ
description: ユーザー一覧画面（user/index.php）のパフォーマンスボトルネック分析と改善優先度
type: project
---

## 概要

`app/Views/user/index.php` のパフォーマンス改善メモ。

View 本体は大きく分割済みで、主な改善余地は以下にある。

- 初回表示時の `UserRepository::findUsers()` の SQL とインデックス
- ポーリング endpoint `user_status.php` の 304 応答率
- `js/user_index.js` の DOM 更新コストとバックグラウンドタブ時の無駄な通信
- 共通マスター `mst_div` のリクエスト横断キャッシュ

## 現在の実装状況

| 項目 | 状態 | メモ |
|---|---|---|
| ETag 早期 304 | 実装済み | `UserStatusController` が `fetchGroupLastTouch()` を使い、payload 生成前に 304 を返す |
| JS 行 Map 化 | 実装済み | `js/user_index.js` で `tr[data-user-uuid]` を初期キャッシュ |
| JS 非表示タブ停止 | 実装済み | `document.hidden` 中は定期ポーリングをスキップし、復帰時に即更新 |
| `buildSummary()` 微速化 | 実装済み | `strtotime()` を避け、日報登録判定を 1 回に統合 |
| SQL インデックス確認 | 確認済み | 主要 JOIN 用インデックスは存在。`findUsers()` は少量 filesort あり |
| `DivCache` 横断キャッシュ | 未実装 | APCu 有無とマスター更新時の反映遅延を考慮する |
| View helper 微速化 | 後回し | 効果は小さいため、規約整理のタイミングで実施する |

## 優先度 1: SQL インデックス確認

`findUsers()` と `findUserStatuses()` は一覧表示とポーリング payload 生成の土台になる。
ETag の 304 でも `fetchGroupLastTouch()` の JOIN + MAX は毎回走るため、ここも同時に確認する。

2026-04-30 確認結果:

- `mst_user_admin` には `idx_mst_user_admin_admin_user (admin_uuid, user_uuid)` が存在
- `mst_user` には `idx_mst_user_group_active (group_uuid, delete_flg, work_style_div, user_id)` が存在
- `tbl_user_status_summary.user_uuid` は PRIMARY
- `tbl_report.report_uuid` は PRIMARY
- 代表条件の `findUsers()` は `idx_mst_user_admin_admin_user`、`mst_user.PRIMARY`、`tbl_user_status_summary.PRIMARY`、`tbl_report.PRIMARY` を使用
- `findUsers()` には `Using temporary; Using filesort` が出るが、確認時点では `mst_user_admin` 起点 88 行程度の並べ替え
- `fetchGroupLastTouch()` は主要 JOIN が index lookup で、新規インデックス追加は不要と判断

結論:

- ただちに `ALTER TABLE` は不要
- まずは HTTP/ブラウザ側で 304 率と体感速度を確認する
- `delete_flg = 9` など 100 行超の条件で体感が悪い場合だけ、実測時間を取って追加対策を検討する

確認対象:

- `mst_user_admin (admin_uuid, user_uuid)`
- `mst_user (group_uuid, delete_flg, work_style_div, user_id)`
- `tbl_user_status_summary (user_uuid)`
- `tbl_report (report_uuid, delete_flg)`

確認 SQL:

```sql
SHOW KEYS FROM mst_user_admin;
SHOW KEYS FROM mst_user;
SHOW KEYS FROM tbl_user_status_summary;
SHOW KEYS FROM tbl_report;
```

`findUsers()` の確認:

```sql
EXPLAIN
SELECT m1.user_uuid, m1.admin_uuid, m2.group_uuid, m2.user_id, m2.user_div, m2.work_style_div,
       m2.user_name, m2.sex_div, m2.birthday, m2.user_area, m2.user_address, m2.user_tel, m2.delete_flg,
       s.contact_uuid, s.contact_div, s.confirm_div, s.contact_date,
       CASE WHEN s.report_date = CURDATE() THEN s.report_uuid ELSE NULL END AS report_uuid,
       CASE WHEN s.report_date = CURDATE() THEN r.admin_uuid ELSE NULL END AS report_admin_uuid,
       CASE WHEN s.report_date = CURDATE() THEN s.charge_comment ELSE NULL END AS charge_comment,
       s.chat_user_uuid
FROM mst_user_admin m1
JOIN mst_user m2 ON m1.user_uuid = m2.user_uuid
LEFT JOIN tbl_user_status_summary s ON s.user_uuid = m2.user_uuid
LEFT JOIN tbl_report r ON r.report_uuid = s.report_uuid AND r.delete_flg = 0
WHERE m2.group_uuid = :group_uuid
  AND m1.admin_uuid = :admin_uuid
  AND m2.delete_flg = :delete_flg
ORDER BY m2.work_style_div ASC, m2.user_id ASC;
```

`fetchGroupLastTouch()` の確認:

```sql
EXPLAIN
SELECT COALESCE(MAX(GREATEST(
  IFNULL(s.contact_touch_at, '1000-01-01 00:00:00'),
  IFNULL(s.report_touch_at, '1000-01-01 00:00:00'),
  IFNULL(s.chat_touch_at, '1000-01-01 00:00:00'),
  s.updated_at
)), '') AS last_touch
FROM tbl_user_status_summary s
JOIN mst_user_admin m1 ON m1.user_uuid = s.user_uuid
JOIN mst_user m2 ON m1.user_uuid = m2.user_uuid
WHERE m2.group_uuid = :group_uuid
  AND m1.admin_uuid = :admin_uuid
  AND m2.delete_flg = :delete_flg;
```

見るポイント:

- `mst_user_admin` が `admin_uuid` 起点で絞れているか
- `mst_user` が `group_uuid + delete_flg` を使えているか
- `tbl_user_status_summary` が `user_uuid` で JOIN できているか
- `Using filesort` が出る場合、件数と実測時間が許容範囲か

## 優先度 2: ETag 304 の実測

実装済み:

- [app/Controllers/UserStatusController.php](../app/Controllers/UserStatusController.php)
- [app/Repositories/UserStatusSummaryRepository.php](../app/Repositories/UserStatusSummaryRepository.php)

現在は以下の流れ。

```php
$lastTouch = $this->summaryRepository->fetchGroupLastTouch($groupUuid, $adminUuid, $deleteFlag);
$etag = '"' . sha1($groupUuid . '|' . $adminUuid . '|' . $deleteFlag . '|' . $lastTouch) . '"';

header('ETag: ' . $etag);
if ($this->request->header('If-None-Match') === $etag) {
	http_response_code(304);
	return;
}
```

期待効果:

- データ未変更時に `buildStatusPayload()` と JSON 出力をスキップする
- ただし `fetchGroupLastTouch()` は毎回走るため、SQL インデックス確認が前提

確認方法:

- ブラウザ Network タブで `user_status.php` が 304 になるか確認
- 変更があると 200、変更がないと 304 に戻るか確認
- `Cache-Control` と `ETag` ヘッダーが期待どおり出るか確認

## 優先度 3: DivCache 横断キャッシュ

現状:

- `App\Support\DivCache::load()` は同一リクエスト内 memoization のみ
- リクエストごとに `mst_div` を 1 回読む

候補:

- APCu が使える環境なら APCu に 10 分程度保存
- APCu がない環境では現状どおり DB 取得
- 管理画面で `mst_div` を更新する導線がある場合、明示 invalidation を検討

注意:

- マスター更新後の反映遅延が発生する
- ファイルキャッシュは権限・ロック・削除運用が増えるため、最初は APCu 優先
- 効果は「全画面で 1 SELECT 削減」なので、SQL ホットパス確認後に実施する

## 優先度 4: View レイヤ整理

`app/Views/user/index.php` では `$h` を再定義している。

```php
use App\Support\Esc;
$h = array(Esc::class, 'h');
```

`View::render()` が `$h` を注入する設計に寄せるなら削除候補。
ただし性能効果は小さいため、速度改善の主作業ではなく規約整理として扱う。

`user_rows.php` の helper 呼び出し削減も可能だが、見通しと速度のバランスを見て後回しにする。

## 実装済みの小改善

### JS ポーリング

[js/user_index.js](../js/user_index.js)

- 初期化時に `rowIndex` を作り、ポーリングごとの `document.querySelector()` を避ける
- `document.hidden` 中は `user_status.php` への定期 fetch を止める
- タブ復帰時は `visibilitychange` で即更新する

### 集計処理

[app/Services/UserListService.php](../app/Services/UserListService.php)

- `contact_date` の日付比較は `substr($contactDate, 0, 10)` を使う
- `report_uuid` がある場合の `hasRegisteredReport()` 判定を 1 回にする

## 次の確認手順

1. PHP 構文確認

```bash
/mnt/c/xampp/php/php.exe -l app/Services/UserListService.php
```

2. JS の目視確認

- `http://localhost/zaitakukanri_honbu_new/user.php`
- Network タブで `user_status.php` の 304 応答を確認
- タブ非表示中にポーリングが止まり、復帰時に 1 回更新されることを確認

3. DB インデックス確認

```sql
SHOW KEYS FROM mst_user_admin;
SHOW KEYS FROM mst_user;
SHOW KEYS FROM tbl_user_status_summary;
SHOW KEYS FROM tbl_report;
```

## まとめ

| 優先度 | 項目 | 状態 |
|---|---|---|
| 1 | SQL インデックス確認 | 確認済み、新規 ALTER 不要 |
| 2 | ETag 304 実測 | 実装済み、HTTP 確認待ち |
| 3 | DivCache 横断キャッシュ | 未実装 |
| 4 | View レイヤ整理 | 後回し |
| 5 | JS ポーリング最適化 | 実装済み |
| 6 | `buildSummary()` 微速化 | 実装済み |

次は `SHOW KEYS` と `EXPLAIN` の結果を見て、必要なインデックスだけを追加する。
