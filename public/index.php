<?php
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
    ProfileController,
    ReportController,
    UserController,
    AuditController,
    CompetencyController,
    SearchController,
    SettingsController
};

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

Session::start();

$router = new Router();

$authMw  = fn() => (new AuthMiddleware())->handle();
$adminMw = fn() => (new RoleMiddleware())->handle('admin');

// ==================== PUBLIC ROUTES ====================
$router->get('/', function() {
    Auth::check() ? header('Location: /dashboard') : header('Location: /login');
    exit;
});

$router->get('/login',  fn() => (new AuthController())->showLogin());
$router->post('/login', fn() => (new AuthController())->login());

// ==================== AUTHENTICATED ====================
$router->post('/logout', fn() => (new AuthController())->logout(), [$authMw]);

$router->get('/dashboard', function() {
    require __DIR__ . '/../views/dashboard.php';
}, [$authMw]);

// ==================== PROFILE ====================
$router->get('/profile',           fn() => (new ProfileController())->show(),           [$authMw]);
$router->post('/profile/update',   fn() => (new ProfileController())->update(),         [$authMw]);
$router->post('/profile/password', fn() => (new ProfileController())->changePassword(), [$authMw]);

// ==================== PAGERS ====================
$router->get('/pagers',               fn()      => (new PagerController())->index(),      [$authMw]);
$router->get('/pagers/create',        fn()      => (new PagerController())->create(),     [$authMw, $adminMw]);
$router->post('/pagers',              fn()      => (new PagerController())->store(),      [$authMw, $adminMw]);
$router->get('/pagers/{id}',          fn($id)   => (new PagerController())->show($id),   [$authMw]);
$router->get('/pagers/{id}/edit',     fn($id)   => (new PagerController())->edit($id),   [$authMw, $adminMw]);
$router->post('/pagers/{id}/update',  fn($id)   => (new PagerController())->update($id), [$authMw, $adminMw]);
$router->post('/pagers/{id}/archive', fn($id)   => (new PagerController())->archive($id),[$authMw, $adminMw]);
$router->post('/pagers/{id}/restore', fn($id)   => (new PagerController())->restore($id),[$authMw, $adminMw]);

// ==================== PAGER WORKFLOWS ====================
$router->get('/pagers/{id}/reserve',       fn($id) => (new WorkflowController())->showReserve($id),         [$authMw, $adminMw]);
$router->post('/pagers/{id}/reserve',      fn($id) => (new WorkflowController())->reserve($id),             [$authMw, $adminMw]);
$router->get('/pagers/{id}/issue',         fn($id) => (new WorkflowController())->showIssue($id),           [$authMw, $adminMw]);
$router->post('/pagers/{id}/issue',        fn($id) => (new WorkflowController())->issue($id),               [$authMw, $adminMw]);
$router->get('/pagers/{id}/return',        fn($id) => (new WorkflowController())->showReturn($id),          [$authMw, $adminMw]);
$router->post('/pagers/{id}/return',       fn($id) => (new WorkflowController())->return($id),              [$authMw, $adminMw]);
$router->get('/pagers/{id}/broken',        fn($id) => (new WorkflowController())->showBroken($id),          [$authMw, $adminMw]);
$router->post('/pagers/{id}/broken',       fn($id) => (new WorkflowController())->markBroken($id),          [$authMw, $adminMw]);
$router->get('/pagers/{id}/preparation',   fn($id) => (new WorkflowController())->showPreparation($id),     [$authMw, $adminMw]);
$router->post('/pagers/{id}/preparation',  fn($id) => (new WorkflowController())->completePreparation($id), [$authMw, $adminMw]);
$router->post('/pagers/quick-assign',      fn()    => (new WorkflowController())->quickAssign(),             [$authMw, $adminMw]);

// ==================== REPAIRS ====================
$router->get('/pagers/{id}/repairs/create', fn($id) => (new RepairController())->create($id),      [$authMw, $adminMw]);
$router->post('/pagers/{id}/repairs',       fn($id) => (new RepairController())->store($id),        [$authMw, $adminMw]);
$router->get('/repairs/{id}/complete',      fn($id) => (new WorkflowController())->showRepaired($id),[$authMw, $adminMw]);
$router->post('/repairs/{id}/complete',     fn($id) => (new WorkflowController())->repaired($id),   [$authMw, $adminMw]);

// ==================== SIM CARDS ====================
$router->post('/pagers/{id}/sim',     fn($id) => (new SIMController())->store($id),      [$authMw, $adminMw]);
$router->post('/sim/{id}/deactivate', fn($id) => (new SIMController())->deactivate($id), [$authMw, $adminMw]);

