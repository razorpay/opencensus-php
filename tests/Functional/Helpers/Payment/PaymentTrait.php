<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Exception\BaseException;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Mockery;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;

trait PaymentTrait
{
    use EntityActionTrait;
    use PaymentAmexTrait;
    use PaymentAtomTrait;
    use PaymentAxisGeniusTrait;
    use PaymentAxisMigsTrait;
    use PaymentBilldeskTrait;
    use PaymentHdfcTrait;
    use PaymentKotakTrait;
    use PaymentNetbankingTrait;
    use PaymentPaytmTrait;
    use PaymentSharpTrait;
    use PaymentMobikwikTrait;
    use PaymentSbiepayTrait;
    use PaymentCybersourceTrait;
    use PaymentEbsTrait;
    use PaymentCreationTrait;

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

    protected function doAuthAndCapturePayment($payment = null, $amount = 0)
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
                $amount, $payment['amount']);
        }
        else
        {
            $payment = $this->capturePayment(
                $paymentAuth['razorpay_payment_id'],
                $payment['amount']);
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

    protected function doAuthAndGetPayment($payment = null, $paymentResponse = array())
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

        $testData = $this->testData[$func];

        if (isset($testData['request']) === false)
            $testData['request'] = [];

        if (isset($testData['request']['content']) === false)
            $testData['request']['content'] = [];

        if ($payment !== null)
            $testData['request']['content'] = $payment;

        $this->replaceDefualtValues($testData['request']['content']);

        $testData['request']['method'] = 'POST';
        $testData['request']['url'] = '/payments';

        $this->ba->publicAuth();

        return $this->runRequestResponseFlow($testData);
    }

    protected function doAutoCapture()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/payments/autocapture',
            'method' => 'post');

        return $this->makeRequestAndGetContent($request);
    }

    protected function sendAutoCaptureEmails()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/payments/autocapture/email',
            'method' => 'get');

        return $this->makeRequestAndGetContent($request);
    }

    protected function signPayment(array $payment, $secret = '')
    {
        $data = array(
            'amount'            => $payment['amount'],
            'currency'          => 'INR',
            'merchant_order_id' => $payment['notes']['merchant_order_id']);

        if ($secret === '')
        {
            $secret = $this->ba->getSecret();
        }

        $str = implode('|', $data);

        return hash_hmac('sha1', $str, $secret);
    }

    protected function assertSignatureMatches(array $content, $secret)
    {
        $this->assertArrayHasKey('signature', $content);

        $data = array(
            'amount'                => $content['amount'],
            'currency'              => $content['currency'],
            'merchant_order_id'     => $content['merchant_order_id'],
            'razorpay_payment_id'   => $content['razorpay_payment_id']);

        $str = implode('|', $data);

        $signature = hash_hmac('sha1', $str, $secret);

        $this->assertEquals($signature, $content['signature']);
    }

    protected function getPaymentJsonFromCallback($content)
    {
        $start = 'var data = ';
        $end = '// Callback data //';

        $data = getTextBetweenStrings($content, $start, $end);

        // Remove ';\n' at the end to get proper json string
        $l = strlen($data);
        $data = substr($data, 0, $l-2);

        return $data;
    }

    protected function defaultAuthPayment(array $payment = array())
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

        $request = array(
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => $content);

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request, $content['callback']);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertLessThanOrEqual(2, count($content));
        if (count($content) === 2)
        {
            $this->assertEquals(200, $content['http_status_code']);
        }

        return $content;
    }

    protected function doAuthPayment($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $request = array(
            'method' => 'POST',
            'url' => '/payments',
            'content' => $payment);

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
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
            'url' => '/payments/create/ajax',
            'method' => 'post'
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

        $request = array(
            'content' => $payment,
            'url' => '/payments/create/checkout',
            'method' => 'post');

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function makeOtpCallback($url)
    {
        $request = array(
            'url'       => $url,
            'method'    => 'POST',
            'content'   => array(
                'otp' => $this->getOtp(),
                'type' => 'otp'
            ),
        );

        return $this->sendRequest($request);
    }

    protected function topupPayment($id)
    {
        $request = array(
            'method' => 'POST',
            'url' => '/payments/'.$id.'/topup/ajax',
            'content' => array()
        );

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function redirectPayment($id)
    {
        $request = [
            'method'    => 'POST',
            'url'       => '/payments/'.$id.'/redirect',
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

        return $this->getJsonContentFromResponse($response);
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
                'url'     =>  '/payments/create/fees',
                'content' =>  $payment);

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function capturePayment($id, $amount, $verifyAmount = 0)
    {
        $request = array(
            'method' => 'POST',
            'url' => "/payments/".$id.'/capture',
            'content' => array('amount' => $amount));

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

    protected function cancelPayment($id)
    {
        $request = array(
            'method' => 'GET',
            'url' => '/payments/'.$id.'/cancel');

        $this->ba->publicAuth();
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('status', $content);
        $this->assertEquals($content['status'], 'failed');
    }

    protected function addPaymentMetadata($id, $content)
    {
        $request = array(
            'method' => 'POST',
            'url' => '/payments/'.$id.'/metadata',
            'content' => $content);

        $this->ba->publicAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function verifyPayment($id)
    {
        $request = array(
            'url' => '/payments/'.$id.'/verify',
            'method' => 'GET');

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function verifyMultiplePayments($filter)
    {
        $request = array(
            'url' => '/payments/verify/'.$filter,
            'method' => 'GET');

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function refundPayment($id, $amount = null)
    {
        $this->ba->privateAuth();

        $content = array();

        if ($amount !== null)
        {
            $content = array('amount' => $amount);
        }

        $request = array(
            'method' => 'POST',
            'url' => '/payments/'.$id.'/refund',
            'content' => $content);

        $refund = $this->makeRequestAndGetContent($request);

        $this->assertEquals('refund', $refund['entity']);

        if ($amount !== null)
        {
            $this->assertEquals($amount, $refund['amount']);
        }

        return $refund;
    }

    protected function verifyRefund($id)
    {
        $this->ba->appAuth();

        $content = array();

        $request = array(
            'method' => 'POST',
            'url' => '/refunds/'.$id.'/verify',
            'content' => $content);

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function refundAuthorizedPayment($id, array $input = array())
    {
        $this->ba->proxyAuth();

        $request = array(
            'method' => 'POST',
            'url' => '/payments/'.$id.'/authorize_refund',
            'content' => $input);

        $refund = $this->makeRequestAndGetContent($request);

        $this->assertEquals('refund', $refund['entity']);

        return $refund;
    }

    protected function refundOldAuthorizedPayments()
    {
        $this->ba->appAuth();

        $request = array(
            'method' => 'POST',
            'url' => '/payments/refund/authorized',
            'content' => []);

        $data = $this->makeRequestAndGetContent($request);

        return $data;
    }
    
    protected function refundMultipleAuthorizedPaymentsForOrders()
    {
        $this->ba->appAuth();
        
        $request = array(
            'method'    => 'POST',
            'url'       => '/payments/orders/refund',
            'content'   => []
        );
        
        $data = $this->makeRequestAndGetContent($request);
        
        return $data;
    }

    protected function authorizeFailedPayment($id)
    {
        $request = array(
            'url' => '/payments/'.$id.'/authorize_failed',
            'method' => 'post');

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function forceAuthorizeFailedPayment($id, $content)
    {
        $request = array(
            'url' => '/payments/'.$id.'/force_authorize',
            'method' => 'post',
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

    protected function getAndMatchPayment($id, $paymentResponse = array())
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

    protected function getDefaultPaymentArray()
    {
        //
        // default payment object
        //
        $payment = [
            'amount'          =>  '50000',
            'currency'        =>  'INR',
            'card' => array(
                'number'            => '4012001038443335',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2017',
                'cvv'               => '566',
            ),
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => array(
                'merchant_order_id' => 'random order id'),
            'description'       => 'random description',
            'bank'              => 'ICIC',
        ];

        return $payment;
    }

    protected function getDefaultPaymentArrayEmi($saved)
    {
        $card = null;

        if ($saved == true)
        {
            $card = array(
                'cvv'   => 111);
        }
        else
        {
            $card = array(
                'number'            => '41476700000006',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2017',
                'cvv'               => '566');
        }

        $payment = [
            'amount'            =>  '300000',
            'currency'          =>  'INR',
            'method'            =>  'emi',
            'emi_duration'      =>  '9',
            'card'              => $card,
            'email'             => 'a@b.com',
            'contact'           => '9918899029',
            'notes'             => array(
                'merchant_order_id' => 'random order id'),
            'description'       => 'random description',
            'bank'              => 'ICIC',
        ];

        return $payment;
    }

    protected function generateRefundsExcelForHdfcNB()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/netbanking/excel',
            'method' => 'post',
            'content' => [
                'bank'   => 'HDFC'
            ],
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultNetbankingPaymentArray($bank = null)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

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

    protected function replaceDefualtValues(array & $content)
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

        $this->config['gateway.mock_netbanking_hdfc'] = true;
    }

    protected function isGatewayMocked()
    {
        $gateway = $this->app['config']->get('gateway');

        if ($this->gateway === null)
            $this->gateway = 'hdfc';

        $var = 'mock_' . $this->gateway;

        if (isset($gateway[$var]))
            return $gateway['mock_' . $this->gateway];

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

            $values = array();
            $method = $request['method'];

            if (($method === 'post') and
                (isset($request['content'])))
            {
                $values = $request['content'];
            }
        }
        else if ($this->isResponseInstanceType($response, 'json'))
        {
            list($url, $method, $values) = $this->getFormDataFromJsonResponse($response);
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
                list($url, $method, $values) = $this->getFormDataFromResponse($response->getContent(), 'https://localhost');
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
    public function getOtpResendUrl($payment)
    {
        $params = [
            'id' => $payment->getPublicId(),
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

    protected function mockServerContentFunction($closure)
    {
        $server = $this->mockServer()
                       ->shouldReceive('content')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->setMockServer($server);
    }

    protected function mockServer()
    {
        $class = $this->app['gateway']->getServerClass($this->gateway);

        return Mockery::mock($class)->makePartial();
    }

    protected function setMockServer($server)
    {
         return $this->app['gateway']->setServer($this->gateway, $server);
    }

    protected function resetMockServer()
    {
        return $this->app['gateway']->resetServer($this->gateway);
    }

    protected function resetGatewayDriver()
    {
        return $this->app['gateway']->resetDriver($this->gateway);
    }

    protected function mockTokenex()
    {
        $tokenex = Mockery::mock('RZP\Services\TokenEx')->makePartial();

        $this->app->instance('card.tokenex', $tokenex);

        $tokenex->shouldReceive('sendRequest')
              ->with(Mockery::type('string'), 'post', Mockery::type('array'))
              ->andReturnUsing(function ($route, $method, $input)
                    {
                        $response = array(
                            "Error" => "",
                            "ReferenceNumber" => "15102913382030662954",
                            "Success" => true,
                        );

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
}
