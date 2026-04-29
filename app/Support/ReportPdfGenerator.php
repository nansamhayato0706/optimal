<?php

declare(strict_types=1);

namespace App\Support;

final class ReportPdfGenerator
{
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

		$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(10, 10, 10);
		$pdf->SetAutoPageBreak(true, 10);
		$pdf->SetFillColor(230, 230, 230);
		$pdf->SetFont('kozminproregular', '', 8);

		foreach ($reports as $report) {
			$pdf->AddPage();
			$pdf->writeHTML($this->buildHtml($report, $divMap), true, false, true, false, '');
		}

		$pdf->Output(str_replace('.pdf', '', $fileName) . '.pdf', 'I');
		exit;
	}

	private function buildHtml(array $report, array $divMap): string
	{
		$title = $this->formatDate((string) ($report['report_date'] ?? ''), 'Y年m月d日') . '　訓練日報';
		$objective = $this->pickedLabels($divMap['objective'] ?? array(), $report['objective_div'] ?? array());
		$outing = $this->pickedLabels($divMap['outing'] ?? array(), $report['outing_div'] ?? array());

		return
			'<style>'
			. 'table{border-collapse:collapse;width:100%;}'
			. 'td,th{border:1px solid #333;padding:4px;line-height:1.4;}'
			. 'th{background-color:#e6e6e6;font-weight:bold;text-align:center;}'
			. '.title{font-size:18px;font-weight:bold;text-align:center;}'
			. '.right{text-align:right;}'
			. '.center{text-align:center;}'
			. '.label{background-color:#e6e6e6;}'
			. '.section{background-color:#e6e6e6;font-weight:bold;}'
			. '</style>'
			. '<div class="right">出力日時：' . $this->h(date('Y-m-d H:i:s')) . '</div>'
			. '<div class="title">' . $this->h($title) . '</div>'
			. '<table cellpadding="3">'
			. '<tr><td colspan="4" class="right">記入者：' . $this->h((string) ($report['user_name'] ?? '')) . '</td></tr>'
			. '<tr>'
			. '<th>昨日の就寝時間</th><td class="center">' . $this->h($this->formatTime((string) ($report['retiring_time'] ?? ''))) . '</td>'
			. '<th>今日の起床時間</th><td class="center">' . $this->h($this->formatTime((string) ($report['rising_time'] ?? ''))) . '</td>'
			. '</tr>'
			. '<tr><th>睡眠時間</th><td colspan="3">' . $this->h((string) ($report['sleep_time'] ?? '')) . '</td></tr>'
			. '<tr><td colspan="4" class="section">該当する内容を選んでください。</td></tr>'
			. '<tr>'
			. '<th>今日の気分</th><td>' . $this->h($this->divName($divMap, 'mood', $report['mood_div'] ?? '')) . '</td>'
			. '<th>今日の体調</th><td>' . $this->h($this->divName($divMap, 'condition', $report['condition_div'] ?? '')) . '</td>'
			. '</tr>'
			. '<tr><th>今日の目標</th><td colspan="3">' . $this->h(implode('、', $objective)) . '</td></tr>'
			. '<tr><td colspan="4" class="section">服薬</td></tr>'
			. '<tr><th>決まった通りに飲みましたか？</th><td>' . $this->h($this->divName($divMap, 'medicine', $report['medicine_div'] ?? '')) . '</td>'
			. '<th>薬を飲まなかった方の理由</th><td>' . $this->h((string) ($report['medicine_reason'] ?? '')) . '</td></tr>'
			. '<tr><th>今日は外出しましたか？</th><td colspan="3">' . $this->h(implode('、', $outing)) . '</td></tr>'
			. '<tr><th>家族以外の方と話した人数</th><td colspan="3">' . $this->h($this->divName($divMap, 'talk', $report['talk_div'] ?? '')) . '</td></tr>'
			. '<tr><td colspan="4" class="section">今日の訓練状況</td></tr>'
			. '<tr><th>訓練内容 午前</th><td colspan="3">' . $this->h($this->joinValues(array($report['training_am_1'] ?? '', $report['training_am_2'] ?? '', $report['training_am_3'] ?? ''))) . '</td></tr>'
			. '<tr><th>訓練内容 午後</th><td colspan="3">' . $this->h($this->joinValues(array($report['training_pm_1'] ?? '', $report['training_pm_2'] ?? '', $report['training_pm_3'] ?? ''))) . '</td></tr>'
			. '<tr><th>開始時間</th><td>' . $this->h($this->formatTime((string) ($report['training_start_time'] ?? ''))) . '</td>'
			. '<th>終了時間</th><td>' . $this->h($this->formatTime((string) ($report['training_end_time'] ?? ''))) . '</td></tr>'
			. '<tr><th>訓練時間</th><td>' . $this->h((string) ($report['training_time'] ?? '')) . '</td>'
			. '<th>実働</th><td>' . $this->h((string) ($report['work_time'] ?? '')) . '</td></tr>'
			. '<tr><th>昼休憩(分)</th><td>' . $this->h((string) ($report['lunch_time'] ?? '')) . '</td>'
			. '<th>途中休憩(分)</th><td>' . $this->h((string) ($report['break_time'] ?? '')) . '</td></tr>'
			. '<tr><td colspan="4" class="section">今日一日の訓練を振り返り</td></tr>'
			. '<tr><th>午前の振り返り</th><td colspan="3">' . nl2br($this->h((string) ($report['rethink_am'] ?? ''))) . '</td></tr>'
			. '<tr><th>午前 達成度</th><td>' . $this->h((string) ($report['achieve_am'] ?? '')) . '</td>'
			. '<th>午前 疲労度</th><td>' . $this->h((string) ($report['fatigue_am'] ?? '')) . '</td></tr>'
			. '<tr><th>午後の振り返り</th><td colspan="3">' . nl2br($this->h((string) ($report['rethink_pm'] ?? ''))) . '</td></tr>'
			. '<tr><th>午後 達成度</th><td>' . $this->h((string) ($report['achieve_pm'] ?? '')) . '</td>'
			. '<th>午後 疲労度</th><td>' . $this->h((string) ($report['fatigue_pm'] ?? '')) . '</td></tr>'
			. '<tr><td colspan="4" class="section">疑問や質問の欄</td></tr>'
			. '<tr><th>備考欄</th><td colspan="3">' . nl2br($this->h((string) ($report['remark'] ?? ''))) . '</td></tr>'
			. '<tr><th>支援記録及び評価</th><td colspan="3">' . nl2br($this->h((string) ($report['charge_comment'] ?? ''))) . '</td></tr>'
			. '</table>';
	}

	private function h(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}

	private function divName(array $divMap, string $parent, $id): string
	{
		return (string) ($divMap[$parent][(int) $id] ?? '');
	}

	private function pickedLabels(array $map, $values): array
	{
		$result = array();
		foreach ((array) $values as $value) {
			$key = (int) $value;
			if (isset($map[$key])) {
				$result[] = (string) $map[$key];
			}
		}
		return $result;
	}

	private function joinValues(array $values): string
	{
		$filtered = array();
		foreach ($values as $value) {
			$value = trim((string) $value);
			if ($value !== '') {
				$filtered[] = $value;
			}
		}
		return implode(' / ', $filtered);
	}

	private function formatDate(string $value, string $format): string
	{
		$timestamp = strtotime($value);
		return $timestamp === false ? $value : date($format, $timestamp);
	}

	private function formatTime(string $value): string
	{
		if ($value === '' || strtotime('today ' . $value) === false) {
			return $value;
		}
		return date('H:i', strtotime('today ' . $value));
	}
}
