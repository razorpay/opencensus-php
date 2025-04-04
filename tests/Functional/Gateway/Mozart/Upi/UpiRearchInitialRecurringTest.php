<?php

namespace RZP\Tests\Functional\Gateway\Mozart;

use RZP\Models\Payment;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Exception\GatewayErrorException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiRearchInitialRecurringTest extends TestCase
{
    use MocksSplitz;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;
    use PaymentsUpiRecurringTrait;

    protected $payment;

    /**
     * @var Terminal\Entity
     */
    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->mockSplitzTreatmentForAutopayRearch('variant_on');
    }

    public function testRecurringAuthenticateSuccess()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['description'] = 'authenticate_success';

        $this->payment['customer_id'] = 'cust_100000customer';

        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertEquals('authenticate_initiated', $upiMetadata['internal_status']);

        $this->assertEquals('created', $upiMandate['status']);

        $this->assertEquals('4', $payment['cps_route']);

        $payment->reload();

        $upiMandate->reload();

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
        ], $upiMandate->toArray());
    }

    public function testRecurringAuthenticateFailed()
    {
        $orderId = $this->createUpiRecurringOrder();

        $this->payment['order_id'] = $orderId;

        $this->payment['description'] = 'authenticate_failure';

        $this->payment['customer_id'] = 'cust_100000customer';

        $payment = $this->payment;

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            GatewayErrorException::class,
            "Something went wrong, please try again after sometime.\nGateway Error Code: \nGateway Error Desc: ");

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $this->assertEquals('pending_for_authenticate', $upiMetadata['internal_status']);

        $this->assertEquals('created', $upiMandate['status']);

        $this->assertEquals(Payment\Entity::UPI_PAYMENT_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $payment->reload();

        $upiMandate->reload();

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($orderId, 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
        ], $upiMandate->toArray());

        $this->assertArraySubset(
            [
                Payment\Entity::STATUS              => 'failed',
                Payment\Entity::GATEWAY             => 'upi_mindgate',
                Payment\Entity::CPS_ROUTE           => Payment\Entity::UPI_PAYMENT_SERVICE,
                Payment\Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
                Payment\Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_VALIDATION_FAILURE'
            ], $payment->toArray()
        );
    }

    public function testRecurringMandatePreProcessAndCallbackSuccess()
    {
        $this->testRecurringAuthenticateSuccess();

        $payment = $this->getDbLastPayment();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $payment['description'] = 'rearch_callback';

        $this->mandateCreateCallback($payment);

        $payment->reload();

        $upiMandate->reload();

        $token->reload();

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($this->payment['order_id'], 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'created',
        ], $payment->toArray());

        $this->assertArraySubset([
            Entity::ORDER_ID        => substr($this->payment['order_id'], 6),
            Entity::CUSTOMER_ID     => '100000customer',
            Entity::FREQUENCY       => 'monthly',
            Entity::RECURRING_VALUE => 31,
            Entity::RECURRING_TYPE  => 'before',
            Entity::TOKEN_ID        => $token['id'],
            Entity::STATUS          => Status::CONFIRMED,
        ], $upiMandate->toArray());
    }

    public function testRecurringMandateDebitSuccess()
    {
        $this->testRecurringMandatePreProcessAndCallbackSuccess();

        $payment = $this->getDbLastPayment();

        $token = $this->getDbLastEntity('token');

        $payment['description'] = 'rearch_callback';

        $this->firstDebitCallback($payment);

        $payment->reload();
        $token->reload();

        $this->assertArraySubset([
            Payment\Entity::ORDER_ID        => substr($this->payment['order_id'], 6),
            Payment\Entity::CUSTOMER_ID     => '100000customer',
            Payment\Entity::STATUS          => 'captured',
        ], $payment->toArray());

        $this->assertArraySubset([
            Token\Entity::RECURRING        => true,
            Token\Entity::RECURRING_STATUS => 'confirmed'
        ], $token->toArray());
    }

    public function testRecurringMandateCreateCallbackRevoked()
    {
        $this->testRecurringMandateDebitSuccess();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $mandate['description'] = 'rearch_callback';

        $this->mandateRevokeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLED, $token['recurring_status']);
    }

    public function testRecurringMandateCreateCallbackPause()
    {
        $this->testRecurringMandateDebitSuccess();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $mandate['description'] = 'rearch_callback';

        $this->mandatePauseCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::PAUSED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::PAUSED, $token['recurring_status']);
    }

    public function testRecurringMandateCreateCallbackResume()
    {
        $this->testRecurringMandateCreateCallbackPause();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $mandate['description'] = 'rearch_callback';

        $this->mandateResumeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::CONFIRMED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token['recurring_status']);
    }

    public function testRevokeMandate()
    {
        $this->testRecurringMandateDebitSuccess();

        $this->fixtures->merchant->addFeatures(['cancel_token_v1']);

        $mandate = $this->getDbLastEntity('upi_mandate');

        $mandate['description'] = 'mandate_revoke';

        $token = $this->getDbLastEntity('token');

        $this->revokeUpiRecurringMandate($token->getPublicId());

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLED, $token['recurring_status']);
    }

    public function testRevokeMandateNewFlow()
    {
        $this->testRecurringMandateDebitSuccess();

        $mandate = $this->getDbLastEntity('upi_mandate');

        $mandate['description'] = 'mandate_revoke';

        $token = $this->getDbLastEntity('token');

        $this->revokeUpiRecurringMandate($token->getPublicId());

        $mandate->reload();

        $this->assertEquals(Status::CONFIRMED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLATION_INITIATED, $token['recurring_status']);

        $mandate = $this->getDbLastEntity('upi_mandate');

        $mandate['description'] = 'rearch_callback';

        $this->mandateRevokeCallback($mandate);

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Token\RecurringStatus::CANCELLED, $token['recurring_status']);
    }

    public function testUpiVerifyPayment()
    {
        $this->testRecurringMandateDebitSuccess();

        $payment = $this->getDbLastPayment();

        $this->assertEquals($payment['verified'], 0);

        $response = $this->verifyPayment($payment->getPublicId());

        $payment->reload();

        $this->assertEquals($payment['verified'], 1);

        $this->assertEquals($response['gateway']["verifyResponseContent"]["success"], true);
    }

    /** all mock callbacks */
    /************************************ HELPERS ***************************************/

    protected function mandateCreateCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseMandateCreate($payment);

        $this->makeS2SCallbackAndGetContent($content, 'upi_hdfc', true);
    }

    protected function firstDebitCallback($payment)
    {
        $content = $this->mockServer()->getAsyncCallbackResponseFirstDebitForMindgate($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_hdfc', true);
    }

    protected function mandatePauseCallback($mandate)
    {
        $content = $this->mockMozartServer()->getAsyncCallbackResponsePause($mandate);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, 'upi_hdfc', true);
    }

    protected function mandateResumeCallback($mandate)
    {
        $content = $this->mockMozartServer()->getAsyncCallbackResponseResume($mandate);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, 'upi_hdfc', true);
    }

    protected function mandateRevokeCallback($mandate)
    {
        $content = $this->mockMozartServer()->getAsyncCallbackResponseRevoke($mandate);

        $this->makeS2sCallbackAndGetContentSilentlyForRecurring($content, 'upi_hdfc', true);
    }

    protected function revokeUpiRecurringMandate(string $tokenId)
    {
        $this->ba->privateAuth();

        $request = [
            'method'  => 'PUT',
            'content' => [],
            'url' => '/customers/cust_100000customer/tokens/' . $tokenId . '/cancel',
        ];

        $this->makeRequestAndGetContent($request);
    }
}
