-- キャプチャ履歴（利用者単位・削除除外・新しい順）の取得を高速化する。
ALTER TABLE tbl_screenshot_request
  ADD INDEX idx_screenshot_history (user_uuid, delete_flg, requested_date);
