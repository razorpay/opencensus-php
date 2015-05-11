<?php

namespace Tests\Functional\Helpers\Payment;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait PaymentTrait
{
    use PaymentAtomTrait;
    use PaymentAxisGeniusTrait;
    use PaymentAxisMigsTrait;
    use PaymentHdfcTrait;

    use RequestResponseFlowTrait
    {
        makeRequest as makeRequestParent;
    }

    protected $gateway = null;

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

    protected function doAuthPayment($payment)
    {
        $request = array(
            'method' => 'POST',
            'url' => '/payments',
            'content' => $payment);

        $this->ba->publicAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
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

    protected function verifyPayment($id)
    {
        $request = array(
            'url' => '/payments/'.$id.'/verify',
            'method' => 'GET');

        $this->ba->proxyAuth();

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
            'bank'              => 'HDFC',
        ];

        return $payment;
    }

    protected function getDefaultNetBankingPaymentArray()
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

    protected function submitPaymentCallbackRequest($request)
    {
        $this->ba->publicCallbackAuth();

        $response = $this->makeRequestParent($request);

        $this->ba->publicAuth();

        $content = $response->getContent();

        $content = $this->getPaymentJsonFromCallback($content);

        $response->setContent($content);

        return $response;
    }

    protected function makeRequest($request, &$callback = null)
    {
        $this->checkAndSetUrl($request);

        $response = $this->makeRequestParent($request);

        $response = $this->runPaymentCallbackFlow($response, $callback);

        return $response;
    }

    protected function runPaymentCallbackFlow($response, &$callback = null)
    {
        $content = $response->getContent();

        $gateway = null;

        if ($callback)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);

            if (isset($content['gateway']) === false)
            {
                return $response;
            }

            $gateway = \Crypt::decrypt($content['gateway']);
        }
        else
        {
            // Has to be either redirect or a gateway form post.
            // First check for normal html form post.
            $ret = ((json_decode($content) === null) and
                    (get_class($response) === 'Illuminate\Http\Response') and
                    ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
                    ($response->getStatusCode() === 200));

            if ($ret === false)
            {
                // Now check for redirect
                $ret = ((get_class($response) === 'Illuminate\Http\RedirectResponse') and
                        ($response->getStatusCode() === 302));

                if ($ret === false)
                    return $response;
            }
        }

        return $this->runPaymentCallbackFlowForGateway($response, $callback, $gateway);
    }

    protected function runPaymentCallbackFlowForGateway($response, &$callback = null, $gateway = null)
    {
        if ($gateway === null)
        {
            $gateway = $this->gateway;
        }

        if ($gateway === null)
        {
            $gateway = 'hdfc';
        }

        $func = 'runPaymentCallbackFlow'.studly_case($gateway);

        return $this->$func($response, $callback);
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

    protected function getFormDataFromResponse($content, $url)
    {
        $crawler = new Crawler($content, $url);

        $form = $crawler->filter('form')->form();

        return $this->getDataFromForm($form);
    }

    protected function getDataFromForm($form)
    {
        $uri = $form->getUri();

        $method = $form->getMethod();
        $values = $form->getValues();

        return array($uri, $method, $values);
    }
}