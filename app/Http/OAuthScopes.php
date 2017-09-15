<?php

namespace RZP\Http;

class OAuthScopes
{
    //
    // Following default scope gets assigned to any of the routes
    // based on HTTP method they are allowed
    //
    const READ_ONLY  = 'read_only';
    const READ_WRITE = 'read_write';

    /**
     * Map of additional scopes for a route (identified by the route name alias)
     *
     * @var array
     */
    protected static $scopes = [

        // Just a dummy route, gets used in tests
        'feature_dummy' => ['dummy.read']
    ];

    /**
     * Gets array of scopes that a route is mapped to. Tokens will be given
     * access if they have at least one of the route scopes allowed during token
     * creation.
     *
     * @param string $route
     *
     * @return array|mixed
     */
    public static function getScopesForRoute(string $route)
    {
        $scopes = self::$scopes[$route] ?? [];

        self::addDefaultScopesForRoute($scopes, $route);

        return $scopes;
    }

    /**
     * If no scope is defined for a route, we assign a default set of scopes
     * to the route
     *
     * @param array  $scopes
     * @param string $route
     */
    protected static function addDefaultScopesForRoute(array & $scopes, string $route)
    {
        $routeParams = Route::getApiRoute($route);

        //
        // Adds the default scopes for the current route to existing $scopes
        //
        $scopes[] = ($routeParams[0] === 'get') ? self::READ_ONLY : self::READ_WRITE;
    }
}
