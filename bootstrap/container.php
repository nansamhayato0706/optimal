<?php

declare(strict_types=1);

use App\Auth\AdminAuth;
use App\Auth\GroupAuth;
use App\Auth\UserAdminAuth;
use App\Controllers\AdminCompleteController;
use App\Controllers\AzureSpeechTokenController;
use App\Controllers\AdminConfirmController;
use App\Controllers\AdminEditController;
use App\Controllers\AdminIndexController;
use App\Controllers\ChatIndexController;
use App\Controllers\ErrorIndexController;
use App\Controllers\FirstIndexController;
use App\Controllers\TrainingController;
use App\Controllers\ChatSendController;
use App\Controllers\ContactDetailController;
use App\Controllers\ContactIndexController;
use App\Controllers\ContactUpdateController;
use App\Controllers\GroupCompleteController;
use App\Controllers\GroupConfirmController;
use App\Controllers\GroupEditController;
use App\Controllers\GroupIndexController;
use App\Controllers\LoginIndexController;
use App\Controllers\LinkIndexController;
use App\Controllers\LogIndexController;
use App\Controllers\NoticeIndexController;
use App\Controllers\ReportCompleteController;
use App\Controllers\ReportConfirmController;
use App\Controllers\ReportDetailController;
use App\Controllers\ReportEditController;
use App\Controllers\ReportIndexController;
use App\Controllers\ReportPdfController;
use App\Controllers\UserIndexController;
use App\Controllers\UserCompleteController;
use App\Controllers\UserConfirmController;
use App\Controllers\UserEditController;
use App\Controllers\UserStatusController;
use App\Repositories\AdminRepository;
use App\Repositories\ChatRepository;
use App\Repositories\ContactRepository;
use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\FirstRepository;
use App\Repositories\GroupRepository;
use App\Repositories\LinkRepository;
use App\Repositories\LogRepository;
use App\Repositories\NoticeRepository;
use App\Repositories\ReportRepository;
use App\Repositories\TrainingRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserStatusSummaryRepository;
use App\Services\FirstService;
use App\Services\GroupFormService;
use App\Services\AdminFormService;
use App\Services\AzureSpeechTokenService;
use App\Services\ChatService;
use App\Services\ContactService;
use App\Services\LoginService;
use App\Services\LinkService;
use App\Services\LogService;
use App\Services\NoticeService;
use App\Services\ReportDetailService;
use App\Services\ReportFormService;
use App\Services\ReportListService;
use App\Services\TrainingService;
use App\Services\UserFormService;
use App\Services\UserListService;
use App\Support\TrainingImageStorage;
use App\Support\InquiryMailer;
use App\Support\InquirySlackNotifier;
use App\Support\AppConfig;
use App\Support\Container;
use App\Support\Database;
use App\Support\RequestContext;
use App\Support\SessionStore;
use App\Support\UrlHelper;
use App\Support\ReportAuth;
use App\Support\ReportPdfGenerator;
use App\Support\ViewRenderer;
use App\Views\View;

$container = new Container();

// --- Infrastructure ---
$container->singleton(AppConfig::class,     static function ()             { return AppConfig::fromGlobals(); });
$container->singleton(RequestContext::class, static function ()             { return RequestContext::fromGlobals(); });
$container->singleton(SessionStore::class,   static function ()             { return new SessionStore(); });
$container->singleton(UrlHelper::class,      static function (Container $c) { return new UrlHelper($c->get(AppConfig::class)); });
$container->singleton(View::class,           static function (Container $c) {
    return new View(dirname(__DIR__) . '/app/Views', $c->get(UrlHelper::class), $c->get(AppConfig::class));
});
$container->singleton(ViewRenderer::class, static function (Container $c) {
    return new ViewRenderer($c->get(UrlHelper::class), $c->get(AppConfig::class));
});
$container->singleton(ReportPdfGenerator::class, static function (Container $c) {
    return new ReportPdfGenerator($c->get(AppConfig::class));
});

