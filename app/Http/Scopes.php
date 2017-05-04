<?php

namespace RZP\Http;

class Scopes
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
    public static function getScopesForRoute(string $route)
    {
        $scopes = self::$scopes[$route] ?? [];

        self::addDefaultScopes($scopes, $route);

        return $scopes;
    }

    public function checkAnyScopesOnRoute(array $tokenScopes, string $route)
    {
        // TODO
    }

    /**
     * If no scope is defined, we assign a default set
     * of scopes to a route
     *
     * @param array  $scopes
     * @param string $route
     *
     * @return array
     */
    protected static function addDefaultScopes(array & $scopes, string $route) : array
    {
        $routeParams = Route::getApiRoute($route);

        $defaultScopes = [];

        if ($routeParams[0] === 'get')
        {
            $defaultScopes[] = 'read_only';
        }
        else
        {
            $defaultScopes[] = 'write_only';
        }

        $scopes = array_merge($scopes, $defaultScopes);

        return $scopes;
    }
}