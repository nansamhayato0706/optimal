<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\BaseRepository;
use PDO;

final class LogRepository extends BaseRepository
{
	public function findUserName(string $userUuid): string
	{
		if ($userUuid === '') {
			return '';
		}
		$stmt = $this->pdo->prepare('SELECT user_name FROM mst_user WHERE user_uuid = :user_uuid LIMIT 1');
		$stmt->execute(array('user_uuid' => $userUuid));
		$value = $stmt->fetchColumn();
		return $value === false ? '' : (string) $value;
	}

	public function findContacts(string $userUuid, string $dateSt, string $dateEd): array
	{
		$sql = 'SELECT * FROM tbl_contact'
			. ' WHERE insert_uuid = :user_uuid AND contact_date >= :date_st AND contact_date <= :date_ed AND delete_flg = 0'
			. ' ORDER BY contact_date DESC, contact_uuid DESC';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute(array(
			'user_uuid' => $userUuid,
			'date_st' => $dateSt . ' 00:00:00',
			'date_ed' => $dateEd . ' 23:59:59',
		));
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findSends(string $userUuid, string $dateSt, string $dateEd): array
	{
		$sql = 'SELECT * FROM tbl_send'
			. ' WHERE insert_uuid = :user_uuid AND send_date >= :date_st AND send_date <= :date_ed'
			. ' ORDER BY send_date DESC, send_uuid DESC';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute(array(
			'user_uuid' => $userUuid,
			'date_st' => $dateSt . ' 00:00:00',
			'date_ed' => $dateEd . ' 23:59:59',
		));
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
