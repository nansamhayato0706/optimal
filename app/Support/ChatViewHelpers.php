<?php

declare(strict_types=1);

namespace App\Support;

final class ChatViewHelpers
{
	// Keep chat sender formatting together so list rows render consistently.
	public static function isAdminMessage(array $message): bool
	{
		return (($message['user_name'] ?? '') === '' || ($message['user_name'] ?? null) === null);
	}

	public static function displayName(array $message): string
	{
		if (self::isAdminMessage($message)) {
			return (string) ($message['admin_name'] ?? '');
		}

		return (string) ($message['user_name'] ?? '');
	}

	public static function cssClass(array $message): string
	{
		return self::isAdminMessage($message) ? 'chat-from-admin' : 'chat-from-user';
	}
}