// --- Repositories ---
$container->singleton(AdminRepositoryInterface::class, static function () { return new AdminRepository(Database::connection()); });
$container->singleton(GroupRepositoryInterface::class, static function () { return new GroupRepository(Database::connection()); });
$container->singleton(UserRepositoryInterface::class,  static function () { return new UserRepository(Database::connection()); });
$container->singleton(ReportRepository::class, static function () { return new ReportRepository(Database::connection()); });

// --- Services ---
$container->singleton(LoginService::class, static function (Container $c) {
    return new LoginService($c->get(AdminRepositoryInterface::class), $c->get(SessionStore::class));
});
$container->singleton(GroupFormService::class, static function (Container $c) {
    return new GroupFormService($c->get(GroupRepositoryInterface::class), $c->get(SessionStore::class));
});
$container->singleton(AdminFormService::class, static function (Container $c) {
    return new AdminFormService($c->get(AdminRepositoryInterface::class), $c->get(SessionStore::class));
});
$container->singleton(UserListService::class, static function (Container $c) {
    return new UserListService($c->get(UserRepositoryInterface::class), new UserStatusSummaryRepository(Database::connection()));
});
$container->singleton(UserFormService::class, static function (Container $c) {
    return new UserFormService($c->get(UserRepositoryInterface::class), $c->get(SessionStore::class));
});
$container->singleton(NoticeService::class, static function () {
    return new NoticeService(new NoticeRepository(Database::connection()));
});
$container->singleton(LinkService::class, static function () {
    return new LinkService(new LinkRepository(Database::connection()));
});
$container->singleton(LogService::class, static function (Container $c) {
    return new LogService(new LogRepository(Database::connection()), $c->get(AppConfig::class));
});
$container->singleton(ChatService::class, static function () {
    $pdo = Database::connection();
    return new ChatService(new ChatRepository($pdo), new UserStatusSummaryRepository($pdo));
});
$container->singleton(ReportListService::class, static function (Container $c) {
    return new ReportListService($c->get(ReportRepository::class), $c->get(AppConfig::class));
});
$container->singleton(ReportDetailService::class, static function (Container $c) {
    return new ReportDetailService($c->get(ReportRepository::class));
});
$container->singleton(ReportFormService::class, static function (Container $c) {
    return new ReportFormService(
        $c->get(ReportRepository::class),
        new UserStatusSummaryRepository(Database::connection()),
        $c->get(SessionStore::class),
        $c->get(AppConfig::class)
    );
});
$container->singleton(ContactService::class, static function (Container $c) {
    $pdo = Database::connection();
    return new ContactService(
        new ContactRepository($pdo),
        $c->get(UserRepositoryInterface::class),
        new UserStatusSummaryRepository($pdo),
        $c->get(AppConfig::class)
    );
});

// --- Auth ---
$container->singleton(AdminAuth::class, static function (Container $c) {
    return new AdminAuth($c->get(AdminRepositoryInterface::class), $c->get(AppConfig::class), $c->get(SessionStore::class));
});
$container->singleton(GroupAuth::class, static function (Container $c) {
    return new GroupAuth($c->get(AppConfig::class), $c->get(SessionStore::class));
});
$container->singleton(UserAdminAuth::class, static function (Container $c) {
    return new UserAdminAuth($c->get(UserRepositoryInterface::class), $c->get(AppConfig::class), $c->get(SessionStore::class));
});
$container->singleton(ReportAuth::class, static function (Container $c) {
    return new ReportAuth($c->get(ReportRepository::class), $c->get(AppConfig::class), $c->get(SessionStore::class), $c->get(RequestContext::class));
});

