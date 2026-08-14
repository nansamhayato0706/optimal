<?php

declare(strict_types=1);

namespace App\Support;

final class ReportPdfGenerator
{
	private const CELL_H     = 7.7;
	private const FONT       = 'kozminproregular';
	private const FONT_SIZE  = 8;

	/** @var \TCPDF|null */
	private $pdf = null;

	/** @var array<string, array<int, string>> */
	private $divMap = array();

	private $config;

	public function __construct(AppConfig $config)
	{
		$this->config = $config;
	}

	public function output(array $reports, array $divMap, string $fileName): void
	{
		$tcpdf = $this->config->rootPath() . 'lib/tcpdf/tcpdf.php';
		if (!is_file($tcpdf)) {
			throw new \RuntimeException('TCPDF library not found.');
		}
		require_once $tcpdf;

		$this->divMap = $divMap;

		$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(10, 10, 10);
		$pdf->SetAutoPageBreak(true, 10);
		$pdf->SetFillColor(200, 200, 200);
		$pdf->SetFont(self::FONT, '', self::FONT_SIZE);
		$this->pdf = $pdf;

		foreach ($reports as $report) {
			$pdf->AddPage();
			$this->renderReport($report);
		}

		$pdf->Output(str_replace('.pdf', '', $fileName) . '.pdf', 'I');
		exit;
	}

	private function renderReport(array $report): void
	{
		$ch = self::CELL_H;

		$this->setFont(self::FONT_SIZE);
		$this->cell('出力日時：' . date('Y-m-d H:i:s'), 190, 0, 1, 'R');

		$this->setFont(self::FONT_SIZE + 10, 'B');
		$this->cell($this->formatDate((string) ($report['report_date'] ?? ''), 'Y年m月d日') . '　訓練日報', 190, 0, 1, 'C');

		$this->setFont(self::FONT_SIZE);
		$this->cell('記入者：' . (string) ($report['user_name'] ?? ''), 190, $ch, 1, 'R');

		// 睡眠
		$this->cell('昨日の就寝時間(時分)', 40, $ch, 0, 'C', 1, 1);
		$this->cell($this->formatTime((string) ($report['retiring_time'] ?? '')), 20, $ch, 0, 'C', 1);
		$this->cell('今日の起床時間(時分)', 40, $ch, 0, 'C', 1, 1);
		$this->cell($this->formatTime((string) ($report['rising_time'] ?? '')), 20, $ch, 0, 'C', 1);
		$this->cell('睡眠時間(時分)', 40, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['sleep_time'] ?? ''), 30, $ch, 1, 'C', 1);

		// 気分・体調
		$this->cell('該当する内容を選んでください。', 190, $ch, 1, 'L');
		$this->cell('今日の気分', 55, $ch, 0, 'C', 1, 1);
		$this->cell($this->getKeyValue('mood', (string) ($report['mood_div'] ?? '')), 40, $ch, 0, 'C', 1);
		$this->cell('今日の体調', 55, $ch, 0, 'C', 1, 1);
		$this->cell($this->getKeyValue('condition', (string) ($report['condition_div'] ?? '')), 40, $ch, 1, 'C', 1);

		// 目標（チェックボックス）
		$this->cell('★今日の目標(今日頑張る事・頑張った事)　(空欄にチェックを入れてください。何個でもＯＫ)', 190, $ch, 1, 'L');
		$objective = $this->getKeyValueList('objective');
		$cnt = 1;
		$max = 4;
		foreach ($objective as $key => $value) {
			$chk = in_array((string) $key, array_map('strval', (array) ($report['objective_div'] ?? array())), true) ? '●' : '';
			$ln  = intdiv($cnt, $max);
			$this->cell($chk, 7.5, $ch, 0, 'C', 1);
			$this->cell($value, 40, $ch, $ln, 'C', 1, 1);
			$cnt++;
			if ($cnt > $max) {
				$cnt = 1;
			}
		}

		// 服薬
		$this->cell('※服薬されている方(該当する内容を選んでください。)', 190, $ch, 1, 'L');
		$this->multiCell('決まった通りに飲みましたか？', 15, $ch * 3, 0, 'C', 1, 1);
		$this->multiCell($this->getKeyValue('medicine', (string) ($report['medicine_div'] ?? '')), 15, $ch * 3, 0, 'C', 1);
		$this->multiCell('※薬を飲まなかった方の理由は何ですか？', 15, $ch * 3, 0, 'C', 1, 1);
		$this->multiCell((string) ($report['medicine_reason'] ?? ''), 145, $ch * 3, 1, 'L', 1);

		// 外出（チェックボックス）
		$this->cell('※今日は外出しましたか？(空欄にチェックを入れてください。何個でもＯＫ)', 190, $ch, 1, 'L');
		$outing = $this->getKeyValueList('outing');
		$cnt = 1;
		$max = 4;
		foreach ($outing as $key => $value) {
			$chk = in_array((string) $key, array_map('strval', (array) ($report['outing_div'] ?? array())), true) ? '●' : '';
			$ln  = intdiv($cnt, $max);
			$this->cell($chk, 7.5, $ch, 0, 'C', 1);
			$this->cell($value, 40, $ch, $ln, 'C', 1, 1);
			$cnt++;
			if ($cnt > $max) {
				$cnt = 1;
			}
		}

