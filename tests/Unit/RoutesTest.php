<?php

namespace RZP\Tests;

use Mockery;
use Mailgun\Mailgun;
use RZP\Http\Route;
use Illuminate\Routing\Router;

class RoutesTest extends TestCase
{
    public function testDuplicateRoutes()
    {
        $knownExceptions = [
            'banking_account_statement_process_cron',
            'merchants_access_map_upsert_bulk',
            'admin_reports_fetch_report_data',
            'mock_esigner_legaldesk_payment',
        ];

        $v2Routes = Route::$routesWithV2Prefix;

        $checkMap = [];

        foreach (Route::getApiRoutes() as $routeName => $routeDetails)
        {
            // ignoring the known exception
            if(in_array($routeName, $knownExceptions) === true)
            {
                continue;
            }

            $endpoint = $routeDetails[1];
            $method   = $routeDetails[0];

            // checking if the endpoint is same or not and then checking if methods are same
            if((array_key_exists($endpoint, $checkMap) === true) and
                ($method === $checkMap[$endpoint]))
            {
                if((in_array($routeName, $v2Routes) === false))
                {
                    $this->assertNotEquals($method, $checkMap[$endpoint], $routeName);
                }
            }

            $checkMap[$endpoint] = $method;
        }
    }

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
}
