<?php

use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardlessEmiTest extends TestCase
{
    use PaymentTrait;

    const PROVIDER = 'earlysalary';

    const PROVIDERS = [Payment\Processor\CardlessEmi::EARLYSALARY,];

    const CUSTOMER_RELATED_ERRORS = [
        'BAD_REQUEST_ERROR',
        'BAD_REQUEST_ERROR',
        'BAD_REQUEST_ERROR',
        'BAD_REQUEST_ERROR',
        'BAD_REQUEST_ERROR',
    ];

    protected function setUp(): void
    {
        // $this->testDataFilePath = __DIR__.'/CardlessEmiGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'cardless_emi';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $this->fixtures->merchant->enableCardlessEmi('10000000000000');

    }

    protected function makeInitialPaymentRequest()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        return $this->makeRequestParent($request);
    }

    protected function prepareOtpVerifyRequest($responseData)
    {
        $otpVerifyRequest = $responseData['data']['data']['request'];

        unset($otpVerifyRequest['content']['amount']);

        unset($otpVerifyRequest['content']['currency']);

        unset($otpVerifyRequest['content']['notes']);

        unset($otpVerifyRequest['content']['description']);

        unset($otpVerifyRequest['content']['emi_duration']);

        $otpVerifyRequest['content']['otp'] = '0007';

        return $otpVerifyRequest;
    }

    public function testCardlessEmiInvalidInput()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);

        unset($payment['contact']);

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        BadRequestValidationFailureException::class,
        'The contact field is required.');

        $payment['contact'] = '+1234-(456)-(789)';

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        BadRequestException::class,
        'Your payment was not successful as international phone number is not accepted by the seller. To pay successfully try using Indian phone number.');

        unset($payment['provider']);

        $this->makeRequestAndCatchException(
        function() use ($payment)
        {
            $this->doAuthPayment($payment);
        },
        BadRequestValidationFailureException::class,
        'The provider field is required when method is cardless_emi.');
    }

    public function testCardlessEmiIncorrectOtt()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);
        $payment['ott'] = '123456';

        $this->setOtp('123456');

        $key = 'payment:cardlessemi.123456.token';
        $data = [
            'contact'  => '9918899029',
            'provider' => 'EARLYSALARY',
        ];

        $emiPlans = $this->app['cache']->set($key, $data, 15 * 60);

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            },
            RZP\Exception\BadRequestException::class,
            'Emi duration is not valid');
    }

    //--------Unit test begin---------

    public function testCardlessEmiPaymentInitiate()
    {
        $response = $this->makeInitialPaymentRequest();

        $data = $response->getOriginalContent()->getData();

        $paymentIdFromResponse = $data['data']['data']['request']['content']['payment_id'];

        //remove pay_ prefix from payment_id before finding in Database
        //Eg: pay_1234 -> 1234
        $paymentEntityFromResponse = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentIdFromResponse));

        $lastPaymentEntity = $this->getLastPayment();

        $this->assertEquals($paymentEntityFromResponse->getStatus(), Payment\Status::CREATED);

        $this->assertEquals($lastPaymentEntity['id'], $paymentIdFromResponse);

        return $data;
    }

    public function testCardlessEmiPaymentInitiateCustomerRelatedError()
    {
        foreach (self::CUSTOMER_RELATED_ERRORS as $error)
        {
            $this->mockServerContentFunction(function (& $content, $action) use ($error)
            {
                if ($action === 'check_account')
                {
                    unset($content['account_exists']);
                    unset($content['emi_plans']);
                    unset($content['loan_agreement']);
                    $content['error_code'] = $error;
                }
            });

            $this->makeRequestAndCatchException(function () {
                $this->makeInitialPaymentRequest();

            }, \RZP\Exception\GatewayErrorException::class);

            $payment = $this->getLastPayment();

            $this->assertEquals($payment['status'], Payment\Status::FAILED);

            $this->assertEquals($payment['error_code'], $error);
        }
    }

    public function testCardlessEmiValidOtpSubmit()
    {
        $responseData = $this->testCardlessEmiPaymentInitiate();

        $paymentId = $responseData['data']['data']['request']['content']['payment_id'];

        $otpVerifyRequest = $this->prepareOtpVerifyRequest($responseData);

        $otpVerifyResponse = $this->makeRequestParent($otpVerifyRequest);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);

        $this->assertEquals($otpVerifyResponse->original['success'], 1);

        $this->assertArrayHasKey('emi_plans', $otpVerifyResponse->original);

        $this->assertArrayHasKey('ott', $otpVerifyResponse->original);

        return [$paymentId, $otpVerifyResponse];
    }

    public function testCardlesssEmiOtpSubmitInvalidPaymentId()
    {
        $responseData = $this->testCardlessEmiPaymentInitiate();

        $paymentId = $responseData['data']['data']['request']['content']['payment_id'];

        $otpVerifyRequest = $this->prepareOtpVerifyRequest($responseData);

        $otpVerifyRequest['content']['payment_id'] = 'pay_1234567';

        $this->makeRequestAndCatchException(function()  use ($otpVerifyRequest) {
            $this->makeRequestParent($otpVerifyRequest);
        }, BadRequestException::class);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);
    }

    public function testCardlesssEmiAuthorize()
    {
        list($paymentId, $otpVerifyResponse) = $this->testCardlessEmiValidOtpSubmit();

        $authorizeRequest = $this->buildAuthPaymentRequest($this->getDefaultCardlessEmiPaymentArray(self::PROVIDER));

        $authorizeRequest['content']['payment_id'] = $paymentId;

        $authorizeRequest['content']['ott'] = $otpVerifyResponse->original['ott'];

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($authorizeRequest);

        $response = $response->decodeResponseJson();

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $paymentIdFromResponse = $response['razorpay_payment_id'];

        $this->assertEquals($paymentId, $paymentIdFromResponse);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::AUTHORIZED);
    }

    public function testCardlesssEmiAuthorizeOttPaymentMismatch()
    {
        list($paymentId, $otpVerifyResponse) = $this->testCardlessEmiValidOtpSubmit();

        $authorizeRequest = $this->buildAuthPaymentRequest($this->getDefaultCardlessEmiPaymentArray(self::PROVIDER));

        $authorizeRequest['content']['payment_id'] = 'pay_123456789';

        $authorizeRequest['content']['ott'] = $otpVerifyResponse->original['ott'];

        $this->ba->publicAuth();

        $this->makeRequestAndCatchException(function () use ($authorizeRequest) {
            $this->makeRequestParent($authorizeRequest);
        }, BadRequestException::class);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);
    }

    public function testCardlesssEmiAuthorizeMissingPaymentId()
    {
        list($paymentId, $otpVerifyResponse) = $this->testCardlessEmiValidOtpSubmit();

        $authorizeRequest = $this->buildAuthPaymentRequest($this->getDefaultCardlessEmiPaymentArray(self::PROVIDER));

        unset($authorizeRequest['content']['payment_id']);

        $authorizeRequest['content']['ott'] = $otpVerifyResponse->original['ott'];

        $this->ba->publicAuth();

        $this->makeRequestAndGetContent($authorizeRequest);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);
    }

    //--------Unit test end---------

    //--------functional test begin
    public function testCardlessEmiPayment()
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);

        $payment['contact'] = '+91' . $payment['contact'];

        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $paymentEntity = $this->getLastPayment();

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $this->assertEquals($paymentEntity['id'], $response['razorpay_payment_id']);

        $this->assertEquals($paymentEntity['status'], Payment\Status::AUTHORIZED);
    }
    //--------functional test end

}
