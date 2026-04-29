<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LinkRepository;

final class LinkService
{
    private $linkRepository;

    public function __construct(LinkRepository $linkRepository)
    {
        $this->linkRepository = $linkRepository;
    }

    public function getPageData(string $groupUuid): array
    {
        return $this->linkRepository->findLinksByGroup($groupUuid);
    }

    public function normalizeRows(array $input): array
    {
        $linkUrls = $input['link_url'] ?? [];
        $linkNames = $input['link_name'] ?? [];
        $sorts = $input['sort'] ?? [];

        $rows = [];
        $count = max(count($linkUrls), count($linkNames), count($sorts));
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'link_url' => trim((string) ($linkUrls[$i] ?? '')),
                'link_name' => trim((string) ($linkNames[$i] ?? '')),
                'sort' => trim((string) ($sorts[$i] ?? '')),
            ];
        }

        return $rows;
    }

    public function validateRows(array $rows): array
    {
        $errors = [];
        foreach ($rows as $index => $row) {
            $isBlank = $row['link_url'] === '' && $row['link_name'] === '' && $row['sort'] === '';
            if ($isBlank) {
                continue;
            }
            if ($row['link_url'] === '') {
                $errors['link_url'][$index] = 'URLは必須です。';
            } elseif (mb_strlen($row['link_url']) > 255) {
                $errors['link_url'][$index] = '255文字以内で入力してください。';
            } elseif (!$this->hasAllowedScheme($row['link_url'])) {
                $errors['link_url'][$index] = 'URLはhttpまたはhttpsで入力してください。';
            }

            if ($row['link_name'] === '') {
                $errors['link_name'][$index] = 'リンク名は必須です。';
            } elseif (mb_strlen($row['link_name']) > 255) {
                $errors['link_name'][$index] = '255文字以内で入力してください。';
            }

            if ($row['sort'] === '') {
                $errors['sort'][$index] = '並び順は必須です。';
            } elseif (!ctype_digit($row['sort'])) {
                $errors['sort'][$index] = '並び順は数字で入力してください。';
            }
        }

        return $errors;
    }

    public function save(string $groupUuid, array $rows, string $actorUuid): bool
    {
        $payload = array_values(array_filter($rows, static function (array $row): bool {
            return !($row['link_url'] === '' && $row['link_name'] === '' && $row['sort'] === '');
        }));
        return $this->linkRepository->replaceLinks($groupUuid, $payload, $actorUuid);
    }

    private function hasAllowedScheme(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
}
