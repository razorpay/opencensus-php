<?php

namespace Functional\Payout;

use Requests_Response;

use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Services\PayoutService\Create as PayoutServiceCreate;
use RZP\Services\PayoutService\Status as PayoutServiceStatus;
use RZP\Services\PayoutService\Details as PayoutServiceDetails;

class PayoutServiceTest extends TestCase
{
    use PayoutTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutServiceTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_SERVICE_ENABLED]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function mockPayoutServiceCreate($fail = false)
    {
        $payoutServiceCreateMock = $this->getMockBuilder(PayoutServiceCreate::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(PayoutServiceCreate::PAYOUT_SERVICE_CREATE, $payoutServiceCreateMock);

        $this->app->payout_service_create->method('sendRequest')
            ->willReturn($this->createResponseForPayoutServiceMock($fail));
    }

    public function mockPayoutServiceStatus($status, $fail = false)
    {
        $payoutServiceStatusMock = $this->getMockBuilder(PayoutServiceStatus::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(PayoutServiceStatus::PAYOUT_SERVICE_STATUS, $payoutServiceStatusMock);

        $this->app->payout_service_status->method('sendRequest')
            ->willReturn($this->createResponseForPayoutServiceMock($fail, $status));
    }

    public function mockPayoutServiceDetails($fail = false)
    {
        $payoutServiceDetailsMock = $this->getMockBuilder(PayoutServiceDetails::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance(PayoutServiceDetails::PAYOUT_SERVICE_DETAIL, $payoutServiceDetailsMock);

        $this->app->payout_service_detail->method('sendRequest')
            ->willReturn($this->createResponseForPayoutServiceMock($fail));
    }

    public function createResponseForPayoutServiceMock($fail, $status = 'created')
    {
        $response = new Requests_Response();

        if ($fail === true)
        {
            $response->body = json_encode(
                [
                    "error"   =>
                        [
                            "code"        => ErrorCode::BAD_REQUEST_ERROR,
                            "description" => "Service Failure",
                            "field"      => null
                        ]
                ]);
            $response->status_code = 400;
            $response->success = true;
        }
        else
        {
            $response->body = json_encode(
                [
                    "id"                =>   "pout_Gg7sgBZgvYjlSB",
                    "entity"            =>   "payout",
                    "fund_account_id"   =>   "fa_100000000000fa",
                    "amount"            =>   100,
                    "currency"          =>   "INR",
                    "merchant_id"       =>   "10000000000000",
                    "notes"             =>   "",
                    "fees"              =>   0,
                    "tax"               =>   0,
                    "status"            =>   $status,
                    "purpose"           =>   "refund",
                    "utr"               =>   "",
                    "reference_id"      =>   null,
                    "narration"         =>   "test Merchant Fund Transfer",
                    "batch_id"          =>   "",
                    "initiated_at"      =>   1614325830,
                    "failure_reason"    =>   null,
                    "created_at"        =>   1614325826,
                    "fee_type"          =>   null
                ]);
            $response->status_code = 200;
            $response->success = true;
        }

        return $response;
    }

    // Check payout Create Entry func on processor base
    public function testCreatePayoutEntry($mode = 'IMPS')
    {
        $this->ba->appAuth();

        $balance = $this->getDbEntities('balance',
            [
                'account_number'   => '2224440041626905',
            ])->first();

        $this->testData[__FUNCTION__]['request']['content']['balance_id'] = $balance->getId();
        $this->testData[__FUNCTION__]['request']['content']['mode'] = $mode;

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $this->assertEquals($payout['id'], 'pout_Gg7sgBZgvYjlSB');
    }

    // Check payout Create transaction func on processor base
    public function testCreatePayoutServiceTransaction($mode = 'IMPS')
    {
        $this->testCreatePayoutEntry($mode);

        $this->ba->appAuth();

        $response = $this->startTest();

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals("txn_" . $response['transaction_id'], $txn['id']);
    }

    // Check payout Create fta func on processor base
    public function testCreatePayoutServiceFtaCreation($mode = 'IMPS')
    {
        $this->testCreatePayoutServiceTransaction($mode);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testCreatePayout(): array
    {
        $this->mockPayoutServiceCreate();

        $this->testCreatePayoutServiceFtaCreation();

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('ba_1000000lcustba', 'ba_' . $payoutAttempt['bank_account_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA5",
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }

    public function testCreatePayoutServiceFailure()
    {
        $this->mockPayoutServiceCreate(true);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateReversalEntry()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => $payout->getId(),
            'utr'        => '123456678',
            'amount'     => $payout['amount'],
            'currency'   => $payout['currency'],
            'channel'    => $payout['channel'],
        ];

        $this->ba->appAuth();

        $response = $this->startTest();

        $this->assertNotEmpty($response['transaction_id']);
    }

    public function testCreateReversalEntryDuplicateCreationNotAllowed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $testData = $this->testData['testCreateReversalEntry'];

        $testData['request']['content'] = [
            'id'         => 'Gg7sgBZgvYjlSk',
            'payout_id'  => $payout->getId(),
            'utr'        => '123456678',
            'amount'     => $payout['amount'],
            'currency'   => $payout['currency'],
            'channel'    => $payout['channel'],
        ];

        $this->ba->appAuth();

        $response1 = $this->startTest($testData);

        $this->assertNotEmpty($response1['transaction_id']);

        $this->ba->appAuth();

        $response2 = $this->startTest($testData);

        $this->assertEquals($response1['transaction_id'], $response2['transaction_id']);
    }

    public function testUpdateFTAAndPayoutProcessed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('processed');

        $this->ba->appAuth();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ])->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('processed', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutToFailed()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('failed');

        $this->ba->appAuth();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ])->first();

        // Assert that fta status didn't update
        $this->assertEquals('failed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('reversed', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutDetailsFailure()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails(true);

        $this->ba->appAuth();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ])->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('created', $payout->getStatus());
    }

    public function testUpdateFTAAndPayoutStatusFailure()
    {
        $this->testCreatePayout();

        $payout = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['content']['source_id'] = $payout->getId();

        $this->mockPayoutServiceDetails();

        $this->mockPayoutServiceStatus('processed', true);

        $this->ba->appAuth();

        $this->startTest();

        // Assert that payout status didn't update
        $this->assertEquals('created', $payout->getStatus());

        $ftaForPayout = $this->getDbEntities('fund_transfer_attempt',
            [
                'source_id'   => $payout->getId(),
                'source_type' => 'payout',
            ])->first();

        // Assert that fta status didn't update
        $this->assertEquals('processed', $ftaForPayout->getStatus());

        $payout->reload();

        $this->assertEquals('created', $payout->getStatus());
    }

    public function testCreatePayoutForCard(): array
    {
        $this->fixtures->edit(
            'fund_account',
            '100000000000fa',
            [
                'account_type' => 'card',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_id'   => '100000000lcard',
                'active'       => 1,
            ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->mockPayoutServiceCreate();

        $this->testCreatePayoutServiceFtaCreation('NEFT');

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        // On private auth, payout.user_id should be null
        $this->assertNull($payout['user_id']);

        // Verify attempt entity
        $this->assertEquals($payout['id'], $payoutAttempt['source']);
        $this->assertEquals($payout['merchant_id'], $payoutAttempt['merchant_id']);
        $this->assertEquals('card_100000000lcard', 'card_' . $payoutAttempt['card_id']);
        $this->assertEquals($payout['channel'], 'yesbank');

        // Verify transaction entity
        $txn = $this->getLastEntity('transaction', true);
        $txnId = str_after($txn['id'], 'txn_');

        $this->assertEquals($payout['transaction_id'], $txn['id']);
        $this->assertNotNull($txn['balance_id']);
        $this->assertNotNull($txn['posted_at']);

        $feesSplit = $this->getEntities('fee_breakup', ['transaction_id' => $txnId], true);

        $expectedBreakup = [
            'name'            => "payout",
            'transaction_id'  => $txnId,
            'pricing_rule_id' => "Bbg7cl6t6I3XA5",
            'percentage'      => null,
            'amount'          => 500,
        ];

        $this->assertArraySelectiveEquals($expectedBreakup, $feesSplit['items'][1]);

        return $payout;
    }
}
