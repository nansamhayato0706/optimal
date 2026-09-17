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

	// $escapedText は htmlspecialchars 済みの文字列を渡すこと
	public static function linkify(string $escapedText): string
	{
		return preg_replace_callback(
			'/(https?:\/\/[^\s<]+)/u',
			static function (array $m): string {
				return '<a href="' . $m[1] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
			},
			$escapedText
		);
	}

	// ファイル送信メッセージ（本文がURLのみ）かどうか。ファイル添付との整合性が崩れるため編集対象から外す判定に使う
	public static function isFileOnlyMessage(array $message): bool
	{
		return (bool) preg_match('#^https?://\S+$#u', trim((string) ($message['chat_text'] ?? '')));
	}

	// ログイン中の管理者が自分で送った、編集可能なメッセージかどうか
	public static function canEdit(array $message, string $loginAdminUuid): bool
	{
		if ($loginAdminUuid === '' || self::isFileOnlyMessage($message)) {
			return false;
		}

		return self::isAdminMessage($message)
			&& (string) ($message['insert_uuid'] ?? '') === $loginAdminUuid;
	}
}
