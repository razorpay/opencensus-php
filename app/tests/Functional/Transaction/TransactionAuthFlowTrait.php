<?php

namespace Tests\Functional\Transaction;

use EE\Exception\BaseException;
use Requests;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Functional\RequestResponseFlowTrait;

trait TransactionAuthFlowTrait
{
    use RequestResponseFlowTrait;

    protected function makeRequest($request)
    {
        $content = $request['content'];

        $response = $this->call('POST', '/transactions', $content);

        $uri = $this->client->getRequest()->getUri();

        $content = $response->getContent();

        if (json_decode($content) === null)
        {
            //
            // Card is a debit card
            //

            // list($response, $content) = $this->runDebitCardAuthFlow($content, $uri);

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

        $url = '/transactions/callback/'.$id;

        $response = $this->call('POST', $url, $values);

        return $response;
    }

    protected function dcTransactionGetJsonFromCallback($content)
    {
        $arr = explode("\n", $content);

        //
        // Actual output is JS, but line 63 of the
        // output contains the data in JSON
        //
        // @todo: explain this part better.
        $line = $arr[67];

        // 11 = strlen("var data = ")
        //-1 = to split the ; from end of js
        $content = substr($line, 11,-1);

        return $content;
    }

    protected function getIdFromUri($uri)
    {
        $pos = strrpos($uri, '/');

        $id = substr($uri, $pos + 1);

        return $id;
    }

    protected function getDefaultTransactionArray()
    {
        //
        // default transaction object
        //
        $transaction = [
            'amount'          =>  '100',
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
            'contact'           => '9918899029'
        ];

        return $transaction;
    }
}
