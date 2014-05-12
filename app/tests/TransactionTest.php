<?php

class TransactionTest extends TestCase {


    public function testCreateTransaction()
    {
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
        $crawler = $this->client->request('POST', '/transactions', $transaction);
        $form = $crawler->selectButton('Submit')->form();
        $form->setValues(array('TermUrl' => Config::get('app.url').'/transactions/callback'));
        $uri = $form->getUri();
        $method = $form->getMethod();
        $values = $form->getValues();
        $response = Requests::post($uri, array(), $values);
        $crawler = new \Symfony\Component\DomCrawler\Crawler('', $uri);
        $crawler->addContent($response->body);
        $form = $crawler->selectButton('Submit')->form();
        $uri = $form->getUri();
        $method = $form->getMethod();
        $values = $form->getValues();
        $response = $this->call('POST', '/transactions/callback', $values);
        s($response);

    }

}
