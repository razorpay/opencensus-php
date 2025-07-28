<?php

namespace RZP\Models\Key;

use App;

class Utils
{
    /**
     * Common method to get route name from request context
     * Can be called from any package as Utils::getRoute()
     *
     * @return string|null
     */
    public static function getRoute()
    {
        $app = App::getFacadeRoot();
        return $app[Constants::REQUEST_CTX]->getRoute();
    }
} 