		// 会話
		$this->cell('※今日は家族以外の方と何人お話ししましたか？(該当する内容を選んでください。)', 190, $ch, 1, 'L');
		$this->cell($this->getKeyValue('talk', (string) ($report['talk_div'] ?? '')), 40, $ch, 1, 'C', 1);

		// 訓練状況
		$this->cell('★今日の訓練状況', 190, $ch, 1, 'L');
		$this->cell('訓練内容　午前', 31, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['training_am_1'] ?? ''), 53, $ch, 0, 'L', 1);
		$this->cell((string) ($report['training_am_2'] ?? ''), 53, $ch, 0, 'L', 1);
		$this->cell((string) ($report['training_am_3'] ?? ''), 53, $ch, 1, 'L', 1);
		$this->cell('訓練内容　午後', 31, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['training_pm_1'] ?? ''), 53, $ch, 0, 'L', 1);
		$this->cell((string) ($report['training_pm_2'] ?? ''), 53, $ch, 0, 'L', 1);
		$this->cell((string) ($report['training_pm_3'] ?? ''), 53, $ch, 1, 'L', 1);
		$this->cell('訓練時間　開始時間', 40, $ch, 0, 'C', 1, 1);
		$this->cell($this->formatTime((string) ($report['training_start_time'] ?? '')), 20, $ch, 0, 'C', 1);
		$this->cell('訓練時間　終了時間', 40, $ch, 0, 'C', 1, 1);
		$this->cell($this->formatTime((string) ($report['training_end_time'] ?? '')), 20, $ch, 0, 'C', 1);
		$this->cell('合計', 40, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['training_time'] ?? ''), 30, $ch, 1, 'C', 1);
		$this->cell('休憩時間　昼休憩(分)', 30, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['lunch_time'] ?? ''), 20, $ch, 0, 'C', 1);
		$this->cell('途中休憩(分)', 30, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['break_time'] ?? ''), 20, $ch, 0, 'C', 1);
		$this->cell('合計(分)', 30, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['lunch_break_time'] ?? ''), 20, $ch, 0, 'C', 1);
		$this->cell('実働', 20, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['work_time'] ?? ''), 20, $ch, 1, 'C', 1);

		// 振り返り
		$this->cell('★今日一日の訓練を振り返り(感想)', 190, $ch, 1, 'L');
		$this->cell('訓練内容　午前', 30, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['rethink_am'] ?? ''), 160, $ch, 1, 'C', 1);
		$this->cell('達成度(％)', 55, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['achieve_am'] ?? ''), 40, $ch, 0, 'C', 1);
		$this->cell('疲労度(％)', 55, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['fatigue_am'] ?? ''), 40, $ch, 1, 'C', 1);
		$this->cell('訓練内容　午後', 30, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['rethink_pm'] ?? ''), 160, $ch, 1, 'C', 1);
		$this->cell('達成度(％)', 55, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['achieve_pm'] ?? ''), 40, $ch, 0, 'C', 1);
		$this->cell('疲労度(％)', 55, $ch, 0, 'C', 1, 1);
		$this->cell((string) ($report['fatigue_pm'] ?? ''), 40, $ch, 1, 'C', 1);

		// 備考・支援記録
		$this->cell('★疑問や質問の欄(悩みでも何でも結構です)', 190, $ch, 1, 'L');
		$this->multiCell('備考欄', 40, $ch * 3, 0, 'C', 1, 1);
		$this->multiCell((string) ($report['remark'] ?? ''), 150, $ch * 3, 1, 'L', 1);
		$this->multiCell('【支援記録】及び【評価】', 40, $ch * 3, 0, 'C', 1, 1);
		$this->multiCell((string) ($report['charge_comment'] ?? ''), 150, $ch * 3, 1, 'L', 1);
	}

	private function cell(string $text = '', float $width = 0, float $height = 0, int $line = 0, string $align = '', int $border = 0, int $fill = 0): void
	{
		$this->pdf->Cell($width, $height, $text, $border, $line, $align, $fill);
	}

	private function multiCell(string $text = '', float $width = 0, float $height = 0, int $line = 0, string $align = '', int $border = 0, int $fill = 0): void
	{
		$this->pdf->MultiCell($width, $height, $text, $border, $align, $fill, $line);
	}

	private function setFont(int $size, string $style = ''): void
	{
		$this->pdf->SetFont(self::FONT, $style, $size);
	}

	private function getKeyValue(string $parent, string $key): string
	{
		if ($key === '') {
			return '';
		}
		return (string) ($this->divMap[$parent][(int) $key] ?? '');
	}

	private function getKeyValueList(string $parent): array
	{
		return $this->divMap[$parent] ?? array();
	}

	private function formatDate(string $value, string $format): string
	{
		$timestamp = strtotime($value . ' 00:00:00');
		return $timestamp === false ? $value : date($format, $timestamp);
	}

	private function formatTime(string $value): string
	{
		if ($value === '') {
			return '';
		}
		$timestamp = strtotime('today ' . $value);
		return $timestamp === false ? $value : date('H:i', $timestamp);
	}
}
