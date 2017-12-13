<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use Mockery;
use Requests;
use RZP\Exception;
use RZP\Models\Merchant\Account;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Payment\Verify\Action;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Payment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Fixtures\Entity\MerchantFluid;

trait PaymentTrait
{
    use EntityActionTrait;
    use PaymentAmexTrait;
    use PaymentAtomTrait;
    use PaymentAxisGeniusTrait;
    use PaymentAxisMigsTrait;
    use PaymentBilldeskTrait;
    use PaymentHdfcTrait;
    use PaymentNetbankingTrait;
    use PaymentPaytmTrait;
    use PaymentSharpTrait;
    use PaymentMobikwikTrait;
    use PaymentCybersourceTrait;
    use PaymentHitachiTrait;
    use PaymentBladeTrait;
    use PaymentFirstDataTrait;
    use PaymentEbsTrait;
    use PaymentCreationTrait;
    use PaymentFssTrait;

    use RequestResponseFlowTrait
    {
        sendRequest as makeRequestParent;
    }

    protected $otp = null;

    protected $gateway = null;

    protected $merchantCallbackUrl = null;

    protected $merchantCallbackFlow = false;

    /**
     * For certain payments, user has the option to fail it
     * on the bank page. If this property is set to true in
     * the test, then we simulate submitting failure option
     * on the bank page
     *
     * @var boolean
     */
    protected $failPaymentOnBankPage = false;

    protected function doAuthAndCapturePayment($payment = null, $amount = 0, $currency = 'INR')
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $paymentAuth = $this->doJsonpAuthPayment($payment);

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

