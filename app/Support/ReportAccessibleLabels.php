<?php

declare(strict_types=1);

namespace App\Support;

final class ReportAccessibleLabels
{
	public static function section(string $key): string
	{
		// Centralize accessible report wording so confirm/detail stay in sync.
		$labels = array(
			'basic' => '1. 基本情報',
			'sleep' => '2. 睡眠',
			'state' => '3. 今日の状態',
			'objective' => '4. 今日の目標',
			'medicine' => '5. 服薬',
			'outing' => '6. 外出',
			'talk' => '7. 会話人数',
			'training' => '8. 訓練状況',
			'review' => '9. 振り返り',
			'question' => '10. 疑問や質問',
		);

		return $labels[$key] ?? $key;
	}

	public static function field(string $key): string
	{
		$labels = array(
			'user_name' => '氏名',
			'report_date' => '日付',
			'retiring_time' => '昨日の就寝時間',
			'rising_time' => '今日の起床時間',
			'sleep_time' => '睡眠時間',
			'mood_div' => '今日の気分',
			'condition_div' => '今日の体調',
			'none' => '選択なし',
			'medicine_div' => '決まった通りに飲みましたか',
			'medicine_reason' => '理由',
			'medicine_reason_detail' => '服薬の理由',
			'talk_div' => '人数',
			'talk_div_detail' => '会話人数',
			'training_am_1' => '午前1',
			'training_am_2' => '午前2',
			'training_am_3' => '午前3',
			'training_pm_1' => '午後1',
			'training_pm_2' => '午後2',
			'training_pm_3' => '午後3',
			'training_am_1_detail' => '訓練内容 午前1',
			'training_am_2_detail' => '訓練内容 午前2',
			'training_am_3_detail' => '訓練内容 午前3',
			'training_pm_1_detail' => '訓練内容 午後1',
			'training_pm_2_detail' => '訓練内容 午後2',
			'training_pm_3_detail' => '訓練内容 午後3',
			'training_start_time' => '開始時間',
			'training_end_time' => '終了時間',
			'training_time' => '訓練時間',
			'lunch_time' => '昼休憩',
			'lunch_time_minutes' => '昼休憩(分)',
			'break_time' => '途中休憩',
			'break_time_minutes' => '途中休憩(分)',
			'lunch_break_time' => '休憩合計',
			'lunch_break_time_minutes' => '合計(分)',
			'work_time' => '実働時間',
			'work_time_short' => '実働',
			'rethink_am' => '午前の振り返り',
			'rethink_am_short' => '午前',
			'achieve_am' => '午前の達成度',
			'achieve_short' => '達成度(％)',
			'fatigue_am' => '午前の疲労度',
			'fatigue_short' => '疲労度(％)',
			'rethink_pm' => '午後の振り返り',
			'rethink_pm_short' => '午後',
			'achieve_pm' => '午後の達成度',
			'fatigue_pm' => '午後の疲労度',
			'remark' => '備考欄',
			'reply' => '返信',
			'charge_comment' => '支援記録と評価',
			'consent_heading' => '利用の確認',
			'consent_text' => '本日の利用について確認し、了承しました！',
			'back' => '戻る',
			'register' => '登録',
			'confirm' => '確認',
			'back_to_edit' => '入力画面に戻る',
			'complete' => 'この内容で登録',
		);

		return $labels[$key] ?? $key;
	}
}
