<?php

declare(strict_types=1);

namespace App\Support;

final class ReportFieldLabels
{
	public static function forEdit(): array
	{
		// Keep report form validation labels outside the view so both layouts can reuse them.
		return array(
			'report_date' => '日付',
			'retiring_time' => '昨日の就寝時間',
			'rising_time' => '今日の起床時間',
			'mood_div' => '今日の気分',
			'condition_div' => '今日の体調',
			'medicine_div' => '服薬',
			'medicine_reason' => '服薬の理由',
			'talk_div' => '会話人数',
			'training_content' => '訓練内容',
			'training_start_time' => '訓練開始時間',
			'training_end_time' => '訓練終了時間',
			'lunch_time' => '昼休憩',
			'break_time' => '途中休憩',
			'achieve_am' => '午前の達成度',
			'fatigue_am' => '午前の疲労度',
			'achieve_pm' => '午後の達成度',
			'fatigue_pm' => '午後の疲労度',
			'remark' => '備考欄',
			'charge_comment' => '支援記録と評価',
			'user_uuid' => '対象ユーザー',
			'report_uuid' => '日報',
		);
	}
}
