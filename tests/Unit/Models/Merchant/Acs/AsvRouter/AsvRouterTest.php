<?php

namespace Unit\Models\Merchant\Acs\AsvRouter;


use Illuminate\Http\Request;
use RZP\Http\RequestContext;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Tests\Functional\TestCase;

class Route {

    public string $route;
    function getName() {
        return $this->route;
    }

}
class AsvRouterTest extends TestCase
{
    public function testAsvRouterTestForMerchantEmail()
    {

        $asvRouter = new AsvRouter();

        $asvRouter->isExclusionFlowOrFailure();

        // if we set no value, this should be true, don't route if we are not sure.
        $this->assertEquals(true, $asvRouter->isExclusionFlowOrFailure());

        // set value not included on email route
        $this->setRequestRoute('fund_transfer_attempt_initiate_action');
        $this->assertEquals(false, $asvRouter->isExclusionFlowOrFailure());

        $emailCheckRouteArray = ['account_create_v2'];

        foreach ($emailCheckRouteArray as $route) {
            $this->setRequestRoute($route);
            $this->assertEquals(app('request.ctx')->getRoute(), $route);
            $this->assertEquals(true, $asvRouter->isExclusionFlowOrFailure());
        }
    }

    function setRequestRoute($routeName) {
        $mockRequest = new Request();
        $mockRequest->setRouteResolver(function () use ($routeName) {
            $route = new Route();
            $route->route = $routeName;
            return $route;
        });

        $this->app['request'] = $mockRequest;
        $this->app['request.ctx'] = new RequestContext(app());

        try {
            $this->app['request.ctx']->init();
        } catch (\Exception $e) {
            // do nothing
        }
    }

}
