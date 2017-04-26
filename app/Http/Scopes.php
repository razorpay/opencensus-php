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
    public function getScopesForRoute(string $route)
    {
        $scopes = self::$scopes[$route] ?? [];

        if (empty($scopes) === true)
        {
            $scopes = $this->getDefaultScope($route);
        }

        return $scopes;
    }

    public function checkAnyScopesOnRoute(array $tokenScopes, string $route)
    {
        $definedScopes = $this->getScopesForRoute($route);


    }

    public static function parseScope(string $scope)
    {
        $scopesParts = explode('.', $scope);

        return [
            'entity'    => $scopesParts[0],
            'operation' => $scopesParts[1]
        ];
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
            $defaultScopes[] = 'read_only';
        }
        else
        {
            $defaultScopes[] = 'write_only';
        }

        return $defaultScopes;
    }
}