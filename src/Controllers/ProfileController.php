<?php
namespace App\Controllers;

use App\Core\{Auth, BaseController};
use App\Services\UserService;

class ProfileController extends BaseController {
    private UserService $service;

    public function __construct() {
        $this->service = new UserService();
    }

    public function show(): void {
        $user = $this->service->getById(Auth::user()['id']);
        require __DIR__ . '/../../views/profile/show.php';
    }

    public function update(): void {
        $this->requireCsrf();

        try {
            $this->service->updateName(Auth::user()['id'], trim($_POST['name']));
            header('Location: /profile?success=updated');
        } catch (\Exception $e) {
            header('Location: /profile?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function changePassword(): void {
        $this->requireCsrf();

        try {
            $this->service->changePassword(
                Auth::user()['id'],
                $_POST['new_password'],
                $_POST['confirm_password'],
                $_POST['current_password']
            );
            header('Location: /profile?success=password_changed');
        } catch (\Exception $e) {
            header('Location: /profile?error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
