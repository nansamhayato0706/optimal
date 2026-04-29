<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\SessionStore;
use App\Views\View;

final class ErrorIndexController
{
    private $session;
    private $view;

    public function __construct(SessionStore $session, View $view)
    {
        $this->session = $session;
        $this->view    = $view;
    }

    public function handle(): void
    {
        $this->session->clear();
        $this->view->render('error/index', ['title' => 'エラー']);
    }
}
