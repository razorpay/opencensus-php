<?php

namespace Tests\Functional\Transaction;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait TransactionAuthFlowTrait
{
    use RequestResponseFlowTrait
    {
        makeRequest as makeRequestParent;
    }

    protected function createAuthorizedTransactionEntity()
    {
        $txn = $this->getDefaultTransactionEntityArray();
        $txn = $this->createEntity('transaction', $txn);
        $txn = $txn->toArrayPublic();
        return $txn;
    }

    protected function createCapturedTransactionEntity()
    {
        $txn = $this->getDefaultTransactionEntityArray();
        $txn['status'] = 'captured';
        $txn = $this->createEntity('transaction', $txn);
        $txn = $txn->toArrayPublic();
        return $txn;
    }

    protected function defaultAuthTransaction()
    {
        $txn = $this->getDefaultTransactionArray();

        return $this->doAuthTransaction($txn);
    }

    protected function doAuthTransaction($txn)
    {
        $request = array(
            'method' => 'POST',
            'url' => '/transactions',
            'content' => $txn);

        $this->setupPublicBasicAuthParams();

        $response = $this->makeRequest($request);

        $content = $response->getContent();

        $content = json_decode($content, true);

        $this->assertEquals($txn['amount'], $content['amount']);
        $this->assertEquals('authorized', $content['status']);

        return $content;
    }

    protected function captureTransaction($id, $amount)
    {
        $request = array(
            'method' => 'POST',
            'url' => "/transactions/".$id.'/capture',
            'content' => array('amount' => $amount));

        $this->setupPrivateBasicAuthParams();
        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['amount'], $amount);
        $this->assertEquals($content['status'], 'captured');

        return $content;
    }

    protected function refundTransaction($id, $amount = null)
    {
        $this->setupPrivateBasicAuthParams();

        $content = array();

        if ($amount !== null)
        {
            $content = array('amount' => $amount);
        }

        $request = array(
            'method' => 'POST',
            'url' => '/transactions/'.$id.'/refund',
            'content' => $content);

        $refund = $this->makeRequestAndGetContent($request);
//s($refund);
        $this->assertEquals('refund', $refund['entity']);

        if ($amount !== null)
        {
            $this->assertEquals($amount, $refund['amount']);
        }

 //       $this->assertEquals('txn-'.$id, $refund['transaction_id']);

        return $refund;
    }

    protected function makeRequest($request)
    {
        $this->checkAndSetUrl($request);

        $this->checkAndSetMethod($request);

        $response = $this->makeRequestParent($request);

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

        $form = $this->dcTransactionSubmitToAcsUrl($crawler);

        $response = $this->dcTransactionSubmitCallbackForm($form);

        $content = $response->getContent();

        $content = $this->dcTransactionGetJsonFromCallback($content);

        // return array($response, $content);

        $response->setContent($content);

        return $response;
    }

    protected function dcTransactionSubmitToAcsUrl($crawler)
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
            if (strpos($e->getMessage(), 'node list is empty') !== False)
            {
                $this->fail('Transaction Timed out');
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

        $uri = $form->getUri();

        $method = $form->getMethod();
        $values = $form->getValues();

        $gateway = $this->app['config']->get('gateway.default');

        if ($gateway === 'mockhdfc')
        {
            $server = $this->auth;

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

        $form = $this->dcTransactionGetCallbackForm($content, $uri);

        return $form;
    }

    protected function dcTransactionGetCallbackForm($content, $uri)
    {
        //
        // crawl the repsonse to get callback form
        //

        $crawler = new Crawler('', $uri);

        $crawler->addContent($content);

        $form = $crawler->selectButton('Submit')->form();

        return $form;
    }

    protected function dcTransactionSubmitCallbackForm($form)
    {
        //
        // third request
        // submit callback form
        //

        $uri = $form->getUri();

        $id = $this->getIdFromUri($uri);

        $auth = $this->auth;
        unset($auth['PHP_AUTH_PW']);

        $request['method'] = 'POST';
        $request['url'] = '/transactions/callback/'.$id;
        $request['content'] = $form->getValues();
        $request['server'] = $auth;

        $response = $this->makeRequestParent($request);

        return $response;
    }

    protected function dcTransactionGetJsonFromCallback($content)
    {
        $arr = explode("\n", $content);

        $n = 91;

        //
        // @see callback.blade.php
        // @todo: a better way might be to extract position of 'var data = '
        // n = 91.
        //
        // Actual output is JS, but line $n of callback.blade.php
        // starts with 'var data = ' and then the json data is printed
        //
        // So first, ensure that $n line is present.
        //
        if (isset($arr[$n]) === false)
        {
            var_dump($arr);
            throw new \Exception('some error occured');
        }

        //
        // If line is present, get it in array.
        //

        $line = $arr[$n];

        //
        // 11 = strlen("var data = ")
        // -1 = to split the ; from end of js
        // This gives us the desired json data returned.
        //
        $content = substr($line, 11,-1);

        return $content;
    }

    protected function getIdFromUri($uri)
    {
        $pos = strrpos($uri, '/');

        $id = substr($uri, $pos + 1);

        return $id;
    }

    protected function checkAndSetUrl(& $request)
    {
        if (isset($request['url']) === false)
        {
            $request['url'] = '/transactions';
        }
    }

    protected function checkAndSetMethod(& $request)
    {
        if (isset($request['method']) === false)
        {
            $request['method'] = 'POST';
        }
    }

    protected function replaceDefualtValues(array & $content)
    {
        $data = $this->getDefaultTransactionArray();

        $this->replaceValuesRecursively($data, $content);

        $content = $data;
    }

    protected function getDefaultTransactionArray()
    {
        //
        // default transaction object
        //
        $transaction = [
            'amount'          =>  '50000',
            'currency'        =>  'INR',
            'card' => array(
                'number'            => '4012001038443335',
                'name'              => 'Harshil',
                'expiry_month'      => '12',
                'expiry_year'       => '2014',
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
            'udf'               => array(),
            'description'       => 'random description'
        ];

        return $transaction;
    }

    protected function getDefaultTransactionEntityArray()
    {
        $txn = $this->getDefaultTransactionArray();

        unset($txn['card']);
        $txn['merchant_id'] = '363e4efa820b0c06208ccd99';
        $txn['status'] = 'authorized';
        $txn['refund_status'] = 'none';
        $txn['amount_authorized'] = $txn['amount'];
        $txn['amount_refunded'] = '0';

        return $txn;
    }

    protected function getDefaultHdfcEntityArray()
    {
        $hdfcTxn = array(
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

        return $hdfcTxn;
    }
}
