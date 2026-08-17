<?php

declare(strict_types=1);

namespace App\Support;

final class AppConfig
{
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $appUrl;
    private $groupLabel;
    private $statusSummaryRebuildEnabled;
    private $inquiryMailEnabled;
    private $inquiryMailTo;
    private $inquiryMailFrom;
    private $slackWebhookUrl;
    private $azureSpeechKey;
    private $azureSpeechRegion;

    private function __construct(
        string $dbHost,
        string $dbName,
        string $dbUser,
        string $dbPass,
        string $appUrl,
        string $groupLabel,
        bool $statusSummaryRebuildEnabled,
        bool $inquiryMailEnabled,
        string $inquiryMailTo,
        string $inquiryMailFrom,
        string $slackWebhookUrl,
        string $azureSpeechKey,
        string $azureSpeechRegion
    ) {
        $this->dbHost  = $dbHost;
        $this->dbName  = $dbName;
        $this->dbUser  = $dbUser;
        $this->dbPass  = $dbPass;
        $this->appUrl  = rtrim($appUrl, '/');
        $this->groupLabel = $groupLabel;
        $this->statusSummaryRebuildEnabled = $statusSummaryRebuildEnabled;
        $this->inquiryMailEnabled = $inquiryMailEnabled;
        $this->inquiryMailTo      = $inquiryMailTo;
        $this->inquiryMailFrom    = $inquiryMailFrom;
        $this->slackWebhookUrl    = $slackWebhookUrl;
        $this->azureSpeechKey     = $azureSpeechKey;
        $this->azureSpeechRegion  = $azureSpeechRegion === '' ? 'japaneast' : $azureSpeechRegion;
    }

    public static function fromGlobals(): self
    {
        return new self(
            (string) (defined('DB_HOST') ? DB_HOST : 'localhost'),
            (string) (defined('DB_NAME') ? DB_NAME : 'jigyodan_zk_honbu'),
            (string) (defined('DB_USER') ? DB_USER : 'root'),
            (string) (defined('DB_PASS') ? DB_PASS : ''),
            (string) (defined('APP_URL') ? APP_URL : ''),
            (string) (defined('GROUP_LABEL') ? GROUP_LABEL : '組織'),
            (bool)   (defined('STATUS_SUMMARY_REBUILD_ENABLED') ? STATUS_SUMMARY_REBUILD_ENABLED : false),
            (bool)   (defined('INQUIRY_MAIL_ENABLED') ? INQUIRY_MAIL_ENABLED : false),
            (string) (defined('INQUIRY_MAIL_TO')      ? INQUIRY_MAIL_TO      : 'info-b@jigyodan.or.jp'),
            (string) (defined('INQUIRY_MAIL_FROM')    ? INQUIRY_MAIL_FROM    : 'info-b@jigyodan.or.jp'),
            (string) (defined('SLACK_WEBHOOK_URL')    ? SLACK_WEBHOOK_URL    : ''),
            (string) (defined('AZURE_SPEECH_KEY')     ? AZURE_SPEECH_KEY     : ''),
            (string) (defined('AZURE_SPEECH_REGION')  ? AZURE_SPEECH_REGION  : 'japaneast')
        );
    }

    public function dbHost(): string  { return $this->dbHost; }
    public function dbName(): string  { return $this->dbName; }
    public function dbUser(): string  { return $this->dbUser; }
    public function dbPass(): string  { return $this->dbPass; }
    public function appUrl(): string  { return $this->appUrl; }
    public function groupLabel(): string { return $this->groupLabel; }
    public function statusSummaryRebuildEnabled(): bool { return $this->statusSummaryRebuildEnabled; }
    public function inquiryMailEnabled(): bool { return $this->inquiryMailEnabled; }
    public function inquiryMailTo(): string    { return $this->inquiryMailTo; }
    public function inquiryMailFrom(): string  { return $this->inquiryMailFrom; }
    public function slackWebhookUrl(): string  { return $this->slackWebhookUrl; }
    public function azureSpeechKey(): string   { return $this->azureSpeechKey; }
    public function azureSpeechRegion(): string { return $this->azureSpeechRegion; }
    public function azureSpeechEnabled(): bool { return $this->azureSpeechKey !== ''; }
    public function rootPath(): string { return dirname(__DIR__, 2) . '/'; }
    public function siblingImageProjectDirs(): array { return array('zaitakukanri_honbu_new', 'zaitakukanri_honbu'); }
    public function siblingProjectRoot(string $dirName): string { return dirname(rtrim($this->rootPath(), '/')) . '/' . $dirName . '/'; }
    public function uploadDir(): string { return (string) (defined('UPLOAD_DIR') ? UPLOAD_DIR : $this->rootPath() . 'img/'); }
    public function imgBase(): string { return $this->appUrl . '/img/'; }
    public function dummyImage(): string { return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMjAiIGhlaWdodD0iMjQwIiB2aWV3Qm94PSIwIDAgMzIwIDI0MCIgcm9sZT0iaW1nIiBhcmlhLWxhYmVsPSJObyBpbWFnZSI+PHJlY3Qgd2lkdGg9IjMyMCIgaGVpZ2h0PSIyNDAiIGZpbGw9IiNmM2Y0ZjYiLz48cGF0aCBkPSJNNjQgMTc2bDU2LTY0IDQwIDQ4IDMyLTMyIDY0IDQ4IiBmaWxsPSJub25lIiBzdHJva2U9IiM5Y2EzYWYiIHN0cm9rZS13aWR0aD0iMTIiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz48Y2lyY2xlIGN4PSIyMTYiIGN5PSI4MCIgcj0iMjQiIGZpbGw9IiNjYmQ1ZTEiLz48dGV4dCB4PSIxNjAiIHk9IjIxMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmaWxsPSIjNmI3MjgwIj5ObyBpbWFnZTwvdGV4dD48L3N2Zz4='; }
    public function remoteImageBase(): string { return ''; }
    public function charset(): string { return 'UTF-8'; }
    public function csvCharset(): string { return 'sjis-win'; }
}
