-- 日報の「備考欄」に対する担当者からの返信欄
ALTER TABLE tbl_report
  ADD COLUMN reply TEXT NULL AFTER remark;
