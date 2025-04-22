<?php

use App\Controllers\Contacts;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('contacts', 'Contacts::index');
$routes->post('contacts', 'Contacts::insert');
$routes->put('contacts/(:num)', "Contacts::update/$1");
$routes->delete('contacts/(:num)', 'Contacts::delete/$1');
 
//$routes->resource('contacts', ['controller' => 'Contacts']);