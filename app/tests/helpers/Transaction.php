<?php

/**
 * Transaction class, inherited by all classes that need to create transactions.
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

class Transaction extends TestCase {
    protected function createTransaction($card_no)
    {
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
            'process'         =>  '1',
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
        ];

    
        switch($cardtype){

            //in case timeout is expected
            // @todo This should be handled internally by transactioncontroller rather than here
            case "timeout":
                try
                {
                $response = $this->call('POST', '/transactions', $transaction);
                }
                catch(Requests_Exception $e)
                {
                    //check if timeout has occured
                    if(strpos($e->xdebug_message, 'Operation timed out'))
                    {
                        $this->assertTrue(true);
                        return true;
                    }

                    $this->fail('Unhandled Exception: '.$e->xdebug_message);
                    return false;
                    
                }
            break;

            //in case card is a CC (no secure code)
            case "CC":
                $response = $this->call('POST', '/transactions', $transaction);
            break;

            //in case card is a DC (secure code)
            case "DC":
                //first request to /transactions route, returns form for submission to acs url
                $crawler = $this->client->request('POST', '/transactions', $transaction);
                
                //get the form
                $form = $crawler->selectButton('Submit')->form();

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
            break;

            default: 
                $this->fail("Invalid Cards.php file");
            break;
        }


            
            //THEN
            $content = $response->getContent();

            //Ensure output is json
            $this->assertJson($content);

            //check processed flag matches as in card.php
            //@todo shift to matching to actual error code rreturned once errors are implemented
            $output=json_decode($content);
            $this->assertEquals($expected_response, $output->processed);
            
            //If expected response is to be successfull, end the test here
            if($expected_response) return $output;

            //Checking for error codes & messages of unsuccessful cards
            $error_code=$cards[$card_no]['code'];
            $error_message=$cards[$card_no]['message'];

            $this->assertEquals($output->error->code, $error_code);
            $this->assertEquals($output->error->message, $error_message);

            return $output;
            
            

    }
}