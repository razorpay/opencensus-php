<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MindgateOtmTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/MindgateOtmTestData.php';

        parent::setUp();

        $this->gateway = 'mozart';

        $this->fixtures->create('terminal:shared_otm_mindgate_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getOtmInitialPaymentArray();

        $this->setMockGatewayTrue();
    }

    public function testMandateCreate()
    {
        $this->initiateMandateCreate();

        $payment = $this->getLastEntity('payment', true);

        $this->assertTestResponse($payment, 'testPayment');
        $this->assertEquals('100MgateOTMTml', $payment['terminal_id']);

        $mozartEntity = $this->getLastEntity('mozart', true);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals(Token\RecurringStatus::INITIATED, $token[Token\Entity::RECURRING_STATUS]);

        $this->assertNotNull($token['start_time']);

        $this->assertTestResponse($mozartEntity, 'testPaymentMozartEntity');

        $payment = $this->getDbLastPayment();

        $this->mandateCreateCallback($payment);

        $updatedToken = $this->getLastEntity('token', true);

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $updatedToken[Token\Entity::RECURRING_STATUS]);

        $this->assertNotNull($updatedToken['start_time']);

        $gatewayToken = $this->getLastEntity('gateway_token', true);

        $gatewayEntity = $this->getLastEntity('mozart', true);

        $this->assertEquals($gatewayToken['terminal_id'], $payment['terminal_id']);
    }

    public function testMandateCreateFailed()
    {
        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'auth_init')
            {
                $content['success'] = false;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->initiateMandateCreate();
        });

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('100MgateOTMTml', $payment['terminal_id']);

        $token = $this->getLastEntity('token', true);

        $this->assertEquals($payment[Payment\Entity::TOKEN_ID], $token[Token\Entity::ID]);

        $this->assertEquals('failed', $payment['status']);
    }

    public function testMandateExecute()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $payment = $this->payment;

        $token = $this->getLastEntity('token', true);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $payment['amount'] = 4000;

        $this->doS2SRecurringPayment($payment);

        $updatedToken = $this->getLastEntity('token', true);

        $this->assertEquals(Token\RecurringStatus::PAID, $updatedToken[Token\Entity::RECURRING_STATUS]);

        $secondPayment = $this->getLastEntity('payment', true);

        $this->assertEquals('authorized', $secondPayment['status']);
    }

    public function testMandateExecuteAmountGreaterThanMaxTokenAmount()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $payment = $this->payment;

        $token = $this->getLastEntity('token', true);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $data = $this->testData[__FUNCTION__];

        $payment['amount'] = 5000;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });
    }

    public function testMandateExecuteBeforeStartTime()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $payment = $this->payment;

        $token = $this->getLastEntity('token', true);

        $this->fixtures->edit(
            'token',
            $token['id'],
            [
                Token\Entity::START_TIME => Carbon::now()->addDays(2)->getTimestamp(),
            ]);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $data = $this->testData[__FUNCTION__];

        $payment['amount'] = 4000;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });
    }

    public function testMandateExecuteForTokenNotConfirmed()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $payment = $this->payment;

        $token = $this->getLastEntity('token', true);

        $this->fixtures->edit(
            'token',
            $token['id'],
            [
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::INITIATED
            ]);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $data = $this->testData[__FUNCTION__];

        $payment['amount'] = 4000;

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });
    }

    public function testMandateExecuteFailed()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $payment = $this->payment;

        $token = $this->getLastEntity('token', true);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'pay_init')
            {
                $content['success'] = false;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $payment['amount'] = 4000;

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });
    }

    public function testMandateExecuteSameTokenTwice()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $payment = $this->payment;

        $token = $this->getLastEntity('token', true);

        $payment[Payment\Entity::TOKEN] = $token['id'];

        $payment['amount'] = 4000;

        $this->doS2SRecurringPayment($payment);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doS2SRecurringPayment($payment);
            });
    }

    public function testMandateUpdate()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $token = $this->getLastEntity('token', true);

        $this->updateCustomerToken($token);

        $payment = $this->getDbLastPayment();

        $this->mandateUpdateCallback($payment);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(1893456000, $token['start_time']);

        $this->assertEquals(70000, $token->getMaxAmount());
    }

    public function testMandateUpdateNotConfirmedToken()
    {
        $this->initiateMandateCreate();

        $token = $this->getLastEntity('token', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($token)
            {
                $this->updateCustomerToken($token);
            });
    }

    public function testMandateUpdateExpiredToken()
    {
        $this->initiateMandateCreate();

        $firstPayment = $this->getDbLastPayment();

        $this->mandateCreateCallback($firstPayment);

        $token = $this->getLastEntity('token', true);

        $this->fixtures->edit(
            'token',
            $token['id'],
            [
                Token\Entity::EXPIRED_AT => Carbon::now()->subDays(2)->getTimestamp(),
            ]);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($token)
            {
                $this->updateCustomerToken($token);
            });
    }

    protected function updateCustomerToken($token)
    {
        $customerId = 'cust_' . $token[Token\Entity::CUSTOMER_ID];

        $this->ba->privateAuth();

        $tokenId = $token['id'];

        $request = [
            'method'  => 'PUT',
            'url'     => '/customers/'.$customerId.'/tokens/'. $tokenId,
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function initiateMandateCreate()
    {
        $response = $this->doAuthPayment($this->payment);

        return $response;
    }

    protected function mandateCreateCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');
    }

    protected function mandateUpdateCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseMandateUpdate($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_mindgate');
    }
}