    protected function doAuthCaptureAndRefundPayment($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

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

    protected function createAndGetFeesForPayment($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $payment['view'] = 'json';

        $content = $this->getFeesForPayment($payment);

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
        $this->ba->appAuth();

        $request = [
            'url'    => '/payments/autocapture',
            'method' => 'post'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function sendAutoCaptureEmails()
    {
        $this->ba->appAuth();

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
        $this->assertLessThanOrEqual(6, $count);

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

        $this->ba->publicAuth($key);

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

    protected function doS2SRecurringPayment($payment = null, $server = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

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

    protected function doS2sPrivateAuthAndCapturePayment($payment = null)
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

    protected function doAuthPaymentViaAjaxRoute($payment)
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

    protected function getWalletFormViaCreateRoute($payment)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestParent($request);

        $response->assertViewIs('gateway.gatewayWalletForm');
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');

        return $this->getFormRequestFromResponse($response->getContent(), 'http://localhost');
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

    protected function makeS2sCallbackAndGetContent($content)
    {
        $request = [
            'url'    => '/callback/' . $this->gateway,
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

    protected function capturePayment($id, $amount, $currency = 'INR', $verifyAmount = 0)
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

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function authorizedFailedPayment($id)
    {
        $request = array(
            'url'    => '/payments/'.$id.'/authorize_failed',
            'method' => 'POST');

        $this->ba->appAuth();

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

    protected function refund($params)
    {
        $this->ba->privateAuth();

        $request = array(
            'method'    => 'POST',
            'url'       => '/refunds',
            'content'   => $params);

        return $this->makeRequestAndGetContent($request);
    }

    protected function refundPayment($id, $amount = null, $reversals = [], $reverseAll = false)
    {
        $this->ba->privateAuth();

        $content = [];

        if ($amount !== null)
        {
            $content = array('amount' => $amount);
        }

        if (empty($reversals) === false)
        {
            $content['reversals'] = $reversals;
        }

        if ($reverseAll === true)
        {
            $content['reverse_all'] = true;
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

        return $refund;
    }

    protected function disputePayment(Payment\Entity $payment, int $deduct = 0): array
    {
        $this->ba->appAuth();

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
        $this->ba->appAuth();

        $content = [];

        $request = array(
            'method'  => 'POST',
            'url'     => '/refunds/'.$id.'/verify',
            'content' => $content);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function retryFailedRefunds($gateway = [])
    {
        $this->ba->appAuth();

        $content = [];

        $request = array(
            'method'  => 'POST',
            'url'     => '/refunds/retry/failed',
            'content' => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function retryFailedRefund($id, $content = [])
    {
        $this->ba->appAuth();

        $request = array(
            'method'  => 'POST',
            'url'     => '/refunds/' . $id . '/retry',
            'content' => $content
        );

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function refundAuthorizedPayment($id, array $input = [])
    {
        $this->ba->adminAuth();

        $this->ba->addAdminAuthHeaders('org_' . Org::RZP_ORG);

        $merchant = (new MerchantFluid())->getMerchant(Account::TEST_ACCOUNT)->get();

        $admin = $this->ba->getAdmin();

        // Linking merchant with admin because admins can access only linked merchants.
        $admin->merchants()->attach($merchant);

        $this->ba->addAccountAuth($merchant->getId());

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/'.$id.'/authorize_refund',
            'content' => $input);

        $refund = $this->makeRequestAndGetContent($request);

        $this->assertEquals('refund', $refund['entity']);

        return $refund;
    }

    protected function refundOldAuthorizedPayments()
    {
        $this->ba->appAuth();

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/refund/authorized',
            'content' => []);

        $data = $this->makeRequestAndGetContent($request);

        return $data;
    }

    protected function authorizeFailedPayment($id)
    {
        $request = array(
            'url'    => '/payments/'.$id.'/authorize_failed',
            'method' => 'post');

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function forceAuthorizeFailedPayment($id, $content)
    {
        $request = array(
            'url'     => '/payments/'.$id.'/force_authorize',
            'method'  => 'post',
            'content' => $content);

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function timeoutOldPayment()
    {
        $this->ba->appAuth();

        $request = array('url' => '/payments/timeout');

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
        $payment['merchant_id'] = '10000000000000';
        $payment['status'] = 'authorized';
        $payment['refund_status'] = 'none';
        $payment['amount_authorized'] = $payment['amount'];
        $payment['amount_refunded'] = '0';
        $payment['terminal_id'] = '1n25f6uN5S1Z5a';

        return $payment;
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
            'bank'              => 'IDIB',
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
            'expiry_year'       => '2017',
            'cvv'               => '566',
        );

        return $payment;
    }

    protected function getDefaultRecurringPaymentArray()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment['recurring'] = true;

        $payment['customer_id'] = 'cust_100000customer';

        return $payment;
    }

    protected function getNetbankingRecurringPaymentArray($bank = 'HDFC')
    {
        $payment = $this->getDefaultNetbankingPaymentArray($bank);

        $payment['amount'] = 2000;

        $payment['recurring'] = true;

        $payment['customer_id'] = 'cust_100000customer';

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
                'expiry_year'  => '2017',
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

    protected function generateRefundsExcelForNb($bank)
    {
        $this->ba->appAuth();

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
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

        unset($payment['card']);

        if ($bank !== null)
        {
            $payment['bank'] = $bank;
        }

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

    protected function sendRequest($request, &$callback = null)
    {
        $this->checkAndSetUrl($request);

        $response = $this->makeRequestParent($request);

        $url = $request['url'];

        if ($this->isPaymentCreationUrl($url))
        {
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

            if (($method === 'post') and
                (isset($request['content'])))
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
            'id' => $payment->getPublicId(),
            'hash' => $hash,
            'key_id' => $this->ba->getKey()
        ];

        $url = \URL::route('payment_otp_submit', $params, false);
        $url = 'http://localhost' . $url;

        return $url;
    }

    /**
     * Get Otp resend Url
     */
    public function getOtpResendUrl($paymentId)
    {
        $params = [
            'id' => $paymentId,
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
                        '510510' => '22.0',
                        '401201' => '15.3',
                        '555555' => '2.4'
                    ];

                    if (isset($binRiskMapping[$bin]) === true)
                    {
                        return ['riskScore' => $binRiskMapping[$bin]];
                    }

                    return null;
                });

        $this->app->instance('maxmind', $maxmind);
    }

    protected function mockTokenex()
    {
        $tokenex = Mockery::mock('RZP\Services\TokenEx')->makePartial();

        $this->app->instance('card.tokenex', $tokenex);

        $tokenex->shouldReceive('sendRequest')
                ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                ->andReturnUsing(function ($route, $method, $input)
                {
                    $response = [
                        'Error' => '',
                        'ReferenceNumber' => '15102913382030662954',
                        'Success' => true,
                    ];

                    switch ($route)
                    {
                        case 'REST/Tokenize':
                            $response['Token'] = base64_encode($input['Data']);
                            break;

                        case 'REST/Detokenize':
                            $response['Value'] = base64_decode($input['Token']);
                            break;

                        case 'REST/ValidateToken':
                            $response['Valid'] = true;
                            break;

                        case 'REST/DeleteToken':
                            break;
                    }
                    return $response;
                });

        $this->app->instance('card.tokenex', $tokenex);
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
}
