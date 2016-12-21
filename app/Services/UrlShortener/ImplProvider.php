<?php

namespace RZP\Services\UrlShortener;

use RZP\Exception;

class ImplProvider
{
    public function get(string $service, array $config)
    {
        //
        // Returns instance of implementation of given service (or its mock)
        //

        $implPath = 'RZP\\Services\\UrlShortener\\Impl\\';

        if ($config['mock'] === true)
        {
            $implPath .= 'Mock\\';
        }

        $impl = $implPath . ucfirst($service);

        if (class_exists($impl) === false)
        {
            throw new Exception\RuntimeException("$impl does not exists.");
        }

        return $impl::instance($config);
    }
}
