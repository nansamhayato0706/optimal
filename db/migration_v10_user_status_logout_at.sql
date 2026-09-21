-- ユーザー一覧で、問合せ後のログアウトを表示色へ反映する。
ALTER TABLE tbl_user_status_summary
    ADD COLUMN logout_at DATETIME NULL AFTER contact_touch_at,
    ADD KEY idx_user_status_summary_logout_at (logout_at);

UPDATE tbl_user_status_summary s
LEFT JOIN (
    SELECT insert_uuid, MAX(COALESCE(send_date, insert_date)) AS logout_at
    FROM tbl_send
    WHERE send_div = 6
    GROUP BY insert_uuid
) lo ON lo.insert_uuid = s.user_uuid
SET s.logout_at = lo.logout_at;
