<?php
// app/Controllers/Teachers/TeachersController.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

abstract class TeachersController extends TeachersModel
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    protected function isAuthenticated(array $session): bool
    {
        return !empty($session['user_logged_in']) && !empty($session['unique_id']);
    }

    protected function requireAuthentication(array $session): ?array
    {
        if (!$this->isAuthenticated($session)) {
            return ['redirect' => 'index.php?page=login'];
        }

        return null;
    }
}
