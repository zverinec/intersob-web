<?php
// Uncomment this line if you must temporarily take down your site for maintenance.
//require __DIR__ .'/../app/maintenance.php';

// Let bootstrap create Dependency Injection container.
$container = require __DIR__ . '/../app/bootstrap.php';

// Run application.
$container->getByType(Nette\Application\Application::class)->run();
