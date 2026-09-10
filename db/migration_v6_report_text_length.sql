-- 疑問・質問(remark)、支援記録及び評価(charge_comment)の入力上限を255文字から500文字に拡張
-- (reply は既に text 型のため変更不要)
ALTER TABLE tbl_report
  MODIFY COLUMN remark VARCHAR(500) DEFAULT NULL COMMENT '備考',
  MODIFY COLUMN charge_comment VARCHAR(500) DEFAULT NULL COMMENT '担当者コメント';
