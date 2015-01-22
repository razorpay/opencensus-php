<?php

namespace Tests\Functional\Payment;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\PaymentCallbackTrait;

trait PaymentAuthFlowTrait
{
    use PaymentCallbackTrait;

    protected function doAuthAndGetPayment($paymentRequest, $paymentResponse = array())
    {
        $payment = $this->doJsonpAuthPayment($paymentRequest);

        $id = $payment['razorpay_payment_id'];

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        return $this->getAndMatchPayment($id, $paymentResponse);
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

    protected function createAuthorizedPaymentEntity()
    {
        $payment = $this->getDefaultPaymentEntityArray();
        $payment = $this->fixtures->create('payment', $payment);
        $payment = $payment->toArrayPublic();

        return $payment;
    }

    protected function createCapturedPaymentEntity()
    {
        $payment = $this->getDefaultPaymentEntityArray();
        $payment['status'] = 'captured';
        $payment = $this->fixtures->create('payment', $payment);
        $payment = $payment->toArrayPublic();
        return $payment;
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

    protected function runPaymentCallbackFlow($response, &$callback = null)
    {
        $tds = $this->is3dSecure($response, $callback);

        $content = $response->getContent();

        if ($callback and $tds)
        {
            $content = $this->getJsonContentFromResponse($response, $callback);
            $callback = null;
            $content = $this->createHtmlFormAfterJsonpRequest($content);
        }

        if ($tds)
        {
            //
            // Card has 3d-secure enabled
            // In which case, run card 3dsecure flow
            //

            $uri = $this->client->getRequest()->getUri();

            $response = $this->runDebitCardAuthFlow($content, $uri);
        }

        return $response;
    }

    protected function is3dSecure($response, $callback = null)
    {
        $content = $response->getContent();

        if ($callback === null)
        {
            $tds = ((json_decode($content) === null) and
                    (get_class($response) === 'Illuminate\Http\Response') and
                    ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
                    ($response->getStatusCode() === 200));
        }
        else
        {
            $tds = ((json_decode($content) === null) and
                    (get_class($response) === 'Illuminate\Http\JsonResponse') and
                    ($response->headers->get('content-type') === 'text/javascript; charset=UTF-8') and
                    ($response->getStatusCode() === 200));

            if ($tds)
            {
                $content = $this->getJsonContentFromResponse($response, $callback);

                $tds = ((isset($content['http_status_code'])) and
                        ($content['http_status_code'] === 200) and
                        (isset($content['data'])));
            }
        }

        return $tds;
    }

    protected function createHtmlFormAfterJsonpRequest($content)
    {
        $data = $content['data'];

        $text = '
            <!doctype html>
            <html lang="en">
                <body>
                <form name="form1" action="'.$data['url'].'" method="post">
                    <input type="text" name="PaReq" value="'.$data['PAReq'].'">
                    <br />
                    <input type="text" name="MD" value="'.$data['paymentid'].'">
                    <br />
                    <input type="text" name="TermUrl" value="'.$content['callbackUrl'].'">
                    <br />
                    <input type="submit" value="Submit" >
                </form>
                <br>
                Submit within 30 secs max!
                </body>
            </html>
            ';

        return $text;
    }

    protected function runDebitCardAuthFlow($content, $uri)
    {
        $crawler = new Crawler($content, $uri);

        $form = $this->dcPaymentSubmitToAcsUrl($crawler);

        return $this->submitPaymentCallbackForm($form);
    }

    protected function dcPaymentSubmitToAcsUrl($crawler)
    {
        //
        // get the form
        //
        try
        {
            $form = $crawler->selectButton('Submit')->form();
        }
        catch(Exception $e)
        {
            if (strpos($e->getMessage(), 'node list is empty') !== false)
            {
                $this->fail('Payment Timed out');
            }
            else
            {
                throw $e;
            }
        }

        //
        // second request
        // submit to acs url
        //

        list($uri, $method, $values) = $this->getDataFromForm($form);

        $gateway = $this->app['config']->get('gateway');

        if ($gateway['mock_hdfc'] === true)
        {
            $server = $this->ba->getCreds();

            $response = $this->call($method, $uri, $values, array(), $server);
            $content = $response->getContent();
        }
        else
        {
            try
            {
                $response = Requests::post($uri, array(), $values);
                $content = $response->body;
            }
            catch(\Requests_Exception $e)
            {
                echo '3d secure failed';
                throw $e;
            }
        }

        $form = $this->dcPaymentGetCallbackForm($content, $uri);

        return $form;
    }

    protected function dcPaymentGetCallbackForm($content, $uri)
    {
        //
        // crawl the repsonse to get callback form
        //

        $crawler = new Crawler($content, $uri);

        return $crawler->selectButton('Submit')->form();
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
            'notes'               => array(),
            'description'       => 'random description'
        ];

        return $payment;
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

    protected function getDefaultHdfcEntityArray()
    {
        $hdfcPayment = array(
            'action'        =>  4,
            'enroll_result' =>  2,
            'status'        =>  'not_enrolled',
            'result'        =>  'APPROVED',
            'eci'           =>  '6',
            'auth'          =>  '999999',
            'ref'           =>  random_integer(12),
            'avr'           =>  'N',
            'postdate'      =>  (new Carbon('now', 'Asia/Kolkata'))->format('md'),
            'tranid'        =>  random_integer(15),
            'payid'         =>  -1,
            'amt'           =>  500);

        return $hdfcPayment;
    }
}
