<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\BaseRepository;
use PDO;

final class ReportRepository extends BaseRepository
{
	public function findUserUuidByLoginToken(string $token): ?string
	{
		$stmt = $this->pdo->prepare(
			'SELECT user_uuid FROM mst_user WHERE login_uuid = :token AND delete_flg = 0 LIMIT 1'
		);
		$stmt->execute(array('token' => $token));
		$value = $stmt->fetchColumn();

		return $value === false ? null : (string) $value;
	}

	public function isUserManagedByAdmin(string $adminUuid, string $userUuid): bool
	{
		if ($adminUuid === '' || $userUuid === '') {
			return false;
		}

		$stmt = $this->pdo->prepare(
			'SELECT 1 FROM mst_user_admin WHERE admin_uuid = :admin_uuid AND user_uuid = :user_uuid LIMIT 1'
		);
		$stmt->execute(array(
			'admin_uuid' => $adminUuid,
			'user_uuid' => $userUuid,
		));

		return $stmt->fetchColumn() !== false;
	}

	public function findUserName(string $userUuid): string
	{
		if ($userUuid === '') {
			return '';
		}

		$stmt = $this->pdo->prepare(
			'SELECT user_name FROM mst_user WHERE user_uuid = :user_uuid LIMIT 1'
		);
		$stmt->execute(array('user_uuid' => $userUuid));
		$value = $stmt->fetchColumn();

		return $value === false ? '' : (string) $value;
	}

	public function findUserVisualImpairmentFlag(string $userUuid): int
	{
		if ($userUuid === '') {
			return 0;
		}

		$stmt = $this->pdo->prepare(
			'SELECT visual_impairment_flg FROM mst_user WHERE user_uuid = :user_uuid LIMIT 1'
		);
		$stmt->execute(array('user_uuid' => $userUuid));
		$value = $stmt->fetchColumn();

		return $value === false ? 0 : (int) $value;
	}

	public function findUserWorkStyleName(string $userUuid): string
	{
		if ($userUuid === '') {
			return '';
		}

		$stmt = $this->pdo->prepare(
			'SELECT d.div_name'
			. ' FROM mst_user u'
			. ' LEFT JOIN mst_div d ON d.div_parent_id = :div_parent_id AND d.div_id = u.work_style_div AND d.show_flg = 0'
			. ' WHERE u.user_uuid = :user_uuid'
			. ' LIMIT 1'
		);
		$stmt->execute(array(
			'div_parent_id' => 'work_style',
			'user_uuid' => $userUuid,
		));
		$value = $stmt->fetchColumn();

		return $value === false ? '' : (string) $value;
	}

