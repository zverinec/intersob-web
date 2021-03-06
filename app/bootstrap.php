<?php

// Load Nette Framework or autoloader generated by Composer
require __DIR__ . '/../libs/autoload.php';

use Nette\Application\Routers\Route;


$configurator = new \Nette\Configurator();

// Enable Nette Debugger for error visualisation & logging
//$configurator->setDebugMode(TRUE);
$configurator->enableDebugger(__DIR__ . '/../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

// Create Dependency Injection container from config.neon file
// First: load config
$configurator->addConfig(__DIR__ . '/config/config.neon');

// Second: overwrite by local changes
$configurator->addConfig(__DIR__ . '/config/config.local.neon');
$container = $configurator->createContainer();

// Turn on HTTPS when request is secured
/** @var Request $httpRequest */
$httpRequest = $container->getByType('Nette\\Http\\Request');
if ($httpRequest->isSecured()) {
       Route::$defaultFlags = Route::SECURED;
}

// Setup router
$container->addService('router', RouterFactory::createRouter());

return $container;
