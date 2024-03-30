<?php

namespace Unit\Models\Merchant\Acs\AsvRouter;


use Illuminate\Http\Request;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\RequestContext;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use Config;
use RZP\Tests\Functional\TestCase;


class Route {

    public string $route;
    function getName() {
        return $this->route;
    }

}
class AsvRouterTest extends TestCase
{

    private $splitzResponse = [
        'id' => '10000000000000',
        'project_id' => 'K1ZCHBSn7hbCMN',
        'experiment' => [
            'id' => 'K1ZaAGS9JfAUHj',
            'name' => 'CallSyncDviationAPI',
            'exclusion_group_id' => '',
        ],
        'variant' => [
            'id' => 'K1ZaAHZ7Lnumc6',
            'name' => 'Dummy Enabled',
            'variables' => [
                [
                    'key' => 'enabled',
                    'value' => 'true',
                ]
            ],
            'experiment_id' => 'K1ZaAGS9JfAUHj',
            'weight' => 100,
            'is_default' => false
        ],
        'Reason' => 'bucketer',
        'steps' => [
            'sampler',
            'exclusion',
            'audience',
            'assign_bucket'
        ]
    ];

    public function testShouldRouteWriteRequestToAccountService() {

        Config::set('applications.asv_v2.splitz_send_write_to_asv', 'K1ZaAHZ7Lnumc62');
        Config::set('applications.asv_v2.splitz_experiment_send_write_route_or_worker_to_asv', 'K1ZaAHZ7Lnumc3');

        $tests = [
            [
                "splitz_call_count" => 1,
                "is_enabled_write_on_entity" => true,
                "is_enabled_write_on_route" => true,
                "splitz_exception" => false,
                "is_write_flow" => true,
                "expected_result" => true,
                "test_id" => "test_id"
            ],
            [
                "splitz_call_count" => 1,
                "is_enabled_write_on_entity" => false,
                "is_enabled_write_on_route" => true,
                "splitz_exception" => false,
                "is_write_flow" => true,
                "expected_result" => false,
                "test_id" => "test_id"
            ],
            [
                "splitz_call_count" => 1,
                "is_enabled_write_on_entity" => true,
                "is_enabled_write_on_route" => true,
                "splitz_exception" => true,
                "is_write_flow" => true,
                "expected_result" => false,
                "test_id" => "test_id"
            ],
            [
                "splitz_call_count" => 0,
                "is_enabled_write_on_entity" => true,
                "is_enabled_write_on_route" => true,
                "splitz_exception" => true,
                "is_write_flow" => false,
                "expected_result" => false,
                "test_id" => "test_id"
            ]
        ];

        for ($i = 0; $i < count($tests); $i++) {
            $test = $tests[$i];
            list($request, $response) = $this->getSplitzRequestAndResponse($test);

            $this->setSplitzWithOutputForBulk($response, $request, $test["splitz_call_count"], $test["splitz_exception"]);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)
                ->enableOriginalConstructor()
                ->onlyMethods(["isWriteFlowOrFailure"])
                ->getMock();

            $asvRouterMock->expects($this->exactly(1))->method('isWriteFlowOrFailure')->willReturn($test['is_write_flow']);

            $this->assertEquals($test["expected_result"], $asvRouterMock->shouldRouteWriteRequestToAccountService(
                Repository::class,
                FunctionConstant::SAVE_OR_FAIL,
                "test_id",
            ));
        }

    }


    public function testShouldRouteImplicitJoinToAccountService() {
        $tests = [
            [
                "is_write_flow" => true,
                "expected_result" => true,
                "is_transaction_active" => false,
                "shouldRouteWriteRequestToAccountService" => true,
            ],
            [
                "is_write_flow" => true,
                "expected_result" => false,
                "is_transaction_active" => true,
            ],
            [
                "is_write_flow" => true,
                "expected_result" => false,
                "is_transaction_active" => false,
                "shouldRouteWriteRequestToAccountService" => false,
            ],
        ];

        for ($i = 0; $i < count($tests); $i++) {
            $test          = $tests[$i];
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)
                ->enableOriginalConstructor()
                ->onlyMethods(["isWriteFlowOrFailure", 'isTransactionActive', 'shouldRouteWriteRequestToAccountService'])
                ->getMock();

            $asvRouterMock->expects($this->exactly(1))->method('isWriteFlowOrFailure')->willReturn($test['is_write_flow']);
            if ($test['is_write_flow'] === true) {
                $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->with(Repository::class)->willReturn($test['is_transaction_active']);
                if ($test['is_transaction_active'] === false) {
                    $asvRouterMock->expects($this->exactly(1))->method('shouldRouteWriteRequestToAccountService')->willReturn($test['shouldRouteWriteRequestToAccountService']);
                }
            }

            $actualResult = $asvRouterMock->shouldRouteImplicitJoinToAccountService(
                "test_id",
                "merchant",
                Repository::class,
                FunctionConstant::FIND_FOR_IMPLICIT_JOIN,
            );

            $this->assertEquals($test["expected_result"], $actualResult);
        }

    }

    public function testShouldRouteToAccountService() {

        $tests = [
            [
                "is_write_flow" => true,
                "expected_result" => true,
                "is_transaction_active" => false,
                "shouldRouteWriteRequestToAccountService" => true,
            ],
            [
                "is_write_flow" => true,
                "expected_result" => false,
                "is_transaction_active" => true,
            ],
            [
                "is_write_flow" => true,
                "expected_result" => false,
                "is_transaction_active" => false,
                "shouldRouteWriteRequestToAccountService" => false,
            ],
        ];

        for($i = 0; $i < count($tests); $i++) {
            $test = $tests[$i];
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)
                ->enableOriginalConstructor()
                ->onlyMethods(["isWriteFlowOrFailure", 'isTransactionActive', 'shouldRouteWriteRequestToAccountService'])
                ->getMock();

            $asvRouterMock->expects($this->exactly(1))->method('isWriteFlowOrFailure')->willReturn($test['is_write_flow']);
            if($test['is_write_flow'] === true) {
                $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->with(Repository::class)->willReturn($test['is_transaction_active']);

                if ($test['is_transaction_active'] === false) {
                    $asvRouterMock->expects($this->exactly(1))->method('shouldRouteWriteRequestToAccountService')->willReturn($test['shouldRouteWriteRequestToAccountService']);
                }
            }

            $actualResult = $asvRouterMock->shouldRouteToAccountService(
                "test_id",
                Repository::class,
                FunctionConstant::FIND_OR_FAIL,
            );

            $this->assertEquals($test["expected_result"], $actualResult);
        }

    }

    public function testAsvRouterTestForMerchantEmail()
    {

        $asvRouter = new AsvRouter();

        $asvRouter->isExclusionFlowOrFailure();

        // if we set no value, this should be true, don't route if we are not sure.
        $this->assertEquals(true, $asvRouter->isExclusionFlowOrFailure());

        // set value not included on email route
        $this->setRequestRoute('fund_transfer_attempt_initiate_action');
        $this->assertEquals(false, $asvRouter->isExclusionFlowOrFailure());

        $emailCheckRouteArray = ['payment_notify'];

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

    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {
        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }

    public function setSplitzWithOutputForBulk(array $output, array $request, $count = 1,$exception = false) {
        $response = [];
        for ($i = 0; $i < count($output); $i++) {
            $tempResponse = $this->splitzResponse;
            $tempResponse["variant"]["variables"][0]["value"] = $output[$i] ? "true" : "false";
            $response[$i] = $tempResponse;
        }

        $splitzMock = $this->createSplitzMock(['bulkCallsToSplitz']);

        if ($exception) {
            $splitzMock->expects($this->exactly($count))->method('bulkCallsToSplitz')->with($request)->willThrowException(new \Exception("sample"));
        } else {
            $splitzMock->expects($this->exactly($count))->method('bulkCallsToSplitz')->with($request)->willReturn($response);
        }

        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return;
    }

    /**
     * @param array $test
     * @return array
     */
    public function getSplitzRequestAndResponse(array $test): array
    {
        $request    = [
            [
                "experiment_id" => "K1ZaAHZ7Lnumc62",
                "id" => $test["test_id"]
            ]
        ];
        $response   = [
            $test["is_enabled_write_on_entity"]
        ];
        $request[]  = [
            "experiment_id" => "K1ZaAHZ7Lnumc3",
            "id" => "not_account_create_v2",
        ];
        $response[] = $test["is_enabled_write_on_route"];

        $this->setRequestRoute('not_account_create_v2');
        return array($request, $response);
    }

    protected function mockBasicAuth($exception = false)
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getMerchantId'])
            ->getMock();

        if ($exception) {
            $mock->expects($this->atLeastOnce())
                ->method('getMerchantId')
                ->willThrowException(new \Exception("sample"));
        } else {
            $mock->expects($this->atLeastOnce())
                ->method('getMerchantId')
                ->willReturn('RANDOM_PARTNER_ID');
        }

        $this->app->instance('basicauth', $mock);

        return $mock;
    }

}
