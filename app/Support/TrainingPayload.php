<?php

declare(strict_types=1);

namespace App\Support;

final class TrainingPayload
{
    private $token;
    private $hookValue;
    private $chatText;

    private function __construct(string $token, string $hookValue, string $chatText)
    {
        $this->token     = $token;
        $this->hookValue = $hookValue;
        $this->chatText  = $chatText;
    }

    public static function fromEventPost(array $post): self
    {
        return new self(
            trim((string) ($post['value']  ?? '')),
            trim((string) ($post['value2'] ?? '')),
            trim((string) ($post['value3'] ?? ''))
        );
    }

    public function token(): string { return $this->token; }
    public function chatText(): string { return $this->chatText; }

    public function hookDiv(): int
    {
        $hookDiv = (int) $this->hookValue;
        return ($hookDiv === 1 || $hookDiv === 2) ? $hookDiv : 0;
    }
}
