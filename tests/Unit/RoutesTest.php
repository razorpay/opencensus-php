<?php

namespace RZP\Tests;

use Mockery;
use Mailgun\Mailgun;
use RZP\Http\Route;
use Illuminate\Routing\Router;

class RoutesTest extends TestCase
{
    public function testAddRouteGroupsWithV2Prefix()
    {
        $origCount = count(Route::$public);

        $routeMock = $this->getMockBuilder(Route::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['addRoute'])
                            ->getMock();

        //Set the array of routes to v2 prefix to contain TWO public routes
        Route::$routesWithV2Prefix = [Route::$public[0]];
        array_push(Route::$routesWithV2Prefix, Route::$public[1]);

        $exp = Route::$public;
        //since initial 2 routes added in the v2 prefix group
        array_shift($exp);
        array_shift($exp);
        $exp = array_map(function($v){
            return [$v];
        }, $exp);

        $routeMock->expects($this->exactly($origCount - 2))
                    ->method('addRoute')
                    ->withConsecutive(...$exp);

        $routeMock->addRouteGroups(['public']);
    }

    public function testAddRouteGroupsWithNoV2Prefix()
    {
        $origCount = count(Route::$public);

        $routeMock = $this->getMockBuilder(Route::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['addRoute'])
                            ->getMock();

        //set empty array to the group of v2 prefix routes
        Route::$routesWithV2Prefix = [];

        $exp = Route::$public;
        $exp = array_map(function($v){
            return [$v];
        }, $exp);

        $routeMock->expects($this->exactly($origCount))
                    ->method('addRoute')
                    ->withConsecutive(...$exp);

        $routeMock->addRouteGroups(['public']);
    }

    public function testAddV2RouteGroupsWithV2Prefix()
    {
        $routeMock = $this->getMockBuilder(Route::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['addRoute'])
                            ->getMock();

        //Set the array of routes to v2 prefix to contain TWO public routes
        Route::$routesWithV2Prefix = [Route::$public[0]];
        array_push(Route::$routesWithV2Prefix, Route::$public[1]);

        $exp = Route::$routesWithV2Prefix;
        $exp = array_map(function($v){
            return [$v];
        }, $exp);

        $routeMock->expects($this->exactly(2))
                    ->method('addRoute')
                    ->withConsecutive(...$exp);

        $routeMock->addV2RouteGroups(['public']);
    }

    public function testAddV2RouteGroupsWithNoV2Prefix()
    {
        $routeMock = $this->getMockBuilder(Route::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['addRoute'])
                            ->getMock();

        //set empty array to the group of v2 prefix routes
        Route::$routesWithV2Prefix = [];

        $routeMock->expects($this->exactly(0))
                    ->method('addRoute');

        $routeMock->addV2RouteGroups(['public']);
    }

    public function testBankingRoutesAreAccessibleViaMerchantDashboard()
    {
        $bankingRoutes = array_keys(Route::$bankingRoutePermissions);

        $merchantDashboardRoutes = array_merge(array_values(Route::$internalApps['merchant_dashboard']),
                                               array_values(Route::$internalApps['dashboard_guest']));

        $diff = array_diff($bankingRoutes, $merchantDashboardRoutes);

        $this->assertEquals([], $diff);
    }

    public function testDuplicateRoute()
    {
        $routes = Route::getApiRoutes();

        $pathArray = [];

        $count = 0;

        foreach ($routes as $route)
        {
            if(array_key_exists($route[1], $pathArray) === true)
            {
                if($route[0] === $pathArray[$route[1]])
                {
                    s($route[1], $route[0]);
                    $count++;
                }
            }

            $pathArray[$route[1]] = $route[0];
        }
        s("count", $count);
    }
}
