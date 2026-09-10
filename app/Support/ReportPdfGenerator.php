<?php

declare(strict_types=1);

namespace App\Support;

final class ReportPdfGenerator
{
	private const PAGE_X       = 10;
	private const PAGE_R       = 200;
	private const PAGE_W       = 190;

	private const FONT_GOTHIC  = 'kozgopromedium';
	private const FONT_MINCHO  = 'kozminproregular';

	private const ORG_NAME     = '就労継続支援B型　在宅就労支援事業団';

	private const INK          = array(31, 43, 38);
	private const INK_SOFT     = array(92, 109, 100);
	private const INK_FAINT    = array(139, 153, 144);
	private const ACCENT       = array(62, 124, 109);
	private const ACCENT_DEEP  = array(37, 78, 68);
	private const ACCENT_TINT  = array(228, 237, 232);
	private const FLAG         = array(169, 118, 46);
	private const FLAG_TINT    = array(242, 230, 211);
	private const LINE         = array(215, 221, 211);
	private const LINE_SOFT    = array(232, 236, 229);
	private const WHITE        = array(255, 255, 255);

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
		$pdf->SetMargins(self::PAGE_X, 10, 10);
		$pdf->SetAutoPageBreak(true, 10);
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->SetLineWidth(0.2);
		$this->pdf = $pdf;

		foreach ($reports as $report) {
			$pdf->AddPage();
			$this->renderReport($report);
		}

		$this->stampPageNumbers();

