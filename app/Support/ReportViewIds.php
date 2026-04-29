<?php

declare(strict_types=1);

namespace App\Support;

final class ReportViewIds
{
	public static function fieldId(string $prefix, string $name): string
	{
		// Prefixes let the normal and accessible report views share one ID generation rule.
		return $prefix . preg_replace('/[^a-zA-Z0-9_]+/', '_', $name);
	}

	public static function errorId(string $prefix, string $name): string
	{
		return self::fieldId($prefix, $name) . '_error';
	}

	public static function helpId(string $prefix, string $name): string
	{
		return self::fieldId($prefix, $name) . '_help';
	}

	public static function describedBy(array $errors, string $prefix, string $name, bool $hasHelp): string
	{
		$ids = array();
		if ($hasHelp) {
			$ids[] = self::helpId($prefix, $name);
		}
		if (($errors[$name] ?? '') !== '') {
			$ids[] = self::errorId($prefix, $name);
		}
		return implode(' ', $ids);
	}
}