// ==================== STAFF ====================
$router->get('/staff',                           fn()      => (new StaffController())->index(),              [$authMw]);
$router->get('/staff/create',                    fn()      => (new StaffController())->create(),             [$authMw, $adminMw]);
$router->post('/staff',                          fn()      => (new StaffController())->store(),              [$authMw, $adminMw]);
$router->get('/staff/{id}',                      fn($id)   => (new StaffController())->show($id),            [$authMw]);
$router->get('/staff/{id}/edit',                 fn($id)   => (new StaffController())->edit($id),            [$authMw, $adminMw]);
$router->post('/staff/{id}/update',              fn($id)   => (new StaffController())->update($id),          [$authMw, $adminMw]);
$router->post('/staff/{id}/deactivate',          fn($id)   => (new StaffController())->deactivate($id),      [$authMw, $adminMw]);
$router->post('/staff/{id}/reactivate',          fn($id)   => (new StaffController())->reactivate($id),      [$authMw, $adminMw]);
$router->post('/staff/{id}/delete',              fn($id)   => (new StaffController())->delete($id),          [$authMw, $adminMw]);
$router->post('/staff/{id}/stations/add',        fn($id)   => (new StaffController())->addStation($id),      [$authMw, $adminMw]);
$router->post('/staff/{id}/stations/remove',     fn($id)   => (new StaffController())->removeStation($id),   [$authMw, $adminMw]);
$router->post('/staff/{id}/competencies/add',    fn($id)   => (new StaffController())->addCompetency($id),   [$authMw, $adminMw]);
$router->post('/staff/{id}/competencies/remove', fn($id)   => (new StaffController())->removeCompetency($id),[$authMw, $adminMw]);

// ==================== STATIONS ====================
$router->get('/stations',              fn()    => (new StationController())->index(),     [$authMw]);
$router->get('/stations/create',       fn()    => (new StationController())->create(),    [$authMw, $adminMw]);
$router->post('/stations',             fn()    => (new StationController())->store(),     [$authMw, $adminMw]);
$router->get('/stations/{id}',         fn($id) => (new StationController())->show($id),  [$authMw]);
$router->get('/stations/{id}/edit',    fn($id) => (new StationController())->edit($id),  [$authMw, $adminMw]);
$router->post('/stations/{id}/update', fn($id) => (new StationController())->update($id),[$authMw, $adminMw]);
$router->post('/stations/{id}/delete', fn($id) => (new StationController())->delete($id),[$authMw, $adminMw]);

// ==================== REPORTS ====================
$router->get('/reports',                 fn() => (new ReportController())->index(),        [$authMw]);
$router->get('/reports/phone-numbers',   fn() => (new ReportController())->phoneNumbers(), [$authMw]);
$router->get('/reports/missing-pagers',  fn() => (new ReportController())->missingPagers(),[$authMw]);
$router->get('/reports/status-overview', fn() => (new ReportController())->statusOverview(),[$authMw]);
$router->get('/reports/export-phones',   fn() => (new ReportController())->exportPhones(), [$authMw]);

// ==================== USERS ====================
$router->get('/users',                      fn()    => (new UserController())->index(),           [$authMw, $adminMw]);
$router->get('/users/create',               fn()    => (new UserController())->create(),          [$authMw, $adminMw]);
$router->post('/users',                     fn()    => (new UserController())->store(),           [$authMw, $adminMw]);
$router->get('/users/{id}/edit',            fn($id) => (new UserController())->edit($id),        [$authMw, $adminMw]);
$router->post('/users/{id}/update',         fn($id) => (new UserController())->update($id),      [$authMw, $adminMw]);
$router->post('/users/{id}/reset-password', fn($id) => (new UserController())->resetPassword($id),[$authMw, $adminMw]);
$router->get('/admin/login-attempts',       fn()    => (new UserController())->loginAttempts(),  [$authMw, $adminMw]);

// ==================== AUDIT LOG ====================
$router->get('/audit',      fn()    => (new AuditController())->index(),  [$authMw, $adminMw]);
$router->get('/audit/{id}', fn($id) => (new AuditController())->show($id),[$authMw, $adminMw]);

// ==================== COMPETENCIES ====================
$router->get('/competencies',               fn()    => (new CompetencyController())->index(),     [$authMw]);
$router->get('/competencies/create',        fn()    => (new CompetencyController())->create(),    [$authMw, $adminMw]);
$router->post('/competencies',              fn()    => (new CompetencyController())->store(),     [$authMw, $adminMw]);
$router->get('/competencies/expiring',      fn()    => (new CompetencyController())->expiring(),  [$authMw]);
$router->get('/competencies/{id}',          fn($id) => (new CompetencyController())->show($id),  [$authMw]);
$router->get('/competencies/{id}/edit',     fn($id) => (new CompetencyController())->edit($id),  [$authMw, $adminMw]);
$router->post('/competencies/{id}/update',  fn($id) => (new CompetencyController())->update($id),[$authMw, $adminMw]);
$router->post('/competencies/{id}/delete',  fn($id) => (new CompetencyController())->delete($id),[$authMw, $adminMw]);

// ==================== SEARCH ====================
$router->get('/search',      fn() => (new SearchController())->index(), [$authMw]);
$router->get('/search/ajax', fn() => (new SearchController())->ajax(),  [$authMw]);

// ==================== SETTINGS ====================
$router->get('/settings',                     fn() => (new SettingsController())->index(),           [$authMw, $adminMw]);
$router->post('/settings/update',             fn() => (new SettingsController())->update(),          [$authMw, $adminMw]);
$router->post('/settings/transitions/update', fn() => (new SettingsController())->updateTransition(),[$authMw, $adminMw]);
$router->post('/settings/symptoms/save',      fn() => (new SettingsController())->saveSym(),         [$authMw, $adminMw]);
$router->post('/settings/symptoms/delete',    fn() => (new SettingsController())->deleteSym(),       [$authMw, $adminMw]);
$router->post('/settings/checks/save',        fn() => (new SettingsController())->saveCheck(),       [$authMw, $adminMw]);
$router->post('/settings/checks/delete',      fn() => (new SettingsController())->deleteCheck(),     [$authMw, $adminMw]);

// ==================== DISPATCH ====================
$router->dispatch();
