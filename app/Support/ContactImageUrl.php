<?php

declare(strict_types=1);

namespace App\Support;

final class ContactImageUrl
{
	public static function buildRelativePath(array $contact): string
	{
		return 'img/'
			. (string) ($contact['insert_uuid'] ?? '') . '/'
			. self::formatDateTime((string) ($contact['contact_date'] ?? ''), 'Ymd') . '/'
			. self::formatDateTime((string) ($contact['contact_date'] ?? ''), 'His') . (string) ($contact['contact_div'] ?? '') . '.jpg';
	}

	public static function resolve(array $contact, AppConfig $config): string
	{
		$relativePath = self::buildRelativePath($contact);
		if ($relativePath === 'img///.jpg') {
			return $config->dummyImage();
		}

		if (is_file($config->rootPath() . $relativePath)) {
			return './' . $relativePath;
		}

		if ($config->remoteImageBase() !== '') {
			return rtrim($config->remoteImageBase(), '/') . '/' . ltrim($relativePath, '/');
		}

		return $config->dummyImage();
	}

	private static function formatDateTime(string $value, string $format): string
	{
		if ($value === '') {
			return '';
		}

		$ts = strtotime($value);
		return $ts === false ? $value : date($format, $ts);
	}
}
