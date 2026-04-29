<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\UserAdminAuth;
use App\Services\ContactService;
use App\Support\RequestContext;
use App\Views\View;

final class ContactIndexController
{
    private $auth;
    private $contactService;
    private $request;
    private $view;

    public function __construct(UserAdminAuth $auth, ContactService $contactService, RequestContext $request, View $view)
    {
        $this->auth = $auth;
        $this->contactService = $contactService;
        $this->request = $request;
        $this->view = $view;
    }

    public function handle(): void
    {
        $this->auth->requireUserAdminRoute();

        $contactUuid = trim((string) $this->request->post('contact_uuid', $this->request->query('i', '')));
        if ($contactUuid === '') {
            $this->redirect('error.php');
        }

        $errors = [];
        if ($this->request->isPost()) {
            csrf_verify_or_abort($this->request);
            $result = $this->contactService->updateContact(
                $contactUuid,
                $this->request->allPost(),
                $this->auth->getLoginAdminUuid()
            );
            if ($result['status'] === 'ok') {
                $this->redirect('user.php');
            }
            if ($result['status'] === 'not_found') {
                $this->redirect('error.php');
            }
            if ($result['status'] === 'validation_failed') {
                $errors = $result['errors'];
            } else {
                $this->redirect('error.php');
            }
        }

        $pageData = $this->contactService->buildPageData($contactUuid, $this->auth->getLoginAdminUuid(), $errors);
        if ($pageData === null) {
            $this->redirect('error.php');
        }

        $this->view->render('contact/index', [
            'title' => '連絡確認',
            'headerLinks' => $this->auth->buildHeaderLinks(),
            'loginAdminId' => $this->auth->getLoginAdminId(),
            'contact' => $pageData['contact'],
            'divMap' => $pageData['divMap'],
            'imageUrl' => $pageData['imageUrl'],
            'errors' => $pageData['errors'],
        ]);
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
