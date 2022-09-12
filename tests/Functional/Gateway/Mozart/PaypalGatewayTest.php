<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Services\SplitzService;
use Mockery;

class PaypalGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/PaypalGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_paypal_terminal', ['currency' => ['USD','INR']]);

        $this->fixtures->merchant->enableInternational();

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->enableWallet('10000000000000', 'paypal');

        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 0]);

        $this->payment = $this->getDefaultWalletPaymentArray('paypal');
        $this->payment['currency'] = 'USD';

    }

    public function testPayment()
    {
        $payment = $this->payment;

        $this->disablePayPalMigrationExperiment();
        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('1ShrdPaypalTml', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');

        return $payment;
    }

    public function testInternationalPayment()
    {
        $payment = $this->payment;

        $payment['contact'] = '491761552902';

        $this->disablePayPalMigrationExperiment();
        $payment = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testInternationalPayment');
        $this->assertEquals('1ShrdPaypalTml', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');

        return $payment;
    }

    public function testRefundPayment()
    {
        $this->disablePayPalMigrationExperiment();
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->payment = $this->refundPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('refunded', $payment['status']);
    }

    public function testVerifyPayment()
    {
        $payment = $this->payment;
        $this->disablePayPalMigrationExperiment();
        $authPayment = $this->doAuthPayment($payment);

        $this->payment = $this->verifyPayment($authPayment['razorpay_payment_id']);

        $this->assertSame($this->payment['payment']['verified'], 1);
    }

    public function testPaymentIdMismatch()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'auth_verify')
            {
                $content['data']['paymentId'] = 'Hacked'; //some random payment_id
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->disablePayPalMigrationExperiment();
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals('failed', $paymentEntity['status']);
    }

    public function testAuthFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'auth_verify')
            {
                $content['success'] = false;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->disablePayPalMigrationExperiment();
            $this->doPaypalAuthAndCapturePayment();
        });
    }

    protected function runPaymentCallbackFlowWalletPaypal($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response,$callback);

        $data = $this->makeFirstGatewayPaymentMockRequest($url, $method, $content);

        return $this->submitPaymentCallbackRequest($data);
    }

    public function testVerifyRefundFailedOnGateway()
    {
        $this->disablePayPalMigrationExperiment();
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'refund')
            {
                $content = [
                    'data' =>
                        [
                            'id' => '09188073PT4749456',
                            'errorCode' => '000',
                            'links' => [
                                [
                                    "href" => "https:debug/debug_id",
                                    "method" => "GET",
                                    "rel" => "self",
                                ]
                            ],
                            'status' => 'FAILURE',
                            '_raw' => '{1234567890qwertyuiopasdfghjklzxcvbnm}',
                        ],
                    'error'             => null,
                    'success'           => false,
                    'mozart_id'         => 'DUMMY_MOZART_ID',
                    'external_trace_id' => 'DUMMY_REQUEST_ID',
                ];
            }
        });

        $this->payment = $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals('created', $refund['status']);
        $this->assertEquals(1, $refund['attempts']);

        $this->clearMockFunction();

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_refund')
            {
                $content = [
                    'data' =>
                        [
                            'id' => '0',
                            "amount"=> [
                                "currency_code" => "USD",
                                "value" => 10000,
                            ],
                            "create_time" => "2019-08-01T02:25:08-07:00",
                            "invoice_id" => "25din",
                            "custom_id" => "25din",
                            "links" => [
                                [
                                    "href" => "https://refund/payment",
                                    "method" => "GET",
                                    "rel" => "self",
                                ],
                                [
                                    "href" => "https://refund/payment/status",
                                    "method" => "GET",
                                    "rel" => "up",
                                ]
                            ],
                            "seller_payable_breakdown" => [
                                "gross_amount" => [
                                    "currency_code" => "USD",
                                    "value" => "100",
                                ],
                                "net_amount" => [
                                    "currency_code" => "USD",
                                    "value" => "96",
                                ],
                                "paypal_fee" => [
                                    "currency_code" => "USD",
                                    "value" => "4",
                                ],
                                "total_refunded_amount" => [
                                    "currency_code" => "USD",
                                    "value" => "100",
                                ]
                            ],
                            'errorCode' => 000,
                            "update_time" => "2019-08-01T02:25:08-07:00",
                            'status' => 'SUCCESS',
                            '_raw' => '{qazwsxedcrfvtgbyhnujmikolp}',
                        ],
                    'error'             => null,
                    'success'           => false,
                    'mozart_id'         => '',
                    'external_trace_id' => '',
                ];

            }
        });

        $response = $this->retryFailedRefund($refund['id'], $refund['payment_id'], [], ['amount' => $refund['amount']],'wallet_paypal');


        $refund = $this->getEntityById('refund', $refund['id'], true);

        $this->assertEquals($refund['id'], $response['refund_id']);
       // Since scrooge is calling mozart directly without going via api, the refund row entity is not present in Mozart. Hence for wallet_paypal these changes are made in the test
        $this->assertEquals('created', $response['status']);
        $this->assertEquals(1, $refund['attempts']);

    }

    protected function doPaypalAuthAndCapturePayment()
    {
        $payment = $this->getDefaultWalletPaymentArray('paypal');
        $payment['currency'] = "USD";
        $this->doAuthAndCapturePayment($payment,$payment['amount'],$payment['currency']);
    }

    protected function disablePayPalMigrationExperiment(){
        $output = [
            "response" => [
                "variant" => [
                    "name" =>'',
                ]
            ]
        ];

        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->atLeast()
            ->once()
            ->andReturn($output);
    }
}
