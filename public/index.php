<?php
// public/index.php
$basePath = '/public';
$_SERVER['SCRIPT_NAME'] = str_replace($basePath, '', $_SERVER['SCRIPT_NAME']);

require_once __DIR__ . '/../autoload.php';

use App\Core\{Router, Session, Auth};
use App\Middleware\{AuthMiddleware, RoleMiddleware};
use App\Controllers\{
    AuthController,
    PagerController,
    StaffController,
    StationController,
    WorkflowController,
    RepairController,
    SIMController,
    ProfileController
};

error_reporting(E_ALL);
ini_set('display_errors', 0); // Slå fra i produktion
ini_set('log_errors', 1);

Session::start();

// Router setup
$router = new Router();

// Middleware shortcuts
$authMw = fn() => (new AuthMiddleware())->handle();
$adminMw = fn() => (new RoleMiddleware())->handle('admin');

// Controllers
$authController = new AuthController();
$pagerController = new PagerController();
$staffController = new StaffController();
$stationController = new StationController();
$workflowController = new WorkflowController();
$repairController = new RepairController();
$simController = new SIMController();
$profileController = new ProfileController();
$reportController = new App\Controllers\ReportController();
$userController = new App\Controllers\UserController();

// ==================== PUBLIC ROUTES ====================
$router->get('/', function() {
    Auth::check() ? header('Location: /dashboard') : header('Location: /login');
    exit;
});

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);

// ==================== AUTHENTICATED ROUTES ====================
$router->post('/logout', [$authController, 'logout'], [$authMw]);

$router->get('/dashboard', function() {
    require __DIR__ . '/../views/dashboard.php';
}, [$authMw]);

// ==================== PROFILE (egen bruger) ====================
$router->get('/profile', [$profileController, 'show'], [$authMw]);
$router->post('/profile/update', [$profileController, 'update'], [$authMw]);
$router->post('/profile/password', [$profileController, 'changePassword'], [$authMw]);

// ==================== PAGERS ====================
$router->get('/pagers', [$pagerController, 'index'], [$authMw]);
$router->get('/pagers/create', [$pagerController, 'create'], [$authMw, $adminMw]);
$router->post('/pagers', [$pagerController, 'store'], [$authMw, $adminMw]);
$router->get('/pagers/{id}', [$pagerController, 'show'], [$authMw]);
$router->get('/pagers/{id}/edit', [$pagerController, 'edit'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/update', [$pagerController, 'update'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/archive', [$pagerController, 'archive'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/restore', [$pagerController, 'restore'], [$authMw, $adminMw]);

// ==================== PAGER WORKFLOWS ====================
$router->get('/pagers/{id}/reserve', [$workflowController, 'showReserve'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/reserve', [$workflowController, 'reserve'], [$authMw, $adminMw]);
$router->get('/pagers/{id}/issue', [$workflowController, 'showIssue'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/issue', [$workflowController, 'issue'], [$authMw, $adminMw]);
$router->get('/pagers/{id}/return', [$workflowController, 'showReturn'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/return', [$workflowController, 'return'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/stock', [$workflowController, 'setToStock'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/repair', [$workflowController, 'setToRepair'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/defect', [$workflowController, 'setDefect'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/preparation', [$workflowController, 'setToPreparation'], [$authMw, $adminMw]);
$router->post('/pagers/quick-assign', [$workflowController, 'quickAssign'], [$authMw, $adminMw]);

// ==================== REPAIRS ====================
$router->get('/pagers/{id}/repairs/create', [$repairController, 'create'], [$authMw, $adminMw]);
$router->post('/pagers/{id}/repairs', [$repairController, 'store'], [$authMw, $adminMw]);
$router->post('/repairs/{id}/complete', [$repairController, 'complete'], [$authMw, $adminMw]);

// ==================== SIM CARDS ====================
$router->post('/pagers/{id}/sim', [$simController, 'store'], [$authMw, $adminMw]);
$router->post('/sim/{id}/deactivate', [$simController, 'deactivate'], [$authMw, $adminMw]);

// ==================== STAFF ====================
$router->get('/staff', [$staffController, 'index'], [$authMw]);
$router->get('/staff/create', [$staffController, 'create'], [$authMw, $adminMw]);
$router->post('/staff', [$staffController, 'store'], [$authMw, $adminMw]);
$router->get('/staff/{id}', [$staffController, 'show'], [$authMw]);
$router->get('/staff/{id}/edit', [$staffController, 'edit'], [$authMw, $adminMw]);
$router->post('/staff/{id}/update', [$staffController, 'update'], [$authMw, $adminMw]);
$router->post('/staff/{id}/deactivate', [$staffController, 'deactivate'], [$authMw, $adminMw]);
$router->post('/staff/{id}/reactivate', [$staffController, 'reactivate'], [$authMw, $adminMw]);
$router->post('/staff/{id}/delete', [$staffController, 'delete'], [$authMw, $adminMw]);
$router->post('/staff/{id}/stations/add', [$staffController, 'addStation'], [$authMw, $adminMw]);
$router->post('/staff/{id}/stations/remove', [$staffController, 'removeStation'], [$authMw, $adminMw]);
$router->post('/staff/{id}/competencies/add', [$staffController, 'addCompetency'], [$authMw, $adminMw]);
$router->post('/staff/{id}/competencies/remove', [$staffController, 'removeCompetency'], [$authMw, $adminMw]);

// ==================== STATIONS ====================
$router->get('/stations', [$stationController, 'index'], [$authMw]);
$router->get('/stations/create', [$stationController, 'create'], [$authMw, $adminMw]);
$router->post('/stations', [$stationController, 'store'], [$authMw, $adminMw]);
$router->get('/stations/{id}', [$stationController, 'show'], [$authMw]);
$router->get('/stations/{id}/edit', [$stationController, 'edit'], [$authMw, $adminMw]);
$router->post('/stations/{id}/update', [$stationController, 'update'], [$authMw, $adminMw]);

// ==================== REPORTS ====================
$router->get('/reports', [$reportController, 'index'], [$authMw]);
$router->get('/reports/phone-numbers', [$reportController, 'phoneNumbers'], [$authMw]);
$router->get('/reports/missing-pagers', [$reportController, 'missingPagers'], [$authMw]);
$router->get('/reports/status-overview', [$reportController, 'statusOverview'], [$authMw]);
$router->get('/reports/export-phones', [$reportController, 'exportPhones'], [$authMw]);

// ==================== USERS ====================
$router->get('/users', [$userController, 'index'], [$authMw, $adminMw]);
$router->get('/users/create', [$userController, 'create'], [$authMw, $adminMw]);
$router->post('/users', [$userController, 'store'], [$authMw, $adminMw]);
$router->get('/users/{id}/edit', [$userController, 'edit'], [$authMw, $adminMw]);
$router->post('/users/{id}/update', [$userController, 'update'], [$authMw, $adminMw]);
$router->post('/users/{id}/reset-password', [$userController, 'resetPassword'], [$authMw, $adminMw]);

// ==================== DISPATCH ====================
$router->dispatch();
