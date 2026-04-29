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

	// Resolve mst_div labels in one helper so the list view does not rebuild closures locally.
	public static function divName(array $divMap, string $parent, $id): string
	{
		return (string) ($divMap[$parent][(int) $id] ?? '');
	}
}
