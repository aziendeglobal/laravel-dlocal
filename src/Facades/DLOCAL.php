<?php

namespace AziendeGlobal\LaravelDLocal\Facades;

use Illuminate\Support\Facades\Facade;

class DLOCAL extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DLOCAL';
    }
}