	public function findReportById(string $reportUuid): ?array
	{
		if ($reportUuid === '') {
			return null;
		}

		$stmt = $this->pdo->prepare(
			'SELECT * FROM tbl_report WHERE report_uuid = :report_uuid AND delete_flg = 0 LIMIT 1'
		);
		$stmt->execute(array('report_uuid' => $reportUuid));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	public function findLatestReportByUserAndDate(string $userUuid, string $reportDate): ?array
	{
		if ($userUuid === '' || $reportDate === '') {
			return null;
		}

		$stmt = $this->pdo->prepare(
			'SELECT * FROM tbl_report'
			. ' WHERE user_uuid = :user_uuid AND report_date = :report_date AND delete_flg = 0'
			. ' ORDER BY report_uuid DESC LIMIT 1'
		);
		$stmt->execute(array(
			'user_uuid' => $userUuid,
			'report_date' => $reportDate,
		));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	public function countReportsByUserAndDate(string $userUuid, string $reportDate): int
	{
		if ($userUuid === '' || $reportDate === '') {
			return 0;
		}

		$stmt = $this->pdo->prepare(
			'SELECT COUNT(*) FROM tbl_report WHERE user_uuid = :user_uuid AND report_date = :report_date AND delete_flg = 0'
		);
		$stmt->execute(array(
			'user_uuid' => $userUuid,
			'report_date' => $reportDate,
		));

		return (int) $stmt->fetchColumn();
	}

	public function saveReportDraft(array $report, array $objectiveIds, array $outingIds): bool
	{
		$this->pdo->beginTransaction();
		try {
			if (($report['report_uuid'] ?? '') === '') {
				$report['report_uuid'] = $this->generateUuid();
				$this->insertReport($report);
			} else {
				$this->updateReport($report);
			}

			$actorUuid = (string) ($report['actor_uuid'] ?? ($report['user_uuid'] ?? ''));
			$this->replaceRelationValues('tbl_report_objective', 'objective_uuid', 'objective_div', (string) $report['report_uuid'], $objectiveIds, $actorUuid);
			$this->replaceRelationValues('tbl_report_outing', 'outing_uuid', 'outing_div', (string) $report['report_uuid'], $outingIds, $actorUuid);
			$this->pdo->commit();
			return true;
		} catch (\Throwable $e) {
			$this->pdo->rollBack();
			return false;
		}
	}

	public function saveAdminComment(string $reportUuid, string $adminUuid, string $reply, string $chargeComment, string $updateUuid): bool
	{
		$stmt = $this->pdo->prepare(
			'UPDATE tbl_report'
			. ' SET admin_uuid = :admin_uuid, reply = :reply, charge_comment = :charge_comment, update_date = NOW(), update_uuid = :update_uuid'
			. ' WHERE report_uuid = :report_uuid'
		);

		return $stmt->execute(array(
			'admin_uuid' => $adminUuid,
			'reply' => $reply,
			'charge_comment' => $chargeComment,
			'update_uuid' => $updateUuid,
			'report_uuid' => $reportUuid,
		));
	}

	public function insertSendLog(string $insertUuid): void
	{
		$stmt = $this->pdo->prepare(
			'INSERT INTO tbl_send (send_uuid, send_div, send_date, hook_div, insert_date, insert_uuid)'
			. ' VALUES (:send_uuid, 9, :send_date, 0, :insert_date, :insert_uuid)'
		);
		$now = date('Y-m-d H:i:s');
		$stmt->execute(array(
			'send_uuid' => $this->generateUuid(),
			'send_date' => $now,
			'insert_date' => $now,
			'insert_uuid' => $insertUuid,
		));
	}

	public function findReports(string $userUuid, ?string $dateStart, ?string $dateEnd): array
	{
		if ($userUuid === '') {
			return array();
		}

		$sql = 'SELECT report_uuid, report_date, training_start_time, training_end_time, admin_uuid, remark, reply, charge_comment'
			 . ' FROM tbl_report'
			 . ' WHERE delete_flg = 0 AND user_uuid = :user_uuid';
		$params = array('user_uuid' => $userUuid);

		if ($dateStart !== null && $dateStart !== '') {
			$sql .= ' AND report_date >= :date_start';
			$params['date_start'] = $dateStart . ' 00:00:00';
		}
		if ($dateEnd !== null && $dateEnd !== '') {
			$sql .= ' AND report_date <= :date_end';
			$params['date_end'] = $dateEnd . ' 23:59:59';
		}

		$sql .= ' ORDER BY report_date DESC';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findReportsForCsv(string $userUuid, ?string $dateStart, ?string $dateEnd): array
	{
		if ($userUuid === '') {
			return array();
		}

		$sql = 'SELECT report_uuid, report_date, retiring_time, rising_time, mood_div, condition_div,'
			 . ' medicine_div, medicine_reason, talk_div, training_am_1, training_am_2, training_am_3,'
			 . ' training_pm_1, training_pm_2, training_pm_3, training_start_time, training_end_time,'
			 . ' lunch_time, break_time, rethink_am, achieve_am, fatigue_am, rethink_pm, achieve_pm,'
			 . ' fatigue_pm, remark, charge_comment'
			 . ' FROM tbl_report'
			 . ' WHERE delete_flg = 0 AND user_uuid = :user_uuid';
		$params = array('user_uuid' => $userUuid);

		if ($dateStart !== null && $dateStart !== '') {
			$sql .= ' AND report_date >= :date_start';
			$params['date_start'] = $dateStart . ' 00:00:00';
		}
		if ($dateEnd !== null && $dateEnd !== '') {
			$sql .= ' AND report_date <= :date_end';
			$params['date_end'] = $dateEnd . ' 23:59:59';
		}

		$sql .= ' ORDER BY report_date DESC';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findObjectiveMap(array $reportUuids): array
	{
		return $this->findRelationMap('tbl_report_objective', 'objective_div', $reportUuids);
	}

	public function findOutingMap(array $reportUuids): array
	{
		return $this->findRelationMap('tbl_report_outing', 'outing_div', $reportUuids);
	}

	public function findObjectiveValues(string $reportUuid): array
	{
		return $this->findRelationValues('tbl_report_objective', 'objective_div', $reportUuid);
	}

	public function findOutingValues(string $reportUuid): array
	{
		return $this->findRelationValues('tbl_report_outing', 'outing_div', $reportUuid);
	}

	private function findRelationMap(string $tableName, string $columnName, array $reportUuids): array
	{
		if ($reportUuids === array()) {
			return array();
		}

		$placeholders = array();
		$params = array();
		foreach (array_values($reportUuids) as $index => $reportUuid) {
			$key = 'report_uuid_' . $index;
			$placeholders[] = ':' . $key;
			$params[$key] = $reportUuid;
		}

		$sql = sprintf(
			'SELECT report_uuid, %s FROM %s WHERE report_uuid IN (%s) ORDER BY report_uuid',
			$columnName,
			$tableName,
			implode(', ', $placeholders)
		);
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($params);

		$result = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			$result[(string) $row['report_uuid']][] = (int) $row[$columnName];
		}

		return $result;
	}

	private function findRelationValues(string $tableName, string $columnName, string $reportUuid): array
	{
		if ($reportUuid === '') {
			return array();
		}

		$stmt = $this->pdo->prepare(
			sprintf('SELECT %s FROM %s WHERE report_uuid = :report_uuid', $columnName, $tableName)
		);
		$stmt->execute(array('report_uuid' => $reportUuid));

		return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
	}

	private function insertReport(array $report): void
	{
		$sql = 'INSERT INTO tbl_report ('
			 . ' report_uuid, user_uuid, report_date, retiring_time, rising_time, mood_div, condition_div, medicine_div, medicine_reason,'
			 . ' talk_div, training_am_1, training_am_2, training_am_3, training_pm_1, training_pm_2, training_pm_3, training_start_time,'
			 . ' training_end_time, lunch_time, break_time, rethink_am, achieve_am, fatigue_am, rethink_pm, achieve_pm, fatigue_pm,'
			 . ' admin_uuid, reply, charge_comment, consent_flg, remark, delete_flg, insert_date, insert_uuid, update_date, update_uuid'
			 . ' ) VALUES ('
			 . ' :report_uuid, :user_uuid, :report_date, :retiring_time, :rising_time, :mood_div, :condition_div, :medicine_div, :medicine_reason,'
			 . ' :talk_div, :training_am_1, :training_am_2, :training_am_3, :training_pm_1, :training_pm_2, :training_pm_3, :training_start_time,'
			 . ' :training_end_time, :lunch_time, :break_time, :rethink_am, :achieve_am, :fatigue_am, :rethink_pm, :achieve_pm, :fatigue_pm,'
			 . ' :admin_uuid, :reply, :charge_comment, :consent_flg, :remark, 0, NOW(), :insert_uuid, NOW(), :update_uuid'
			 . ' )';
		$stmt = $this->pdo->prepare($sql);
		$params = $this->normalizeReportParams($report);
		unset($params['actor_uuid']);
		$stmt->execute($params);
	}

	private function updateReport(array $report): void
	{
		$sql = 'UPDATE tbl_report SET'
			 . ' user_uuid = :user_uuid, report_date = :report_date, retiring_time = :retiring_time, rising_time = :rising_time,'
			 . ' mood_div = :mood_div, condition_div = :condition_div, medicine_div = :medicine_div, medicine_reason = :medicine_reason,'
			 . ' talk_div = :talk_div, training_am_1 = :training_am_1, training_am_2 = :training_am_2, training_am_3 = :training_am_3,'
			 . ' training_pm_1 = :training_pm_1, training_pm_2 = :training_pm_2, training_pm_3 = :training_pm_3,'
			 . ' training_start_time = :training_start_time, training_end_time = :training_end_time, lunch_time = :lunch_time, break_time = :break_time,'
			 . ' rethink_am = :rethink_am, achieve_am = :achieve_am, fatigue_am = :fatigue_am, rethink_pm = :rethink_pm,'
			 . ' achieve_pm = :achieve_pm, fatigue_pm = :fatigue_pm, admin_uuid = :admin_uuid, reply = :reply, charge_comment = :charge_comment,'
			 . ' consent_flg = :consent_flg, remark = :remark, update_date = NOW(), update_uuid = :actor_uuid'
			 . ' WHERE report_uuid = :report_uuid';
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute($this->normalizeReportParams($report));
	}

	private function normalizeReportParams(array $report): array
	{
		return array(
			'report_uuid' => (string) ($report['report_uuid'] ?? ''),
			'user_uuid' => (string) ($report['user_uuid'] ?? ''),
			'report_date' => (string) ($report['report_date'] ?? ''),
			'retiring_time' => (string) ($report['retiring_time'] ?? ''),
			'rising_time' => (string) ($report['rising_time'] ?? ''),
			'mood_div' => (int) ($report['mood_div'] ?? 0),
			'condition_div' => (int) ($report['condition_div'] ?? 0),
			'medicine_div' => (int) ($report['medicine_div'] ?? 0),
			'medicine_reason' => (string) ($report['medicine_reason'] ?? ''),
			'talk_div' => (int) ($report['talk_div'] ?? 0),
			'training_am_1' => (string) ($report['training_am_1'] ?? ''),
			'training_am_2' => (string) ($report['training_am_2'] ?? ''),
			'training_am_3' => (string) ($report['training_am_3'] ?? ''),
			'training_pm_1' => (string) ($report['training_pm_1'] ?? ''),
			'training_pm_2' => (string) ($report['training_pm_2'] ?? ''),
			'training_pm_3' => (string) ($report['training_pm_3'] ?? ''),
			'training_start_time' => (string) ($report['training_start_time'] ?? ''),
			'training_end_time' => (string) ($report['training_end_time'] ?? ''),
			'lunch_time' => (int) ($report['lunch_time'] ?? 0),
			'break_time' => (int) ($report['break_time'] ?? 0),
			'rethink_am' => (string) ($report['rethink_am'] ?? ''),
			'achieve_am' => (int) ($report['achieve_am'] ?? 0),
			'fatigue_am' => (int) ($report['fatigue_am'] ?? 0),
			'rethink_pm' => (string) ($report['rethink_pm'] ?? ''),
			'achieve_pm' => (int) ($report['achieve_pm'] ?? 0),
			'fatigue_pm' => (int) ($report['fatigue_pm'] ?? 0),
			'admin_uuid' => (string) ($report['admin_uuid'] ?? ''),
			'reply' => (string) ($report['reply'] ?? ''),
			'charge_comment' => (string) ($report['charge_comment'] ?? ''),
			'consent_flg' => ((string) ($report['consent_flg'] ?? '0')) === '1' ? 1 : 0,
			'remark' => (string) ($report['remark'] ?? ''),
			'actor_uuid' => (string) ($report['actor_uuid'] ?? ($report['user_uuid'] ?? '')),
			'insert_uuid' => (string) ($report['actor_uuid'] ?? ($report['user_uuid'] ?? '')),
			'update_uuid' => (string) ($report['actor_uuid'] ?? ($report['user_uuid'] ?? '')),
		);
	}

	private function replaceRelationValues(string $tableName, string $uuidColumn, string $valueColumn, string $reportUuid, array $values, string $actorUuid): void
	{
		$deleteStmt = $this->pdo->prepare(sprintf('DELETE FROM %s WHERE report_uuid = :report_uuid', $tableName));
		$deleteStmt->execute(array('report_uuid' => $reportUuid));

		if ($values === array()) {
			return;
		}

		$insertSql = sprintf(
			'INSERT INTO %s (%s, report_uuid, %s, insert_date, insert_uuid) VALUES (:%s, :report_uuid, :%s, NOW(), :insert_uuid)',
			$tableName,
			$uuidColumn,
			$valueColumn,
			$uuidColumn,
			$valueColumn
		);
		$insertStmt = $this->pdo->prepare($insertSql);
		foreach ($values as $value) {
			$insertStmt->execute(array(
				$uuidColumn => $this->generateUuid(),
				'report_uuid' => $reportUuid,
				$valueColumn => (int) $value,
				'insert_uuid' => $actorUuid,
			));
		}
	}

}
