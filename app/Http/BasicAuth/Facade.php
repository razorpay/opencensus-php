<?php

namespace Http\BasicAuth;

use Illuminate\Support\Facades\Facade as BaseFacade;

/**
 * @see \Http\BasicAuth
 */
class Facade extends BaseFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'basicauth'; }
}
