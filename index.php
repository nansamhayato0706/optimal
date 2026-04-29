<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap/app.php';

app_routes()->dispatch(app_request());
