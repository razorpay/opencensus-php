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

        $response = $this->makeRequest($request);

        $content = $response->getContent();

        $content = json_decode($content, true);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['amount'], $amount);
        $this->assertEquals($content['status'], 'captured');

        return $content;
    }

    protected function refundTransaction($id)
    {
        $request = array(
            'method' => 'POST',
            'url' => '/transactions/'.$id.'/refund',
            'content' => array());

        $response = $this->makeRequest($request);

        $content = $response->getContent();

        $this->assertJson($content);

        $refund = json_decode($content, true);

        //
        // Check if transaction id matches, and refunded sucessfully
        //
        $this->assertEquals($id, $refund['id']);
        $this->assertEquals('refunded', $refund['status']);

        return $refund;
    }

    protected function makeRequest($request)
    {
        $this->checkAndSetUrl($request);

        $this->checkAndSetMethod($request);

        $response = $this->makeRequestParent($request);

        $content = $response->getContent();

        if (json_decode($content) === null)
        {
            //
            // Card is a debit card
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
                throw $e;
        }

        //
        // second request
        // submit to acs url
        //

        $uri = $form->getUri();
        $method = $form->getMethod();
        $values = $form->getValues();

        $response = Requests::post($uri, array(), $values);

        $form = $this->dcTransactionGetCallbackForm($response, $uri);

        return $form;
    }

    protected function dcTransactionGetCallbackForm($response, $uri)
    {
        //
        // crawl the repsonse to get callback form
        //

        $crawler = new Crawler('', $uri);

        $crawler->addContent($response->body);

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

        $method = $form->getMethod();
        $values = $form->getValues();

        $id = $this->getIdFromUri($uri);

        $auth = $this->auth;
        unset($auth['PHP_AUTH_PW']);

        $server = $auth;

        $url = '/transactions/callback/'.$id;

        $response = $this->call('POST', $url, $values, array(), $server);

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
}