		$pdf->Output(str_replace('.pdf', '', $fileName) . '.pdf', 'I');
		exit;
	}

	/**
	 * 全ページの生成が終わってから、各ページ下部に「n / 総ページ数」を追記する。
	 */
	private function stampPageNumbers(): void
	{
		$totalPages = $this->pdf->getNumPages();
		if ($totalPages <= 1) {
			return;
		}

		for ($i = 1; $i <= $totalPages; $i++) {
			$this->pdf->setPage($i);
			// setPage() はページ生成時点の自動改ページ設定を復元してしまうため、
			// 呼び出すたびに無効化し直す（有効のままだと下端余白への描画が
			// 「はみ出し」と判定され、余分な白紙ページが追加されてしまう）。
			$this->pdf->SetAutoPageBreak(false);
			$this->font(self::FONT_GOTHIC, '', 8);
			$this->textColor(self::INK_FAINT);
			$this->pdf->SetXY(self::PAGE_X, $this->pdf->getPageHeight() - 12);
			$this->pdf->Cell(self::PAGE_W, 5, $i . ' / ' . $totalPages, 0, 0, 'C');
		}
	}

	private function renderReport(array $report): void
	{
		$this->renderMasthead($report);

		$this->sectionHeader('睡眠');
		$this->fieldRow(array(
			array('label' => '昨日の就寝時間', 'value' => $this->formatTime((string) ($report['retiring_time'] ?? '')), 'width' => 63),
			array('label' => '今日の起床時間', 'value' => $this->formatTime((string) ($report['rising_time'] ?? '')), 'width' => 63),
			array('label' => '睡眠時間', 'value' => (string) ($report['sleep_time'] ?? ''), 'width' => 64),
		));
		$this->pdf->Ln(2.5);

		$this->sectionHeader('気分・体調');
		$this->ensureSpace(16);
		$y = $this->pdf->GetY();
		$b1 = $this->fieldChip('今日の気分', $this->getKeyValue('mood', (string) ($report['mood_div'] ?? '')), self::PAGE_X, $y, 90);
		$b2 = $this->fieldChip('今日の体調', $this->getKeyValue('condition', (string) ($report['condition_div'] ?? '')), 105, $y, 90);
		$this->pdf->SetXY(self::PAGE_X, max($b1, $b2) + 2.5);

		$this->sectionHeader('今日の目標');
		$this->sectionNote('複数選択可・選ばれた項目のみ塗りつぶし表示');
		$this->renderPickList('objective', (array) ($report['objective_div'] ?? array()));
		$this->pdf->Ln(2.5);

		$this->sectionHeader('服薬');
		$medicineAnswer = $this->getKeyValue('medicine', (string) ($report['medicine_div'] ?? ''));
		$reasonText = (string) ($report['medicine_reason'] ?? '');
		$this->noteLine('服薬されている方：決まった通りに飲みましたか？　', $medicineAnswer);
		if ($reasonText !== '') {
			$this->noteLineWrapped('薬を飲まなかった方の理由は何ですか？　', $reasonText);
		}
		$this->pdf->Ln(2.5);

		$this->sectionHeader('外出');
		$this->sectionNote('※今日は外出しましたか？（該当する内容を選んでください）');
		$this->renderPickList('outing', (array) ($report['outing_div'] ?? array()));
		$this->pdf->Ln(2.5);

		$this->sectionHeader('会話');
		$this->noteLine('今日は家族以外の方と何人お話ししましたか？　', $this->getKeyValue('talk', (string) ($report['talk_div'] ?? '')));
		$this->pdf->Ln(2.5);

		$this->sectionHeader('訓練状況');
		$this->trainingContentRow('午前', array(
			(string) ($report['training_am_1'] ?? ''),
			(string) ($report['training_am_2'] ?? ''),
			(string) ($report['training_am_3'] ?? ''),
		));
		$this->trainingContentRow('午後', array(
			(string) ($report['training_pm_1'] ?? ''),
			(string) ($report['training_pm_2'] ?? ''),
			(string) ($report['training_pm_3'] ?? ''),
		));
		$this->pdf->Ln(2);
		$this->fieldRow(array(
			array('label' => '開始時間', 'value' => $this->formatTime((string) ($report['training_start_time'] ?? '')), 'width' => 38),
			array('label' => '終了時間', 'value' => $this->formatTime((string) ($report['training_end_time'] ?? '')), 'width' => 38),
			array('label' => '訓練時間合計', 'value' => (string) ($report['training_time'] ?? ''), 'width' => 38),
			array('label' => '昼休憩', 'value' => (string) ($report['lunch_time'] ?? '') . '分', 'width' => 25),
			array('label' => '途中休憩', 'value' => (string) ($report['break_time'] ?? '') . '分', 'width' => 25),
			array('label' => '実働', 'value' => (string) ($report['work_time'] ?? ''), 'width' => 26),
		));
		$this->pdf->Ln(2.5);

		$this->sectionHeader('振り返り');
		$this->reflectHalf('午前', (string) ($report['rethink_am'] ?? ''), (string) ($report['achieve_am'] ?? ''), (string) ($report['fatigue_am'] ?? ''));
		$this->reflectHalf('午後', (string) ($report['rethink_pm'] ?? ''), (string) ($report['achieve_pm'] ?? ''), (string) ($report['fatigue_pm'] ?? ''));

		$this->sectionHeader('質問や気になること');
		$this->noteCard('本人記入', (string) ($report['remark'] ?? ''), self::ACCENT_DEEP, self::ACCENT_TINT);
		$this->noteCard('返信', (string) ($report['reply'] ?? ''), self::ACCENT_DEEP, self::ACCENT_TINT);
		$this->noteCard('支援記録・評価', (string) ($report['charge_comment'] ?? ''), self::FLAG, self::FLAG_TINT);
	}

	private function renderMasthead(array $report): void
	{
		$this->font(self::FONT_GOTHIC, 'B', 9.5);
		$this->textColor(self::INK);
		$this->pdf->Cell(self::PAGE_W, 5, self::ORG_NAME, 0, 1, 'L');

		$this->font(self::FONT_GOTHIC, '', 8.5);
		$this->textColor(self::INK_FAINT);
		$this->pdf->Cell(120, 5, '在宅就労　訓練日報', 0, 0, 'L');
		$this->pdf->Cell(self::PAGE_W - 120, 5, '記入者', 0, 1, 'R');

		$this->font(self::FONT_MINCHO, 'B', 19);
		$this->textColor(self::INK);
		$this->pdf->Cell(120, 10, $this->formatDateWithWeekday((string) ($report['report_date'] ?? '')), 0, 0, 'L');
		$this->font(self::FONT_MINCHO, 'B', 13);
		$this->pdf->Cell(self::PAGE_W - 120, 10, (string) ($report['user_name'] ?? ''), 0, 1, 'R');

		$this->font(self::FONT_GOTHIC, '', 7.5);
		$this->textColor(self::INK_FAINT);
		$this->pdf->Cell(self::PAGE_W, 5, '出力日時　' . date('Y-m-d H:i:s'), 0, 1, 'L');

		$this->pdf->Ln(1);
		$this->drawColor(self::INK);
		$this->pdf->SetLineWidth(0.5);
		$y = $this->pdf->GetY();
		$this->pdf->Line(self::PAGE_X, $y, self::PAGE_R, $y);
		$this->pdf->SetLineWidth(0.2);
		$this->pdf->Ln(5);
	}

	/**
	 * 罫線・角丸矩形など自動改ページの対象外となる描画の前に、必要な高さが
	 * ページ内に収まるか確認し、収まらない場合はここで改ページする。
	 */
	private function ensureSpace(float $height): void
	{
		$bottom = $this->pdf->getPageHeight() - $this->pdf->getBreakMargin();
		if ($this->pdf->GetY() + $height > $bottom) {
			$this->pdf->AddPage();
		}
	}

	private function sectionHeader(string $title): void
	{
		$this->font(self::FONT_GOTHIC, 'B', 10.5);
		$this->textColor(self::INK);
		$this->pdf->Cell(self::PAGE_W, 6, $title, 0, 1, 'L');

		$y = $this->pdf->GetY();
		$this->drawColor(self::ACCENT);
		$this->pdf->SetLineWidth(0.5);
		$this->pdf->Line(self::PAGE_X, $y, self::PAGE_R, $y);
		$this->pdf->SetLineWidth(0.2);
		$this->pdf->Ln(2);
	}

	private function sectionNote(string $text): void
	{
		$this->font(self::FONT_GOTHIC, '', 7.8);
		$this->textColor(self::INK_FAINT);
		$this->pdf->Cell(self::PAGE_W, 4.5, $text, 0, 1, 'L');
	}

	/**
	 * ラベルを段上、値を段下に配置したフィールドを横並びで描画する。
	 * @param array<int, array{label:string, value:string, width:float}> $fields
	 */
	private function fieldRow(array $fields): void
	{
		$this->ensureSpace(11);
		$x0 = $this->pdf->GetX();
		$y0 = $this->pdf->GetY();

		$this->font(self::FONT_GOTHIC, '', 7.5);
		$this->textColor(self::INK_SOFT);
		foreach ($fields as $field) {
			$this->pdf->Cell($field['width'], 4, $field['label'], 0, 0, 'L');
		}

		$this->pdf->SetXY($x0, $y0 + 4.3);
		$this->font(self::FONT_MINCHO, 'B', 11.5);
		$this->textColor(self::INK);
		foreach ($fields as $field) {
			$this->pdf->Cell($field['width'], 6.5, $field['value'] !== '' ? $field['value'] : '―', 0, 0, 'L');
		}
		$this->pdf->SetXY($x0, $y0 + 4.3 + 6.5);
	}

	private function fieldChip(string $label, string $value, float $x, float $y, float $width): float
	{
		$this->font(self::FONT_GOTHIC, '', 7.5);
		$this->textColor(self::INK_SOFT);
		$this->pdf->SetXY($x, $y);
		$this->pdf->Cell($width, 4, $label, 0, 0, 'L');

		$this->font(self::FONT_GOTHIC, 'B', 9);
		$text = $value !== '' ? $value : '未選択';
		$chipW = $this->pdf->GetStringWidth($text) + 9;
		$chipY = $y + 5;
		$chipH = 6.6;
		$this->pdf->RoundedRect($x, $chipY, $chipW, $chipH, $chipH / 2, '1111', 'F', array(), self::ACCENT_TINT);
		$this->textColor(self::ACCENT_DEEP);
		$this->pdf->SetXY($x, $chipY);
		$this->pdf->Cell($chipW, $chipH, $text, 0, 0, 'C');

		return $chipY + $chipH;
	}

	/**
	 * 複数選択項目をピル表示する（選択済みは塗りつぶし、未選択は輪郭のみ）。
	 * @param array<int|string, string> $values 現在選択されているキーの配列
	 */
	private function renderPickList(string $parent, array $values): void
	{
		$selected = array_map('strval', $values);
		$rowH = 6.6;
		$gap = 3;
		$bottom = $this->pdf->getPageHeight() - $this->pdf->getBreakMargin();

		$this->ensureSpace($rowH);
		$x = self::PAGE_X;
		$y = $this->pdf->GetY();

		$this->font(self::FONT_GOTHIC, '', 8.5);
		foreach ($this->getKeyValueList($parent) as $key => $label) {
			$isOn = in_array((string) $key, $selected, true);
			$pillW = $this->pdf->GetStringWidth($label) + 8;

			if ($x + $pillW > self::PAGE_R) {
				$x = self::PAGE_X;
				$y += $rowH + $gap;
				if ($y + $rowH > $bottom) {
					$this->pdf->AddPage();
					$y = $this->pdf->GetY();
				}
			}

			if ($isOn) {
				$this->pdf->RoundedRect($x, $y, $pillW, $rowH, $rowH / 2, '1111', 'F', array(), self::ACCENT);
				$this->textColor(self::WHITE);
			} else {
				$this->pdf->RoundedRect($x, $y, $pillW, $rowH, $rowH / 2, '1111', 'D', array('width' => 0.2, 'color' => self::LINE), array());
				$this->textColor(self::INK_FAINT);
			}
			$this->pdf->SetXY($x, $y);
			$this->pdf->Cell($pillW, $rowH, $label, 0, 0, 'C');

			$x += $pillW + $gap;
		}

		$this->pdf->SetXY(self::PAGE_X, $y + $rowH);
	}

	private function noteLine(string $question, string $answer): void
	{
		$markWidth = 5.0;
		$answerWidth = self::PAGE_W * 0.26;
		$questionWidth = self::PAGE_W - $markWidth - $answerWidth;

		$this->font(self::FONT_GOTHIC, 'B', 9);
		$this->textColor(self::FLAG);
		$this->pdf->Cell($markWidth, 5.5, '※', 0, 0, 'L');

		$this->font(self::FONT_MINCHO, '', 10);
		$this->textColor(self::INK);
		$this->pdf->Cell($questionWidth, 5.5, $question, 0, 0, 'L');

		$this->font(self::FONT_MINCHO, 'B', 10);
		$this->textColor(self::ACCENT_DEEP);
		$this->pdf->Cell($answerWidth, 5.5, $answer !== '' ? $answer : '―', 0, 1, 'L');
	}

	/**
	 * 自由記述の回答など、長さが不定な値を質問文に続けて折り返し表示する。
	 */
	private function noteLineWrapped(string $question, string $answer): void
	{
		$markWidth = 5.0;

		$this->font(self::FONT_GOTHIC, 'B', 9);
		$this->textColor(self::FLAG);
		$this->pdf->Cell($markWidth, 5.5, '※', 0, 0, 'L');

		$this->font(self::FONT_MINCHO, '', 10);
		$this->textColor(self::INK);
		$this->pdf->MultiCell(self::PAGE_W - $markWidth, 5.5, $question . $answer, 0, 'L', 0, 1);
	}

	private function trainingContentRow(string $label, array $slots): void
	{
		$slotWidth = (self::PAGE_W - 18) / 3;
		$pad = 3;
		$textWidth = $slotWidth - $pad;
		$lineH = 4.8;

		$this->font(self::FONT_MINCHO, '', 9.5);
		$maxLines = 1;
		foreach ($slots as $slot) {
			$maxLines = max($maxLines, $this->pdf->getNumLines($slot !== '' ? $slot : '―', $textWidth));
		}
		$rowH = $maxLines * $lineH;

		$this->ensureSpace($rowH);
		$x0 = $this->pdf->GetX();
		$y0 = $this->pdf->GetY();

		$this->font(self::FONT_GOTHIC, 'B', 8.5);
		$this->textColor(self::ACCENT_DEEP);
		$this->pdf->SetXY($x0, $y0);
		$this->pdf->Cell(18, $rowH, $label, 0, 0, 'L');

		$this->font(self::FONT_MINCHO, '', 9.5);
		$this->textColor(self::INK);
		$x = $x0 + 18;
		foreach ($slots as $slot) {
			$this->pdf->SetXY($x + $pad, $y0);
			$this->pdf->MultiCell($textWidth, $lineH, $slot !== '' ? $slot : '―', 0, 'L', 0, 1);
			$x += $slotWidth;
		}

		// 列と列の区切り線（ラベル列の右端〜3列それぞれの境界〜右端）
		$this->drawColor(self::LINE_SOFT);
		$this->pdf->SetLineWidth(0.2);
		for ($i = 0; $i <= 3; $i++) {
			$lx = $x0 + 18 + ($i * $slotWidth);
			$this->pdf->Line($lx, $y0, $lx, $y0 + $rowH);
		}

		$this->pdf->SetXY($x0, $y0 + $rowH);
		$this->drawColor(self::LINE_SOFT);
		$this->pdf->Line(self::PAGE_X, $this->pdf->GetY(), self::PAGE_R, $this->pdf->GetY());
		$this->pdf->Ln(1);
	}

	private function reflectHalf(string $label, string $text, string $achieve, string $fatigue): void
	{
		$bodyWidth = self::PAGE_W - 8;
		$this->font(self::FONT_MINCHO, '', 9.5);
		$lines = max(1, $this->pdf->getNumLines($text !== '' ? $text : '　', $bodyWidth));
		$lineH = 4.8;
		$blockH = 6 + ($lines * $lineH) + 10;

		$this->ensureSpace($blockH);
		$x = self::PAGE_X;
		$y = $this->pdf->GetY();
		$this->pdf->RoundedRect($x, $y, self::PAGE_W, $blockH, 2, '1111', 'F', array(), self::LINE_SOFT);

		$this->font(self::FONT_GOTHIC, 'B', 8);
		$this->textColor(self::INK_SOFT);
		$this->pdf->SetXY($x + 4, $y + 3);
		$this->pdf->Cell(20, 4, $label, 0, 0, 'L');

		$this->font(self::FONT_MINCHO, '', 9.5);
		$this->textColor(self::INK);
		$this->pdf->SetXY($x + 4, $y + 7.5);
		$this->pdf->MultiCell($bodyWidth, $lineH, $text, 0, 'L', 0, 1);

		$meterY = $y + 7.5 + ($lines * $lineH) + 2;
		$meterWidth = (self::PAGE_W - 8 - 10) / 2;
		$this->progressBar('達成度', $achieve, $x + 4, $meterY, $meterWidth, self::ACCENT);
		$this->progressBar('疲労度', $fatigue, $x + 4 + $meterWidth + 10, $meterY, $meterWidth, self::FLAG);

		$this->pdf->SetXY($x, $y + $blockH + 2.5);
	}

	private function progressBar(string $label, string $rawValue, float $x, float $y, float $width, array $fillColor): void
	{
		$percent = is_numeric($rawValue) ? max(0, min(100, (float) $rawValue)) : 0.0;

		$this->font(self::FONT_GOTHIC, '', 7.5);
		$this->textColor(self::INK_FAINT);
		$this->pdf->SetXY($x, $y);
		$this->pdf->Cell($width * 0.6, 4, $label, 0, 0, 'L');

		$this->font(self::FONT_GOTHIC, 'B', 9);
		$this->textColor(self::INK);
		$this->pdf->SetXY($x + ($width * 0.6), $y);
		$this->pdf->Cell($width * 0.4, 4, ($rawValue !== '' ? $rawValue : '―') . ($rawValue !== '' ? '%' : ''), 0, 0, 'R');

		$barY = $y + 4.6;
		$barH = 1.6;
		$this->pdf->RoundedRect($x, $barY, $width, $barH, $barH / 2, '1111', 'F', array(), self::LINE);
		$fillWidth = $width * ($percent / 100);
		if ($fillWidth > 0.6) {
			$this->pdf->RoundedRect($x, $barY, $fillWidth, $barH, $barH / 2, '1111', 'F', array(), $fillColor);
		}
	}

	private function noteCard(string $tag, string $body, array $tagColor, array $tagBg): void
	{
		$bodyWidth = self::PAGE_W - 8;
		$this->font(self::FONT_MINCHO, '', 9.5);
		$lines = max(1, $this->pdf->getNumLines($body !== '' ? $body : '　', $bodyWidth));
		$lineH = 4.8;
		$tagH = 6.4;
		$cardH = 2.5 + $tagH + 1 + ($lines * $lineH) + 2.5;

		$this->ensureSpace($cardH);
		$x = self::PAGE_X;
		$y = $this->pdf->GetY();
		$this->pdf->RoundedRect($x, $y, self::PAGE_W, $cardH, 2, '1111', 'D', array('width' => 0.2, 'color' => self::LINE), array());

		$this->font(self::FONT_GOTHIC, 'B', 8);
		$tagW = $this->pdf->GetStringWidth($tag) + 8;
		$this->pdf->RoundedRect($x + 4, $y + 2.5, $tagW, $tagH, 1.5, '1111', 'F', array(), $tagBg);
		$this->textColor($tagColor);
		$this->pdf->SetXY($x + 4, $y + 2.5);
		$this->pdf->Cell($tagW, $tagH, $tag, 0, 0, 'C');

		$this->font(self::FONT_MINCHO, '', 9.5);
		$this->textColor(self::INK);
		$this->pdf->SetXY($x + 4, $y + 2.5 + $tagH + 1);
		$this->pdf->MultiCell($bodyWidth, $lineH, $body !== '' ? $body : '―', 0, 'L', 0, 1);

		$this->pdf->SetXY($x, $y + $cardH + 2.5);
	}

	private function font(string $family, string $style, float $size): void
	{
		$this->pdf->SetFont($family, $style, $size);
	}

	private function textColor(array $rgb): void
	{
		$this->pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
	}

	private function drawColor(array $rgb): void
	{
		$this->pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
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

	private function formatDateWithWeekday(string $value): string
	{
		$timestamp = strtotime($value . ' 00:00:00');
		if ($timestamp === false) {
			return $value;
		}
		$weekdays = array('日', '月', '火', '水', '木', '金', '土');
		return date('Y年m月d日', $timestamp) . '(' . $weekdays[(int) date('w', $timestamp)] . ')';
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
