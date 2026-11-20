<?php

require_once __DIR__ . '/../../Models/Admin/AdminModel.php';

class AdminStudentsController
{
    private AdminModel $adminModel;

    public function __construct(PDO $pdo)
    {
        $this->adminModel = new AdminModel($pdo);
    }

    public function getPageData(): array
    {
        return [
            'sections' => $this->adminModel->getSectionOptions(),
        ];
    }
}
