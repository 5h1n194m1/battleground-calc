<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$sessionFilters = ['filter' => ['session', 'idle']];
$adminFilters   = ['filter' => ['session', 'idle', 'group:admin']];

$routes->get('/', 'Dashboard::index', $sessionFilters);
$routes->get('dashboard', 'Dashboard::index', $sessionFilters);
$routes->get('session/keep-alive', 'Dashboard::keepAlive', $sessionFilters);
$routes->get('leaderboard/pot/(:num)', 'LeaderboardController::pot/$1', $sessionFilters);

$routes->get('register', '\App\Controllers\Auth\RegisterController::registerView');
$routes->post('register', '\App\Controllers\Auth\RegisterController::registerAction');

service('auth')->routes($routes);

$routes->get('tournaments', 'TournamentController::index', $adminFilters);
$routes->get('tournaments/create', 'TournamentController::create', $adminFilters);
$routes->post('tournaments/store', 'TournamentController::store', $adminFilters);
$routes->get('tournaments/edit/(:num)', 'TournamentController::edit/$1', $adminFilters);
$routes->post('tournaments/update/(:num)', 'TournamentController::update/$1', $adminFilters);
$routes->post('tournaments/update-status/(:num)', 'TournamentController::updateStatus/$1', $adminFilters);
$routes->post('tournaments/delete/(:num)', 'TournamentController::delete/$1', $adminFilters);
$routes->get('tournaments/(:num)/pots', 'PotController::index/$1', $adminFilters);

$routes->post('pots/store', 'PotController::store', $adminFilters);
$routes->post('pots/update/(:num)', 'PotController::update/$1', $adminFilters);
$routes->post('pots/update-images/(:num)', 'PotController::updateImages/$1', $adminFilters);
$routes->post('pots/advance/(:num)', 'PotController::advanceSelected/$1', $adminFilters);
$routes->post('pots/delete/(:num)', 'PotController::delete/$1', $adminFilters);
$routes->get('pots/(:num)/teams', 'TeamController::index/$1', $adminFilters);
$routes->get('pots/(:num)/scores', 'ScoreController::index/$1', $adminFilters);

$routes->post('teams/store', 'TeamController::store', $adminFilters);
$routes->get('teams/roster', 'TeamController::rosterIndex', $adminFilters);
$routes->get('teams/export-template', 'TeamController::exportTemplate', $adminFilters);
$routes->get('teams/export-template/csv/(:num)', 'TeamController::downloadTemplateCsv/$1', $adminFilters);
$routes->post('teams/bulk-update', 'TeamController::bulkUpdate', $adminFilters);
$routes->post('teams/update/(:num)', 'TeamController::update/$1', $adminFilters);
$routes->post('teams/detach/(:num)', 'TeamController::detach/$1', $adminFilters);
$routes->post('teams/delete/(:num)', 'TeamController::delete/$1', $adminFilters);
$routes->get('teams/manager-data', 'TeamController::managerData', $adminFilters);

$routes->post('scores/save', 'ScoreController::save', $adminFilters);
$routes->post('scores/save-bulk', 'ScoreController::saveBulk', $adminFilters);

$routes->get('imports/teams', 'ImportController::teams', $adminFilters);
$routes->post('imports/teams', 'ImportController::storeTeams', $adminFilters);
