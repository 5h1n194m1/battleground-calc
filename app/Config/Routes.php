<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index', ['filter' => 'session']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'session']);
$routes->get('leaderboard/pot/(:num)', 'LeaderboardController::pot/$1', ['filter' => 'session']);

service('auth')->routes($routes);

$adminFilters = ['filter' => ['session', 'group:admin']];

$routes->get('tournaments', 'TournamentController::index', $adminFilters);
$routes->get('tournaments/create', 'TournamentController::create', $adminFilters);
$routes->post('tournaments/store', 'TournamentController::store', $adminFilters);
$routes->get('tournaments/edit/(:num)', 'TournamentController::edit/$1', $adminFilters);
$routes->post('tournaments/update/(:num)', 'TournamentController::update/$1', $adminFilters);
$routes->post('tournaments/delete/(:num)', 'TournamentController::delete/$1', $adminFilters);
$routes->get('tournaments/(:num)/pots', 'PotController::index/$1', $adminFilters);

$routes->post('pots/store', 'PotController::store', $adminFilters);
$routes->post('pots/update/(:num)', 'PotController::update/$1', $adminFilters);
$routes->post('pots/delete/(:num)', 'PotController::delete/$1', $adminFilters);
$routes->get('pots/(:num)/teams', 'TeamController::index/$1', $adminFilters);
$routes->get('pots/(:num)/scores', 'ScoreController::index/$1', $adminFilters);

$routes->post('teams/store', 'TeamController::store', $adminFilters);
$routes->post('teams/update/(:num)', 'TeamController::update/$1', $adminFilters);
$routes->post('teams/delete/(:num)', 'TeamController::delete/$1', $adminFilters);

$routes->post('scores/save', 'ScoreController::save', $adminFilters);

$routes->get('imports/registrations', 'ImportController::registrations', $adminFilters);
$routes->post('imports/registrations', 'ImportController::storeRegistrations', $adminFilters);
