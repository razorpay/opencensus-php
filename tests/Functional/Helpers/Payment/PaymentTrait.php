<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use App;
use Mockery;
use Razorpay\IFSC\Bank;
use Requests;
use Carbon\Carbon;
use RZP\Models\Pricing\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\FeeBearer;
use RZP\Constants\Shield as ShieldConstants;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use Symfony\Component\DomCrawler\Crawler;

use RZP\Exception;
use RZP\Models\Risk;
use RZP\Models\Payment;
use RZP\Services\Scrooge;
use RZP\Constants\Timezone;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Payment\Verify\Action;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Models\Payment\Refund\Entity as RefundEntity;

trait PaymentTrait
{
    use PaymentMpgsTrait;
    use PaymentEbsTrait;
    use PaymentFssTrait;
    use PaymentAmexTrait;
    use PaymentAtomTrait;
    use PaymentHdfcTrait;
    use PaymentAuthTrait;
    use PaymentPayuTrait;
    use PaymentPaytmTrait;
    use PaymentSharpTrait;
    use PaymentBladeTrait;
    use EntityActionTrait;
    use PaymentHitachiTrait;
    use PaymentMobikwikTrait;
    use PaymentOlamoneyTrait;
    use PaymentPayLaterTrait;
    use PaymentGetsimplTrait;
    use PaymentCreationTrait;
    use PaymentAxisMigsTrait;
    use PaymentBilldeskTrait;
    use PaymentCashfreeTrait;
    use PaymentFirstDataTrait;
    use PaymentPaysecureTrait;
    use PaymentMandateHQTrait;
    use PaymentAxisGeniusTrait;
    use PaymentNetbankingTrait;
    use PaymentFreechargeTrait;
    use PaymentTraitMpiEnstage;
    use PaymentCybersourceTrait;
    use PaymentCardlessEmiTrait;
    use PaymentBajajFinservTrait;
    use PaymentHdfcDebitEmiTrait;
    use PaymentWalletAmazonpayTrait;
    use PaymentWalletAirtelMoneyTrait;
    use PaymentKotakDebitEmiTrait;
    use PaymentIndusindDebitEmiTrait;
    use PaymentIciciTrait;
    use PaymentBilldeskOptimizerTrait;

    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    protected $otp = null;

    protected $redirectTo3ds = null;

    protected $gateway = null;

    protected $merchantCallbackUrl = null;

    protected $merchantCallbackFlow = false;

    protected $redirectToAuthorize = false;

    protected $redirectToDCCInfo = false;

    protected $redirectToUpdateAndAuthorize = false;

    protected $redirectToAddressCollect = false;

    protected $addressNameCollect = false;


    /**
     * For certain payments, user has the option to fail it
     * on the bank page. If this property is set to true in
     * the test, then we simulate submitting failure option
     * on the bank page
     *
     * @var boolean
     */
    protected $failPaymentOnBankPage = false;
    protected $gatewayDown = false;

    protected $mandateHqTerminal = null;

    protected function doAuthAndCapturePayment($payment = null, $amount = 0, $currency = 'INR', $discountedPrice = 0, $rearch = false)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $paymentAuth = $rearch ? $this->doS2SPrivateAuthJsonPayment($payment) : $this->doJsonpAuthPayment($payment);

