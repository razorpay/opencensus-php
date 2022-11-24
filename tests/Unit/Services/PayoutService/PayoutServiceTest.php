<?php

namespace Unit\Services\PayoutService;

use Mockery;
use \WpOrg\Requests\Response;

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
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('payouts', $entityId);

        $response = $payoutServiceAdminFetchMock->fetch('payouts', $entityId, []);

        $this->assertEquals('pout_' . $entityId, $response['id']);
    }

    public function testAdminFetchViaServiceForReversals()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('reversals', $entityId);

        $response = $payoutServiceAdminFetchMock->fetch('reversals', $entityId, []);

        $this->assertEquals('rev_' . $entityId, $response['id']);
    }

    public function testAdminFetchViaServiceForPayoutLogs()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('payout_logs', $entityId);

        $response = $payoutServiceAdminFetchMock->fetch('payout_logs', $entityId, []);

        $this->assertEquals('poutlog_' . $entityId, $response['id']);
    }

    public function testAdminFetchViaServiceForPayoutSources()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('payout_sources', $entityId);

        $response = $payoutServiceAdminFetchMock->fetch('payout_sources', $entityId, []);

        $this->assertEquals('poutsrc_' . $entityId, $response['id']);
    }

    public function testAdminFetchViaServiceForPayoutsWithServiceFailure()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('payouts', $entityId, true);

        try
        {
            $payoutServiceAdminFetchMock->fetch('payouts', $entityId, []);

        }
        catch (\Throwable $throwable)
        {
            $this->assertEquals("Service Failure", $throwable->getMessage());
            $this->assertEquals(ErrorCode::BAD_REQUEST_ERROR, $throwable->getCode());

        }
    }

    public function testAdminFetchViaServiceForReversalsWithServiceFailure()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('reversals', $entityId, true);

        try
        {
            $payoutServiceAdminFetchMock->fetch('reversals', $entityId, []);

        }
        catch (\Throwable $throwable)
        {
            $this->assertEquals("Service Failure", $throwable->getMessage());
            $this->assertEquals(ErrorCode::BAD_REQUEST_ERROR, $throwable->getCode());

        }
    }

    public function testAdminFetchViaServiceForPayoutLogsWithServiceFailure()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('payout_logs', $entityId, true);

        try
        {
            $payoutServiceAdminFetchMock->fetch('payout_logs', $entityId, []);

        }
        catch (\Throwable $throwable)
        {
            $this->assertEquals("Service Failure", $throwable->getMessage());
            $this->assertEquals(ErrorCode::BAD_REQUEST_ERROR, $throwable->getCode());

        }
    }

    public function testAdminFetchViaServiceForPayoutSourcesWithServiceFailure()
    {
        $entityId = 'Gg7sgBZgvYjlSB';

        $payoutServiceAdminFetchMock = $this->createMockForAdminFetch('payout_sources', $entityId, true);

        try
        {
            $payoutServiceAdminFetchMock->fetch('payout_sources', $entityId, []);

        }
        catch (\Throwable $throwable)
        {
            $this->assertEquals("Service Failure", $throwable->getMessage());
            $this->assertEquals(ErrorCode::BAD_REQUEST_ERROR, $throwable->getCode());

        }
    }

    protected function createMockForAdminFetch($entity, $entityId, $fail = false)
    {
        $payoutServiceAdminFetchMock = Mockery::mock('RZP\Services\PayoutService\AdminFetch',
                                                     [$this->app])->makePartial();

        $expectedUrl = '/admin/' . $entity . '/' . $entityId;

        $payoutServiceAdminFetchMock->shouldReceive('sendRequest')
                                    ->withArgs(
                                        function($request) use ($expectedUrl) {
                                            try
                                            {
                                                if (empty($request['url']) === false)
                                                {
                                                    return (substr($request['url'], -1 * strlen($expectedUrl)) ===
                                                            $expectedUrl);
                                                }

                                                return false;
                                            }
                                            catch (\Throwable $e)
                                            {
                                                return false;
                                            }
                                        })
                                    ->andReturnUsing(
                                        function() use ($entity, $entityId, $fail) {
                                            return $this->adminGetResponseForService($entity, $entityId, $fail);
                                        }
                                    );

        return $payoutServiceAdminFetchMock;
    }

    protected function adminGetResponseForService($entity, $id, $fail = false)
    {
        $response = new \WpOrg\Requests\Response();

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
