<?php

namespace Tests\Functional\Helpers\Payment;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait PaymentTrait
{
    use PaymentHdfcTrait;
    use PaymentAtomTrait;

    use RequestResponseFlowTrait
    {
        makeRequest as makeRequestParent;
    }

    protected $gateway = 'hdfc';

    protected function doAuthAndGetPayment($paymentRequest, $paymentResponse = array())
    {
        $payment = $this->doJsonpAuthPayment($paymentRequest);

        $id = $payment['razorpay_payment_id'];

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        return $this->getAndMatchPayment($id, $paymentResponse);
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

        $this->assertArrayHasKey('razorpay_payment_id', $content);
        $this->assertEquals(1, count($content));

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
            'description'       => 'random description'
        ];

        return $payment;
    }

    protected function submitPaymentCallbackForm($form)
    {
        //
        // third request
        // submit callback form
        //

        $uri = $form->getUri();

        // Extract the payment id from absolute url

        $id = $this->getIdFromUri($uri);

        $this->ba->publicAuth();

        $request['method'] = 'POST';
        $request['content'] = $form->getValues();

        $request['url'] = '/payments/'.$id.'/callback';

        $response = $this->makeRequestParent($request);

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

        if ($callback)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
        }

        if (isset($content['redirectUrl']) or $this->gateway === 'atom')
        {
            return $this->runPaymentCallbackFlowAtom($response, $callback);
        }

        return $this->runPaymentCallbackFlowHdfc($response, $callback);
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