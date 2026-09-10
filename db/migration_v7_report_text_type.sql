-- remark(疑問・質問)、charge_comment(支援記録及び評価)を varchar(500) から text 型に変更
-- (reply は既に text 型のため対象外。文字数上限500文字はアプリ側(ReportFormService)のバリデーションで維持する)
ALTER TABLE tbl_report
  MODIFY COLUMN remark TEXT DEFAULT NULL COMMENT '備考',
  MODIFY COLUMN charge_comment TEXT DEFAULT NULL COMMENT '担当者コメント';
