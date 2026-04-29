<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NoticeRepository;

final class NoticeService
{
    private $noticeRepository;

    public function __construct(NoticeRepository $noticeRepository)
    {
        $this->noticeRepository = $noticeRepository;
    }

    public function validate(string $notification): array
    {
        $errors = [];
        if (mb_strlen($notification) > 255) {
            $errors['notification'] = '255文字以内で入力してください。';
        }
        return $errors;
    }

    public function getPageData(string $groupUuid): ?array
    {
        return $this->noticeRepository->findNoticeByGroup($groupUuid);
    }

    public function update(string $groupUuid, string $notification, string $actorUuid): bool
    {
        return $this->noticeRepository->updateNotice($groupUuid, $notification, $actorUuid);
    }
}
