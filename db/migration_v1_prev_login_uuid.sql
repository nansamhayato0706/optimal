-- =============================================================
-- migration_v1_prev_login_uuid.sql
-- mst_user に prev_login_uuid / prev_login_uuid_expires_at を追加
-- （schema_v2.sql には未記載だが、本番DBには適用済み）
--
-- 目的:
--   WPF クライアントがトークンローテーション直後に旧トークンで
--   リクエストを送信した場合（ネットワーク遅延・再送）に備え、
--   旧トークンを 5 分間有効とする猶予期間を設ける。
--
-- 適用コマンド（初回のみ）:
--   mysql -u root -p jigyodan_zk_honbu < migration_v1_prev_login_uuid.sql
-- =============================================================

ALTER TABLE mst_user
    ADD COLUMN prev_login_uuid varchar(32) DEFAULT NULL
        COMMENT '前回ログインUUID（トークン二重送信の猶予用）'
        AFTER login_uuid,
    ADD COLUMN prev_login_uuid_expires_at datetime DEFAULT NULL
        COMMENT '前回UUIDの有効期限（5分間）'
        AFTER prev_login_uuid;
