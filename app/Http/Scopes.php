<?php

namespace RZP\Http;

class Scope
{
    protected static $scopes = [
        'transfer_create'                   => ['transfers.write']
    ];

    /**
     * Get an array of scopes that the a route
     * is mapped to
     *
     * @param string $route
     * @return array|mixed
     */
    public function getScopesForRoute(string $route)
    {
        $scopes = self::$scopes[$route] ?? [];

        if (empty($scopes) === true)
        {
            $scopes = $this->getDefaultScope($route);
        }

        return $scopes;
    }

    public function checkScopesOnRoute(string $route)
    {
        // TODO
    }

    /**
     * If no scope is defined, we assign a default set
     * of scopes to a route
     *
     * @param string $route
     * @return array
     */
    protected function getDefaultScope(string $route) : array
    {
        $routeParams = Route::getApiRoute($route);

        $defaultScopes = [];

        if ($routeParams[0] === 'get')
        {
            $defaultScopes[] = 'all.read';
        }
        else
        {
            $defaultScopes[] = 'all.write';
        }

        return $defaultScopes;
    }
}