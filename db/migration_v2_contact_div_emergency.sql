-- =============================================================
-- migration_v2_contact_div_emergency.sql
-- mst_div に contact_div=5「緊急」を追加
--
-- 目的:
--   WPF クライアントの緊急ボタン押下で /training_emergency.php を
--   呼び出す。tbl_contact に contact_div=5 で記録し、
--   user/index.php の利用状況列に「緊急」を表示する。
--
-- 適用コマンド（初回のみ）:
--   mysql -u root -p jigyodan_zk_honbu < migration_v2_contact_div_emergency.sql
-- =============================================================

INSERT INTO mst_div (div_parent_id, div_id, div_name, show_flg)
VALUES ('contact', 5, '緊急', 0)
ON DUPLICATE KEY UPDATE div_name = VALUES(div_name), show_flg = 0;
