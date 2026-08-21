-- 日報登録時の「本日の利用について確認し、了承しました！」同意チェック
ALTER TABLE tbl_report
  ADD COLUMN consent_flg TINYINT(1) NOT NULL DEFAULT 0 AFTER charge_comment;
