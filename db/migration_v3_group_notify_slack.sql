-- 事業所（グループ）ごとの問合せ通知 Slack Webhook URL
-- 空（NULL または ''）の場合は従来どおり全体設定 SLACK_WEBHOOK_URL へフォールバックする
ALTER TABLE mst_group
  ADD COLUMN notify_slack_webhook_url VARCHAR(255) NULL AFTER group_email;
