<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiPaymentServiceBase extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $terminal;

    protected $upiPaymentService;

    protected $shouldCreateTerminal = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->upiPaymentService = Mockery::mock('RZP\Services\UpiPayment\Mock\Service', [$this->app])->makePartial();

        $this->app->instance('upi.payments', $this->upiPaymentService);

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    /**
     * Test Successful Collect Payment Creation
     *
     * @return void
     */
    public function testCollectPaymentCreateSuccess(): void
    {
        $this->payment['description'] = 'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('vpa', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => 'created',
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::REFUND_AT => null,
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Successful Intent Payment Creation
     *
     * @return void
     */
    public function testIntentPaymentCreateSuccess(): void
    {
        $this->payment['description'] = 'create_intent_success';
        unset($this->payment['vpa']);
        $this->payment['upi']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => 'created',
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::REFUND_AT => null,
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Failed Collect Payment with pre-process through Mozart
     * @return void
     */
    public function testCollectPaymentFailure(): void
    {
        $this->payment['description'] = 'payment_failed';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $this->makeCallbackForPayment($payment, false);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::FAILED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => 'GATEWAY_ERROR',
                Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Success Collect Payment with pre-process through Mozart
     * @return void
     */
    public function testCollectPaymentSuccess(): void
    {
        $this->payment['description'] = 'create_collect_success';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $this->makeCallbackForPayment($payment, true);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::AUTHORIZED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => '',
                Entity::INTERNAL_ERROR_CODE => '',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Success Intent Payment with pre-process through Mozart
     * @return void
     */
    public function testIntentPaymentSuccess(): void
    {
        $this->payment['description'] = 'create_intent_success';
        unset($this->payment['vpa']);
        $this->payment['upi']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $this->makeCallbackForPayment($payment, true);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::AUTHORIZED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => '',
                Entity::INTERNAL_ERROR_CODE => '',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Amount Mismatch Intent Payment with pre-process through Mozart
     * @return void
     */
    public function testIntentPaymentAmountMismatch(): void
    {
        $this->payment['description'] = 'amount_mismatch';
        unset($this->payment['vpa']);
        $this->payment['upi']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $this->makeCallbackForPayment($payment, false);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::FAILED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => 'SERVER_ERROR',
                Entity::INTERNAL_ERROR_CODE => 'SERVER_ERROR_AMOUNT_TAMPERED',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Multiple Credit Intent Payment with pre-process through Mozart
     * @return void
     */
    public function testIntentPaymentMultipleCredit(): void
    {
        $this->payment['description'] = 'create_intent_success';
        unset($this->payment['vpa']);
        $this->payment['upi']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $this->makeCallbackForPayment($payment, true);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::AUTHORIZED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => '',
                Entity::INTERNAL_ERROR_CODE => '',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);

        // Make same callback again
        $this->makeCallbackForPayment($payment, false);

        $payments = $this->getDbEntities('payment');
        $this->assertCount(1, $payments);
        $this->assertEquals($payment['id'], $payments[0]->getId());
    }

    /**
     * Test Unexpected Intent Payment with pre-process through Mozart
     * @return void
     */
    public function testIntentPaymentUnexpected(): void
    {
        $payment = new Entity([
            Entity::ID => 'RandomUnexpectedID',
            Entity::AMOUNT => 50000,
            Entity::DESCRIPTION => 'unexpected_payment',
            Entity::VPA => $this->payment[Entity::VPA],
        ]);
        $payment->associateTerminal($this->terminal);

        $this->makeCallbackForPayment($payment, false);

        $payments = $this->getDbEntities('payment');
        $this->assertCount(0, $payments);

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);
        $this->assertNull($upiEntity);
    }

    /**
     * Test Success on verify Intent Payment with pre-process through Mozart
     * @return void
     */
    public function testIntentPaymentSuccessOnVerify(): void
    {
        $this->payment['description'] = 'create_intent_success';
        unset($this->payment['vpa']);
        $this->payment['upi']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);
        $this->assertNull($upiEntity);

        $this->authorizedFailedPayment($payment->getPublicId());

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::AUTHORIZED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => '',
                Entity::INTERNAL_ERROR_CODE => '',
                Entity::LATE_AUTHORIZED => true,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Amount mismatch on Intent Payment with pre-process through Mozart
     * @return void
     */
    public function testIntentPaymentAmountMismatchOnVerify(): void
    {
        $this->payment['description'] = 'verify_amount_mismatch';
        unset($this->payment['vpa']);
        $this->payment['upi']['flow'] = 'intent';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $payment = $this->getDbLastpayment();
        $this->assertArraySubset([
            Entity::STATUS      => Status::CREATED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
        ], $payment->only([Entity::STATUS, Entity::TERMINAL_ID]));

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);
        $this->assertNull($upiEntity);

        $this->makeRequestAndCatchException(function () use ($payment)
            {
                $this->authorizedFailedPayment($payment->getPublicId());
            },
            Exception\RuntimeException::class,
            'Payment verification failed due to amount mismatch.');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => Status::CREATED,
                Entity::GATEWAY => $this->gateway,
                Entity::TERMINAL_ID => $this->terminal->getId(),
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE => null,
                Entity::INTERNAL_ERROR_CODE => null,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    protected function makeCallbackForPayment(Entity $entity, bool $success): array
    {
        $gateway = $this->gateway;
        $this->gateway = 'upi_mozart';
        $this->setMockGatewayTrue();
        $this->gateway = $gateway;

        // These gateways are fully live on UPS and Mozart, And in tests the callback response is going to be
        // delivered by mocked UPS, which is not even touching mozart. Thus there is no need to add exact
        // callback response to UPS, only few mandatory fields are required to make pre-process work.
        // NOTE: All fully ramped gateways can follow the same pattern
        $content = json_encode($entity->only(
            [Entity::ID, Entity::AMOUNT, Entity::DESCRIPTION, Entity::GATEWAY, Entity::TERMINAL_ID, Entity::VPA]));
        $response = $this->makeS2SCallbackAndGetContent($content, $this->gateway);
        // This assertion makes sure that gateway is response properly
        $this->assertArrayHasKey('success', $response);
        $this->assertEquals($success, $response['success']);

        return $response;
    }
}