        if($discountedPrice != 0)
        {
            return $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $payment['amount'], $currency, $discountedPrice);
        }

        if ($amount !== 0)
        {
            $payment = $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $amount, $currency, $payment['amount']);
        }
        else
        {
            $payment = $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $payment['amount'], $currency);
        }

        return $payment;
    }

    protected function doAuthAndCapturePaymentViaAjaxRoute($payment = null, $amount = 0, $currency = 'INR', $discountedPrice = 0, $rearch = false)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $paymentAuth =  $this->doAuthPaymentViaAjaxRoute($payment);

        if($discountedPrice != 0)
        {
            return $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $payment['amount'], $currency, $discountedPrice);
        }

        if ($amount !== 0)
        {
            $payment = $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $amount, $currency, $payment['amount']);
        }
        else
        {
            $payment = $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $payment['amount'], $currency);
        }

        return $payment;
    }

    protected function doAuthCaptureAndRefundPayment($payment = null, $refundAmount = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->gateway = $payment['gateway'];

        $refund = $this->refundPayment($payment['id'], $refundAmount);

        return $refund;
    }

    protected function doAuthCaptureAndRefundPaymentViaAjaxRoute($payment = null, $refundAmount = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment = $this->doAuthAndCapturePaymentViaAjaxRoute($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->gateway = $payment['gateway'];

        $refund = $this->refundPayment($payment['id'], $refundAmount);

        return $refund;
    }

    protected function doAuthAndGetPayment($payment = null, $paymentResponse = [])
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment = $this->doJsonpAuthPayment($payment);

        $id = $payment['razorpay_payment_id'];

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $func = $trace[1]['function'];

        return $this->getAndMatchPayment($id, $paymentResponse);
    }

    protected function doAuthAndGetPaymentForMyMerchant($payment = null, $paymentResponse = [])
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArrayForMYMerchant();
        }
        $this->app['config']->set('applications.pg_router.mock', true);

        $amount = $payment['amount'];
        $currency = $payment['currency'];

        $payment = $this->fixtures->payment->createAuthorized(
            [
                'order_id'   => explode("_", $payment['order_id'])[1],
                'amount'   => $amount,
                'currency' => $currency,
            ]);

        $payment = $this->capturePayment(
            'pay_' . $payment['id'],
            $amount, $currency, $amount);

        $id = $payment['razorpay_payment_id'];

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $func = $trace[1]['function'];

        return $payment;
    }

    public function createRefundFromPayments($payments)
    {
        foreach ($payments as $payment)
        {
            $attrs = ['payment' => $payment, 'amount'  => '100'];
            $refund = $this->fixtures->create('refund:from_payment', $attrs);
            $refunds[] = $refund;
        }
    }

    protected function createAndGetFeesForPayment($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $content = $this->getFeesForPayment($payment);

        return $content;
    }

    protected function createAndGetFeesForPaymentS2S($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $content = $this->getFeesForPaymentS2S($payment);

        return $content;
    }

    protected function runTestForAuthPayment($payment = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func] ?? [];

        if (isset($testData['request']) === false)
            $testData['request'] = [];

        if (isset($testData['request']['content']) === false)
            $testData['request']['content'] = [];

        if ($payment !== null)
            $testData['request']['content'] = $payment;

        $this->replaceDefaultValues($testData['request']['content']);

        $testData['request']['method'] = 'POST';
        $testData['request']['url'] = '/payments';

        $this->ba->publicAuth();

        return $this->runRequestResponseFlow($testData);
    }

    protected function authorizeEmandateFileBasedDebitPayment(array $debitPayment)
    {
        assert($debitPayment[Payment\Entity::STATUS] === Payment\Status::CREATED);

        assert($debitPayment[Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::AUTO);

        assert($debitPayment[Payment\Entity::RECURRING] === true);

        $debitPaymentId = substr($debitPayment[Payment\Entity::ID], 4);

        $this->fixtures->create('netbanking', [
            'payment_id'        => $debitPaymentId,
            'action'            => Payment\Action::AUTHORIZE,
            'amount'            => $debitPayment[Payment\Entity::AMOUNT],
            'bank'              => $debitPayment[Payment\Entity::BANK],
            'received'          => 1,
            'caps_payment_id'   => strtoupper($debitPaymentId),
        ]);

        $this->fixtures->edit('payment', $debitPaymentId, [
            'status'                => Payment\Status::AUTHORIZED,
            'amount_authorized'     => $debitPayment[Payment\Entity::AMOUNT],
            'authorized_at'         => time(),
        ]);
    }

    protected function doAutoCapture()
    {
        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/autocapture',
            'method' => 'post'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function sendAutoCaptureEmails()
    {
        $this->ba->adminAuth();

        $request = [
            'url'    => '/payments/autocapture/email',
            'method' => 'get'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getSignature(array $data, $secret = '')
    {
        if ($secret === '')
        {
            $secret = $this->ba->getSecret();
        }

        $str = implode('|', $data);

        return hash_hmac(BasicAuth::HMAC_ALGO, $str, $secret);
    }

    protected function getPaymentJsonFromCallback($content)
    {
        $start = 'var data = ';
        $end = '// Callback data //';

        $data = getTextBetweenStrings($content, $start, $end);

        // Remove ';\n' at the end to get proper json string
        $data = substr($data, 0, -2);

        return $data;
    }

    protected function defaultAuthPaymentForMY(array $payment = [])
    {
        $defaultPayment = $this->getDefaultPaymentArrayForMYMerchant();

        $payment = array_merge($defaultPayment, $payment);

        $content = $this->doAuthPayment($payment);

        $id = $content['razorpay_payment_id'];

        return array_merge($payment, ['id' => $id]);
    }

    protected function defaultAuthPayment(array $payment = [])
    {
        $defaultPayment = $this->getDefaultPaymentArray();

        $payment = array_merge($defaultPayment, $payment);

        $content = $this->doAuthPayment($payment);

        $id = $content['razorpay_payment_id'];

        return array_merge($payment, ['id' => $id]);
    }

    protected function doJsonpAuthPayment($payment)
    {
        $content = [
            'callback' => 'abcdefghijkl',
            '_' => '',
        ];

        $content = array_merge($content, $payment);

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/create/jsonp',
            'content' => $content
        ];

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request, $content['callback']);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $count = count($content);
        $this->assertLessThanOrEqual(36, $count);

        return $content;
    }

    protected function createCustomerToken(int $recurring)
    {
        $this->ba->proxyAuth();

        $request = [
            'url'     => '/customers/cust_100000customer/tokens',
            'method'  => 'post',
            'content' => [
                'method'     => 'netbanking',
                'bank'       => 'ICIC',
                'max_amount' => 100000,
                'recurring'  => $recurring,
            ]
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function deleteCustomerToken(string $tokenId, $customerId = 'cust_100000customer')
    {
        $this->ba->privateAuth();

        $request = [
            'url'     => '/customers/' . $customerId . '/tokens/' . $tokenId,
            'method'  => 'delete',
            'content' => []
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getTokenById(string $id)
    {
        $this->ba->privateAuth();

        $request = [
            'url'     => '/customers/cust_100000customer/tokens/' . $id,
            'method'  => 'get',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function doAuthPayment($payment = null, $server = null, $key = null)
    {
        $request = $this->buildAuthPaymentRequest($payment, $server);
        $request['content']['validate_payment']['afa_required'] = false;
        $request['content']['acs_afa_authentication'] = array();

        $this->ba->publicAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function buildAuthPaymentRequest($payment = null, $server = null): array
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        return $request;
    }

    public function doAuthPaymentOAuth($payment = null, $server = null, $key = null)
    {
        $request = $this->buildAuthPaymentRequest($payment, $server);

        $this->ba->oauthPublicTokenAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function doS2SPrivateAuthPayment($payment = null, $server = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function doFetchTokenFromCustomerID($tokenId)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/customers/cust_100000customer/tokens/'.$tokenId
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function doS2SPrivateAuthJsonPayment($payment = null, $server = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function doS2SRecurringPayment($payment = null, $server = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment['acs_afa_authentication'] = array();
        $payment['validate_payment']['afa_required'] = false;

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/recurring',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        $this->ba->privateAuth();
//        $this->mockValidatePayment();
        $content = $this->makeRequestAndGetContent($request);


        return $content;
    }

    protected function doS2sUpiPayment($payment = null, $server = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/upi',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function doS2sUpiPaymentPartner($client, $submerchantId, $payment = null, $server = null)
    {
        $server = [
            'HTTP_X-Razorpay-Account' => $submerchantId,
        ];

        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/upi',
            'content' => $payment
        ];

        if (isset($server))
        {
            $request['server'] = $server;
        }

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function doS2SPrivateAuthAndCapturePayment($payment = null)
    {
        $paymentAuth = $this->doS2SPrivateAuthPayment($payment);

        return $this->capturePayment($paymentAuth['razorpay_payment_id'], $payment['amount']);
    }

    protected function doAuthWalletPayment($payment = null, $wallet = 'paytm')
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment['method'] = 'wallet';
        $payment['wallet'] = $wallet;

        return $this->doAuthPayment($payment);
    }

    protected function doAuthPaymentViaAjaxRoute($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/ajax',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function doAuthPaymentViaCheckoutRoute($payment)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'content' => $payment,
            'url'     => '/payments/create/checkout',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function getFormViaCreateRoute($payment, $view = 'gateway.gatewayWalletForm')
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response->assertViewIs($view);
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');

        return $this->getFormRequestFromResponse($response->getContent(), 'http://localhost');
    }

    protected function generateGatewayFile($bank, string $type, $begin = null, $end = null)
    {
        $request = [
            'url'       => '/gateway/files',
            'method'    => 'POST',
            'content'   => [
                'targets' => (array) $bank,
                'type'    => $type,
                'begin'   => $begin ?? Carbon::yesterday(Timezone::IST)->getTimestamp(),
                'end'     => $end ?? Carbon::today(Timezone::IST)->getTimestamp()
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function makeOtpCallback($url)
    {
        $request = [
            'url'       => $url,
            'method'    => 'POST',
            'content'   => [
                'otp'  => $this->getOtp(),
                'type' => 'otp'
            ],
        ];

        return $this->sendRequest($request);
    }

    protected function makeOtpVerifyCallback($url, $email, $contact)
    {
        $otp = '0007';

        if ($email === "invalid_otp@gmail.com"){
            $otp = 'sasad12';
        }

        $request = [
            'url'       => $url,
            'method'    => 'POST',
            'content'   => [
                'otp'   => $otp,
                'email'  => $email,
                'contact' => $contact,
            ],
        ];

        return $this->sendRequest($request);
    }

    protected function makeRedirectTo3ds($url)
    {
        $request = [
            'url'       => $url,
            'method'    => 'POST',
        ];

        $response = $this->sendRequest($request);

        list ($url, $method, $values) = $this->getDataForGatewayRequest($response);

        $this->ba->publicAuth();

        $request = $this->makeFirstGatewayPaymentMockRequest($url, $method, $values);

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function makeS2sCallbackAndGetContent($content, $gateway = null, $isRecurring = false)
    {
        $gateway = $gateway ?: $this->gateway;

        $url = '/callback/' . $gateway ;

        if ($isRecurring === true)
        {
            $url = '/callback/recurring/' . $gateway ;
        }

        $request = [
            'url'    => $url,
            'method' => 'post'
        ];

        if (is_string($content))
        {
            $request['raw'] = $content;
        }
        else
        {
            $request['content'] = $content;
        }

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function doWalletTopup($id)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/'.$id.'/topup',
            'content' => []
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response = $this->handleWalletTopupFlow($response, $request);

        return $this->getJsonContentFromResponse($response);
    }

    protected function doWalletTopupViaAjaxRoute($id)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/'.$id.'/topup/ajax',
            'content' => []
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response = $this->handleWalletTopupFlow($response, $request);

        return $this->getJsonContentFromResponse($response);
    }

    protected function redirectPayment($id)
    {
        $request = [
            'method'    => 'POST',
            'url'       => '/payments/'.$id.'/redirect_callback',
            'content'   => []
        ];

        $this->ba->publicAuth();

        $response = $this->sendRequest($request);

        $content = $response->getContent();

        $marker = '// Callback data //';

        if (strpos($content, $marker) !== false)
        {
            $content = $this->getPaymentJsonFromCallback($content);

            $response->setContent($content);
        }

        return $response;
    }

    protected function getPaymentStatus($id)
    {
        $request = [
            'method'    => 'GET',
            'url'       => '/payments/'.$id.'/status',
            'content'   => []
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function getOtp()
    {
        return $this->otp ?: '123456';
    }

    protected function setOtp($otp)
    {
        $this->otp = $otp;
    }

    protected function getRedirectTo3ds()
    {
        if ($this->redirectTo3ds === null)
        {
            return false;
        }

        return $this->redirectTo3ds;
    }

    protected function setRedirectTo3ds($bool)
    {
        $this->redirectTo3ds = $bool;
    }

    protected function getFeesForPayment($payment)
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/create/fees',
            'content' => $payment);

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function getFeesForPaymentS2S($payment)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments/fees',
            'content' => $payment
        ];

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function capturePayment($id, $amount, $currency = 'INR', $verifyAmount = 0, $status = 'captured')
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $id . '/capture',
            'content' => array('amount' => $amount));

        if ($currency !== 'INR')
        {
            $request['content']['currency'] = $currency;
        }

        $this->ba->privateAuth();
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        if ($verifyAmount !== 0)
        {
            $this->assertEquals($content['amount'], $verifyAmount);
        }
        else
        {
            $this->assertEquals($content['amount'], $amount);
        }

        $this->assertEquals($content['status'], $status);

        return $content;
    }

    protected function capturePaymentByPartnerAuth(string $id, int $amount, $client, string $subMerchantId): mixed
    {
        $server = [
            'HTTP_X-Razorpay-Account' => $subMerchantId,
        ];

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $id . '/capture',
            'content' => array('amount' => $amount, 'currency' => 'INR'),
            'server'  => $server
        );

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);

        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['status'], 'captured');

        return $content;
    }

    protected function capturePaymentByOAuth(string $id, int $amount, string $accessToken): mixed
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $id . '/capture',
            'content' => array('amount' => $amount, 'currency' => 'INR'),
        );

        $this->ba->oauthBearerAuth($accessToken);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);

        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['status'], 'captured');

        return $content;
    }

    protected function cancelPayment($id, $content = [])
    {
        $request = array(
            'method'  => 'GET',
            'url'     => '/payments/'.$id.'/cancel',
            'content' => $content
        );

        $this->ba->publicAuth();
        return $this->makeRequestAndGetContent($request);

        // $this->assertArrayHasKey('status', $content);
        // $this->assertEquals($content['status'], 'failed');
    }

    protected function transferPayment(string $id, array $transfers)
    {
        $request = [
            'method'        => 'POST',
            'url'           => '/payments/' . $id . '/transfers',
            'content'       => [
                'transfers' => $transfers,
            ],
        ];

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function addPaymentMetadata($id, $content)
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/'.$id.'/metadata',
            'content' => $content);

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function verifyPayment($id)
    {
        $request = array(
            'url'    => '/payments/'.$id.'/verify',
            'method' => 'GET');

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function verifyGatewayPayment($id)
    {
        $request = array(
            'url'    => '/payments/barricade/'.$id.'/verify',
            'method' => 'GET',
            'headers' => ['x-barricade-flow'=> 'true']);

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function verifyPaymentNew($id)
    {
        $request = array(
            'url'    => '/payments/'.$id.'/verify_new',
            'method' => 'POST');

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function verifyAllPayments()
    {
        $request = array(
            'url'     => '/payments/verify/all',
            'method'  => 'POST');

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function authorizedFailedPayment($id)
    {
        $request = array(
            'url'    => '/payments/'.$id.'/authorize_failed',
            'method' => 'POST');

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function verifyMultiplePayments($filter)
    {
        $request = array(
            'url'    => '/payments/verify/'.$filter,
            'method' => 'GET');

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function refund($params, $data = [])
    {
        $this->ba->privateAuth();

        $request = array(
            'method'    => 'POST',
            'url'       => '/refunds',
            'content'   => $params);

        $refund = $this->makeRequestAndGetContent($request);

        $this->scroogeRefund($refund, $data);

        return $refund;
    }

    protected function refundPayment($id, $amount = null, $data = [], $reversals = [], $reverseAll = false, $auth = [])
    {
        if ((empty($auth['key']) === false) and
            (empty($auth['secret']) === false))
        {
            $this->ba->privateAuth($auth['key'], $auth['secret']);
        }
        else
        {
            $this->ba->privateAuth();
        }

        $content = [];

        if ($amount !== null)
        {
            $content = array('amount' => $amount);
        }

        if (empty($data['speed']) === false)
        {
            $content['speed'] = $data['speed'];
        }

        if (empty($reversals) === false)
        {
            $content['reversals'] = $reversals;
        }

        if ($reverseAll === true)
        {
            $content['reverse_all'] = true;
        }

        if (empty($data['notes']) === false)
        {
            $content['notes'] = $data['notes'];
        }

        $request = [
            'method'    => 'POST',
            'url'       => '/payments/'.$id.'/refund',
            'content'   => $content
        ];

        $refund = $this->makeRequestAndGetContent($request);

        $this->assertEquals('refund', $refund['entity']);

        if ($amount !== null)
        {
            $this->assertEquals($amount, $refund['amount']);
        }

        $this->scroogeRefund($refund, $data);

        return $refund;
    }

    protected function scroogeRefund(array $refund, array $data = [])
    {
        $input = $this->getDefaultScroogeInputArray();

        // sets gateway in the context if missing
        if (empty($this->gateway) === true)
        {
            $payment = $this->getEntityById('payment', $refund['payment_id'], true);

            $this->gateway = $payment['gateway'] ?? null;
        }

        $input['gateway'] = $this->gateway;
        //
        // Support for internal id - PR https://github.com/razorpay/api/pull/18132
        //
        $input['id'] = $this->formatRefundId($refund['id']);

        $input['payment_id'] = substr($refund['payment_id'], strlen('pay_'));
        $input['attempts'] = $refund['attempts'] ?? 0;
        $input['amount'] = $refund['amount'] ?? $input['amount'];
        $input['base_amount'] = $refund['amount'] ?? $input['base_amount'];
        $input['is_fta'] = $data['is_fta'] ?? false;
        $input['status'] = $data['status'] ?? 'created';
        $input['created_at'] = $data['created_at'] ?? Carbon::now()->getTimestamp();
        $input['last_attempted_at'] = $input['created_at'];
        $input['transaction_id'] = $refund['transaction_id'] ?? null;

        if (isset($data['bank_account']) === true)
        {
            $input['fta_data'] = $data;
            $input['is_fta'] = true;
        }

        if (isset($data['fta_data']) === true)
        {
            $input['fta_data'] = $data['fta_data'];
        }

        if (isset($data['mode_requested']) === true)
        {
            $input['mode_requested'] = $data['mode_requested'];
        }

        $this->ba->scroogeAuth();

        $request = array(
            'method'  => 'POST',
            'url'     => '/refunds/'.$input['id'].'/gateway_verify',
            'content' => $input);

        $response = $this->makeRequestAndGetContent($request);

        $callRefund = $this->checkForRefundCall($response);

        if ($callRefund === true)
        {
            $request = array(
                'method'  => 'POST',
                'url'     => '/refunds/'.$input['id'].'/gateway_refund',
                'content' => $input);

            $response = $this->makeRequestAndGetContent($request);
        }

        $rrn = $response['gateway_keys']['rrn'] ?? null;

        if ($response['status_code'] === 'REFUND_SUCCESSFUL')
        {
            $this->scroogeUpdateRefundStatus($refund, 'processed_event', null, $rrn);
        }
        // Adding specific amount check - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        else if (isset($refund['amount']) === true)
        {
            $event = '';

            switch ($refund['amount'])
            {
                case 200:
                    $failed = $data['failed'] ?? false;

                    if ($failed === false)
                    {
                        $event = 'processed_event';
                    }

                    break;

                case 3459:
                    $event = 'failed_event';
                    break;

                case 3470:
                    $event = 'fee_only_reversal_event';
                    break;

                case 3471:
                    $event = 'processed_event';
                    $refund[RefundEntity::SPEED_PROCESSED] = 'instant';
                    break;

                // Emandate Debit
                case 4000:
                    $event = 'processed_event';
                    break;
            }

            if ($event !== '')
            {
                $this->scroogeUpdateRefundStatus($refund, $event);
            }
        }

        return $response;
    }

    protected function checkForRefundCall($response)
    {
        $verifyFailures = [
            '0',
            'GATEWAY_VERIFY_OLDER_REFUNDS_DISABLED',
            'GATEWAY_ERROR_REQUEST_ERROR',
            'REFUND_SUCCESSFUL',
            'GATEWAY_ERROR_UNEXPECTED_STATUS'
        ];

        if (in_array($response['status_code'], $verifyFailures, true) === true)
        {
            return false;
        }

        return true;
    }

    protected function scroogeUpdateRefundStatus(array $refund, $event, $status = null, $rrn = null)
    {
        $input = $this->getDefaultScroogeInputArray();

        //
        // Support for internal id - PR https://github.com/razorpay/api/pull/18132
        //
        $input['id'] = $this->formatRefundId($refund['id']);

        if (($this->gateway === Payment\Gateway::UPI_MINDGATE) or ($this->gateway === Payment\Gateway::UPI_ICICI))
        {
            $input['reference_no'] = random_integer(12);
        }

        if (empty($refund[RefundEntity::SPEED_PROCESSED]) === false)
        {
            $input[RefundEntity::SPEED_PROCESSED] = $refund[RefundEntity::SPEED_PROCESSED];
        }

        if ($rrn !== null)
        {
            $input['reference_no'] = $rrn;
        }

        $input['event'] = $event;
        $input['status'] = $status;

        $this->ba->scroogeAuth();

        $request = array(
            'method'    => 'PUT',
            'url'       => '/refunds/' . $input['id'] . '/update_status',
            'content'   => $input);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function disputePayment(Payment\Entity $payment, int $deduct = 0): array
    {
        $this->ba->adminAuth();

        $reason = $this->fixtures->create('dispute_reason');

        $content = [
            'gateway_dispute_id' => '4342frf34r',
            'raised_on'          => '946684800',
            'expires_on'         => '1912162918',
            'amount'             => $payment->getAmount(),
            'phase'              => 'chargeback',
            'deduct_at_onset'    => $deduct,
            'reason_id'          => $reason->getId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/' . $payment->getPublicId() . '/disputes',
            'content' => $content
        ];

        $dispute = $this->makeRequestAndGetContent($request);

        return $dispute;
    }

    protected function verifyRefund($id)
    {
        $this->ba->adminAuth();

        $content = [];

        $request = array(
            'method'  => 'POST',
            'url'     => '/refunds/'.$id.'/verify',
            'content' => $content);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function retryFailedRefund($id, $paymentId = null, $content = [], $data = [], $gateway = null)
    {
        $this->ba->adminAuth();

        $request = array(
            'method'  => 'POST',
            'url'     => '/refunds/' . $id . '/retry',
            'content' => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        $gateway = $gateway ?? $this->gateway;

        $response['id'] = $response['refund_id'];
        $response['payment_id'] = $paymentId;
        $response['attempts'] = 1;

        if (isset($data['amount']) === true)
        {
            $response['amount'] = $data['amount'];
        }

        if (isset($response['status']) === true)
        {
            $content['status'] = $response['status'];
        }

        if (isset($data['status']) === true)
        {
            $content['status'] = $data['status'];
        }

        $this->scroogeRefund($response, $content);

        return $response;
    }

    protected function refundAuthorizedPayment($id, array $input = [], $internal = false)
    {
        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/'.$id.'/authorize_refund',
            'content' => $input);

        // For testing route - internal_payment_authorize_refund
        // This route is used by PG Router.
        if ($internal === true)
        {
            $this->ba->appAuth();
            $request['url'] = '/internal' . $request['url'];
        }
        else
        {
            $this->ba->adminAuth();
        }

        $refund = $this->makeRequestAndGetContent($request);

        if ($internal === true)
        {
            $refund = $refund['data'];
        }

        $this->assertEquals('refund', $refund['entity']);

        if ($this->updatePaymentStatus($id, $input, true) === true)
        {
            return $refund;
        }

        $this->scroogeRefund($refund);

        return $refund;
    }

    // updatePaymentStatus: updates payment status when refunds are not created in API db
    protected function updatePaymentStatus($id = null, array $input = [], $admin = false)
    {
        $refundEntity = $this->getLastEntity('refund', $admin);

        if (empty($refundEntity) === true)
        {
            if (empty($id) === true)
            {
                $payment = $this->getDbLastPayment();
                $this->assertNotEmpty($payment);

                $id = $payment->getId();
            }

            $input['status']        = 'refunded';
            $input['refund_status'] = 'full';
            $input['refund_at']     = null;

            // update payment status
             $this->fixtures->base->editEntity('payment', $id, $input);

             return true;
        }

        return false;
    }

    protected function refundOldAuthorizedPayments($razorx = false, $offset = null)
    {
        $this->ba->cronAuth();

        $content = [];

        if (empty($offset) === false)
        {
            $content['offset'] = $offset;
        }

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/refund/authorized',
            'content' => $content);

        $data = $this->makeRequestAndGetContent($request);

        // razorx was set as flag, because to test with new refund flow, experiment is turned on
        if ($razorx === true)
        {
            return $data;
        }

        $this->scroogeRefund($this->getLastEntity('refund'));

        return $data;
    }

    protected function paymentRefundFetchFee(string $paymentId, int $amount)
    {
        $this->ba->proxyAuth();

        $request = [
            'url'     => '/refunds/fee',
            'method'  => 'get',
            'content' => [
                'amount'     => $amount,
                'payment_id' => $paymentId,
            ]
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function paymentScroogeFetchRefundFee(string $paymentId, int $amount, string $mode)
    {
        $this->ba->appAuth();

        $request = [
            'url'     => '/refunds/scrooge_fetch_fee',
            'method'  => 'get',
            'content' => [
                'amount'     => $amount,
                'payment_id' => $paymentId,
                'mode'       => $mode,
            ]
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function authorizeFailedPayment($id)
    {
        $request = array(
            'url'    => '/payments/'.$id.'/authorize_failed',
            'method' => 'post');

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function forceAuthorizeFailedPayment($id, $content)
    {
        $request = array(
            'url'     => '/payments/'.$id.'/force_authorize',
            'method'  => 'post',
            'content' => $content);

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function timeoutOldPayment($appendToPayload = null)
    {
        $this->ba->cronAuth();

        $request = [
            'url'     => '/payments/timeout',
            'content' => [
                'limit' => 10,
            ],
        ];

        if ($appendToPayload !== null)
        {
            $request['content'] = array_merge($request['content'], $appendToPayload);
        }

        return $this->makeRequestAndGetContent($request);
    }

    protected function timeoutOldEmandatePayment($recurringType)
    {
        $this->ba->cronAuth();

        $request = [
            'url'     => '/payments/timeout',
            'content' => [
                'limit' => 10,
                'recurring_type' => $recurringType,
                'methods' => ['emandate']
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function timeoutAuthenticatedPayment()
    {
        $this->ba->cronAuth();

        $request = [
            'url'     => '/payments/auth/timeout',
            'content' => [
                'limit' => 10,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getAndMatchPayment($id, $paymentResponse = [])
    {
        $testData['request']['url'] = '/payments/'.$id;
        $testData['request']['method'] = 'GET';

        $defaults = array(
            'id'                => $id,
            'status'            => 'authorized',
            'refund_status'     => null,
            'amount_refunded'   => 0,
            'error_code'        => null,
            'error_description' => null,
            'order_id'          => null,
            'currency'          => 'INR',
            'entity'            => 'payment');

        $payment = array_merge($defaults, $paymentResponse);
        $testData['response']['content'] = $payment;

        $this->ba->privateAuth();
        return $this->runRequestResponseFlow($testData);
    }

    protected function getAndMatchPaymentForMyMerchant($id, $paymentResponse = [])
    {
        $testData['request']['url'] = '/payments/'.$id;
        $testData['request']['method'] = 'GET';

        $defaults = array(
            'id'                => $id,
            'status'            => 'authorized',
            'refund_status'     => null,
            'amount_refunded'   => 0,
            'error_code'        => null,
            'error_description' => null,
            'order_id'          => null,
            'currency'          => 'MYR',
            'entity'            => 'payment');

        $payment = array_merge($defaults, $paymentResponse);
        $testData['response']['content'] = $payment;

        $this->ba->privateAuth();
        return $this->runRequestResponseFlow($testData);
    }

    protected function fetchPayment($paymentId, $content = [])
    {
        $request['url'] = '/payments/'.$paymentId;
        $request['method'] = 'GET';

        $request['content'] = $content;

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function fetchPaymentWithCpsResponse($paymentId, $CpsResponse, $content = [])
    {
        $request['url'] = '/payments/'.$paymentId;
        $request['method'] = 'GET';

        $request['content'] = $content;

        $this->ba->privateAuth();

        $paymentFetchResponse = $this->makeRequestAndGetContent($request);

        $paymentFetchResponse['acquirer_data']['authentication_reference_number'] = $CpsResponse['gateway_reference_id2'];
        $paymentFetchResponse['authentication']['version'] = $CpsResponse['protocol_version'];
        $paymentFetchResponse['authentication']['authentication_channel'] = $CpsResponse['notes'];

        return $paymentFetchResponse;
    }

    protected function fetchRefundsForPayment($paymentId)
    {
        $request['url'] = '/payments/'.$paymentId.'/refunds';
        $request['method'] = 'GET';

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultPaymentEntityArray()
    {
        $payment = $this->getDefaultPaymentArray();

        unset($payment['card']);
        $payment['status'] = 'authorized';
        $payment['refund_status'] = 'none';
        $payment['amount_authorized'] = $payment['amount'];
        $payment['amount_refunded'] = '0';
        $payment['terminal_id'] = '1n25f6uN5S1Z5a';

        return $payment;
    }

    protected function getDefaultScroogeInputArray()
    {
        $refund = [
            'amount'                => 100,
            'base_amount'           => 100,
            'currency'              => 'INR',
            'merchant_id'           => '10000000000000',
            'method'                => 'card',
            'payment_amount'        => 100,
            'payment_base_amount'   => 100,
            'payment_created_at'    => 1536067732,
        ];

        return $refund;
    }

    protected function getDefaultPaymentArrayNeutral()
    {
        //
        // default payment object
        //
        $payment = [
            'amount'            => '50000',
            'currency'          => 'INR',
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => [
                'merchant_order_id' => 'random order id',
            ],
            'description'       => 'random description',
            'bank'              => 'UCBA',
        ];

        return $payment;
    }

    protected function getDefaultPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        );


        return $payment;
    }

    protected function getDefaultPaymentArrayForMYMerchant()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['currency'] = 'MYR';

        $payment['card'] = array(
            'number'            => '5140241918501669',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2030',
            'cvv'               => '566',
        );


        return $payment;
    }

    protected function getDefaultRecurringPaymentArray()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['recurring'] = true;

        $payment['customer_id'] = 'cust_100000customer';
        $payment['acs_afa_authentication'] = array();
        $payment['validate_payment']['afa_required'] = false;
        $payment['network_transaction_id'] = "039217544591994";

        return $payment;
    }

    protected function getDefaultRecurringPaymentArrayForMYMerchant()
    {
        $payment = $this->getDefaultPaymentArrayForMYMerchant();

        $payment['recurring'] = true;

        $payment['acs_afa_authentication'] = array();
        $payment['validate_payment']['afa_required'] = false;
        $payment['network_transaction_id'] = "039217544591994";

        return $payment;
    }

    protected function getDefaultUpiRecurringPaymentArray()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['recurring'] = true;
        $payment['customer_id'] = 'cust_100000customer';

        return $payment;
    }

    protected function getEmandatePaymentArray($bank = 'HDFC', $authType = 'netbanking', $amount = 2000)
    {
        $payment = $this->getDefaultNetbankingPaymentArray($bank);

        $payment['method'] = Payment\Method::EMANDATE;
        $payment['bank'] = $bank;
        $payment['amount'] = $amount;
        $payment['auth_type'] = $authType;
        $payment['recurring'] = true;

        $payment['customer_id'] = 'cust_100000customer';

        if ($authType === Payment\AuthType::AADHAAR)
        {
            $payment['aadhaar']['number'] = '123123123123';

            $payment['bank_account'] = [
                'account_number' => '914010009305862',
                'ifsc'           => 'utib0000123',
                'name'           => 'Test account',
                'account_type'   => 'savings',
            ];
        }

        return $payment;
    }

    protected function getEmandateNetbankingRecurringPaymentArray($bank = 'HDFC', $amount = 4000)
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['card']);

        $payment['bank'] = $bank;
        $payment['amount'] = $amount;

        if (in_array($bank, Payment\Gateway::$zeroRupeeEmandateBanks, true) === true)
        {
            $payment['amount'] = 0;
        }

        $payment['method'] = Payment\Method::EMANDATE;
        $payment['auth_type'] = Payment\AuthType::NETBANKING;

        $payment['customer_id'] = 'cust_100000customer';

        return $payment;
    }

    protected function getNachNetbankingRecurringPaymentArray($bank = 'HDFC', $amount = 4000)
    {
        $payment = $this->getDefaultPaymentArray();
        unset($payment['card']);

        $payment['bank'] = $bank;
        $payment['amount'] = $amount;


        $payment['amount'] = 0;

        $payment['method'] = Payment\Method::NACH;
        $payment['auth_type'] = Payment\AuthType::NETBANKING;

        $payment['customer_id'] = 'cust_100000customer';

        return $payment;
    }

    protected function getOtmInitialPaymentArray()
    {
        $payment = $this->getDefaultUpiPaymentArray();
        unset($payment['card']);

        $payment['recurring'] = 1;
        $payment['amount'] = 0;

        $payment['customer_id'] = 'cust_100000customer';

        $payment['recurring_token']['max_amount'] = 4000;

        $payment['recurring_token']['expire_by'] = Carbon::now()->addDays(3)->getTimestamp();

        $payment['recurring_token']['start_time'] = Carbon::now()->getTimestamp();

        return $payment;
    }

    protected function getDefaultEmiPaymentArray($saved)
    {
        $card = null;

        if ($saved === true)
        {
            $card = [
                'cvv' => 111
            ];
        }
        else
        {
            $card = [
                'number'       => '41476700000006',
                'name'         => 'Harshil',
                'expiry_month' => '12',
                'expiry_year'  => '2024',
                'cvv'          => '566'
            ];
        }

        $payment = $this->getDefaultPaymentArrayNeutral();

        $attributes = [
            'amount'       => '300000',
            'method'       => 'emi',
            'emi_duration' => '9',
            'card'         => $card,
            'bank'         => 'ICIC',
        ];

        $payment = array_merge($payment, $attributes);

        return $payment;
    }

    protected function getDefaultUpiPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'upi';
        $payment['vpa'] = 'vishnu@icici';

        return $payment;
    }

    protected function getDefaultUpiIntentPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'upi';

        return $payment;
    }

    protected function getDefaultUpiBlockPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method'] = 'upi';

        $payment['upi'] = [
            'vpa'   => 'vishnu@icici'
        ];

        return $payment;
    }

    protected function getDefaultUpiBlockIntentPaymentArray()
    {
        $payment = $this->getDefaultUpiBlockPaymentArray();

        $payment['upi'] = [
            'flow'  => 'intent'
        ];

        unset($payment['upi']['vpa']);

        return $payment;
    }

    protected function getDefaultUpiOtmPayment()
    {
        $payment = $this->getDefaultUpiBlockPaymentArray();

        $payment['upi']['type'] = 'otm';

        $payment['upi']['flow'] = 'collect';

        $payment['upi']['start_time'] = Carbon::now(Timezone::IST)->getTimestamp();

        $payment['upi']['end_time'] = Carbon::now(Timezone::IST)->addDays(1)->getTimestamp();

        return $payment;
    }

    protected function getDefaultAepsPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['method']                 = 'aeps';
        $payment['aadhaar']['fingerprint'] = 'sample fingerprint data';
        $payment['aadhaar']['session_key'] = str_repeat('abcdefgh', 43);
        $payment['aadhaar']['hmac']        = str_repeat('smplhmac', 8);
        $payment['aadhaar']['number']      = '123456789012';
        $payment['aadhaar']['cert_expiry'] = '20191230';

        return $payment;
    }

    protected function generateRefundsExcelForNb($bank)
    {
        $this->ba->cronAuth();

        $request = array(
            'url'     => '/refunds/excel',
            'method'  => 'post',
            'content' => [
                'bank'   => $bank,
                'method' => 'netbanking',
            ],
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultNetbankingPaymentArray($bank = null)
    {
        $payment = $this->getDefaultPaymentArrayNeutral();
        $payment['method'] = 'netbanking';

        if ($bank !== null)
        {
            $payment['bank'] = $bank;
        }

        return $payment;
    }

    protected function getDefaultTokenPanPaymentArray()
    {
        $payment = $this->getDefaultPaymentArrayNeutral();

        $payment['card'] = array(
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
            'cryptogram_value'  => 'test',
            'tokenised'         => true,
            'token_provider'    => 'PayU'
        );

        return $payment;
    }

    protected function getDefaultWalletPaymentArray($wallet = 'mobikwik')
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'wallet';
        $payment['wallet'] = $wallet;

        unset($payment['card'], $payment['bank']);

        return $payment;
    }

    protected function getDefaultOpenwalletPaymentArray($customerId = null, $amount = null)
    {
        $payment = $this->getDefaultWalletPaymentArray('openwallet');

        if ($customerId !== null)
        {
            $payment['customer_id'] = $customerId;
        }

        $payment['amount'] = $amount ?? $payment['amount'];

        unset($payment['bank'], $payment['card']);

        return $payment;
    }

    protected function getDefaultCardlessEmiPaymentArray($provider)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'cardless_emi';
        $payment['provider'] = $provider;
        $payment['emi_duration'] = 6;

        unset($payment['card'], $payment['bank']);

        return $payment;
    }

    protected function getDefaultCredPayment()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'app';
        $payment['amount'] = 100000;
        $payment['provider'] = 'cred';
        $payment['app_present'] = true;
        $payment['_']['device'] = 'mobile';

        return $payment;
    }

    protected function getDefaultAppPayment($provider)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'app';
        $payment['amount'] = 100000;
        $payment['contact'] = '+91'. $payment['contact'];
        $payment['provider'] = $provider;

        unset($payment['card'], $payment['bank']);

        return $payment;
    }

    protected function getDefaultPayLaterPaymentArray($provider)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'paylater';
        $payment['provider'] = $provider;
        $payment['contact'] = '+91'. $payment['contact'];

        unset($payment['card'], $payment['bank']);

        return $payment;
    }

    protected function getDefaultUpiUnexpectedPaymentArray()
    {
        return [
            'upi' => [
                'npci_reference_id'     => '123456789012',
                'status_code'           => 'success',
                'gateway_payment_id'    => '4531245576',
                'account_number'        => '12345778690',
                'ifsc'                  => 'SBI0000230103',
                'npci_txn_id'           => 'AXId27bf16312dc428ab7a305ea57e20393',
                'merchant_reference'    => 'IShcnbF6tsOy',
                'gateway_merchant_id'   => 'SBI0000000000119',
                'vpa'                   => 'razor.pay@sbi',
                'gateway_data'          => [
                    'addInfo2' => '7971807546'
                ],
            ],
            'payment' => [
                'method'    => 'upi',
                'amount'    => 50000,
                'currency'  => 'INR',
                'vpa'       => 'razor.pay@sbi',
                'contact'   => '+919999999999',
                'email'     => 'void@razorpay.com',

            ],
            'terminal' => [
                'gateway'             => 'upi_sbi',
                'gateway_merchant_id' => 'SBI0000000000119',
            ],
            'meta' => [
                "art_request_id" => '123423454',
                "version"        => 'api_v2',
            ]
        ];
    }

    protected function getDefaultUpiAuthorizeFailedPaymentArray()
    {
        return [
            'netbanking' => [
                'gateway'              => '',
            ],
            'upi' => [
                'npci_reference_id'     => '123456789013',
                'gateway_payment_id'    => '4531245576',
                'npci_txn_id'           => 'AXId27bf16312dc428ab7a305ea57e20393',
                'merchant_reference'    => 'IShcnbF6tsOy',
                'vpa'                   => 'razor.pay@sbi',
                'gateway'               => 'upi_sbi',
            ],
            'payment' => [
                'method'    => 'upi',
                'amount'    => 50000,
            ],
            'meta' => [
                'art_request_id' => '123423454',
                'version'        => 'api_v2',
            ]
        ];
    }

    protected function getDefaultNetbankingAuthorizeFailedPaymentArray()
    {
        return [
            'netbanking' => [
                'status_code'            => 'Transaction_status field from MIS corresponds to status',
                'gateway_transaction_id' => '',
	            'bank_transaction_id'    => '222649038989',
	            'bank_account_number'    => '00061330021110',
                'gateway'                => 'netbanking_sbi'
            ],
            'upi' => [
                'gateway'               => '',
            ],
            'payment' => [
                'method'    => 'netbanking',
                'amount'    => 50000,
                'id'        => 'IShcnbF6tsOy'
            ],
            'meta' => [
                'art_request_id' => '123423454',
                'version'        => 'api_v2',
            ]
        ];
    }


    protected function getDefaultCardForceAuthorizePayload()
    {
        return [
             'card' => [
                'auth_code'     => '123456789013',
                'rrn'           => '4531245576',
                'arn'           => 'AXId27bf16312dc428ab7a305ea57e20393',
                'gateway'       => 'axis',
            ],
            'payment' => [
                'id'        =>  'LYW58PWqtYdvVl',
                'method'    => 'card',
                'amount'    => 50000,
            ],
            'meta' => [
                'art_request_id' => '123423454',
                'version'        => 'api_v2',
            ]
        ];
    }

    protected function markForceAuthorizeFailedPaymentAndGetPayment(array $content)
    {
        $request = [
            'url'      => '/payments/authorize/card/failed',
            'method'   => 'POST',
            'content'  => $content,
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultUpiPostReconArray()
    {
        return [
            'upi' => [
                'npci_reference_id'     => '123456789012',
                'gateway_payment_id'    => '4531245576',
                'npci_txn_id'           => 'AXId27bf16312dc428ab7a305ea57e20393',
            ],
                'payment_id'      => 'IShcnbF6tsOy',
                'reconciled_type' => 'mis',
                'amount'          => 50000,
                'reconciled_at'   => '1642476459',
        ];
    }

    protected function getDefaultNetbankingPostReconArray()
    {
        return [
            'netbanking' => [
                'gateway_transaction_id'    => '',
                'bank_transaction_id'       => '222649038989',
                'bank_payment_id'           => '00061330021110',
            ],
            'upi' => [],
            'payment_id'      => 'KCweWA1oWlWWO2',
            'reconciled_type' => 'mis',
            'amount'          => 5000,
            'reconciled_at'   => '1642476459',
        ];
    }


    protected function getDefaultCardPostReconArray()
    {
        return [
            'card' => [
                'arn'                 => '123455789012',
                'rrn'                 => '4531245576',
                'auth_code'           => '433455',
                'gateway_fee'         => '118',
                'gateway_service_tax' => '18'
            ],
                'payment_id'      => 'IShcnbF6tsOy',
                'reconciled_type' => 'mis',
                'amount'          => 50000,
                'reconciled_at'   => '1642476459',
        ];
    }

  protected function getDefaultArtPayloadForCreatingTransaction()
    {
        return [
                'payment_id'      => 'IShcnbF6tsOy',
                'art_request_id'          => 'qwywqergweru'
        ];
    }

    protected function sendRequest($request, &$callback = null)
    {
        $this->checkAndSetUrl($request);

        $response = $this->makeRequestParent($request);


        $url = $request['url'];

        if ($this->isPaymentCreationUrl($url))
        {
            $this->resetSingletons();

            $response = $this->handlePaymentCreationFlow($response, $request, $callback);

        }
        return $response;
    }

    protected function decryptGatewayText($gateway)
    {
        list($gateway, ) = explode('__', \Crypt::decrypt($gateway, 2));

        return $gateway;
    }

    protected function getIdFromUri($uri)
    {
        // The url should be of format http://localhost/v1/payments/{id}/callback
        // We will simply extract the id from it.

        $id = getTextBetweenStrings($uri, '/payments/', '/callback');

        return $id;
    }

    protected function checkAndSetUrl(& $request)
    {
        if (isset($request['url']) === false)
        {
            $request['url'] = '/payments';
        }
    }

    protected function replaceDefaultValues(array & $content)
    {
        $data = $this->getDefaultPaymentArray();

        $this->replaceValuesRecursively($data, $content);

        $content = $data;
    }

    protected function makeRequestAndGetFormData($url, $method, $headers = [], $data = [], $options = [])
    {
        if (isset($options['timeout']) === false)
            $options['timeout'] = 30;

        $response = Requests::$method($url, $headers, $data, $options);

        list ($uri, $method, $values) = $this->getFormDataFromResponse($response->body, $url);

        return [$uri, $method, $values, $response];
    }

    protected function makeCreateTransactionRequestAndGetContent(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/payments/recon/create/transaction',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    private function makeUpdatePostReconRequestAndGetContentForCard(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function getFormRequestFromResponse($content, $url)
    {
        list($url, $method, $content) = $this->getFormDataFromResponse($content, $url);

        return compact('url', 'method', 'content');
    }

    protected function getFormDataFromJsonResponse(\Illuminate\Http\JsonResponse $response)
    {
        $data = $response->getData(true);
        $request = $data['request'];

        $url = $request['url'];
        $method = $request['method'];

        $values = isset($request['content']) ? $request['content'] : [];

        return [$url, $method, $values];
    }

    protected function getFormDataFromResponse($content, $url)
    {
        $crawler = new Crawler($content, $url);

        $form = $crawler->filter('form')->form();

        return $this->getDataFromForm($form);
    }

    protected function getSecondFormDataFromResponse($content)
    {
        $url = 'http://localhost';

        $crawler = new Crawler($content, $url);

        $last = $crawler->filter('form')->last();

        if (count($last) === 0)
            return false;

        $form = $last->form();

        list(, , $content) = $this->getDataFromForm($form);

        return $content;
    }

    protected function getDataFromForm($form)
    {
        $uri = $form->getUri();

        $method = $form->getMethod();
        $values = $form->getValues();

        return array($uri, $method, $values);
    }

    protected function setMockGatewayTrue()
    {
        $var = 'gateway.mock_'.$this->gateway;

        $this->config[$var] = true;
    }

    protected function isGatewayMocked()
    {
        $gateway = $this->app['config']->get('gateway');

        if ($this->gateway === null)
            $this->gateway = 'hdfc';

        $var = 'mock_' . $this->gateway;

        if (isset($gateway[$var]))
        {
            return $gateway['mock_' . $this->gateway];
        }

        return false;
    }

    protected function getMetaRefreshUrl($response)
    {
        $crawler = new Crawler($response->getContent());

        $contents = $crawler->filterXpath("//meta[@http-equiv='refresh']")->extract(array('content'));

        if (count($contents) === 0)
        {
            return '';
        }

        preg_match('/0;url=(.*)/', $contents[0], $matches);

        if (count($matches) !== 2)
        {
            return '';
        }

        return $matches[1];
    }

    protected function getDataForGatewayRequest($response, &$callback = null)
    {
        $url = $values = $method = null;

        if ($callback)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;

            $request = $content['request'];
            $url = $content['request']['url'];

            $values = [];
            $method = $request['method'];
            $gateway = $this->decryptGatewayText($content['gateway']);

            if (($method === 'post') and
                (isset($request['content'])))
            {
                $values = $request['content'];
            }
            if (($method === 'get') and
                (isset($request['content'])) and
                (Payment\Gateway::isGatewaySupportingGetRedirectForm($gateway)) === true)
            {
                $values = $request['content'];
            }
        }
        else if ($this->isResponseInstanceType($response, 'json'))
        {
            list($url, $method, $values) = $this->getFormDataFromJsonResponse($response->baseResponse);
        }
        else
        {
            if ($response->getStatusCode() === 302)
            {
                $url = $response->getTargetUrl();
                $method = 'get';
                $values = [];
            }
            else
            {
                list($url, $method, $values) = $this->getFormDataFromResponse(
                                                    $response->getContent(),
                                                    'https://localhost');
            }
        }

        return array($url, $method, $values);
    }

    public function getLocalMerchantCallbackUrl()
    {
        if ($this->merchantCallbackUrl !== null)
        {
            return $this->merchantCallbackUrl;
        }

        $params = ['key_id' => $this->ba->getKey()];
        $url = \URL::route('dummy_return_callback', $params, false);
        $url = 'http://localhost'.$url;

        $this->merchantCallbackUrl = $url;

        return $url;
    }

    /**
     * Get Otp Submit Url
     */
    public function getOtpSubmitUrl($payment)
    {
        $secret = \App::make('config')->get('app.key');

        $hash = hash_hmac('sha1', $payment->getPublicId(), $secret);

        $params = [
            'x_entity_id' => $payment->getPublicId(),
            'hash' => $hash,
            'key_id' => $this->ba->getKey()
        ];

        $url = \URL::route('payment_otp_submit', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    /**
     * Get Otp Submit Url
     */
    public function getPaymentRedirectTo3dsUrl($paymentId)
    {
        $params = [
            'x_entity_id' => $paymentId,
            'key_id' => $this->ba->getKey()
        ];

        $url = \URL::route('payment_redirect_3ds', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    public function getPaymentRedirectToAuthorizrUrl($trackId)
    {
        $params = [
            'id' => $trackId,
        ];

        $url = \URL::route('payment_redirect_to_authenticate_get', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    public function getPaymentRedirectToDCCInfoUrl($trackId)
    {
        $params = [
            'id' => $trackId,
        ];

        $url = \URL::route('payment_redirect_to_dcc_info', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    public function getPaymentRedirectToUpdateDCCAndAuthorize($trackId)
    {
        $params = [
            'id' => $trackId,
        ];

        $url = \URL::route('payment_update_and_redirect', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    public function getPaymentRedirectToAddressCollectUrl($trackId)
    {
        $params = [
            'id' => $trackId,
        ];

        $url = \URL::route('payment_redirect_to_address_collect', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    /**
     * Get Otp resend Url
     */
    public function getOtpResendUrl($paymentId)
    {
        $params = [
            'x_entity_id' => $paymentId,
            'key_id' => $this->ba->getKey()
        ];

        $url = \URL::route('payment_otp_resend', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    /**
     * Checks the laravel class of $response,
     * whether it's json, http or redirect.
     * @param  string  $type
     * @param  mixed   $response
     * @return boolean
     */
    protected function isResponseInstanceType($response, $type = 'json')
    {
        $response = $response->baseResponse;

        $match = 'Response';

        if ($type !== 'http')
            $match = ucfirst($type) . $match;

        $match = 'Illuminate\Http\\'.$match;

        $class = get_class($response);

        return ($match === $class);
    }

    protected function assertResponse($type, $response)
    {
        $this->assertTrue($this->isResponseInstanceType($response, $type));
    }

    protected function mockServerContentFunction($closure, $gateway = null)
    {
        $server = $this->mockServer($gateway)
                       ->shouldReceive('content')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->setMockServer($server, $gateway);

        return $server;
    }

    protected function mockServerRequestFunction($closure, $gateway = null)
    {
        $server = $this->mockServer($gateway)
                       ->shouldReceive('request')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->setMockServer($server, $gateway);

        return $server;
    }

    protected function mockServer($gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        $class = $this->app['gateway']->getServerClass($gateway);

        return Mockery::mock($class, [])->makePartial();
    }

    protected function getMockServer($gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        return $this->app['gateway']->server($gateway);
    }

    protected function setMockServer($server, $gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        return $this->app['gateway']->setServer($gateway, $server);
    }

    protected function resetMockServer($gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        return $this->app['gateway']->resetServer($this->gateway);
    }

    protected function resetGatewayDriver($gateway = null)
    {
        $gateway = $gateway ?: $this->gateway;

        return $this->app['gateway']->resetDriver($this->gateway);
    }

    protected function mockMaxmind()
    {
        $maxmind = Mockery::mock('RZP\Services\Mock\MaxMind')->makePartial();

        $maxmind->shouldReceive('query')
                ->with(Mockery::type('RZP\Models\Payment\Entity'))
                ->andReturnUsing(function ($payment)
                {
                    $bin = $payment->card->getIin();

                    $binRiskMapping = [
                        '341111' => '22.0',
                        '510510' => '22.0',
                        '401201' => '15.3',
                        '555555' => '2.4',
                        '514906' => '35.0',
                    ];

                    if (isset($binRiskMapping[$bin]) === true)
                    {
                        return ['riskScore' => $binRiskMapping[$bin]];
                    }

                    return null;
                });

        $this->app->instance('maxmind', $maxmind);
    }

    public function startGatewayRefundRecordCron($gateway)
    {
        $request = [
            'url'     => '/refunds/' . $gateway . '/create_record',
            'action'  => 'post',
            'content' => [],
        ];

        $this->ba->appAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function startGatewayRefundValidateCron(string $gateway)
    {
        $request = [
            'url'     => '/refunds/' . $gateway . '/validate',
            'action'  => 'post',
            'content' => [],
        ];

        $this->ba->appAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function getVerificationSkipError()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\PaymentVerificationException(
                ['test' => 'test'],
                '',
                Action::FINISH);
        });
    }

    public function getFatalErrorInVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\FatalThrowableError();
        });
    }

    public function getTimeoutInVerify()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\GatewayTimeoutException(
                'cURL error 28: Operation timed out after ' .
                '10001 milliseconds with 0 bytes received');
        });
    }

    public function getVerificationRetryError()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\PaymentVerificationException(
                ['test' => 'test'],
                '',
                Action::RETRY);
        });
    }

    public function getVerificationBlockError()
    {
        $this->mockServerContentFunction(function (& $content)
        {
            throw new Exception\PaymentVerificationException(
                ['test' => 'test'],
                '',
                Action::BLOCK);
        });
    }

    protected function getGatewayRequestException()
    {
        $this->i = true;

        $this->mockServerContentFunction(function (& $content)
        {
            if ($this->i === true)
            {
                $this->i = false;

                $content = [
                    'status_code'   => 500,
                ];

                throw new Exception\GatewayRequestException(
                    'cURL error 35: LibreSSL SSL_connect: SSL_ERROR_SYSCALL in connection to upi.hdfcbank.com:443 ',
                    new \WpOrg\Requests\Exception\Transport\Curl('SSL_ERROR_SYSCALL in connection to upi.hdfcbank.com:443 ',
                        'curlerror',
                        'cURL error 35: LibreSSL SSL_connect: SSL_ERROR_SYSCALL in connection to upi.hdfcbank.com:443 ',
                        35));
            }

        });
    }

    protected function mockMozartWebhookTranslateRequest($closure, $times = 1)
    {
        $mozart = Mockery::mock('RZP\Services\Mozart')->makePartial();

        $mozart->shouldAllowMockingProtectedMethods();

        $mozart->shouldReceive('translateWebhook')
                ->times($times)
                ->andReturnUsing($closure);

        $this->app->instance('mozart', $mozart);

        return $mozart;
    }

    protected function mockShield($shouldAssertBillingAddress = false)
    {
        $shield = Mockery::mock('RZP\Services\Mock\Shield')->makePartial();

        $shield->shouldReceive('getRiskAssessment')
                ->with(Mockery::type('RZP\Models\Payment\Entity'),  Mockery::type('array'))
                ->andReturnUsing(function ($payment, $input) use ($shouldAssertBillingAddress)
                {
                    $bin = $payment->card->getIin();

                    $riskData = [];

                    $binWithHighRiskScore = [
                        '514906',
                        '556763',
                    ];

                    $binConfirmedRiskMapping = [
                        '401201',
                    ];

                    $binSuspectedRiskMapping = [];

                    if (in_array($bin, $binWithHighRiskScore) === true)
                    {
                        $riskScore = 35;
                    }
                    else
                    {
                        $riskScore = 0.01;
                    }

                    if (in_array($bin, $binConfirmedRiskMapping) === true)
                    {
                        $recommendedAction = ShieldConstants::ACTION_BLOCK;
                    }
                    elseif (in_array($bin, $binSuspectedRiskMapping) === true)
                    {
                            $recommendedAction = ShieldConstants::ACTION_REVIEW;
                    }
                    else
                    {
                            $recommendedAction = ShieldConstants::ACTION_ALLOW;
                    }

                    switch ($recommendedAction)
                    {
                        case ShieldConstants::ACTION_BLOCK:
                            $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::CONFIRMED;
                            $riskData[Risk\Entity::REASON]     = Risk\RiskCode::PAYMENT_CONFIRMED_FRAUD_BY_SHIELD;
                            $riskData[Risk\Entity::RISK_SCORE] = $riskScore;

                            break;

                        case ShieldConstants::ACTION_REVIEW:
                            $riskData[Risk\Entity::FRAUD_TYPE] = Risk\Type::SUSPECTED;
                            $riskData[Risk\Entity::REASON]     = Risk\RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_SHEILD;
                            $riskData[Risk\Entity::RISK_SCORE] = $riskScore;

                            break;

                        default:
                            $riskData[Risk\Entity::RISK_SCORE] = $riskScore;

                            break;
                    }

                    if ($shouldAssertBillingAddress === true)
                    {
                        $this->assertNotNull($input['billing_address']);
                    }

                    return $riskData;
                });

        $this->app->instance('shield.service', $shield);
    }

    /**
     * @param $payment
     * @param $clientId
     * @param $submerchantId Signed submerchant id
     *
     * @return bool|mixed|string
     */
    protected function doPartnerAuthPayment($payment, $clientId, $submerchantId)
    {
        $server = [
            'HTTP_X-Razorpay-Account' => $submerchantId,
        ];

        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment,
            'server'  => $server,
        ];

        $this->ba->publicAuth('rzp_test_partner_' . $clientId);

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * @param $payment
     * @param $client
     * @param $submerchantId
     *
     * @return bool|mixed|string
     */
    protected function doS2SPartnerAuthPayment($payment, $client, $submerchantId)
    {
        $server = [
            'HTTP_X-Razorpay-Account' => $submerchantId,
        ];

        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/redirect',
            'content' => $payment,
            'server'  => $server,
        ];

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * @param $payment
     * @param $client
     * @param $submerchantId
     *
     * @return bool|mixed|string
     */
    protected function doS2SJsonPartnerAuthPayment($payment, $client, $submerchantId)
    {
        $server = [
            'HTTP_X-Razorpay-Account' => $submerchantId,
        ];

        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/json',
            'content' => $payment,
            'server'  => $server,
        ];

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());
        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function mockFundAccountService($callable = null)
    {
        $fts = Mockery::mock('RZP\Services\FTS\CreateAccount', [$this->app])->makePartial();

        $callable = $callable ?: function ($endpoint, $method, $data = [])
        {
            switch ($endpoint)
            {
                case '/account':
                    $response = [
                        'body' => [
                            'fund_account_id' => random_integer(2),
                        ],
                        'code' => 201
                    ];

                    return $response;

                case '/source_account':
                    $response = [
                            'body'=> [
                                'message' => 'source account registered',
                            ]
                        ];
                    return $response;

                case '/direct_source_accounts':
                    $response = [
                        'body'=> [
                            'fund_account_id' => random_integer(2),
                            'source_account_ids' => [random_integer(2),random_integer(2)],
                        ],
                        'code' => 200
                    ];

                    return $response;

                default:
                    return null;
            }
        };

        $fts->shouldReceive('createAndSendRequest')
            ->andReturnUsing($callable);

        $this->app->instance('fts_create_account', $fts);
    }

    protected function setDefaultMerchantMethods()
    {
        // Disable all methods and only enable card.
        // The default pricing plan has only card enabled

        $this->fixtures->merchant->disableAllMethods();

        $this->fixtures->merchant->enableCard();
    }

    protected function setMerchantBanks(array $banks)
    {
        $request = [
            'url'     => '/merchants/10000000000000/banks',
            'method'  => 'post',
            'content' => [
                'banks' => $banks,
            ]
        ];

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function createPricingPlan($pricingPlan = [])
    {
        $defaultPricingPlan = [
            'plan_name'           => 'TestPlan1',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'DICL',
            'payment_issuer'      => 'HDFC',
            'percent_rate'        => 1000,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
        ];

        $pricingPlan = array_merge($defaultPricingPlan, $pricingPlan);

        $plan = $this->fixtures->create('pricing', $pricingPlan);

        $plan = $plan->toArray();

        $plan['id'] = $plan['plan_id'];

        return $plan;
    }

    protected function createBuyPricingPlan()
    {
        $request = array(
            'method'    => 'POST',
            'url'       => '/buy_pricing',
            'content'   => $this->getDefaultBuyPricingPlan(),
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultBuyPricingPlan()
    {
        $rules = [
            [
                'payment_method'        => 'card',
                'payment_method_type'   => 'credit',
                'gateway'               => 'hdfc',
                'payment_issuer'        => ['hdfc'],
                'payment_network'       => ['VISA', 'MC'],
                'percent_rate'          => '10',
                'international'         => '0',
                'amount_range_active'   => '1',
                'amount_range_min'      => 500,
                'amount_range_max'      => null,
            ],
            [
                'payment_method'        => 'card',
                'payment_method_type'   => 'credit',
                'gateway'               => 'hdfc',
                'payment_issuer'        => ['hdfc'],
                'payment_network'       => ['VISA', 'MC'],
                'percent_rate'          => '10',
                'international'         => '0',
                'amount_range_active'   => '1',
                'amount_range_min'      => 0,
                'amount_range_max'      => 500,
            ],
            [
                'payment_method'        => 'card',
                'payment_method_type'   => 'debit',
                'gateway'               => 'hdfc',
                'payment_issuer'        => ['hdfc'],
                'payment_network'       => ['VISA', 'MC'],
                'percent_rate'          => '10',
                'international'         => '0',
                'amount_range_active'   => '1',
                'amount_range_min'      => 0,
                'amount_range_max'      => 500,
            ],
            [
                'payment_method'        => 'card',
                'payment_method_type'   => 'debit',
                'gateway'               => 'hdfc',
                'payment_issuer'        => ['hdfc'],
                'payment_network'       => ['VISA', 'MC'],
                'percent_rate'          => '10',
                'international'         => '0',
                'amount_range_active'   => '1',
                'amount_range_min'      => 500,
                'amount_range_max'      => 700,
            ],
            [
                'payment_method'        => 'card',
                'payment_method_type'   => 'debit',
                'gateway'               => 'hdfc',
                'payment_issuer'        => ['hdfc'],
                'payment_network'       => ['VISA', 'MC'],
                'percent_rate'          => '10',
                'international'         => '0',
                'amount_range_active'   => '1',
                'amount_range_min'      => 700,
                'amount_range_max'      => null,
            ],
        ];

        $plan = [
            Entity::PLAN_NAME => 'testPlan',
            Entity::RULES     => $rules,
        ];

        return $plan;
    }

    public function getPricingPlanForFeeBearerTest(string $pricingFeeBearer)
    {
        $defaultPricingPlan = [
            'plan_name'                 => 'TestPlan1',
            'payment_method'            => 'card',
            'payment_method_type'       => 'credit',
            'percent_rate'              => 1000,
            'fixed_rate'                =>  0,
            'payment_network'           => 'MC',
            'payment_issuer'            => 'SBIN',
            'org_id'                    => '10000000000000',
            'type'                      => 'pricing',
            'fee_bearer'                => $pricingFeeBearer,
        ];

        $plan = $this->createPricingPlan($defaultPricingPlan);

        return $plan;
    }

    protected function setUpMerchantForFeeBearerTest(string $merchantFeeBearer, array $pricingPlan)
    {
        $this->fixtures->merchant->edit('10000000000000', [
            'pricing_plan_id' => $pricingPlan['id'],
            'fee_bearer'      => $merchantFeeBearer,
        ]);
    }

    protected function setUpAndGetPaymentArrayForFeeBearerPricingTest(string $merchantFeeBearer, string $pricingFeeBearer)
    {
        $this->mockCardVault();

        $this->ba->publicAuth();

        $this->setDefaultMerchantMethods();

        $this->fixtures->iin->create([
            'iin' => '555555',
            'country' => 'IN',
            'network' => 'MasterCard',
            'type'    => 'credit',
        ]);

        $plan = $this->getPricingPlanForFeeBearerTest($pricingFeeBearer);

        $this->setUpMerchantForFeeBearerTest($merchantFeeBearer, $plan);

        return $this->getPaymentArrayForFeeBearerTest($merchantFeeBearer);
    }

    protected function getPaymentArrayForFeeBearerTest($merchantFeeBearer)
    {
        $defaultPaymentArray = $this->getDefaultPaymentArray();

        $defaultPaymentArray['card']['number'] = '555555555555558';

        if ($merchantFeeBearer === FeeBearer::PLATFORM)
        {
            return $defaultPaymentArray;
        }

        try
        {
            return $this->getFeesForPayment($defaultPaymentArray)['input'];
        }
        catch (Exception\LogicException $logicException)
        {
            return $defaultPaymentArray;
        }
    }

    protected function getDefaultBillingAddressArray($international = false)
    {
        $address = [];

        if ($international === false)
        {
            $address = [
                'line1' => 'Razorpay Software, 1st Floor, 22, SJR Cyber',
                'line2' => 'Hosur Main Road, Adugodi',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'country' => 'in',
                'postal_code' => '560030',
            ];
        }

        else{
            $address = [
                'line1' => '21 Applegate Appartment',
                'line2' => 'Rockledge Street',
                'city' => 'New York',
                'state' => 'New York',
                'country' => 'us',
                'postal_code' => '11561',
            ];
        }

        return $address;
    }

    protected function setFetchFileBasedRefundsFromScroogeMockResponse(array $refundEntities)
    {
        $scroogeResponse = [
            'code'     => 200,
            'body'     => [
                'data' => [],
            ],
        ];

        $scroogeResponseForRef1Update = json_decode('{
            "api_failed_count": 0,
            "api_failures": [],
            "scrooge_failed_count": 0,
            "scrooge_failures": [],
            "success_count": 1,
            "time_taken": 0.24121499061584473
        }', true);

        foreach ($refundEntities as $refundEntity)
        {
            $scroogeResponse['body']['data'][] = [
                'id'                => $refundEntity['id'],
                'amount'            => $refundEntity['amount'],
                'base_amount'       => $refundEntity['base_amount'],
                'payment_id'        => $refundEntity['payment_id'],
                'bank'              => $refundEntity->payment['bank'],
                'gateway'           => $refundEntity['gateway'],
                'currency'          => $refundEntity['currency'],
                'gateway_amount'    => $refundEntity['gateway_amount'],
                'gateway_currency'  => $refundEntity['gateway_currency'],
                'method'            => $refundEntity->payment['method'],
                'created_at'        => $refundEntity['created_at'],
            ];
        }

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['getFileBasedRefunds', 'bulkUpdateRefundReference1'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('getFileBasedRefunds')
                           ->willReturn($scroogeResponse);

        $this->app->scrooge->method('bulkUpdateRefundReference1')
                           ->willReturn($scroogeResponseForRef1Update);
    }

    protected function callFTAPatchRoute($content = [])
    {
        $this->ba->adminAuth();

        $request = array(
            'method'    => 'PATCH',
            'url'       => '/fund_transfer_attempts',
            'content'   => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function markProcessedInstantRefundFailed($refund, $fta)
    {
        $strippedFtaId = substr($fta['id'], 4);

        $ftaUpdateContent = [];
        $ftaUpdateContent[$strippedFtaId]['status'] = 'failed';
        $ftaUpdateContent[$strippedFtaId]['remarks'] = 'transaction got reversed';
        $ftaUpdateContent[$strippedFtaId]['failure_reason'] = '[manual] transaction got reversed';

        $this->callFTAPatchRoute($ftaUpdateContent);

        $event = 'fee_only_reversal_event';
        $this->scroogeUpdateRefundStatus($refund, $event);

        $event = 'processed_to_file_init_event';
        $status = 'file_init';
        $this->scroogeUpdateRefundStatus($refund, $event, $status);
    }

    protected function enableRazorXTreatmentForRazorXRefund()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1)
                                  {
                                      return 'off';
                                  }

                                  return 'on';
                              }));
    }

    protected function assignSubMerchant(string $tid, string $mid)
    {
        $url = '/terminals/' . $tid . '/merchants/' . $mid;

        $request = [
            'url'    => $url,
            'method' => 'PUT',
        ];

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);
    }

    protected function deleteSubmerchant(string $tid, string $mid)
    {
        $url = '/terminals/' . $tid . '/merchants/' . $mid;

        $request = [
            'url' => $url,
            'method' => 'DELETE',
        ];

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function updateRefundAtForPayments($content)
    {
        $this->ba->adminAuth();

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/update_refund_at/bulk',
            'server'  => [
                'CONTENT_TYPE' => 'application/json',
            ],
            'raw' => json_encode(['payments' => $content])
        );

        $data = $this->makeRequestAndGetContent($request);

        return $data;
    }

    public function formatRefundId(string $refundId): string
    {
        $refundId = (strpos($refundId, 'rfnd_') === false) ? $refundId : substr($refundId, strlen('rfnd_'));

        return $refundId;
    }

    protected function mockRegisterMandate()
    {
        $callable = function ()
        {
            return [
                'redirect_url' => "https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage",
                'id' => "ratn_PP3VC146gmBVGG",
                "status" => "created",
            ];
        };

        return $this->mockMandateHQ($callable);
    }

    protected function mockShouldSkipSummaryPage($skip)
    {
        $callable = function () use ($skip)
        {
            return $skip;
        };

        return $this->mockMandateHQ($callable, 'shouldSkipSummaryPage');
    }

    protected function mockCheckBin()
    {

        $callable = function ()
        {
            return true;
        };

        return $this->mockMandateHQ($callable, 'isBinSupported');
    }

    protected function mockReportPayment()
    {
        $callable = function ()
        {
            return [];
        };

        return $this->mockMandateHQ($callable, 'reportPayment');
    }

    protected function mockMandateHQ($callable = null, $method = 'registerMandate')
    {
        $this->mandateHQ->shouldReceive($method)
            ->andReturnUsing($callable);
    }

    // For testing new refund V2 flow.
    // Pls contact Scrooge team for any queries.
    protected function enableRazorXTreatmentForRefundV2()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode)
                              {
                                  if ($feature === RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1)
                                  {
                                      return 'on';
                                  }
                                  return 'off';
                              }));
    }

    // For testing new refund V2 flow.
    // Create transaction entity.
    public function createTransactionForRefunds($input = [], $reconcile = false)
    {
        $this->ba->scroogeAuth();

        $requestData = [
            'request' => [
                'method'  => 'post',
                'url'     => '/refunds/transaction_create',
                'content' => [
                        'id'               => $input['refund_id'],
                        'payment_id'       => $input['payment_id'],
                        'amount'           => $input['amount'],
                        'base_amount'      => $input['base_amount'],
                        'gateway'          => $input['gateway'],
                        'speed_decisioned' => $input['speed_decisioned'],
                        'mode'             => $input['mode'] ?? null,
                ],
            ],
            'response' => [
                'content' => []
            ],
        ];

        $response = $this->runRequestResponseFlow($requestData);

        $this->assertNull($response['error']);

        $this->assertNotNull($response['data']['transaction_id']);

        if ($reconcile === true)
        {
            $this->fixtures->edit('transaction', $response['data']['transaction_id'], [
                'reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp(),
            ]);

            $transaction = $this->getDbLastEntityToArray('transaction');

            $this->assertNotNull($transaction['reconciled_at']);
        }
    }

    protected function getWhitelistedVpaHandlesUpiRegular() {
        $vpaHandles = [
            "abfspay",
            "airtel",
            "airtelpaymentsbank",
            "albk",
            "allahabadbank",
            "allbank",
            "andb",
            "apb",
            "apl",
            "yapl",
            "aubank",
            "axis",
            "axisbank",
            "axisgo",
            "axisb",
            "axl",
            "bandhan",
            "barodampay",
            "barodapay",
            "birla",
            "bob",
            "boi",
            "cbin",
            "cboi",
            "centralbank",
            "citi",
            "citibank",
            "citigold",
            "cmsidfc",
            "cnrb",
            "csbcash",
            "csbpay",
            "cub",
            "db",
            "dbs",
            "dcb",
            "dcbbank",
            "denabank",
            "dlb",
            "eazypay",
            "equitas",
            "ezeepay",
            "fbl",
            "fbpe",
            "federal",
            "finobank",
            "freecharge",
            "hdfcbank",
            "hdfcbankjd",
            "hsbc",
            "ibl",
            "icici",
            "icicipay",
            "icicibank",
            "idbi",
            "idbibank",
            "idfc",
            "idfcbank",
            "idfcfirst",
            "idfcnetc",
            "idfcfirstbank",
            "ikwik",
            "imobile",
            "indbank",
            "indianbank",
            "indianbk",
            "indus",
            "iob",
            "jio",
            "jkb",
            "jsb",
            "jsbp",
            "jupiteraxis",
            "karb",
            "karurvysyabank",
            "kaypay",
            "kbl",
            "kbl052",
            "kmb",
            "kmbl",
            "kotak",
            "kvb",
            "kvbank",
            "lime",
            "lvb",
            "lvbank",
            "mahb",
            "mairtel",
            "myicici",
            "obc",
            "okaxis",
            "okbizaxis",
            "okhdfcbank",
            "okicici",
            "oksbi",
            "paytm",
            "payzapp",
            "pingpay",
            "pnb",
            "pnbpay",
            "pockets",
            "postbank",
            "psb",
            "purz",
            "rajgovhdfcbank",
            "rbl",
            "rmhdfcbank",
            "s2b",
            "sbi",
            "sc",
            "scb",
            "scbl",
            "scmobile",
            "sib",
            "srcb",
            "synd",
            "syndbank",
            "syndicate",
            "tjsb",
            "ubi",
            "uboi",
            "uco",
            "unionbank",
            "unionbankofindia",
            "united",
            "upi",
            "utbi",
            "vijayabank",
            "vijb",
            "vjb",
            "waaxis",
            "wahdfcbank",
            "waicici",
            "wasbi",
            "ybl",
            "yesbank",
            "yesbankltd",
            "yesb",
            "nsdl",
            "razorpay",
            "timecosmos",
            "tapicici",
            "trans",
            "liv",
            "sliceaxis",
            "pz",
            "apay",
            "amazon",
            "amazonpay",
            "equitasbank",
            "yesg",
            "axb",
            "fam",
            "rapl",
            "pinelabs",
            "zoicici",
            "goaxb",
            "utkarshbank",
            "tmb",
            "omni",
            "dhani",
            "niyoicici",
            "naviaxis",
            "shriramhdfcbank",
        ];

        return $vpaHandles;
    }

    protected function allowAllTerminalRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        // we are ramping up auth terminal selection hence to make sure all test cases passes
        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'card_payments_authorize_all_terminals')
                    {
                        return 'off';
                    }

                    return 'on';

                }) );
    }

    protected function mockCardVaultWithCryptogram($callable = null , $useActualCard = false)
    {
        $app = \App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = $callable ?: function ($route, $method, $input) use  ($useActualCard) {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    if($useActualCard == true)
                    {
                        $response['value'] = '4012001038443335';
                        break;
                    }
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'delete':
                    break;

                case 'tokens/cryptogram':
                    $response['service_provider_tokens'] = [
                        [
                            'type'  => 'network',
                            'name'  => 'Visa',
                            'provider_data'  => [
                                'token_number' => '4044649165235890',
                                'cryptogram_value' => 'test',
                                'token_expiry_month' => 12,
                                'token_expiry_year' => 2024,
                            ],
                        ]
                    ];
                    break;

                case 'cards/fingerprints':
                    $response['fingerprint'] = '1234';
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', null)
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }

    public function mockCardVaultWithMigrateToken()
    {
        $app = \App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = function ($route, $method, $input)
        {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'tokens/migrate':
                    $response['success'] = true;

                    $response['provider'] = strtolower($input['iin']['network']);

                    $token = base64_encode($input['card']['vault_token']);
                    $response['token']  = $token;

                    $response['fingerprint'] = strrev($token);
                    $response['last4'] = 1234;
                    $response['providerReferenceId'] = "12345678911234";

                    $token_iin = 411111;

                    $expiry_year = $input['card']['expiry_year'];
                    if (strlen($expiry_year) == 2)
                    {
                        $expiry_year = '20' . $expiry_year;
                    }

                    $response['service_provider_tokens'] = [
                        [
                            'id'             => 'spt_1234abcd',
                            'entity'         => 'service_provider_token',
                            'provider_type'  => 'network',
                            'provider_name'  => $input['iin']['network'],
                            'status'         => 'created',
                            'interoperable'  => true,
                            'provider_data'  => [
                                'token_reference_number'     => $token,
                                'payment_account_reference'  => strrev($token),
                                'token_expiry_month'     => $input['card']['expiry_month'],
                                'token_expiry_year'      => $expiry_year,
                                'token_iin'              => $token_iin,
                                'token_number'           => 411111,
                            ],
                        ]
                    ];
                    break;

                case 'delete':
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', null)
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }

    public function mockCardVaultWithMigrateDualToken()
    {
        $app = \App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = function ($route, $method, $input)
        {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'tokens/migrate':
                    $response['success'] = true;

                    $response['provider'] = strtolower($input['iin']['network']);

                    $token = base64_encode($input['card']['vault_token']);
                    $response['token']  = $token;

                    $response['fingerprint'] = strrev($token);
                    $response['last4'] = 1234;
                    $response['providerReferenceId'] = "12345678911234";

                    $token_iin = 411111;

                    $expiry_year = $input['card']['expiry_year'];
                    if (strlen($expiry_year) == 2)
                    {
                        $expiry_year = '20' . $expiry_year;
                    }

                    $response['service_provider_tokens'] = [
                        [
                            'id'=> 'spt_1234abcd',
                            'entity'=> 'service_provider_token',
                            'provider_type'=> 'network',
                            'provider_name'=> $input['iin']['network'],
                            'interoperable'=> true,
                            'provider_data'=> [
                                'token_reference_number'=> $token,
                                'payment_account_reference'=> strrev($token),
                                'token_iin'=> $token_iin,
                                'token_expiry_month'=> $input['card']['expiry_month'],
                                'token_expiry_year'=> $expiry_year
                            ],
                            'status'=> 'active'
                        ],
                        [

                            'id'=> 'spt_L3EIQJCnojUgjA',
                            'entity'=> 'service_provider_token',
                            'provider_type'=> 'issuer',
                            'provider_name'=> 'axis',
                            'interoperable'=> true,
                            'provider_data'=> [
                                'token_reference_number'=> 'L3EIPeeJkmIDCb',
                                'token_expiry_month'=> $input['card']['expiry_month'],
                                'token_expiry_year'=> $expiry_year
                            ],
                            'status'=> 'active'
                        ]
                    ];
                    break;

                case 'delete':
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', null)
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }

    protected function fixturesToCreateToken(
        $tokenId,
        $cardId,
        $iin,
        $merchantId = '100000Razorpay',
        $customerId = '10000gcustomer',
        $inputFields = []
    )
    {
        $acknowledgedAt = Carbon::now()->getTimestamp();

        if (isset($inputFields['do_not_acknowledged']) && $inputFields['do_not_acknowledged'] === true)
        {
            $acknowledgedAt = null;
        }

        $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $inputFields['network'] ?? 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => $inputFields['vault'] ?? 'rzpvault',
                'vault_token'   => 'test_token',
                'international' => $inputFields['international'] ?? null,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'token'           => '1000lcardtoken',
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => $acknowledgedAt,
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'status'          => $inputFields['status'] ?? 'active',
            ]
        );
    }
}
