<?php

declare(strict_types=1);

use App\Controllers\AdminCompleteController;
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
use App\Support\RouteRegistry;

$routes = new RouteRegistry();

// --- 認証 ---
$routes->add(['GET', 'POST'], '/',          static function (): void { app_container()->get(LoginIndexController::class)->handle(); });
$routes->add(['GET', 'POST'], '/login.php', static function (): void { app_container()->get(LoginIndexController::class)->handle(); });

// --- グループ管理（admin_div=1） ---
$routes->add(['GET'],         '/group.php',          static function (): void { app_container()->get(GroupIndexController::class)->handle(); });
$routes->add(['GET', 'POST'], '/group_edit.php',     static function (): void { app_container()->get(GroupEditController::class)->handle(); });
$routes->add(['GET'],         '/group_confirm.php',  static function (): void { app_container()->get(GroupConfirmController::class)->handle(); });
$routes->add(['GET'],         '/group_complete.php', static function (): void { app_container()->get(GroupCompleteController::class)->handle(); });

// --- 管理者管理（admin_div=1,2） ---
$routes->add(['GET'],         '/admin.php',          static function (): void { app_container()->get(AdminIndexController::class)->handle(); });
$routes->add(['GET', 'POST'], '/admin_edit.php',     static function (): void { app_container()->get(AdminEditController::class)->handle(); });
$routes->add(['GET'],         '/admin_confirm.php',  static function (): void { app_container()->get(AdminConfirmController::class)->handle(); });
$routes->add(['GET'],         '/admin_complete.php', static function (): void { app_container()->get(AdminCompleteController::class)->handle(); });
$routes->add(['GET', 'POST'], '/notice.php',         static function (): void { app_container()->get(NoticeIndexController::class)->handle(); });
$routes->add(['GET', 'POST'], '/link.php',           static function (): void { app_container()->get(LinkIndexController::class)->handle(); });

// --- ユーザー管理（admin_div=3以上） ---
$routes->add(['GET', 'POST'], '/user.php', static function (): void { app_container()->get(UserIndexController::class)->handle(); });
$routes->add(['GET'], '/user_status.php', static function (): void { app_container()->get(UserStatusController::class)->handle(); });
$routes->add(['GET', 'POST'], '/user_edit.php', static function (): void { app_container()->get(UserEditController::class)->handle(); });
$routes->add(['GET'], '/user_confirm.php', static function (): void { app_container()->get(UserConfirmController::class)->handle(); });
$routes->add(['GET'], '/user_complete.php', static function (): void { app_container()->get(UserCompleteController::class)->handle(); });
$routes->add(['GET', 'POST'], '/chat.php', static function (): void { app_container()->get(ChatIndexController::class)->handle(); });
$routes->add(['POST'], '/chat_send.php', static function (): void { app_container()->get(ChatSendController::class)->handle(); });
$routes->add(['GET', 'POST'], '/contact.php', static function (): void { app_container()->get(ContactIndexController::class)->handle(); });
$routes->add(['GET'], '/contact_detail.php', static function (): void { app_container()->get(ContactDetailController::class)->handle(); });
$routes->add(['POST'], '/contact_update.php', static function (): void { app_container()->get(ContactUpdateController::class)->handle(); });
$routes->add(['GET', 'POST'], '/log.php', static function (): void { app_container()->get(LogIndexController::class)->handle(); });
$routes->add(['GET', 'POST'], '/report.php', static function (): void { app_container()->get(ReportIndexController::class)->handle(); });
$routes->add(['GET', 'POST'], '/report_edit.php', static function (): void { app_container()->get(ReportEditController::class)->handle(); });
$routes->add(['GET'], '/report_confirm.php', static function (): void { app_container()->get(ReportConfirmController::class)->handle(); });
$routes->add(['GET', 'POST'], '/report_complete.php', static function (): void { app_container()->get(ReportCompleteController::class)->handle(); });
$routes->add(['GET'], '/report_detail.php', static function (): void { app_container()->get(ReportDetailController::class)->handle(); });
$routes->add(['GET'], '/report_pdf.php', static function (): void { app_container()->get(ReportPdfController::class)->handle(); });

// --- WPF連携（Training） ---
$routes->add(['POST'], '/training_login.php',    static function (): void { app_container()->get(TrainingController::class)->login(); });
$routes->add(['POST'], '/training_logout.php',   static function (): void { app_container()->get(TrainingController::class)->handleEvent(6); });
$routes->add(['POST'], '/training_start.php',    static function (): void { app_container()->get(TrainingController::class)->handleEvent(1); });
$routes->add(['POST'], '/training_end.php',      static function (): void { app_container()->get(TrainingController::class)->handleEvent(2); });
$routes->add(['POST'], '/training_inquiry.php',  static function (): void { app_container()->get(TrainingController::class)->handleEvent(3); });
$routes->add(['POST'], '/training_middle.php',   static function (): void { app_container()->get(TrainingController::class)->handleEvent(4); });
$routes->add(['POST'], '/training_keyboard.php', static function (): void { app_container()->get(TrainingController::class)->handleEvent(7); });
$routes->add(['POST'], '/training_mouse.php',    static function (): void { app_container()->get(TrainingController::class)->handleEvent(8); });
$routes->add(['POST'], '/training_insert.php',   static function (): void { app_container()->get(TrainingController::class)->handleEvent(11); });
$routes->add(['POST'], '/training_update.php',   static function (): void { app_container()->get(TrainingController::class)->handleEvent(12); });

// --- WPF初期データ ---
$routes->add(['POST'], '/first.php', static function (): void { app_container()->get(FirstIndexController::class)->handle(); });

// --- エラーページ ---
$routes->add(['GET', 'POST'], '/error.php', static function (): void { app_container()->get(ErrorIndexController::class)->handle(); });

// 未定義ルートは 404
$routes->fallback(static function (): void {
    http_response_code(404);
    echo 'Not Found';
});

return $routes;
