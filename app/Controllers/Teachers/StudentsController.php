<?php

require_once __DIR__ . '/../../Models/Teachers/TeachersModel.php';

class StudentsController
{
    private PDO $pdo;
    private TeachersModel $teachersModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->teachersModel = new TeachersModel($pdo);
    }

    public function addStudent(array $studentData): array
    {
        return $this->teachersModel->addStudent($studentData);
    }

    public function getStudentsMasterlist(int $sectionId): array
    {
        return $this->teachersModel->getStudentsMasterlist($sectionId);
    }

}
?>