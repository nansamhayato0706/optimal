<?php

declare(strict_types=1);

namespace App\Support;

final class ContactViewHelpers
{
	// Keep contact date formatting in one place so modal and page views stay aligned.
	public static function formatDateTime(?string $value, string $format): string
	{
		if ($value === null || $value === '') {
			return '';
		}

		$timestamp = strtotime($value);
		return $timestamp === false ? (string) $value : date($format, $timestamp);
	}
}
