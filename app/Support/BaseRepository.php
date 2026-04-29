<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\AbstractRepository;
use PDO;

abstract class BaseRepository extends AbstractRepository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
