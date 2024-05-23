# DLOCAL FOR LARAVEL
<p align="center"><img src="https://aziende.global/img/dlocal-logo.png"></p>

<p align="center"><img src="https://aziende.global/img/laravel-logo.png"></p>

# DESCRIPCIÓN 

# Laravel Facade para dLocal 

* [Instalación](#install)
* [Configuración](#configuration)
* [Como utilizar](#how-to)

* Compatibilidad en revisión !!




<a name="install"></a>
### Instalación

`composer require aziendeglobal/laravel-dlocal`

Dentro de `config/app.php` agregar los siguientes Provider y Alias

Provider

```php
'providers' => [
  // Otros Providers...
  AziendeGlobal\LaravelDLocal\Providers\DLocalServiceProvider::class,
  /*
   * Application Service Providers...
   */
],
```

Alias

```php
'aliases' => [
  // Otros Aliases
  'DLOCAL' => AziendeGlobal\LaravelDLocal\Facades\DLOCAL::class,
],
```



<a name="configuration"></a>
### Configuración

Antes de configurar el X_LOGIN, X_TRANS_KEY, SECRET_KEY y API_KEY, ejecutar el siguiente comando: 

`php artisan vendor:publish`

Despues de haber ejecutado el comando, ir al archivo `.env` y agregar los campos `DLOCAL_X_LOGIN`, `DLOCAL_X_TRANS_KEY`, `DLOCAL_SECRET_KEY` y `DLOCAL_API_KEY` con los correspondientes valores de tu aplicacion de dLocal.

Para saber cuales son tus datos podes ingresar aqui: 

* [Credenciales](https://dashboard.dlocal.com/settings/integration)

Si no deseas usar el archivo `.env`, ir a `config/dlocal.php` y agregar tus datos de aplicación correspondientes.

```php
return [
	'app_x_login'     => env('DLOCAL_X_LOGIN', 'tu X_LOGIN'),
	'app_x_trans_key'     => env('DLOCAL_X_TRANS_KEY', 'tu X_TRANS_KEY'),
	'app_secret_key' => env('DLOCAL_SECRET_KEY', 'tu SECRET_KEY'),
	'app_api_key' => env('DLOCAL_API_KEY', 'tu API_KEY'),
];
```



<a name="how-to"></a>
### Como utilizar

En este ejemplo vamos a crear un pago, usando la Facade `DLOCAL` 

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DLOCAL;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class DLocalController extends Controller
{
  public function createPayment()
  {
  	$paymentData = [        
        "amount" => 120.00,
        "currency" => "USD",
        "country"=> "BR",
        "payment_method_id" => "CARD",
        "payment_method_flow" => "DIRECT",
        "payer" =>[
            "name" => "Thiago Gabriel",
            "email" => "thiago@example.com",
            "document" => "53033315550",
            "user_reference" => "12345",
            "address" => [
                "state"  => "Rio de Janeiro",
                "city" => "Volta Redonda",
                "zip_code" => "27275-595",
                "street" => "Servidao B-1",
                "number" => "1106"
            ],
            "ip" => "2001:0db8:0000:0000:0000:ff00:0042:8329",
            "device_id" => "2fg3d4gf234"
        ],
        "card" => [
            "holder_name" => "Thiago Gabriel",
            "number" => "4111111111111111",
            "cvv" => "123",
            "expiration_month" => 10,
            "expiration_year" => 2040
        ],
        "order_id"=> "657434343",
        "notification_url"=> "http://merchant.com/notifications"
  	];

  	$payment = DLOCAL::create_secure_payment($paymentData);

  	return dd($payment);

  }
```

