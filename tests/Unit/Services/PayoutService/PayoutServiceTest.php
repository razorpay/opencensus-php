<?php

namespace Unit\Services\PayoutService;

use Mockery;
use Requests_Response;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use Functional\Payout\PayoutServiceTest as PayoutServiceFunctionalTest;

class PayoutServiceTest extends TestCase
{
    use TestsBusinessBanking;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMerchantForBusinessBanking(false, 10000000);
    }

    // Adding these tests here to test the functionality of Admin Fetch code because it is not possible to mock it and
    // test it completely via functional tests. Functional test for the same have been written though to test the
    // functionality before the fetch function is called and what happens after it's response.
    public function testAdminFetchViaServiceForPayouts()
    {
        $payoutServiceAdminFetchMock = Mockery::mock('RZP\Services\PayoutService\AdminFetch',
                                                     [$this->app])->makePartial();

        $payoutId = "Gg7sgBZgvYjlSB";

        $payoutServiceAdminFetchMock->shouldReceive('sendRequest')
                                    ->withArgs(
                                        function($request) use ($payoutId) {
                                            try
                                            {
                                                if (empty($request['url']) === false)
                                                {
                                                    return (substr($request['url'], -37) ===
                                                            '/payouts/admin/payouts/' . $payoutId);
                                                }

                                                return false;
                                            }
                                            catch (\Throwable $e)
                                            {
                                                return false;
                                            }
                                        })
                                    ->andReturnUsing(
                                        function() use ($payoutId) {
                                            return $this->adminGetResponseForService('payouts', $payoutId);
                                        }
                                    );

        $response = $payoutServiceAdminFetchMock->fetch('payouts', $payoutId, []);

        $this->assertEquals('pout_' . $payoutId, $response['id']);
    }

    public function testAdminFetchViaServiceForPayoutsWithServiceFailure()
    {
        $payoutServiceAdminFetchMock = Mockery::mock('RZP\Services\PayoutService\AdminFetch',
                                                     [$this->app])->makePartial();

        $payoutId = "Gg7sgBZgvYjlSB";

        $payoutServiceAdminFetchMock->shouldReceive('sendRequest')
                                    ->withArgs(
                                        function($request) use ($payoutId) {
                                            try
                                            {
                                                if (empty($request['url']) === false)
                                                {
                                                    return (substr($request['url'], -37) ===
                                                            '/payouts/admin/payouts/' . $payoutId);
                                                }

                                                return false;
                                            }
                                            catch (\Throwable $e)
                                            {
                                                return false;
                                            }
                                        })
                                    ->andReturnUsing(
                                        function() use ($payoutId) {
                                            return $this->adminGetResponseForService('payouts', $payoutId, true);
                                        }
                                    );

        try
        {
            $payoutServiceAdminFetchMock->fetch('payouts', $payoutId, []);

        }
        catch (\Throwable $throwable)
        {
            $this->assertEquals("Service Failure", $throwable->getMessage());
            $this->assertEquals(ErrorCode::BAD_REQUEST_ERROR, $throwable->getCode());

        }
    }

    protected function adminGetResponseForService($entity, $id, $fail = false)
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body        = json_encode(
                [
                    "error" =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"       => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success     = true;
        }
        else
        {

            $body = (new PayoutServiceFunctionalTest)->adminGetResponseForService($entity, $id);

            $response->body = json_encode($body);

            $response->status_code = 200;
            $response->success     = true;
        }

        return $response;
    }
}
