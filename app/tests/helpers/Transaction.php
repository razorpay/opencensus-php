<?php

use EE\Exception\BaseException;

/**
 * Transaction class, inherited by all classes that need to create transactions.
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

use Symfony\Component\DomCrawler\Crawler;

class Transaction extends TestCase
{
    protected $card = null;

    /**
     * Creates a transaction & tests it is corrrectly created
     */
    protected function runTestFlow($testData)
    {
        //GIVEN
        $content = $statusCode = null;

        $txn = $testData['request']['content'];

        try
        {
            $response = $this->call('POST', '/transactions', $txn);

            $uri = $this->client->getRequest()->getUri();

            $content = $response->getContent();

            if (json_decode($content) === null)
            {
                //
                // Card is a debit card
                //

                $crawler = new Crawler($content, $uri);

                $form = $this->dcTransactionSubmitToAcsUrl($crawler);

                $response = $this->dcTransactionSubmitCallbackForm($form);

                $content = $response->getContent();

                $content = $this->dcTransactionGetJsonFromCallback($content);

            }

            $statusCode = $response->getStatusCode();

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

            $internalError = $e->getErrorArray();

            $statusCode = $e->getPublicError()->getHttpStatusCode();

            $this->unsuccessfulCardAsserts($testData['exception'], $internalError['error']);
        }

        // THEN

        //
        // Match the status codes if it's defined
        //
        if (isset($testData['response']['status_code']))
            $this->assertEquals(
                $testData['response']['status_code'],
                $statusCode);

        //
        // Ensure output is json
        //
        $this->assertJson($content);

        $content = json_decode($content, true);

        $expectedContent = $testData['response']['content'];
        $actualContent = $content;

        $this->match($expectedContent, $actualContent);

        return $content;
    }

    public function unsuccessfulCardAsserts($expected, $actual)
    {
        $this->assertEquals($expected['code'], $actual['code']);

        if (isset($expected['gateway_error_code']))
        {
            $this->assertEquals($expected['gateway_error_code'], $actual['gateway_error_code']);

            $gatewayErrorDesc = \Gateway\HdfcGateway\HdfcGatewayErrorCode::$errorMessages[$actual['gateway_error_code']];

            $this->assertEquals($gatewayErrorDesc, $actual['gateway_error_desc']);
        }

        if (isset($expected['field']))
        {
            $this->assertEquals($expected['field'], $actual['field']);
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

    protected function hitDCTransactionEndpoints(array $transaction, $response)
    {
        //
        // first request to /transactions route,
        // returns form for submission to acs url
        //
        $crawler = new Crawler($content);

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
            'udf' => array(
                'email'     =>  'lol@lko.com',
                'contact'   =>  '991889902'
            ),
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
