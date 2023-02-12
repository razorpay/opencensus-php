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

    protected function makeInitialPaymentRequest($email, $contact, $route)
    {
        $payment = $this->getDefaultCardlessEmiPaymentArray(self::PROVIDER);

        $payment['email'] = $email;

        $payment['contact'] = $contact;

        if ($route === 'JSON')
        {
            $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

            $response = $this->doS2SPrivateAuthJsonPayment($payment);

            return $response;
        }elseif ($route === 'REDIRECT')
        {
            $this->fixtures->merchant->addFeatures(['s2s']);

            $response = $this->doS2SPrivateAuthPayment($payment);

            return $response;
        }
        $request = $this->buildAuthPaymentRequest($payment);

        $this->ba->publicAuth();

        return $this->makeRequestParent($request);
    }

    protected function fetchAndValidatePaymentStatus($id, $status)
    {
        $paymentEntityFromResponse = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($id));

        $lastPaymentEntity = $this->getLastPayment();

        $this->assertEquals($paymentEntityFromResponse->getStatus(), $status);

        $this->assertEquals($lastPaymentEntity['id'], $id);
    }

    protected function otpVerifyAssert($otpVerifyResponse){

        $this->assertEquals($otpVerifyResponse->original['success'], 1);

        $this->assertArrayHasKey('emi_plans', $otpVerifyResponse->original);

        $this->assertArrayHasKey('ott', $otpVerifyResponse->original);
    }

    protected function prepareOtpVerifyRequest($responseData)
    {
        $otpVerifyRequest = $responseData['data']['data']['request'];

        unset($otpVerifyRequest['content']['amount']);

        unset($otpVerifyRequest['content']['currency']);

        unset($otpVerifyRequest['content']['notes']);

        unset($otpVerifyRequest['content']['description']);

        unset($otpVerifyRequest['content']['emi_duration']);

        $email = $responseData['data']['data']['request']['content']['email'];
         if ($email === "invalid_otp@gmail.com")
         {
             $otpVerifyRequest['content']['otp'] = '000ppppp8';
         }
         else {
             $otpVerifyRequest['content']['otp'] = '0007';
         }


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


    // account does not exist
    public function testAccountDoesNotExist($email='no_accound@b.com',$contact = '+919918899021'){
        $this->makeRequestAndCatchException(
            function() use ($email, $contact)
            {
                $this->makeInitialPaymentRequest($email,$contact,"AJAX");
            },
            RZP\Exception\GatewayErrorException::class);
    }

    public function testAccountDoesNotExists2sJSON($email='no_accound@b.com',$contact = '+919918899021'){

        $this->makeRequestAndCatchException(
            function() use ($email, $contact)
            {
                $this->makeInitialPaymentRequest($email,$contact,"JSON");
            },
            RZP\Exception\GatewayErrorException::class);
    }

    public function testAccountDoesNotExists2sRedirect($email='no_accound@b.com',$contact = '+919918899021'){

        $this->makeRequestAndCatchException(
            function() use ($email, $contact)
            {
                $this->makeInitialPaymentRequest($email,$contact,"REDIRECT");
            },
            RZP\Exception\GatewayErrorException::class);
    }

    // invalid ott

    // missing contact
    // request tempered
    // valid ott, failed authorize
    // valid ott, success authorize, capture
    // authorize, failed refund
    // authorize, success refund



    //--------Unit test begin---------
    //done
    public function testCardlessEmiPaymentInitiateAjax($email='a@b.com',$contact = '+919918899022')
    {
        $response= $this->makeInitialPaymentRequest($email,$contact,"AJAX");

        $data = $response->getOriginalContent()->getData();

        $paymentIdFromResponse = $data['data']['data']['request']['content']['payment_id'];

        //remove pay_ prefix from payment_id before finding in Database
        //Eg: pay_1234 -> 1234
        $this->fetchAndValidatePaymentStatus($paymentIdFromResponse, Payment\Status::CREATED);

        return [$paymentIdFromResponse, $data];
    }

    //done
    public function testCardlessEmiPaymentInitiateS2SJson($email='valid_ott@gmail.com',$contact = '+919918899022')
    {
        $response= $this->makeInitialPaymentRequest($email,$contact,"JSON");

        $this->assertArrayHasKey('next', $response);

        $this->assertArrayHasKey('action', $response['next'][0]);

        $this->assertArrayHasKey('url', $response['next'][0]);

        $paymentIdFromResponse = $response['razorpay_payment_id'];

        //remove pay_ prefix from payment_id before finding in Database
        //Eg: pay_1234 -> 1234
        $this->fetchAndValidatePaymentStatus($paymentIdFromResponse, Payment\Status::CREATED);

        $redirectContent = $response['next'][0];

        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));

        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/authenticate');

        $url = $this->getPaymentRedirectToAuthorizrUrl($id);

        return [$response, $paymentIdFromResponse, $url];
    }


    //done
    public function testIncorrectOtpAjax()
    {
        list($paymentId, $response) = $this->testCardlessEmiPaymentInitiateAjax($email='invalid_otp@gmail.com',$contact = '+919918899022');

        $this->makeRequestAndCatchException(
            function() use ($response,  $paymentId)
            {
                $otpVerifyRequest = $this->prepareOtpVerifyRequest($response);

                $otpVerifyResponse = $this->makeRequestParent($otpVerifyRequest);
            },
            RZP\Exception\BadRequestValidationFailureException::class,
        'The otp format is invalid.');

        $this->fetchAndValidatePaymentStatus($paymentId, Payment\Status::CREATED);

    }

    //done
    public function testIncorrectOtpS2SJson()
    {
        list($response, $paymentIdFromResponse, $url) = $this->testCardlessEmiPaymentInitiateS2SJson($email='invalid_otp@gmail.com',$contact = '+919918899022');

        $this->ba->directAuth();

        $request = [
            'url'   => $url,
            'method' => 'get',
            'content' => [],
        ];

        $infoResponse = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        $this->makeRequestAndCatchException(
            function() use ($infoResponse)
            {
                $callbackResponse = $this->runPaymentCallbackFlowCardlessEmi($infoResponse, $true,null);

            },
            RZP\Exception\BadRequestValidationFailureException::class,
            'The otp format is invalid.');


        $this->fetchAndValidatePaymentStatus($paymentIdFromResponse, Payment\Status::CREATED);


    }

    //done
    public function testCorrectOtpAjax()
    {
        list($paymentId, $response) = $this->testCardlessEmiPaymentInitiateAjax($email='valid_otp@gmail.com',$contact = '+919918899022');

        $otpVerifyRequest = $this->prepareOtpVerifyRequest($response);

        $otpVerifyResponse = $this->makeRequestParent($otpVerifyRequest);

        $this->fetchAndValidatePaymentStatus($paymentId, Payment\Status::CREATED);

        $this->otpVerifyAssert($otpVerifyResponse);

        return [$paymentId, $otpVerifyResponse];
    }

    //done
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
                $this->makeInitialPaymentRequest("a@b.com","+919918899021","ajax");

            }, \RZP\Exception\GatewayErrorException::class);

            $payment = $this->getLastPayment();

            $this->assertEquals($payment['status'], Payment\Status::FAILED);

            $this->assertEquals($payment['error_code'], $error);
        }
    }

    //needs to be fixed
