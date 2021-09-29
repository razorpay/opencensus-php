<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiPaymentServiceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        // We have Airtel Gateway Enabled for Service
        $this->terminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    /**
     * Test Successful Collect Payment Creation
     *
     * @return void
     */
    public function testCollectPaymentCreateSuccess()
    {
        $payment = $this->payment;

        $payment['description'] = 'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('vpa', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::REFUND_AT       => null,
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
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
    public function testIntentPaymentCreateSuccess()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_upi_airtel_intent_terminal');

        $payment = $this->payment;

        $payment['description'] = 'create_intent_success';

        unset($payment['vpa']);
        $payment['upi']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::REFUND_AT       => null,
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Validation failure for collect payment
     *
     * @return void
     */
    public function testCollectPaymentCreateValidationFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'validation_failure_collect_vpa';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\BadRequestException::class,
            'Vpa is required for UPI collect request');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_INPUT_VALIDATION_FAILURE'
            ], $payment->toArray()
        );
    }

    /**
     * test UPS service failure
     *
     * @return void
     */
    public function testPaymentServiceFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'service_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\ServerErrorException::class,
            'internal server error');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'SERVER_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'SERVER_ERROR_UPI_PAYMENT_SERVICE_FAILURE'
            ], $payment->toArray());
    }

    /**
     * Test Mozart failure for collect payment
     *
     * @return void
     */
    public function testMozartFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'mozart_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\GatewayErrorException::class);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_ENCRYPTION_ERROR'
            ], $payment->toArray()
        );
    }
}
