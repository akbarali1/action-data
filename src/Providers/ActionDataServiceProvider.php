<?php

namespace Akbarali\ActionData\Providers;

use Akbarali\ActionData\ActionDataBase;
use Illuminate\Support\ServiceProvider;

class ActionDataServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
		$this->app->beforeResolving(ActionDataBase::class, function ($className) {
			$this->app->bind($className, fn($item) => $className::createFromRequest($item->make('request')));
		});
	}
	
	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		//
	}
}
