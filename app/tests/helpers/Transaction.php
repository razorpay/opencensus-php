<?php

use EE\Exception\BaseException;

/**
 * Transaction class, inherited by all classes that need to create transactions.
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

class Transaction extends TestCase
{
    protected $card = null;

    /**
     * Creates a transaction & tests it is corrrectly created
     * @param $card_no Numeric The array index of card in cards.php to be used for transaction
     * @param $hold Boolean True if transaction is to be of hold type (not captured automatically)
     * @return created transaction object in json
     */
    protected function createTransaction($card)
    {
        //GIVEN

        $this->card = $card;

        //get details of requested card
        $expected_response = $card['response'];

        $cardtype = $card['type'];

        $response = null;
        $content = null;

        $transaction = $this->getTransactionArray($card['PAN']);
        $e = null;

        try
        {
            switch($cardtype){

                //
                // in case card is a CC (no secure code)
                //
                case "CC":
                    $response = $this->call('POST', '/transactions', $transaction);
                    $content = $response->getContent();
                    break;

                //
                // in case card is a DC (secure code)
                //
                case "DC":

                    $content = $this->hitDCTransactionEndpoints($transaction);

                    $arr = explode("\n", $content);

                    $line = $arr[67];

                    // 11 = strlen("var data = ")
                    //-1 = to split the ; from end of js
                    $content = substr($line, 11,-1);
                    break;

                default:
                    $this->fail("Invalid Cards type");

            }
        }
        catch (BaseException $e)
        {
            if (isset($card['exception']) === false)
                throw $e;

            $expected = $card['exception'];
            $actual = get_class($e);
            if ($expected !== $actual)
            {
                throw $e;
            }
            else
            {
                $this->assertEquals(
                    $expected,
                    $actual);
            }

            $content = $e->generatePublicJsonResponse()->getContent();
        }

        // THEN

        //
        // Ensure output is json
        //
        $this->assertJson($content);

        //
        // check processed flag matches as in card.php
        // @todo shift to matching to actual error code
        // returned once errors are implemented
        //
        $output = json_decode($content);

        //
        // If expected response is to be successfull,
        // do following sets of tests
        //
        if ($expected_response)
        {
            $this->assertEquals(Models\Manager\TransactionStatus::AUTH, $output->status);

            return $output;
        }
        else
        {
            $this->unsuccessfulCardAsserts($output, $e);
        }
    }

    public function unsuccessfulCardAsserts($output, $e)
    {
        //
        // Tests for unsuccessful cards
        //
        $card = $this->card;

        $internalError = $e->getError();

        $this->assertEquals($card['internal_error_code'], $internalError->getCode());

        $this->assertEquals($card['public_error_code'], $output->error->code);

        if (isset($card['public_error_desc']))
            $this->assertEquals($card['public_error_desc'], $output->error->description);

        if (isset($card['gateway_error_code']))
        {
            $this->assertEquals($card['gateway_error_code'], $internalError->getGatewayErrorCode());

            $gatewayErrorDesc = \Gateway\HdfcGateway\HdfcGatewayErrorCode::$errorMessages[$card['gateway_error_code']];

            $this->assertEquals($gatewayErrorDesc, $internalError->getGatewayErrorDesc());
        }

        if (isset($card['field']))
        {
            $this->assertEquals($card['field'], $output->error->field);
        }

        return $output;
    }

    protected function hitDCTransactionEndpoints(array $transaction)
    {
        //
        // first request to /transactions route,
        // returns form for submission to acs url
        //
        $crawler = $this->client->request('POST', '/transactions', $transaction);

        $form = $this->dcTransactionSubmitToAcsUrl($crawler);

        // $form = $this->dcTransactionGetCallbackForm($response);

        $response = $this->dcTransactionSubmitCallbackForm($form);

        //
        // Actual output is JS, but line 63 of the output contains the data in JSON
        //
        $content = $response->getContent();

        return $content;
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
            s(get_class($e));

            if(strpos($e->getMessage(), 'node list is empty') !== False)
            {
                $this->fail('Transaction Timed out');
            }
            else throw $e;
        }

        //
        // second request
        // submit to acs url
        //

        $uri = $form->getUri();
        $method = $form->getMethod();
        $values = $form->getValues();

        $response = Requests::post($uri, array(), $values);

        //
        // crawl the repsonse to get callback form
        //

        $crawler = new \Symfony\Component\DomCrawler\Crawler('', $uri);

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

    protected function getIdFromUri($uri)
    {
        $pos = strrpos($uri, '/');

        $id = substr($uri, $pos + 1);

        return $id;
    }

    protected function getTransactionArray($number)
    {
        //
        // default transaction object
        //
        $transaction = [
            'amount'          =>  '100',
            'currency'        =>  'INR',
            'card' => array(
                'number'     => $number,
                'name'       => 'Harshil',
                'expiry_month'    =>'12',
                'expiry_year'     => '2014',
                'cvv'             => '566',
                'address_line1'   => '21, Rameshwar',
                'address_line2'   => 'jaipurwa',
                'address_city'    => 'jaipur',
                'address_state'   =>  'Rajasathan',
                'address_country' =>  'India',
                'address_zip'     =>  '123345',
            ),
            'udf' => array(
                'email'     =>  'lol@lko.com',
                'contact'   =>  '991889902'
            ),
            // 'hold'      => (int)$hold
        ];

        return $transaction;
    }
}
