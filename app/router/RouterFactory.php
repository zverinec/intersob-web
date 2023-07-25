<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;
use Nette\Routing\Router;


/**
 * Router factory.
 */
class RouterFactory
{

	public static function createRouter(): Router
	{
		$router = new RouteList();
		$router->addRoute('index.php', 'Page:default', Route::ONE_WAY);

        $router->addRoute('year/', 'Year:default');
        $router->addRoute('<year [0-9]{4}>/page/show/<url>/', 'Page:show');
        $router->addRoute('[<year [0-9]{4}>/]', 'Page:default');

        $router->addRoute('<year [0-9]{4}>/page/<action>/', 'Page:default');
        $router->addRoute('<year [0-9]{4}>/team/<action>/', 'Team:default');

        $router->addRoute('admin/year/<action>/[<id>/]', 'Year:list');
        $router->addRoute('admin/page/<action >/[<id>/]', 'Page:list');

        $router->addRoute('<presenter>/<action>/[<id>/]', 'Page:default');

		return $router;
	}

}
