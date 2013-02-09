<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
		$router[] = new Route('index.php', 'Page:default', Route::ONE_WAY);
		
		$router[] = new Route('year/', 'Year:default');
		$router[] = new Route('<year [0-9]{4}>/page/show/<url>/', 'Page:show');
		$router[] = new Route('[<year [0-9]{4}>/]', 'Page:default');
		
		$router[] = new Route('<year [0-9]{4}>/page/<action>/', 'Page:default');
		$router[] = new Route('<year [0-9]{4}>/team/<action>/', 'Team:default');
		
		$router[] = new Route('admin/year/<action>/[<id>/]', 'Year:list');
		$router[] = new Route('admin/page/<action >/[<id>/]', 'Page:list');		
		
		$router[] = new Route('<presenter>/<action>/[<id>/]', 'Page:default');
		
		return $router;
	}

}
