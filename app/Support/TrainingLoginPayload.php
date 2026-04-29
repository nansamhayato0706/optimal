<?php

declare(strict_types=1);

namespace App\Support;

final class TrainingLoginPayload
{
    private $userId;
    private $userPassword;
    private $serialNo;
    private $volumeNo;

    private function __construct(string $userId, string $userPassword, string $serialNo, string $volumeNo)
    {
        $this->userId       = $userId;
        $this->userPassword = $userPassword;
        $this->serialNo     = $serialNo;
        $this->volumeNo     = $volumeNo;
    }

    public static function fromPost(array $post): self
    {
        return new self(
            trim((string) ($post['user_id']       ?? '')),
            trim((string) ($post['user_password'] ?? '')),
            trim((string) ($post['serial_no']     ?? '')),
            trim((string) ($post['volume_no']     ?? ''))
        );
    }

    public function userId(): string       { return $this->userId; }
    public function userPassword(): string { return $this->userPassword; }
    public function serialNo(): string     { return $this->serialNo; }
    public function volumeNo(): string     { return $this->volumeNo; }

    public function isComplete(): bool
    {
        return $this->userId !== ''
            && $this->userPassword !== ''
            && $this->serialNo !== ''
            && $this->volumeNo !== '';
    }
}
