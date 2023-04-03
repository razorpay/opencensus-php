<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Sbi;

use Mail;
use Mockery;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment\Entity;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Sbi\Status;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Netbanking\Sbi\RequestFields;
use RZP\Gateway\Netbanking\Sbi\ResponseFields;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingSbiGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingSbiGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_sbi';

        $this->bank = 'SBIN';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function makePayment($bank)
    {
        $this->doNetbankingSbiAuthAndCapturePayment($bank);

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertEquals($bank, $paymentEntity['bank']);

        $this->assertEquals('1234', $paymentEntity[Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $this->assertArraySelectiveEquals($this->testData['testPayment'], $paymentEntity);
    }

    public function testPaymentForSbiAndSubsidiaryBanks()
    {
        $this->makePayment("SBIN");
        $this->makePayment("SBBJ");
        $this->makePayment("SBHY");
        $this->makePayment("SBMY");
        $this->makePayment("STBP");
        $this->makePayment("SBTR");
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:sbi_tpv_terminal');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTpv();

        $data = $this->testData[__FUNCTION__];

        $order = $this->startTest();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];

        $this->doAuthPayment($this->payment);;

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->assertEquals('authorized',$payment['status']);

        $this->fixtures->merchant->disableTPV();

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals($data['request']['content'], $order);
    }

    public function testTamperedAmount()
    {
        $this->markTestSkipped();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[RequestFields::AMOUNT] = '50';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testPaymentIdMismatch()
    {
        $this->markTestSkipped();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[RequestFields::REF_NO] = 'ABCD1234567890'; //some random payment_id
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testChecksumValidationFailed()
    {
        $this->markTestSkipped();

        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content = '1234';
            }
        });

        $this->runRequestResponseFlow($data, function()
        {
            $this->doNetbankingSbiAuthAndCapturePayment();
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testAuthFailed()
    {
        $this->markTestSkipped();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::STATUS]      = 'Failed';
                $content[ResponseFields::STATUS_DESC] = 'failed at bank end';
                $content[ResponseFields::BANK_REF_NO] = null;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doNetbankingSbiAuthAndCapturePayment();
        });

        $netbankingEntity = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentFailedNetbankingEntity'], $netbankingEntity);
    }

    public function testAuthInvalidStatus()
    {
        $this->markTestSkipped();

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::STATUS] = 'invalid';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doNetbankingSbiAuthAndCapturePayment();
        });
    }

    public function testAuthFailedVerifySuccess()
    {
        $this->markTestSkipped();

        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment');

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::STATUS] = Status::SUCCESS;
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthSuccessVerifyFailed()
    {
        $this->markTestSkipped();

        $data = $this->testData[__FUNCTION__];

        $this->makePayment($this->bank);

        $payment = $this->getLastEntity('payment');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::STATUS] = 'Failed';
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $gatewayPayment = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertTestResponse($gatewayPayment, 'testAuthSuccessVerifyFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);
    }

    public function testVerifyInvalidResponse()
    {
        $this->makePayment($this->bank);

        $payment = $this->getLastEntity('payment', true);

        $this->nbPlusService->shouldReceive('content')->andReturnUsing(function(& $content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'RUNTIME',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'SERVER_ERROR_RUNTIME_ERROR',
                        'gateway_error_code'                            =>  '',
                        'gateway_error_description'                     =>  '',
                    ]
                ],
            ];
        });

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        }, LogicException::class);

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentErrorPaymentEntity');
    }

    public function testForceAuthorizePayment()
    {
        $this->markTestSkipped();

        $testData = $this->testData['testAuthFailed'];

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::STATUS] = 'Failed';
            }
        });

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doNetbankingSbiAuthAndCapturePayment();
        });

        $payment = $this->getLastEntity('payment', true);

        $content = $this->forceAuthorizeFailedPayment($payment['id'], ['gateway_payment_id' => 100]);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment['bank_payment_id'], 100);
    }

    public function testAuthorizeFailedPayment()
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing(function(& $content, $action = null)
        {
            $content = [
                NbPlusPaymentService\Response::RESPONSE => null,
                NbPlusPaymentService\Response::ERROR => [
                    NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                    NbPlusPaymentService\Error::CAUSE => [
                        NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED',
                        'gateway_error_code'                            =>  '',
                        'gateway_error_description'                     =>  '',
                    ]
                ],
            ];
        });

        $this->makeRequestAndCatchException(function ()
        {
            $this->doNetbankingSbiAuthAndCapturePayment();
        }, GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $this->assertEmpty($payment['transaction_id']);

        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        $content['payment']['id'] = substr($payment['id'],4);

        $content['meta']['force_auth_payment'] = true;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference1']);
    }

    /**
     * Validate negative case of authorizing succesfulpayment
     */
    public function testForceAuthorizeSucessfulPayment()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('captured', $payment['status']);

        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        $content['payment']['id'] = substr($payment['id'],4);

        $content['meta']['force_auth_payment'] = true;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/nbplus/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        unset($content['payment']['amount']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/nbplus/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    /**
     * Authorize the failed payment by verifying at gateway
     */
    public function testVerifyAuthorizeFailedPayment()
    {
        $this->nbPlusService->shouldReceive('content')->andReturnUsing(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE   =>  'BAD_REQUEST_PAYMENT_FAILED',
                            'gateway_error_code'                            =>  '',
                            'gateway_error_description'                     =>  '',
                        ]
                    ],
                ];
            }
        });

        $this->makeRequestAndCatchException(function ()
        {
            $this->doNetbankingSbiAuthAndCapturePayment();
        }, GatewayErrorException::class);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('failed', $payment['status']);

        $content = $this->getDefaultNetbankingAuthorizeFailedPaymentArray();

        $content['payment']['id'] = substr($payment['id'], 4);

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference1']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }


    protected function makeAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/nbplus/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function doNetbankingSbiAuthAndCapturePayment($bank = "SBIN")
    {
        $payment = $this->getDefaultNetbankingPaymentArray($bank);

        $payment = $this->doAuthAndCapturePayment($payment);
    }
}
