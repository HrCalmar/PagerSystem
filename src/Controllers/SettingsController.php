<?php
namespace App\Controllers;

use App\Services\{SettingsService, WorkflowTransitionService, DefectService, PreparationService};
use App\Core\BaseController;

class SettingsController extends BaseController {
    private SettingsService $settings;

    public function __construct() {
        $this->settings = new SettingsService();
    }

    public function index(): void {
        $transitions = (new WorkflowTransitionService())->getAll();
        $symptoms    = (new DefectService())->getAllSymptoms();
        $checks      = (new PreparationService())->getAllChecks();
        $settings    = $this->settings->getAll();
        require __DIR__ . '/../../views/settings/index.php';
    }

    public function update(): void {
        $this->requireCsrf();
        try {
            $all = $this->settings->getAll();
            foreach ($all as $s) {
                $key = $s['key'];
                if ($s['type'] === 'boolean') {
                    $this->settings->set($key, isset($_POST[$key]));
                } elseif (isset($_POST[$key])) {
                    $this->settings->set($key, $_POST[$key]);
                }
            }
            header('Location: /settings?success=saved');
        } catch (\Exception $e) {
            header('Location: /settings?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    public function updateTransition(): void {
        $this->requireCsrf();
        try {
            (new WorkflowTransitionService())->update(
                (int)$_POST['id'],
                isset($_POST['is_enabled']),
                isset($_POST['is_default'])
            );
            header('Location: /settings?success=saved#transitions');
        } catch (\Exception $e) {
            header('Location: /settings?error=' . urlencode($e->getMessage()) . '#transitions');
        }
        exit;
    }

    public function saveSym(): void {
        $this->requireCsrf();
        try {
            $defect = new DefectService();
            $id     = (int)($_POST['id'] ?? 0);
            if ($id) {
                $defect->updateSymptom($id, trim($_POST['label']), (int)$_POST['sort_order'], isset($_POST['is_enabled']));
            } else {
                $defect->saveSymptom(trim($_POST['label']), (int)$_POST['sort_order']);
            }
            header('Location: /settings?success=saved#symptoms');
        } catch (\Exception $e) {
            header('Location: /settings?error=' . urlencode($e->getMessage()) . '#symptoms');
        }
        exit;
    }

    public function deleteSym(): void {
        $this->requireCsrf();
        (new DefectService())->deleteSymptom((int)$_POST['id']);
        header('Location: /settings?success=saved#symptoms');
        exit;
    }

    public function saveCheck(): void {
        $this->requireCsrf();
        try {
            $prep = new PreparationService();
            $id   = (int)($_POST['id'] ?? 0);
            if ($id) {
                $prep->updateCheck($id, trim($_POST['label']), (int)$_POST['sort_order'], isset($_POST['is_enabled']));
            } else {
                $prep->saveCheck(trim($_POST['label']), (int)$_POST['sort_order']);
            }
            header('Location: /settings?success=saved#checks');
        } catch (\Exception $e) {
            header('Location: /settings?error=' . urlencode($e->getMessage()) . '#checks');
        }
        exit;
    }

    public function deleteCheck(): void {
        $this->requireCsrf();
        (new PreparationService())->deleteCheck((int)$_POST['id']);
        header('Location: /settings?success=saved#checks');
        exit;
    }
}
