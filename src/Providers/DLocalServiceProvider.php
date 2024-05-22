<?php

namespace AziendeGlobal\LaravelDLocal\Providers;

use Illuminate\Support\ServiceProvider;
use AziendeGlobal\LaravelDLocal\DLOCAL;

class DLocalServiceProvider extends ServiceProvider 
{

	protected $app_x_login;
	protected $app_x_trans_key;
	protected $app_secret_key;
	protected $app_api_key;

	public function boot()
	{
		
		$this->publishes([__DIR__.'/../config/dlocal.php' => config_path('dlocal.php')]);

		$this->app_x_login     = config('dlocal.app_x_login');
		$this->app_x_trans_key     = config('dlocal.app_x_trans_key');
		$this->app_secret_key = config('dlocal.app_secret_key');
		$this->app_api_key = config('dlocal.app_api_key');
	}

	public function register()
	{
		$this->app->singleton('DLOCAL', function(){
			return new DLOCAL($this->app_x_login, $this->app_x_trans_key, $this->app_secret_key, $this->app_api_key);
		});
	}
}