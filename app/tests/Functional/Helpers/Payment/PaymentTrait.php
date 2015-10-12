<?php

namespace Tests\Functional\Helpers\Payment;

use EE\Exception\BaseException;
use Mockery;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait PaymentTrait
{
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

    use RequestResponseFlowTrait
    {
        makeRequest as makeRequestParent;
    }

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

    protected function doAuthAndCapturePayment($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $paymentAuth = $this->doJsonpAuthPayment($payment);

        $payment = $this->capturePayment(
            $paymentAuth['razorpay_payment_id'],
            $payment['amount']);

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

    protected function capturePayment($id, $amount)
    {
        $request = array(
            'method' => 'POST',
            'url' => "/payments/".$id.'/capture',
            'content' => array('amount' => $amount));

        $this->ba->privateAuth();
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['amount'], $amount);
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

        $this->assertEquals($content['success'], true);
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

    protected function refundAuthorizedPayment($id, array $input = array())
    {
        $this->ba->proxyAuth();

        $content = array();

        $request = array(
            'method' => 'POST',
            'url' => '/payments/'.$id.'/authorize_refund',
            'content' => $input);

        $refund = $this->makeRequestAndGetContent($request);

        $this->assertEquals('refund', $refund['entity']);

        return $refund;
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

    protected function timeoutOldPayment()
    {
        $this->ba->appAuth();

        $request = array('url' => '/payments/timeout');

        return $this->makeRequestAndGetContent($request);
    }

    protected function deleteTerminal($mid, $tid)
    {
        $request = array(
            'url' => '/merchants/'.$mid.'/terminals/'.$tid,
            'method' => 'delete');

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function editTerminal($tid, $input)
    {
        $request = array(
            'url' => '/terminals/'.$tid,
            'method' => 'put',
            'content' => $input);

        $this->ba->appAuth();

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
                'expiry_year'       => '2015',
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

    protected function generateRefundsExcelForHdfcNB()
    {
        $this->ba->appAuth();

        $request = array(
            'url' => '/refunds/netbanking/excel',
            'method' => 'post',
            'content' => [
                'bank'  => 'HDFC'
            ],
        );

        return $this->makeRequestAndGetContent($request);
    }

    protected function getDefaultNetbankingPaymentArray()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['method'] = 'netbanking';

        return $payment;
    }

    protected function submitPaymentCallbackForm($form)
    {
        //
        // third request
        // submit callback form
        //

        $uri = $form->getUri();
        $ix = strpos($uri, 'v1');

        $uri = substr($uri, $ix+2);

        $request['method'] = 'POST';
        $request['content'] = $form->getValues();

        $request['url'] = $uri;

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function submitPaymentCallbackRedirect($url)
    {
        $request['method'] = 'GET';
        $request['url'] = $url;

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function submitPaymentCallbackData($url, $method, $values)
    {
        $request['method'] = 'POST';
        $request['url'] = $url;
        $request['content'] = $values;

        return $this->submitPaymentCallbackRequest($request);
    }

    protected function submitPaymentCallbackRequest($request)
    {
        $this->ba->publicCallbackAuth();

        $response = $this->makeRequestParent($request);

        $content = $response->getContent();

        if ($this->isResponseInstanceType('http', $response))
        {
            $formData = $this->getSecondFormDataFromResponse($content, 'http://localhost');

            if ((isset($formData['type'])) and
                ($formData['type'] === 'return'))
            {
                return $this->processMerchantReturnCallbackForm($response);
            }
        }

        $this->ba->publicAuth();

        $content = $this->getPaymentJsonFromCallback($content);

        $response->setContent($content);

        return $response;
    }

    protected function makeRequest($request, &$callback = null)
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

    protected function isPaymentCreationUrl($url)
    {
        $urls = array(
            '/payments/create/jsonp',
            '/payments/create/checkout',
            '/payments');

        return in_array($url, $urls);
    }

    protected function handlePaymentCreationFlow($response, $request, &$callback = null)
    {
        $content = $response->getContent();

        $gateway = null;

        if ($request['url'] === '/payments/create/checkout')
        {
            $this->assertTrue($this->isResponseInstanceType('http', $response));
            $this->assertEquals($response->headers->get('content-type'), 'text/html; charset=UTF-8');

            $marker = '// Callback data //';
            if (strpos($content, $marker) !== false)
            {
                $content = $this->getPaymentJsonFromCallback($content);

                $response->setContent($content);

                return $response;
            }
        }

        if ($callback)
        {
            // Should be the jsonp payment creation url
            $this->assertEquals($request['url'], '/payments/create/jsonp');

            $content = $this->getJsonContentFromResponse($response, $callback);

            // For no 2-auth payments, it could be a direct json response.
            if (isset($content['gateway']) === false)
            {
                return $response;
            }

            $gateway = $content['gateway'];

            if (isset($content['type']) === 'return')
            {
                // @note: This case isn't happening right now but it can in future
                $request = $content['request'];

                return $this->makeRequestParent($request);
            }
        }
        else
        {
            // Has to be either redirect or a html form post.
            // First check for normal html form post.
            $ret = ((json_decode($content) === null) and
                    ($this->isResponseInstanceType('http', $response)) and
                    ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
                    ($response->getStatusCode() === 200));

            if ($ret === false)
            {
                // Now check for redirect
                $ret = (($this->isResponseInstanceType('redirect', $response)) and
                        ($response->getStatusCode() === 302));

                if ($ret === true)
                {
                    $gateway = $response->headers->get('X-gateway');
                }
                else
                {
                    return $response;
                }
            }
            else
            {
                //
                // When doing form posts relevant here, we put in a
                // second form which is not submitted but it contains gateway
                // field in encrypted form and 'type' field with value as 'first'
                // or 'return'. Otherwise, don't take an action here.
                //
                $content = $this->getSecondFormDataFromResponse($content, 'http://localhost');

                if ((isset($content['type'])) and
                    ($content['type'] === 'first'))
                {
                    $gateway = $content['gateway'];
                }
                else if ($content['type'] === 'return')
                {
                    return $this->processMerchantReturnCallbackForm($response);
                }
            }
        }

        return $this->runPaymentCallbackFlowForGateway($response, $gateway, $callback);
    }

    protected function runPaymentCallbackFlowForGateway($response,  $gateway, &$callback = null)
    {
        $gateway = $this->decryptGatewayText($gateway);

        if (strpos($gateway, 'netbanking') !== false)
            $gateway = 'netbanking';

        $func = 'runPaymentCallbackFlow'.studly_case($gateway);

        return $this->$func($response, $callback);
    }

    protected function processMerchantReturnCallbackForm($response)
    {
        $content = $response->getContent();

        $content = $this->getSecondFormDataFromResponse($content, 'http://localhost');

        if ($content['type'] === 'return')
        {
            $this->merchantCallbackFlow = true;

            $request = $this->getFormRequestFromResponse($response->getContent(), 'http://localhost');

            $this->assertEquals($request['url'], $this->getLocalMerchantCallbackUrl());

            $response = $this->makeRequestParent($request);

            $this->assertResponse('json', $response);

            return $response;
        }
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

            if ($method === 'post')
            {
                $values = $request['content'];
            }
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

    protected function makeFirstGatewayPaymentMockRequest($url, $method = 'get', $content = array())
    {
        $request = array(
           'url' => $url,
           'method' => strtoupper($method),
           'content' => $content);

        $response = $this->makeRequestParent($request);

        $statusCode = $response->getStatusCode();
        $this->assertEquals($statusCode, '302');

        $url = $response->getTargetUrl();

        return $url;
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
     * Checks the laravel class of $response,
     * whether it's json, http or redirect.
     * @param  string  $type
     * @param  mixed   $response
     * @return boolean
     */
    protected function isResponseInstanceType($type = 'json', $response)
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
        $this->assertTrue($this->isResponseInstanceType($type, $response));
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
}
