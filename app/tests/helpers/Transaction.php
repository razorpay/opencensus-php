<?php

/**
 * Transaction class, inherited by all classes that need to create transactions.
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

class Transaction extends TestCase {
    /**
     * Creates a transaction & tests it is corrrectly created
     * @param $card_no Numeric The array index of card in cards.php to be used for transaction
     * @param $hold Boolean True if transaction is to be of hold type (not captured automatically)
     * @return created transaction object in json
     */
    protected function createTransaction($card_no)
    {
        //flush any previous output
        ob_flush();

        //GIVEN

        //load list of cards with expected responses for each
        $cards=include('cards.php');

        //get details of requested cards
        $expected_response=$cards[$card_no]['response'];
        $cardtype=$cards[$card_no]['type'];
        $response;

        //create transaction object
        $transaction = [
            'amount'          =>  '100',
            'currency'        =>  'INR',
            'card' => array(
                'number'     => $cards[$card_no]['PAN'],
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


        switch($cardtype){

            //in case card is a CC (no secure code)
            case "CC":
                $response = $this->call('POST', '/transactions', $transaction);
                $content = $response->getContent();
            break;

            //in case card is a DC (secure code)
            case "DC":
                //first request to /transactions route, returns form for submission to acs url
                $crawler = $this->client->request('POST', '/transactions', $transaction);

                //get the form
                try
                {
                $form = $crawler->selectButton('Submit')->form();
                }
                catch(Exception $e)
                {
                  if(strpos($e->getMessage(), 'node list is empty') != False)
                  {
                    $this->fail('Transaction Timed out');
                  }
                  else throw $e;
                }

                //submit to acs url
                $form->setValues(array('TermUrl' => Config::get('app.url').'/transactions/callback'));

                $uri = $form->getUri();
                $method = $form->getMethod();
                $values = $form->getValues();

                $response = Requests::post($uri, array(), $values);

                //crawl the repsonse to get callback form
                $crawler = new \Symfony\Component\DomCrawler\Crawler('', $uri);
                $crawler->addContent($response->body);

                $form = $crawler->selectButton('Submit')->form();

                //submit callback form
                $uri = $form->getUri();
                $method = $form->getMethod();
                $values = $form->getValues();

                $response = $this->call('POST', '/transactions/callback', $values);
                //Actual output is JS, but line 63 of the output contains the data in JSON
                $content = $response->getContent();
                $arr = explode("\n", $content);
                $line = $arr[62];
                // 11 = strlen("var data = ")
                //-1 = to split the ; from end of js
                $content = substr($line, 11,-1);
            break;

            default:
                $this->fail("Invalid Cards.php file");
            break;
        }

            //THEN

            //Ensure output is json
            $this->assertJson($content);
            //check processed flag matches as in card.php
            //@todo shift to matching to actual error code rreturned once errors are implemented
            $output=json_decode($content);

            //If expected response is to be successfull, do following sets of tests
            if($expected_response){
                // if(!$hold)
                // {
                //     //By default transaction should be captured
                //     $this->assertEquals('captured', $output->status);
                // }
                // else
                // {
                    //if hold is set to true, it stops at auth
                    $this->assertEquals('auth', $output->status);
                // }
                return $output;
            }

            //Tests for unsuccessful cards
            $error_code=$cards[$card_no]['code'];
            $error_message=$cards[$card_no]['message'];

            $this->assertEquals($error_code, $output->error->code);
            $this->assertEquals($error_message, $output->error->message);

            return $output;



    }
}
