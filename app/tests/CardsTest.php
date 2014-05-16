<?php
class CardsTest extends TestCase {

    /**
    *  Tests transaction for a particular card number
    */

    private function createTransaction($card_no)
    {
        //GIVEN
        
        //create transaction object
        $transaction = [
            'amount'          =>  '100',
            'currency'        =>  'INR',
            'process'         =>  '1',
            'card' => array(
                'number'     => '',
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

        //load list of cards with expected responses for each
        $cards=include('helpers/cards.php');        
        $transaction['card']['number']=$cards[$card_no]['PAN'];
        $expected_response=$cards[$card_no]['response'];
        $cardtype=$cards[$card_no]['type'];
        $response;
        switch($cardtype){

            case "timeout":
                try
                {
                $crawler = $this->client->request('POST', '/transactions', $transaction);
                }
                catch(Requests_Exception $e)
                {
                    $this->assertTrue(true);
                    return;
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
        return;

    }

    /**
    * Tests for individual cards
    */

    public function testCard0()
    {
        $this->createTransaction(0);

    }
    public function testCard1()
    {
        $this->createTransaction(1);

    }
    public function testCard2()
    {
        $this->createTransaction(2);

    }
    public function testCard3()
    {
        $this->createTransaction(3);

    }
    public function testCard4()
    {
        $this->createTransaction(4);

    }
    public function testCard5()
    {
        $this->createTransaction(5);

    }
    public function testCard6()
    {
        $this->createTransaction(6);

    }
    public function testCard7()
    {
        $this->createTransaction(7);

    }
    public function testCard8()
    {
        $this->createTransaction(8);

    }
    public function testCard9()
    {
        $this->createTransaction(9);

    }
    public function testCard10()
    {
        $this->createTransaction(10);

    }
    public function testCard11()
    {
        $this->createTransaction(11);

    }
    public function testCard12()
    {
        $this->createTransaction(12);

    }

}