<?php

namespace Tests\Functional\Payment;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\PaymentCallbackTrait;

trait PaymentAuthFlowTrait
{
    use PaymentCallbackTrait;

    protected function createAuthorizedPaymentEntity()
    {
        $payment = $this->getDefaultPaymentEntityArray();
        $payment = $this->fixtures->createEntity('payment', $payment);
        $payment = $payment->toArrayPublic();
        return $payment;
    }

    protected function createCapturedPaymentEntity()
    {
        $payment = $this->getDefaultPaymentEntityArray();
        $payment['status'] = 'captured';
        $payment = $this->fixtures->createEntity('payment', $payment);
        $payment = $payment->toArrayPublic();
        return $payment;
    }

    protected function defaultAuthPayment(array $payment = array())
    {
        $defaultPayment = $this->getDefaultPaymentArray();

        $payment = array_merge($defaultPayment, $payment);

        return array_merge($payment, $this->doAuthPayment($payment));
    }

    protected function doAuthPayment($payment)
    {
        $request = array(
            'method' => 'POST',
            'url' => '/payments',
            'content' => $payment);

        $this->ba->publicAuth();

        $response = $this->makeRequest($request);

        $content = $response->getContent();

        $content = json_decode($content, true);

        $this->assertArrayHasKey('id', $content);

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
//s($refund);
        $this->assertEquals('refund', $refund['entity']);

        if ($amount !== null)
        {
            $this->assertEquals($amount, $refund['amount']);
        }

        return $refund;
    }

    protected function runPaymentCallbackFlow($response)
    {
        $content = $response->getContent();

        if ((json_decode($content) === null) and
            (get_class($response) === 'Illuminate\Http\Response') and
            ($response->headers->get('content-type') === 'text/html; charset=UTF-8') and
            ($response->getStatusCode() === 200))
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
                'address_line1'     => '21, Rameshwar',
                'address_line2'     => 'jaipurwa',
                'address_city'      => 'jaipur',
                'address_state'     => 'Rajasathan',
                'address_country'   => 'India',
                'address_zip'       => '123345',
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
        $payment['terminal_id'] = $this->fixtures->entities['terminal']->getKey();

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
