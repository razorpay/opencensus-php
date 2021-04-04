<?php

namespace RZP\Tests;

use Mailgun\Mailgun;
use RZP\Http\Route;

class RoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        //
        // Setting up db
        //
        \Artisan::call('migrate');

        //
        // Enable filters
        //
        // Route::enableFilters();
    }

    public function testAuthGroupsAreDisjoint()
    {
        $groups = [
            Route::$internal,
            Route::$private,
            Route::$public,
            Route::$publicCallback,
            Route::$proxy,
            Route::$device,
            Route::$direct,
            // @todo add $admin back once we remove Internal
            // Route::$admin,
        ];

        $uniqueRoutes = [];

        // Loop through every route in the auth groups and
        // add them to a hash set. isset checks if the route
        // has already been added, in constant time.

        foreach ($groups as $group)
        {
            foreach ($group as $route)
            {
                $this->assertEquals(
                    false,
                    isset($uniqueRoutes[$route]),
                    "$route route appears in two distinct auth groups"
                );

                $uniqueRoutes[$route] = true;
            }
        }
    }

    public function testMailgunRoute()
    {
        $this->markTestSkipped();

        $routes = array(
        );

        $mgConfig = \Config::get('applications.mailgun');

        if ($mgConfig['mock'])
        {
            $this->markTestSkipped('Can only run this test when mailgun is not mocked');
        }

        $mg = new Mailgun($mgConfig['key']);

        $result = $mg->get('routes')->http_response_body;

        $count = $result->total_count;

        foreach ($routes as $route)
        {
            $this->matchRouteWithMailgunRoutes($route, $result);
        }
    }

    /**
     * Verify that internal[] array is composed
     * of everything in the internalApps list
     */
    public function testSecurityInternalRoutes()
    {
        // These apps only have access to merchant routes, and cannot make requests to internal routes
        $merchantProxyOnlyApps = [
            'subscriptions',
        ];

        $merchantRoutes = array_merge(Route::$private, Route::$proxy);

        foreach ($merchantProxyOnlyApps as $app)
        {
            $appRoutesOutsideMerchantAuth = array_diff(Route::$internalApps[$app], $merchantRoutes);

            // No routes apart from merchant auth routes
            $this->assertEquals([], $appRoutesOutsideMerchantAuth);
        }

        $internalAppRoutes = [];

        foreach (Route::$internalApps as $app => $routes)
        {
            // These can be skipped, since we've already checked that they have no access to internal routes
            if (in_array($app, $merchantProxyOnlyApps, true) === true)
            {
                continue;
            }

            foreach ($routes as $route)
            {
                $internalAppRoutes[] = $route;
            }
        }

        // Verify that diffing both ways returns 0 elements
        $knownExceptions = [
            '*',
            // The following are proxy routes called from CRON
            // and as such are not in $internal
            // When we get rid of $internal entirely, this will
            // get fixed, and we can use [] instead of knownExceptions
            'reports_transaction_dsp',
            'reports_refund_irctc',
            'payment_acknowledge'
        ];

        $this->assertEquals($knownExceptions, array_values(array_diff($internalAppRoutes, Route::$internal)));
        $this->assertEquals([], array_diff(Route::$internal, $internalAppRoutes));
    }

    /**
     * Verify that no private/admin routes are listed in
     * internal apps as well
     *
     * ie, cron/mailgun etc should not be able to call a route
     * that we also expect to be hit from admin
     *
     * @todo Once we get rid of internal the first argument
     * to assertEquals would be an empty array.
     */
    public function testSecurityOnlyInternal()
    {
        $disAllowedRoutes = array_merge(Route::$admin, Route::$private);

        $this->assertEquals([], array_values(array_intersect(Route::$internal, $disAllowedRoutes)));
    }

    protected function matchRouteWithMailgunRoutes($route, $result)
    {
        $urls = \Config::get('url');
        $mg = \Config::get('applications.mailgun');
        $secret = $mg['secret'];

        $match = false;
        $repeat = false;

        $tokens = explode('_', $route);
        $mode = $tokens[3];
        $env = $tokens[2];

        $apiKey = 'rzp_'.$mode;
        $basicAuth = $apiKey . ':' . $secret;
        $url = $urls[$env];

        $https = strstr($url, 'https://');
        $scheme = ($https === false) ? 'http://' : 'https://';
        $host = substr($url, strlen($scheme));

        // @todo: Needs to be rewritten.
        $action = "forward('".$scheme . $basicAuth . '@' . $host . "/v1/gateway/mpr/reconcile')";
        $expression = "match_recipient('". $route . '@' . $mg['url']."')";

        foreach ($result->items as $item)
        {
            if (strcmp($item->expression, $expression) === 0)
            {
                foreach ($item->actions as $itemAction)
                {
                    if (strcmp($itemAction, $action) === 0)
                    {
                        if ($match === true)
                        {
                            $repeat = true;
                            break;
                        }

                        $match = true;
                    }
                }
            }
        }

        $this->assertEquals($match, true, 'No match found for route: ' . $route);
        $this->assertEquals($repeat, false, 'Multiple matches found for route: ' . $route);
    }
}
