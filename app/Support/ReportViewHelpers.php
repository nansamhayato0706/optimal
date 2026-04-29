<?php

declare(strict_types=1);

namespace App\Support;

final class ReportViewHelpers
{
	public static function pickedLabels(array $map, array $values): array
	{
		// Convert selected numeric ids back to display labels for read-only confirmation screens.
		$labels = array();
		foreach ($values as $value) {
			if (isset($map[(int) $value])) {
				$labels[] = (string) $map[(int) $value];
			}
		}
		return $labels;
	}

	public static function pickedLabel(array $map, $value): string
	{
		return isset($map[(int) $value]) ? (string) $map[(int) $value] : '';
	}

	public static function sectionId(string $prefix, string $name): string
	{
		return $prefix . preg_replace('/[^a-zA-Z0-9_]+/', '_', $name);
	}
}