// --- Controllers ---
$container->bind(LoginIndexController::class, static function (Container $c) {
    return new LoginIndexController($c->get(AppConfig::class), $c->get(LoginService::class), $c->get(View::class), $c->get(RequestContext::class), $c->get(SessionStore::class));
});
$container->bind(GroupIndexController::class, static function (Container $c) {
    return new GroupIndexController($c->get(GroupAuth::class), $c->get(AppConfig::class), $c->get(GroupRepositoryInterface::class), $c->get(GroupFormService::class), $c->get(View::class));
});
$container->bind(GroupEditController::class, static function (Container $c) {
    return new GroupEditController($c->get(GroupAuth::class), $c->get(AppConfig::class), $c->get(GroupRepositoryInterface::class), $c->get(GroupFormService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(GroupConfirmController::class, static function (Container $c) {
    return new GroupConfirmController($c->get(GroupAuth::class), $c->get(AppConfig::class), $c->get(GroupRepositoryInterface::class), $c->get(GroupFormService::class), $c->get(View::class));
});
$container->bind(GroupCompleteController::class, static function (Container $c) {
    return new GroupCompleteController($c->get(GroupAuth::class), $c->get(AppConfig::class), $c->get(GroupRepositoryInterface::class), $c->get(GroupFormService::class), $c->get(View::class));
});
$container->bind(AdminIndexController::class, static function (Container $c) {
    return new AdminIndexController($c->get(AdminAuth::class), $c->get(AdminRepositoryInterface::class), $c->get(AdminFormService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(AdminEditController::class, static function (Container $c) {
    return new AdminEditController($c->get(AdminAuth::class), $c->get(AdminRepositoryInterface::class), $c->get(AdminFormService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(AdminConfirmController::class, static function (Container $c) {
    return new AdminConfirmController($c->get(AdminAuth::class), $c->get(AdminRepositoryInterface::class), $c->get(AdminFormService::class), $c->get(View::class));
});
$container->bind(AdminCompleteController::class, static function (Container $c) {
    return new AdminCompleteController($c->get(AdminAuth::class), $c->get(AdminRepositoryInterface::class), $c->get(AdminFormService::class), $c->get(View::class));
});
$container->bind(UserIndexController::class, static function (Container $c) {
    return new UserIndexController($c->get(UserAdminAuth::class), $c->get(AppConfig::class), $c->get(UserRepositoryInterface::class), $c->get(UserListService::class), $c->get(View::class), $c->get(RequestContext::class));
});
$container->bind(UserStatusController::class, static function (Container $c) {
    return new UserStatusController($c->get(UserAdminAuth::class), $c->get(UserRepositoryInterface::class), $c->get(UserListService::class), new UserStatusSummaryRepository(Database::connection()), $c->get(RequestContext::class));
});
$container->bind(UserEditController::class, static function (Container $c) {
    return new UserEditController($c->get(UserAdminAuth::class), $c->get(UserRepositoryInterface::class), $c->get(UserFormService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(UserConfirmController::class, static function (Container $c) {
    return new UserConfirmController($c->get(UserAdminAuth::class), $c->get(UserRepositoryInterface::class), $c->get(UserFormService::class), $c->get(View::class));
});
$container->bind(UserCompleteController::class, static function (Container $c) {
    return new UserCompleteController($c->get(UserAdminAuth::class), $c->get(UserRepositoryInterface::class), $c->get(UserFormService::class), $c->get(View::class));
});
$container->bind(NoticeIndexController::class, static function (Container $c) {
    return new NoticeIndexController($c->get(AdminAuth::class), $c->get(NoticeService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(LinkIndexController::class, static function (Container $c) {
    return new LinkIndexController($c->get(AdminAuth::class), $c->get(LinkService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(ChatIndexController::class, static function (Container $c) {
    return new ChatIndexController($c->get(UserAdminAuth::class), $c->get(ChatService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(ChatSendController::class, static function (Container $c) {
    return new ChatSendController($c->get(UserAdminAuth::class), $c->get(ChatService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(ContactIndexController::class, static function (Container $c) {
    return new ContactIndexController($c->get(UserAdminAuth::class), $c->get(ContactService::class), $c->get(RequestContext::class), $c->get(View::class));
});
$container->bind(ContactDetailController::class, static function (Container $c) {
    return new ContactDetailController($c->get(UserAdminAuth::class), $c->get(ContactService::class), $c->get(RequestContext::class));
});
$container->bind(ContactUpdateController::class, static function (Container $c) {
    return new ContactUpdateController($c->get(UserAdminAuth::class), $c->get(ContactService::class), $c->get(RequestContext::class));
});
$container->bind(LogIndexController::class, static function (Container $c) {
    return new LogIndexController($c->get(UserAdminAuth::class), $c->get(LogService::class), $c->get(RequestContext::class), $c->get(ViewRenderer::class));
});
$container->bind(ReportIndexController::class, static function (Container $c) {
    return new ReportIndexController($c->get(ReportAuth::class), $c->get(ReportListService::class), $c->get(ViewRenderer::class), $c->get(RequestContext::class));
});
$container->bind(ReportDetailController::class, static function (Container $c) {
    return new ReportDetailController($c->get(ReportAuth::class), $c->get(ReportDetailService::class), $c->get(ReportRepository::class), $c->get(ViewRenderer::class), $c->get(RequestContext::class));
});
$container->bind(ReportPdfController::class, static function (Container $c) {
    return new ReportPdfController($c->get(ReportAuth::class), $c->get(ReportDetailService::class), $c->get(ReportRepository::class), $c->get(ReportPdfGenerator::class), $c->get(RequestContext::class));
});
$container->bind(ReportEditController::class, static function (Container $c) {
    return new ReportEditController($c->get(ReportAuth::class), $c->get(ReportFormService::class), $c->get(ReportRepository::class), $c->get(ViewRenderer::class), $c->get(RequestContext::class));
});
$container->bind(ReportConfirmController::class, static function (Container $c) {
    return new ReportConfirmController($c->get(ReportAuth::class), $c->get(ReportFormService::class), $c->get(ReportRepository::class), $c->get(ViewRenderer::class));
});
$container->bind(ReportCompleteController::class, static function (Container $c) {
    return new ReportCompleteController($c->get(ReportAuth::class), $c->get(ReportFormService::class), $c->get(ViewRenderer::class), $c->get(RequestContext::class));
});

// --- WPF連携（Training / First） ---
$container->singleton(InquiryMailer::class, static function (Container $c) {
    return new InquiryMailer($c->get(AppConfig::class));
});
$container->singleton(InquirySlackNotifier::class, static function (Container $c) {
    return new InquirySlackNotifier($c->get(AppConfig::class));
});
$container->singleton(TrainingService::class, static function (Container $c) {
    $pdo = Database::connection();
    return new TrainingService(
        new TrainingRepository($pdo),
        new UserStatusSummaryRepository($pdo),
        new TrainingImageStorage($c->get(AppConfig::class)),
        $c->get(InquiryMailer::class),
        $c->get(InquirySlackNotifier::class)
    );
});
$container->singleton(FirstService::class, static function () {
    return new FirstService(new FirstRepository(Database::connection()));
});
$container->bind(TrainingController::class, static function (Container $c) {
    return new TrainingController($c->get(TrainingService::class), $c->get(RequestContext::class));
});
$container->bind(FirstIndexController::class, static function (Container $c) {
    return new FirstIndexController($c->get(FirstService::class), $c->get(RequestContext::class));
});
$container->singleton(AzureSpeechTokenService::class, static function (Container $c) {
    return new AzureSpeechTokenService($c->get(AppConfig::class));
});
$container->bind(AzureSpeechTokenController::class, static function (Container $c) {
    return new AzureSpeechTokenController(
        $c->get(AzureSpeechTokenService::class),
        new TrainingRepository(Database::connection()),
        $c->get(RequestContext::class)
    );
});
$container->bind(ErrorIndexController::class, static function (Container $c) {
    return new ErrorIndexController($c->get(SessionStore::class), $c->get(View::class));
});

return $container;
