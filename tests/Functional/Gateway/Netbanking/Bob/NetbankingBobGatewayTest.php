<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Bob;

use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Gateway\Netbanking\Bob\Status;
use RZP\Gateway\Netbanking\Bob\ResponseFields;
use RZP\Jobs\CorePaymentServiceSync;
use RZP\Models\Payment;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

use Carbon\Carbon;
use Mail;
use RZP\Constants\Timezone;

class NetbankingBobGatewayTest extends TestCase
{
    use PaymentTrait;

    const CUSTOMER_ACCOUNT_NUMBER = '10430200000843';

    protected $bank = null;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingBobGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_bob';

        $this->bank = 'BARB_R';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_bob_terminal');

        $this->fixtures->create(
            'feature',
            [
                'name'          => 'corporate_banks',
                'entity_id'     => '10000000000000',
                'entity_type'   => 'merchant'
            ]
        );

        $this->markTestSkipped('this flow is deprecated and is moved to nbplus service');
    }

    public function testPayment()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentNetbankingEntity');
    }

    public function testPaymentOnCorporate()
    {
        $payment = $this->payment;

        $payment['bank'] = 'BARB_C';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentOnCorporateNetbankingEntity');
    }

    public function testAuthorizationFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });

        // Assert that we don't save any information into the netbanking entity
        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentFailedNetbankingEntity');
    }

    public function testCpsGatewayEntitySync()
    {
        $payment = $this->fixtures->create('payment:status_created');

        $gatewayData = [
            'mode'       => 'test',
            'timestamp'  => 294832,
            'payment_id' => $payment->getId(),
            'gateway'    => 'netbanking_bob',
            'input'      => [
                'payment'   => [
                    'id'        => $payment->getId(),
                    'amount'    => 500000,
                    "currency"  => "21180100010529",
                    "gateway"   => "108114903",
                    'bank'      =>'BARB_R',
                ],
                'terminal'      => [
                    'gateway_merchant_id' => '123456',
                ],
                'action'   => 'authorize',
            ],
            'gateway_transaction'       => [
                'bank'      =>'BARB_R',
                'gateway_merchant_id' => '123456',
                'payment_id'        => $payment->getId(),
                'paymentId'         => $payment->getId(),
                "bank_payment_id"   => "108114903",
                "account_number"    => "21180100010529",
                'currency'          => "INR",
                'amount'            => 50000,
                'status'            => 'created',
            ],
        ];

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $netbankingEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($netbankingEntity['status'], 'created');

        $gatewayData['gateway_transaction']['status'] = 'authorize';

        $cpsSync = new CorePaymentServiceSync($gatewayData);

        $cpsSync->handle();

        $netbankingEntity = $this->getLastEntity('netbanking', true);

        $this->assertEquals($netbankingEntity['status'], 'authorize');
    }


    public function testUserCancelledPayments()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockFailedCallbackResponse();

        $this->mockCancelledPaymentResponse();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });
    }

    public function testPaymentAmountMismatch()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockAmountMismatch();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthAndCapturePayment($this->payment);
            });
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testPaymentFailedVerifyFailed()
    {
        $this->testAuthorizationFailure();

        $payment = $this->getLastEntity('payment', true);

        $this->mockVerifyResponse();

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifyFailedEntity');
    }

    public function testPaymentFailedVerifyFailedSingleCharResp()
    {
        $this->testAuthorizationFailure();

        $payment = $this->getLastEntity('payment', true);

        $this->mockVerifyResponseAsSingleChar();

        $this->verifyPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedVerifyFailedEntity');
    }

    public function testAuthorizeFailedPayment()
    {
        $this->testAuthorizationFailure();

        $payment = $this->getLastEntity('payment', true);

        $this->assertNull($payment['reference1']);

        $this->authorizeFailedPayment($payment['id']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['reference1']);

        $this->assertTestResponse($gatewayPayment, 'testAuthFailedEntity');
    }

    protected function mockFailedCallbackResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::STATUS] = Status::FAILURE;
                unset($content[ResponseFields::BANK_REF_NUMBER]);
            }
        });
    }

    protected function mockCancelledPaymentResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'cancelPayment')
            {
                $content['request']['method'] = 'get';
                $content['request']['url'] = $content['request']['url'] . http_build_query($content['content']);
                unset($content['request']['content']);
            }
        });
    }

    protected function mockVerifyResponse()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'verify')
            {
             $content[ResponseFields::STATUS] = status::FAILURE;
            }
        });
    }

    protected function mockVerifyResponseAsSingleChar()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'verifyafterquerybuild')
            {
                $content = status::FAILURE;
            }
        });
    }


    protected function mockAmountMismatch()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::STATUS] = Status::SUCCESS;
                $content[ResponseFields::AMOUNT] = '10.00';
            }
        });
    }

    // In reconciliation, we get more details and add it to the gateway entities.
    // Here we manually update these values with hardcoded ones to emulate this behaviour
    protected function updateAccountDetailsInNetbankingEntity(array $payments)
    {
        foreach ($payments as $payment)
        {
            $paymentId = Payment\Entity::verifyIdAndStripSign($payment);

            $netbankingEntity = $this->getEntities('netbanking', ['payment_id' => $paymentId], true);

            $this->fixtures->base->editEntity(
                'netbanking',
                $netbankingEntity['items'][0]['id'],
                [NetbankingEntity::ACCOUNT_NUMBER => self::CUSTOMER_ACCOUNT_NUMBER]
            );
        }
    }
}
