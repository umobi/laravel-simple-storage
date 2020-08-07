<?php

namespace Umobi\LaravelSimpleStorage;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Umobi\LaravelSimpleStorage\LaravelSimpleStorage
 */
class LaravelSimpleStorageFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-simple-storage';
    }
}
