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
     */
    protected function runTestFlow($testData)
    {
        //GIVEN

        $cardtype = $testData['type'];

        $response = null;
        $content = null;

        $e = null;
        $txn = $testData['request']['content'];

        try
        {
            switch($cardtype){

                //
                // in case card is a CC (no secure code)
                //
                case "CC":
                    $response = $this->call('POST', '/transactions', $txn);
                    $content = $response->getContent();
                    break;

                //
                // in case card is a DC (secure code)
                //
                case "DC":

                    $response = $this->hitDCTransactionEndpoints($txn);
                    $content = $response->getContent();
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
                    break;

                default:
                    $this->fail("Invalid Cards type");

            }
        }
        catch (BaseException $e)
        {
            if (isset($testData['exception']) === false)
                throw $e;

            $expected = $testData['exception']['class'];

            $actual = get_class($e);

            if ($expected !== $actual)
            {
                throw $e;
            }

            $this->assertEquals(
                $expected,
                $actual);

            unset($testData['exception']['class']);

            $response = $e->generatePublicJsonResponse();

            $content = $response->getContent();

            $this->unsuccessfulCardAsserts($testData['exception'], $e);
        }

        // THEN

        //
        // Match the status codes if it's defined
        //
        if (isset($testData['response']['status_code']))
            $this->assertEquals(
                $testData['response']['status_code'],
                $response->status_code);

        //
        // Ensure output is json
        //
        $this->assertJson($content);

        $content = json_decode($content, true);

        $expectedContent = $testData['response']['content'];
        $actualContent = $content;

        $this->match($expectedContent, $actualContent);
    }

    public function unsuccessfulCardAsserts($expected, $actual)
    {
        $internalError = $e->getError();

        $code = $expected['code'];

        $this->assertEquals($code, $internalError->getCode());

        if (isset($expected['gateway_error_code']))
        {
            $this->assertEquals($expected['gateway_error_code'], $internalError->getGatewayErrorCode());

            $gatewayErrorDesc = \Gateway\HdfcGateway\HdfcGatewayErrorCode::$errorMessages[$card['gateway_error_code']];

            $this->assertEquals($gatewayErrorDesc, $internalError->getGatewayErrorDesc());
        }

        if (isset($expected['field']))
        {
            $this->assertEquals($card['field'], $actual['field']);
        }

    }

    public function match($expected, $actual)
    {
        foreach ($expected as $key => $value)
        {
            if (is_array($value))
            {
                if (isset($actual[$key]))
                {
                    $this->match($expected[$key], $actual[$key]);
                }
                else
                {
                    $this->assertArrayHasKey($key, $actual);
                }
            }
            else
            {
                $this->assertEquals($value, $actual[$key]);
            }
        }
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
            'udf' => array(
                'email'     =>  'lol@lko.com',
                'contact'   =>  '991889902'
            ),
            // 'hold'      => (int)$hold
        ];

        return $transaction;
    }

    protected function replaceDefualtValues(array & $content)
    {
        $data = $this->getDefaultTransactionArray();

        $this->replaceValuesRecursively($data, $content);

        $content = $data;
    }

    protected function replaceValuesRecursively(array & $data, array $toReplace)
    {
        foreach ($toReplace as $key => $value)
        {
            if (is_array($value))
            {
                $this->replaceValuesRecursively($data[$key], $value);
            }
            else
            {
                $data[$key] = $value;
            }
        }
    }
}
