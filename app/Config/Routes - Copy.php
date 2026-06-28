<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

service('auth')->routes($routes);

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Dashboard');
$routes->setDefaultMethod('index');

/*
|--------------------------------------------------------------------------
| Routing Strategy
|--------------------------------------------------------------------------
| - Auto-route dimatikan supaya tidak muncul fallback controller yang salah.
| - Semua halaman utama dibuat explicit route.
| - Alias lama tetap disediakan supaya URL lama tidak rusak.
*/

$routes->setAutoRoute(false);

$sessionFilters = ['filter' => ['session', 'idle']];
$adminFilters   = ['filter' => ['session', 'idle', 'group:admin']];

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

$routes->get('/', 'Dashboard::index', $sessionFilters);
$routes->get('dashboard', 'Dashboard::index', $sessionFilters);
$routes->get('keep-alive', 'Dashboard::keepAlive', $sessionFilters);
$routes->get('session/keep-alive', 'Dashboard::keepAlive', $sessionFilters);

/*
|--------------------------------------------------------------------------
| Tournament
|--------------------------------------------------------------------------
*/

$routes->get('tournaments', 'TournamentController::index', $sessionFilters);
$routes->get('tournaments/create', 'TournamentController::create', $adminFilters);
$routes->post('tournaments/store', 'TournamentController::store', $adminFilters);
$routes->get('tournaments/edit/(:num)', 'TournamentController::edit/$1', $adminFilters);
$routes->post('tournaments/update/(:num)', 'TournamentController::update/$1', $adminFilters);
$routes->post('tournaments/update-status/(:num)', 'TournamentController::updateStatus/$1', $adminFilters);
$routes->post('tournaments/delete/(:num)', 'TournamentController::delete/$1', $adminFilters);
$routes->post('scores/save-bulk', 'ScoreController::saveBulk');
$routes->post('index.php/scores/save-bulk', 'ScoreController::saveBulk');
$routes->post('index.php/scores/save', 'ScoreController::save');

/*
|--------------------------------------------------------------------------
| Pot
|--------------------------------------------------------------------------
*/

$routes->get('tournaments/(:num)/pots', 'PotController::index/$1', $sessionFilters);

$routes->get('pots/(:num)/scores', 'ScoreController::index/$1', $sessionFilters);
$routes->get('pots/(:num)/teams', 'TeamController::index/$1', $sessionFilters);

$routes->post('pots/store', 'PotController::store', $adminFilters);
$routes->post('pots/update/(:num)', 'PotController::update/$1', $adminFilters);
$routes->post('pots/update-images/(:num)', 'PotController::updateImages/$1', $adminFilters);

/*
| Aliases lama dan baru untuk aksi advance
| - view saat ini memakai pots/advance/{id}
| - sebagian file lama memakai pots/advance-selected
*/
$routes->post('pots/advance/(:num)', 'PotController::advanceSelected/$1', $adminFilters);
$routes->post('pots/advance-selected', 'PotController::advanceSelected', $adminFilters);

$routes->post('pots/delete/(:num)', 'PotController::delete/$1', $adminFilters);

/*
|--------------------------------------------------------------------------
| Teams
|--------------------------------------------------------------------------
*/

$routes->get('teams', 'TeamController::rosterIndex', $sessionFilters);
$routes->get('teams/roster', 'TeamController::rosterIndex', $sessionFilters);

/*
| Export template alias:
| - current view memakai teams/export-template
| - file lama kadang masih memakai teams/template/export
*/
$routes->get('teams/export-template', 'TeamController::exportTemplate', $sessionFilters);
$routes->get('teams/template/export', 'TeamController::exportTemplate', $sessionFilters);
$routes->get('teams/export-template/csv/(:num)', 'TeamController::downloadTemplateCsv/$1', $sessionFilters);
$routes->get('teams/template/csv/(:num)', 'TeamController::downloadTemplateCsv/$1', $sessionFilters);

$routes->get('teams/manager-data', 'TeamController::managerData', $sessionFilters);

/*
| Route kerja untuk membuka pot dari roster/team list
*/
$routes->get('teams/(:num)', 'TeamController::index/$1', $sessionFilters);

$routes->post('teams/store', 'TeamController::store', $adminFilters);
$routes->post('teams/update/(:num)', 'TeamController::update/$1', $adminFilters);
$routes->post('teams/bulk-update', 'TeamController::bulkUpdate', $adminFilters);
$routes->post('teams/delete/(:num)', 'TeamController::delete/$1', $adminFilters);
$routes->post('teams/detach/(:num)', 'TeamController::detach/$1', $adminFilters);

/*
|--------------------------------------------------------------------------
| Import
|--------------------------------------------------------------------------
*/

$routes->get('imports/teams', 'ImportController::teams', $adminFilters);
$routes->post('imports/teams', 'ImportController::storeTeams', $adminFilters);

/* backward compatibility */
$routes->get('import/teams', 'ImportController::teams', $adminFilters);
$routes->post('import/teams', 'ImportController::storeTeams', $adminFilters);

/*
|--------------------------------------------------------------------------
| Leaderboard
|--------------------------------------------------------------------------
*/

$routes->get('leaderboard/pot/(:num)', 'LeaderboardController::pot/$1', $sessionFilters);
$routes->get('leaderboard/(:num)', 'LeaderboardController::pot/$1', $sessionFilters);

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

$routes->get('home', 'Home::index');

/*
|--------------------------------------------------------------------------
| 404 fallback
|--------------------------------------------------------------------------
*/

$routes->set404Override();
