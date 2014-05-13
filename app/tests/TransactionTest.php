<?php

class TransactionTest extends TestCase {


    public function testCreateTransaction()
    {
        //GIVEN
        $transaction = [
            'amount'          =>  '100',
            'currency'        =>  'INR',
            'process'         =>  '1',
            'card' => array(
                'number'     => '4012001037490014',
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
        $cards=include('helpers/cards.php');
        foreach($cards as $card)
        {
            $transaction['card']['number']=$card['PAN'];
            $expected_response=$card['response'];
            
            //WHEN
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

            //THEN
            $this->assertEquals($expected_response, $response->getContent());
        }

    }
 }