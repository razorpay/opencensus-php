<?php
class Transaction extends TestCase {
    protected function createTransaction($card_no)
    {
        //GIVEN

        //load list of cards with expected responses for each
        $cards=include('cards.php');
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

            case "timeout":
                try
                {
                $response = $this->call('POST', '/transactions', $transaction);
                }
                catch(Requests_Exception $e)
                {
                    $this->assertTrue(true);
                    return true;
                }
            break;

            case "CC":
                $response = $this->call('POST', '/transactions', $transaction);
            break;

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

            //check output is json
            $this->assertJson($content);

            //check processed flag matches as in card.php
            //@todo shift to matching to actual error code rreturned once errors are implemented
            $output=json_decode($content, true);
            $this->assertEquals($expected_response, $output['processed']);
            return $output;

    }
}