//    public function testCardlessEmiPaymentInitiateInvalidOttS2SJson()
//    {
//        $response = $this->testCardlessEmiPaymentInitiateS2SJson('invalid_ott@gmail.com',$contact = '+919918899022');
//
//        print_r($response);
//        $this->assertArrayHasKey('next', $response);
//
//        $this->assertArrayHasKey('action', $response['next'][0]);
//
//        $this->assertArrayHasKey('url', $response['next'][0]);
//
//        $paymentIdFromResponse = $response['razorpay_payment_id'];
//
//        //remove pay_ prefix from payment_id before finding in Database
//        //Eg: pay_1234 -> 1234
//        $paymentEntityFromResponse = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentIdFromResponse));
//
//        $this->assertEquals($paymentEntityFromResponse->getStatus(), Payment\Status::CREATED);
//
//        $redirectContent = $response['next'][0];
//
//        $this->assertTrue($this->isRedirectToAuthorizeUrl($redirectContent['url']));
//
//        $id = getTextBetweenStrings($redirectContent['url'], '/payments/', '/authenticate');
//
//        $url = $this->getPaymentRedirectToAuthorizrUrl($id);
//
//        $this->ba->directAuth();
//
//        $request = [
//            'url'   => $url,
//            'method' => 'get',
//            'content' => [],
//        ];
//
//        $infoResponse = $this->makeRequestParent($request);
//
//        $this->ba->publicAuth();
//
//        $this->makeRequestAndCatchException(
//            function() use ($infoResponse)
//            {
//                $callbackResponse = $this->runPaymentCallbackFlowCardlessEmi($infoResponse, $true,null);
//            },
//            BadRequestValidationFailureException::class);
//
//    }


    public function testCardlesssEmiOtpSubmitInvalidPaymentId()
    {
        list($paymentId, $response) = $this->testCardlessEmiPaymentInitiateAjax($email='valid_otp@gmail.com',$contact = '+919918899022');

        $paymentId = $response['data']['data']['request']['content']['payment_id'];

        $otpVerifyRequest = $this->prepareOtpVerifyRequest($response);

        $otpVerifyRequest['content']['payment_id'] = 'pay_1234567';

        $this->makeRequestAndCatchException(function()  use ($otpVerifyRequest) {
            $this->makeRequestParent($otpVerifyRequest);
        }, BadRequestException::class);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);
    }

    //done
    public function testCardlesssEmiAuthorize()
    {
        list($paymentId, $otpVerifyResponse) = $this->testCorrectOtpAjax();

        $authorizeRequest = $this->buildAuthPaymentRequest($this->getDefaultCardlessEmiPaymentArray(self::PROVIDER));

        $authorizeRequest['content']['payment_id'] = $paymentId;

        $authorizeRequest['content']['ott'] = $otpVerifyResponse->original['ott'];

        $authorizeRequest['content']['email'] = 'valid_otp@gmail.com';

        $authorizeRequest['content']['contact'] = '+919918899022';

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($authorizeRequest);

        $response = $response->decodeResponseJson();

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $paymentIdFromResponse = $response['razorpay_payment_id'];

        $this->assertEquals($paymentId, $paymentIdFromResponse);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::AUTHORIZED);
    }

    //done
    public function testCardlesssEmiAuthorizeOttPaymentMismatch()
    {
        list($paymentId, $otpVerifyResponse) = $this->testCorrectOtpAjax();

        $authorizeRequest = $this->buildAuthPaymentRequest($this->getDefaultCardlessEmiPaymentArray(self::PROVIDER));

        $authorizeRequest['content']['payment_id'] = $paymentId;

        $authorizeRequest['content']['ott'] = 'invalid_ott';

        $authorizeRequest['content']['email'] = 'valid_otp@gmail.com';

        $authorizeRequest['content']['contact'] = '+919918899022';

        $this->ba->publicAuth();

        $this->makeRequestAndCatchException(function () use ($authorizeRequest) {
            $this->makeRequestParent($authorizeRequest);
        }, BadRequestValidationFailureException::class);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);
    }

    public function testCardlesssEmiAuthorizeMissingPaymentId()
    {


        list($paymentId, $otpVerifyResponse) = $this->testCorrectOtpAjax();

        $authorizeRequest = $this->buildAuthPaymentRequest($this->getDefaultCardlessEmiPaymentArray(self::PROVIDER));


        $authorizeRequest['content']['ott'] = $otpVerifyResponse->original['ott'];

        $authorizeRequest['content']['email'] = 'valid_otp@gmail.com';

        $authorizeRequest['content']['contact'] = '+919918899022';


        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($authorizeRequest);

        $paymentEntity = Payment\Entity::findOrFail(Payment\Entity::stripDefaultSign($paymentId));

        $this->assertEquals($paymentEntity->getStatus(), Payment\Status::CREATED);
    }
}

