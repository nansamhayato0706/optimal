<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ContactRepository;
use App\Repositories\UserStatusSummaryRepository;
use App\Repositories\UserRepository;
use App\Support\AppConfig;
use App\Support\ContactImageUrl;

final class ContactService
{
	private $config;
	private $contactRepository;
	private $userStatusSummaryRepository;
	private $userRepository;

	public function __construct(ContactRepository $contactRepository, UserRepository $userRepository, UserStatusSummaryRepository $userStatusSummaryRepository, AppConfig $config)
	{
		$this->config = $config;
		$this->contactRepository = $contactRepository;
		$this->userStatusSummaryRepository = $userStatusSummaryRepository;
		$this->userRepository = $userRepository;
	}

	public function buildPageData(string $contactUuid, string $adminUuid, array $errors = array()): ?array
	{
		$contact = $this->contactRepository->findContactByUuid($contactUuid);
		if (!$this->canAccessContact($contact, $adminUuid)) {
			return null;
		}

		return array(
			'contact' => $contact,
			'divMap' => $this->contactRepository->findDivMap(),
			'imageUrl' => $this->buildImageUrl($contact),
			'errors' => $errors,
		);
	}

	public function buildDetailPayload(string $contactUuid, string $adminUuid): ?array
	{
		$contact = $this->contactRepository->findContactByUuid($contactUuid);
		if (!$this->canAccessContact($contact, $adminUuid)) {
			return null;
		}

		$divMap = $this->contactRepository->findDivMap();
		return array(
			'contact_uuid' => (string) $contact['contact_uuid'],
			'contact_date' => (string) $contact['contact_date'],
			'contact_date_label' => $this->formatDateTime((string) $contact['contact_date'], 'Y/m/d H:i:s'),
			'contact_div' => (int) $contact['contact_div'],
			'contact_label' => (string) ($divMap['contact'][(int) $contact['contact_div']] ?? ''),
			'confirm_div' => (int) $contact['confirm_div'],
			'confirm_options' => $divMap['confirm'] ?? array(),
			'comment' => (string) ($contact['comment'] ?? ''),
			'image_url' => $this->buildImageUrl($contact),
		);
	}

	public function updateContact(string $contactUuid, array $input, string $actorUuid): array
	{
		$contact = $this->contactRepository->findContactByUuid($contactUuid);
		if (!$this->canAccessContact($contact, $actorUuid)) {
			return array('status' => 'not_found');
		}

		$updated = $contact;
		$errors = array();

		$confirmDiv = trim((string) ($input['confirm_div'] ?? ''));
		if ($confirmDiv === '') {
			$errors['confirm_div'] = '確認状況を選択してください。';
		} elseif (!ctype_digit($confirmDiv)) {
			$errors['confirm_div'] = '確認状況が不正です。';
		} else {
			$updated['confirm_div'] = (int) $confirmDiv;
			if ((string) $updated['confirm_div'] !== (string) $contact['confirm_div']) {
				$updated['confirm_date'] = date('Y-m-d H:i:s');
			}
		}

		$comment = trim((string) ($input['comment'] ?? ''));
		if (mb_strlen($comment) > 255) {
			$errors['comment'] = 'コメントは255文字以内で入力してください。';
		} else {
			$updated['comment'] = $comment;
			if ($comment !== (string) ($contact['comment'] ?? '')) {
				$updated['comment_date'] = date('Y-m-d H:i:s');
				$updated['comment_admin_uuid'] = $actorUuid;
			}
		}

		if ($errors !== array()) {
			return array(
				'status' => 'validation_failed',
				'contact' => $updated,
				'errors' => $errors,
			);
		}

		if (!$this->contactRepository->updateContact($updated, $actorUuid)) {
			return array('status' => 'update_failed');
		}

		$this->userStatusSummaryRepository->refreshUserStatusSummary((string) $updated['insert_uuid']);

		return array(
			'status' => 'ok',
			'contact' => $updated,
			'cell' => $this->buildContactCell($updated),
		);
	}

	public function buildContactCell(array $contact): array
	{
		$divMap = $this->contactRepository->findDivMap();
		$confirmDiv = (int) ($contact['confirm_div'] ?? 0);
		$contactDiv = (int) ($contact['contact_div'] ?? 0);
		$dateStr = $this->formatDateTime((string) ($contact['contact_date'] ?? ''), 'y/m/d H:i');
		$contactLabel = htmlspecialchars((string) ($divMap['contact'][$contactDiv] ?? ''), ENT_QUOTES, 'UTF-8');
		$html = htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . ' ' . $contactLabel;
		if ($confirmDiv === 1 || $confirmDiv === 2) {
			$html .= ' <a class="h_link user-status-link" href="contact.php?i='
				. htmlspecialchars((string) ($contact['contact_uuid'] ?? ''), ENT_QUOTES, 'UTF-8')
				. '">確認</a>';
			return array(
				'contact_class' => 'user_contact_' . $contactDiv,
				'contact_html' => $html,
			);
		}

		$html .= ' ' . htmlspecialchars((string) ($divMap['confirm'][$confirmDiv] ?? ''), ENT_QUOTES, 'UTF-8');
		return array(
			'contact_class' => 'user_contact_f_' . $contactDiv,
			'contact_html' => $html,
		);
	}

	private function buildImageUrl(array $contact): string
	{
		return ContactImageUrl::resolve($contact, $this->config);
	}

	private function formatDateTime(string $value, string $format): string
	{
		if ($value === '') {
			return '';
		}
		$ts = strtotime($value);
		return $ts === false ? $value : date($format, $ts);
	}

	private function canAccessContact(?array $contact, string $adminUuid): bool
	{
		if ($contact === null || $adminUuid === '') {
			return false;
		}

		return $this->userRepository->userAssignedToAdmin((string) $contact['insert_uuid'], $adminUuid);
	}
}
