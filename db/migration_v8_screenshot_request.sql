-- 管理画面からのスクリーンショット取得要求（WPFクライアントへのプル型コマンド配信用）
CREATE TABLE IF NOT EXISTS tbl_screenshot_request (
  request_uuid    varchar(32) NOT NULL,
  user_uuid       varchar(32) NOT NULL COMMENT '対象ユーザー',
  status_div      tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1:pending 2:done 3:failed',
  image_path      varchar(255) DEFAULT NULL COMMENT '保存先相対パス',
  requested_date  datetime NOT NULL,
  completed_date  datetime DEFAULT NULL,
  insert_date     datetime NOT NULL,
  insert_uuid     varchar(32) NOT NULL COMMENT '要求した管理者',
  update_date     datetime DEFAULT NULL,
  update_uuid     varchar(32) DEFAULT NULL,
  delete_flg      tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (request_uuid),
  KEY idx_user_status (user_uuid, status_div)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
