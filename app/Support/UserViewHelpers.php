<?php

declare(strict_types=1);

namespace App\Support;

final class UserViewHelpers
{
	// Keep user list formatting in one place so the table and modal stay consistent.
	public static function formatDateTime(?string $value, string $format): string
	{
		if ($value === null || $value === '') {
			return '';
		}

		$timestamp = strtotime($value);
		return $timestamp === false ? (string) $value : date($format, $timestamp);
	}

	public static function age(?string $birthday): string
	{
		if ($birthday === null || $birthday === '') {
			return '';
		}

		return (string) intval((date('Ymd') - date('Ymd', strtotime($birthday))) / 10000);
	}

	public static function nameTitle(array $user): string
	{
		$lines = array();
		$address = trim((string) ($user['user_address'] ?? ''));
		$tel = trim((string) ($user['user_tel'] ?? ''));
		if ($address !== '') {
			$lines[] = '住所: ' . $address;
		}
		if ($tel !== '') {
			$lines[] = '電話番号: ' . $tel;
		}

		return implode("\n", $lines);
	}

	// Chat column markup shared by the initial render and the status polling payload.
	// Counts are today's messages: send = admin -> worker, recv = worker -> admin.
	// $state: 'warn' = やり取り不足の注意, 'clear' = 基準を満たして注意解除, '' = 表示なし
	public static function chatCellHtml(string $userUuid, int $sendCount, int $recvCount, string $state = ''): string
	{
		$uuid = htmlspecialchars($userUuid, ENT_QUOTES, 'UTF-8');
		$counts = array(
			array('send', '送', $sendCount, '本日の送信回数（管理者→就労者）'),
			array('recv', '受', $recvCount, '本日の受信回数（就労者→管理者）'),
		);
		// 注意のときだけ「確認」を赤い円で囲む。クリア・対象外は通常のリンクのまま
		$linkAttr = $state === 'warn'
			? ' class="user-chat-link user-chat-link--warn" title="注意：本日のチャットのやり取りが2回以下です"'
			: '';
		$html = '<div class="user-chat-cell"><a' . $linkAttr . ' href="chat.php?i=' . $uuid . '">確認</a><span class="user-chat-counts">';
		foreach ($counts as $count) {
			$html .= '<span class="user-chat-count user-chat-count--' . $count[0] . ($count[2] === 0 ? ' is-zero' : '') . '" title="' . $count[3] . '">'
				. '<span class="user-chat-count__label">' . $count[1] . '</span>'
				. '<span class="user-chat-count__num">' . $count[2] . '</span></span>';
		}

		return $html . '</span></div>';
	}

	// Resolve mst_div labels in one helper so the list view does not rebuild closures locally.
	public static function divName(array $divMap, string $parent, $id): string
	{
		return (string) ($divMap[$parent][(int) $id] ?? '');
	}
}
