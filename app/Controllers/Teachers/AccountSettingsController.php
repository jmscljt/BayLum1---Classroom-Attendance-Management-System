<?php

require_once __DIR__ . '/TeachersController.php';

class AccountSettingsController extends TeachersController
{
    public function getPageData(array &$session, array $post, string $requestMethod): array
    {
        $authenticationRedirect = $this->requireAuthentication($session);
        if ($authenticationRedirect !== null) {
            header('Location: ' . $authenticationRedirect['redirect']);
            exit();
        }

        if ($requestMethod === 'POST') {
            $csrfToken = $post['csrf_token'] ?? '';
            $sessionToken = $session['csrf_token'] ?? '';
            if (
                !is_string($csrfToken)
                || !is_string($sessionToken)
                || $sessionToken === ''
                || !hash_equals($sessionToken, $csrfToken)
            ) {
                $session['account_form_result'] = [
                    'success' => false,
                    'message' => 'Your session expired. Refresh the page and try again.',
                ];
            } else {
                $action = $post['action'] ?? '';
                $teacherId = (int) $session['user_id'];

                if ($action === 'update_profile') {
                    $firstName = trim((string) ($post['first_name'] ?? ''));
                    $lastName = trim((string) ($post['last_name'] ?? ''));
                    $emailUsername = trim((string) ($post['email_username'] ?? ''));
                    $email = $emailUsername . '@gmail.com';
                    $sectionId = filter_var($post['section_id'] ?? null, FILTER_VALIDATE_INT);
                    if ($firstName === '' || $lastName === '' || $emailUsername === '' || $sectionId === false || $sectionId < 1) {
                        $session['account_form_result'] = [
                            'success' => false,
                            'message' => 'Please enter your first name, last name, and email, and select a valid grade and section.',
                        ];
                    } else {
                        $session['account_form_result'] = $this->updateTeacherProfile(
                            $teacherId,
                            $firstName,
                            $lastName,
                            $email,
                            (int) $sectionId
                        );
                        if (!empty($session['account_form_result']['success'])) {
                            $account = $session['account_form_result']['account'];
                            $session['user_name'] = $account['full_name'];
                            $session['user_email'] = $account['email'] ?? '';
                            $session['user_first_name'] = $account['first_name'] ?? '';
                            $session['user_last_name'] = $account['last_name'] ?? '';
                            $session['section_id'] = $account['section_id'] ?? null;
                            $session['assigned_grade_level'] = $account['assigned_grade_level'] ?? '';
                            $session['advisory_section'] = $account['advisory_section'] ?? '';
                        }
                    }
                } elseif ($action === 'update_password') {
                    $session['account_form_result'] = $this->updateTeacherPassword(
                        $teacherId,
                        (string) ($post['current_password'] ?? ''),
                        (string) ($post['new_password'] ?? ''),
                        (string) ($post['confirm_password'] ?? '')
                    );
                } else {
                    $session['account_form_result'] = [
                        'success' => false,
                        'message' => 'Unknown account action.',
                    ];
                }
            }

            header('Location: index.php?page=accountSettings');
            exit();
        }

        if (empty($session['csrf_token'])) {
            $session['csrf_token'] = bin2hex(random_bytes(32));
        }

        $account = $this->getTeacherAccount((int) $session['user_id']);
        if ($account) {
            $session['user_name'] = $account['full_name'];
            $session['user_unique_id'] = $account['user_id'];
            $session['user_email'] = $account['email'] ?? '';
            $session['user_first_name'] = $account['first_name'] ?? '';
            $session['user_last_name'] = $account['last_name'] ?? '';
            $session['section_id'] = $account['section_id'];
            $session['assigned_grade_level'] = $account['assigned_grade_level'] ?? '';
            $session['advisory_section'] = $account['advisory_section'] ?? '';
        }

        $result = $session['account_form_result'] ?? null;
        unset($session['account_form_result']);

        return [
            'account' => $account,
            'sections' => $this->getAvailableSections(),
            'csrfToken' => $session['csrf_token'],
            'result' => $result,
        ];
    }
}