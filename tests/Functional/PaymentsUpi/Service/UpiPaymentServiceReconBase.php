<?php


namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\UpsPaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiPaymentServiceReconBase extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use UpsPaymentTrait;

    protected $payment;

    protected $terminal;

    protected $upiPaymentService;

    protected $shouldCreateTerminal = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->upiPaymentService = Mockery::mock('RZP\Services\UpiPayment\Mock\Service', [$this->app])->makePartial();

        $this->app->instance('upi.payments', $this->upiPaymentService);
    }

    /**
     * Tests unexpected payment creation
     */
    public function testUnexpectedPaymentCreation()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);
    }

    /**
     * Tests failed unexpected payment creation
     */
    public function testFailedUnexpectedPaymentCreation()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $content['upi']['vpa'] = 'failedunexpectedpayment@test';

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertEmpty($response['payment_id']);

        $this->assertFalse($response['success']);
    }

    /**
     * Test unexpected payment request mandatory validation
     */
    public function testUnexpectedPaymentValidationFailure()
    {
        $content = $this->buildUnexpectedPaymentRequest();
        // Unsetting the npci_reference_id to mimic validation failure
        unset($content['upi']['npci_reference_id']);
        unset($content['terminal']['gateway_merchant_id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url' => '/payments/create/upi/unexpected',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        },Exception\BadRequestValidationFailureException::class);
    }

    /**
     * Tests the payment create for duplicate unexpected payment
     */
    public function testDuplicateUnexpectedPayment()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        // Hit payment create again
        $this->makeRequestAndCatchException(function() use ($content) {

            $this->makeUnexpectedPaymentAndGetContent($content);

        }, Exception\BadRequestException::class,
            'Duplicate Unexpected payment with same amount');
    }

    public function testUnexpectedPaymentDuplicateRRN()
    {
        $content = $this->buildUnexpectedPaymentRequest();

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        $content['payment']['amount'] = 1000;

        $content['upi']['vpa'] = 'unexpectedPayment@kotak';

        $response = $this->makeUnexpectedPaymentAndGetContent($content);

        $this->assertNotEmpty($response['payment_id']);

        $this->assertTrue($response['success']);

        // Hit payment create again
        $this->makeRequestAndCatchException(function() use ($content) {

            $this->makeUnexpectedPaymentAndGetContent($content);

        }, Exception\BadRequestException::class,
            'Multiple payments with same RRN');
    }

   /*
     * Authorize the failed payment by verifying at gateway
     */
    public function testVerifyAuthorizeFailedPayment()
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

        $content = $this->buildUpiAuthorizeFailedPaymentRequest($payment->getId());

        $content['meta']['force_auth_payment'] = false;

        $response = $this->makeAuthorizeFailedPaymentAndGetPayment($content);

        $updatedPayment = $this->getDbEntityById('payment', $payment['id']);

        $this->assertEquals('authorized', $updatedPayment['status']);

        $this->assertNotNull($updatedPayment['reference16']);

        // asset the late authorized flag for authorizing via verify
        $this->assertTrue($updatedPayment['late_authorized']);

        $this->assertNotEmpty($updatedPayment['transaction_id']);
    }

    /**
     * Validate negative case of authorizing succesfulpayment
     */
    public function testForceAuthorizeSucessfulPayment()
    {
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

        $content = $this->buildUpiAuthorizeFailedPaymentRequest($payment->getId());

        $content['meta']['force_auth_payment'] = false;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'Non failed payment given for authorization');
    }

    /**
     * Checks for validation failure in case of missing payment_id
     */
    public function testForceAuthorizePaymentValidationFailure()
    {
        $content = $this->buildUpiAuthorizeFailedPaymentRequest('');

        unset($content['payment']['id']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'The payment.id field is required.');
    }

    //Tests for force authorize with mismatched amount in request.
    public function testForceAuthorizePaymentAmountMismatch()
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

        $content = $this->buildUpiAuthorizeFailedPaymentRequest($payment->getId());

        $content['meta']['force_auth_payment'] = false;

        // Setting different amount for validating amount mismatch while authorizing failed payment
        $content['payment']['amount'] = 1000;

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url'     => '/payments/authorize/upi/failed',
                'method'  => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class,
            'The amount does not match with payment amount');
    }
}
