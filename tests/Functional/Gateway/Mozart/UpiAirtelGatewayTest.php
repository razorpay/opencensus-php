<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Mail;
use Excel;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Method;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\RuntimeException;
use RZP\Jobs\CorePaymentServiceSync;
use RZP\Gateway\Upi\Base as UpiBase;
use RZP\Excel\Import as ExcelImport;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;


class UpiAirtelGatewayTest extends TestCase
{
    use PaymentTrait;

    use DbEntityFetchTrait;

    protected $payment;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiAirtelGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->setMockGatewayTrue();

        $this->gateway = "upi_mozart";

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_airtel';

        $this->setMockGatewayTrue();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $this->fixtures->on('live')->create('terminal:shared_bank_account_terminal');

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                    ->setConstructorArgs([$this->app])
                    ->onlyMethods(['getTreatment'])
                    ->getMock();

        $this->app->instance('razorx', $razorxMock);
    }

    public function testPayment($status = 'created')
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);

        $this->capturePayment($paymentId, $payment['amount']);

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::TYPE    => UpiBase\Type::COLLECT,
            UpiEntity::ACTION  => 'authorize',
            UpiEntity::GATEWAY => 'upi_airtel',
        ], $upi->toArray());

        return $payment;
    }

    public function testIntentPayment()
    {
        //create Shared Upi-Airtel IntentTerminal
        $this->sharedTerminal = $this->fixtures->create('terminal:sharedUpiAirtelIntentTerminal');

        $this->payment['_']['flow'] = 'intent';

        unset($this->payment['vpa']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $payment['status']);

        $content =  $this->mockServer()->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $payment['status']);

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::TYPE    => UpiBase\Type::PAY,
            UpiEntity::ACTION  => 'authorize',
            UpiEntity::GATEWAY => 'upi_airtel',
        ], $upi->toArray());

    }

    public function testIntentFailedPayment()
    {
        //create Shared Upi-Airtel IntentTerminal
        $this->sharedTerminal = $this->fixtures->create('terminal:sharedUpiAirtelIntentTerminal');

        $this->payment['_']['flow'] = 'intent';

        unset($this->payment['vpa']);

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        // Co Proto must be working
        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('created', $payment['status']);

        $this->mockServerContentFunction(function (& $content, $action)
        {
            if ($action === 'callback')
            {
                $content['code'] = 'PAYMENT_FAILED';

                $content['success'] = false;
            }
        });

        $data = $this->testData[__FUNCTION__];

        //Getting failed payment
        $content = $this->getMockServer()->getFailedAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $this->assertEquals($response, ['success' => false]);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testFailedCallbackResponse($status = 'created')
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, $status);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getFailedAsyncCallbackContent($payment);

        $data = $this->testData[__FUNCTION__];

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals($response, ['success' => false]);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('failed', $payment['status']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->testPayment();

        $this->payment = $this->verifyPayment($payment['id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testPaymentVerifyFailed()
    {
        $payment = $this->testPayment();

        $paymentId = $payment['id'];

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['success'] = false;
            }
        }, 'mozart');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($paymentId)
        {
            $this->verifyPayment($paymentId);
        });

        $payment = $this->getDbLastPayment();

        $this->assertSame($payment['verified'], 0);
    }

    public function testLateAuthorizePayment()
    {
        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        $this->sharedTerminal = $this->fixtures->create('terminal:sharedUpiAirtelIntentTerminal');

        unset($this->payment['description']);
        unset($this->payment['vpa']);
        $this->payment['_']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertSame('created', $payment->getStatus());

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $this->assertNull($payment->getReference16());

        $this->authorizedFailedPayment($payment->getPublicId());

        $payment->reload();

        $this->assertNotNull($payment->getReference16());
        $this->assertTrue($payment->isAuthorized());
        $this->assertTrue($payment->isLateAuthorized());
    }

    public function testRefundPayment()
    {
        $payment = $this->testPayment();

        $this->payment = $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);
    }

    public function testRefundPaymentFileFlow()
    {
        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, 'created');

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, $this->gateway);

        // We should have gotten a successful response
        $this->assertEquals(['success' => true], $response);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertNotNull($payment['acquirer_data']['rrn']);

        $this->capturePayment($paymentId, $payment['amount']);

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::TYPE    => UpiBase\Type::COLLECT,
            UpiEntity::ACTION  => 'authorize',
            UpiEntity::GATEWAY => $this->gateway,
        ], $upi->toArray());

        $this->payment = $this->refundPayment($payment['id']);

        $payment = $this->getDbLastPayment();

        $this->assertEquals('refunded', $payment['status']);

        $refund = $this->getDbLastEntityToArray('refund');

        $upi = $this->getDbLastEntityToArray('upi');

        $this->fixtures->edit('upi', $upi['id'], [Upi::GATEWAY_PAYMENT_ID => 'AIR3urfgi12344']);

        $refundArray[] = $refund;

        $this->setFetchFileBasedRefundsFromScroogeMockResponse($refundArray);

        $data = $this->generateRefundsExcelForAirtelUpi();

        $content = $data['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $time = Carbon::now(Timezone::IST)->format('dmY_Hi');

        $this->assertEquals('file_store', $file['entity']);
        $this->assertEquals('rzp-1415-prod-sftp', $file['bucket']);
        $this->assertEquals('ap-south-1', $file['region']);
        $this->assertEquals('upi/upi_airtel/refund/normal_refund_file/AirtelRefund_' . $time .'.xlsx', $file['location']);
        $this->assertEquals('upi/upi_airtel/refund/normal_refund_file/AirtelRefund_' . $time, $file['name']);

        $refundFileRows = (new ExcelImport)->toArray('storage/files/filestore/'.$file['location'])[0];

        $expectedRefundFile = [
            'org_rrn'                 => $upi['npci_reference_id'],
            'date_and_time'           => Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('m/d/Y'),
            'bank_org_transaction_id' => 'AIR3urfgi12344',
            'org_amount'              => '500',
            'refund_amount'           => '500',
            'refund_status'           => "full",
            'refund_reason'           => "Manual Refund",
            'refund_id'               => $refund['id'],
        ];

        $this->assertArraySelectiveEquals($expectedRefundFile, $refundFileRows[0]);
    }

    /** Generate refunds file for Upi Airtel
     * @param false $date
     * @return mixed
     */
    protected function generateRefundsExcelForAirtelUpi($date = false)
    {
        $this->ba->adminAuth();

        $request = [
            'url'     => '/gateway/files',
            'method'  => 'POST',
            'content' => [
                'type'     => 'refund',
                'targets'  => [$this->gateway],
                'begin'    => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'      => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
        ];

        if ($date === true)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * Tests if the refund callback is handled properly for both the cases
     * - Refund Success
     * - Refund Failure
     *
     * @dataProvider refundCallbackDataProvider
     * @param array $override To override the callback contents
     */
    public function testRefundCallback(array $override)
    {
        $this->testRefundPayment();

        $content = $this->mockServer()->getAsyncRefundCallbackContentForAirtel();

        $response = $this->makeS2sCallbackAndGetContent($content);

        $this->assertEquals(['success' => true], $response);
    }

    public function refundCallbackDataProvider(): array
    {
        $cases = [
            'successCallback'   => [[]],
            'failureCallback'   => [[
                'code'          => '1',
                'errorCode'     => '1210',
                'messageText'   => 'FAILURE',
                'txnStatus'     => 'FAILURE',
                'amount'        => 200,
                'txnRefNo'	    => null,
            ]],
        ];

        return $cases;
    }

    public function testVerifyRefundSuccessfulOnGateway()
    {
        $payment = $this->testPayment();

        $this->payment = $this->refundPayment($payment['id'], 200, ['failed' => true]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount' => $refund['amount']]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $payment = $this->testPayment();

        $this->payment = $this->refundPayment($payment['id'], 200, ['failed' => true]);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount' => $refund['amount']]);

        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['id'], $response['refund_id']);
        $this->assertEquals('processed', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

    }

    public function testCpsGatewayEntitySync()
    {
        $payment = $this->fixtures->create('payment:status_created');
        $gatewayData = [
            'mode'                => 'test',
            'timestamp'           => 294832,
            'payment_id'          => $payment->getId(),
            'gateway'             => 'upi_airtel',
            'input'               => [
                'payment'  => [
                    'id'       => $payment->getId(),
                    'amount'   => 100,
                    'currency' => 'INR',
                    'gateway'  => 'upi_airtel',
                ],
                'terminal' => [

                ],
                'action'   => 'authorize',
            ],
            'gateway_transaction' => [
                "amount"=>100,
                "code"=>"0",
                "errorCode"=>"000",
                "expiry_time"=>5,
                "hash"=>"85eda95c91bd5ca7a573b232d18375b130afdadd500af1fd3d51e3d5c206df858cf7c025d18173782a486597b6a84133e1dee5721a7c6d95869c8f3014bb66f3",
                "hdnOrderID"=>"DR9fyiL6mQoHGB",
                "message"=>"Success",
                "payment_id"=>$payment->getId(),
                "rrn"=>"928016135785",
                "status"=>"authorization_inititated",
                "type"=>"collect",
                "vpa"=>"razorpay@mairtel",
                "acquirer"=>[
                    "gateway_reference_id1"=>null,
                    "rrn"=>"928016135785",
                    "vpa"=>"7829250063@upi"
                ],
                "mid"=>"MER0000000548542",
                "paymentId"=>$payment->getId(),
                "txnStatus"=>"SUCCESS",
            ],
        ];
        $cpsSync = new CorePaymentServiceSync($gatewayData);
        $cpsSync->handle();
        $mozart = $this->getLastEntity('mozart', true);
        $this->assertEquals($mozart['action'], 'authorize');
        $this->assertEquals($mozart['gateway'], 'upi_airtel');
        $this->assertEquals($mozart['payment_id'], $payment->getId());
        $this->assertArraySelectiveEquals(json_decode($mozart['raw'], true), $gatewayData['gateway_transaction']);
        $this->assertArraySelectiveEquals($mozart['data'], $gatewayData['gateway_transaction']);
    }

    protected function checkPaymentStatus($id, $expectedStatus)
    {
        $response = $this->getPaymentStatus($id);

        $status = $response['status'];

        $this->assertEquals($expectedStatus, $status);
    }

    public function testUnexpectedPaymentSuccess()
    {
        $content = $this->mockServer()->getUnexpectedAsyncCallbackContentForAirtel();

        $this->makeS2SCallbackAndGetContent($content);

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                  => $paymentEntity['merchant_id'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testVerifyPaymentAmountMismatch()
    {
        $payment = $this->testPayment();
        $paymentId = $payment['id'];

        // Original payment amount is 500.00. Mocking amount mismatch
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['data']['amount'] = 11111;
            }
        }, 'mozart');

        $this->makeRequestAndCatchException(
            function() use ($paymentId)
            {
                $this->verifyPayment($paymentId);
            },
            RuntimeException::class,
            'Payment amount verification failed.'
        );
    }

    public function testVerifyLateAuthWithCorrectAmount()
    {
        $payment = $this->testPayment();
        $paymentId = $payment['id'];

        $this->verifyPayment($paymentId);
        // Fetch the last payment
        $payment = $this->getLastEntity('payment', true);

        // Payment should be captured
        $this->assertEquals('captured', $payment['status']);
    }

    /**
     * Test for collect payment success with pre_process action.
     */
    public function testCollectPaymentWithPreProcess()
    {
        $this->app->razorx
        ->method('getTreatment')
        ->will($this->returnCallback(
            function ($mid, $feature, $mode)
            {
                if ($feature === 'api_upi_airtel_pre_process_v1')
                {
                    return 'upi_airtel';
                }

                return 'control';
            })
        );

        $this->testPayment();
    }

    /**
     * Test for collect payment failure with pre_process action.
     */
    public function testCollectPaymentFailureWithPreProcess()
    {
        $this->app->razorx
        ->method('getTreatment')
        ->will($this->returnCallback(
            function ($mid, $feature, $mode)
            {
                if ($feature === 'api_upi_airtel_pre_process_v1')
                {
                    return 'upi_airtel';
                }

                return 'control';
            })
        );

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $paymentId = $response['payment_id'];

        // Co Proto must be working
        $this->assertEquals('async', $response['type']);

        $this->checkPaymentStatus($paymentId, 'created');

        $payment = $this->getEntityById('payment', $paymentId, true);

        $payment['description'] = 'payment_failed';

        $content = $this->mockServer()->getAsyncCallbackContent($payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals($response, ['success' => false]);

        // The payment should now be authorized
        $payment = $this->getEntityById('payment', $paymentId, true);
        $this->assertEquals('failed', $payment['status']);

        $upi = $this->getDbLastUpi();

        $this->assertArraySubset([
            UpiEntity::TYPE                 => Flow::COLLECT,
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::GATEWAY              => $this->gateway,
            UpiEntity::VPA                  => $payment['vpa'],
            UpiEntity::STATUS_CODE          => 'U30',
        ], $upi->toArray());
    }

    /**
     * Resolves npci_reference_id of Upi entity from mozart entity if empty.
     */
    public function testResolveUpiRrnUsingMozartEntity()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'authorized_at'     => time(),
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $rrn = '22712135190';

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id'    => $payment['id'],
                'action'        => 'authorize',
                'gateway'       => 'upi_airtel',
                'amount'        => $payment['amount'],
                'raw'           => json_encode(
                    [
                        'rrn'   => $rrn,
                    ]
                )
            )
        );

        $gatewayEntity = $this->fixtures->create('upi', [
            'payment_id' => $payment->getId(),
            'action' => 'authorize',
            'gateway' => 'upi_airtel',
            'npci_reference_id' => '',
        ]);

        $this->assertEmpty($gatewayEntity->getNpciReferenceId());

        $upi = $this->app['repo']->upi->findByPaymentIdAndActionOrFail($gatewayEntity->getPaymentId(), 'authorize');

        $this->assertArraySubset([
            UpiEntity::ACTION  => 'authorize',
            UpiEntity::GATEWAY => 'upi_airtel',
            UpiEntity::NPCI_REFERENCE_ID => $rrn,
        ], $upi->toArray());

        return $payment;
    }
}
