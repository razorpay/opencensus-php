<?php

class TransactionRetrieveTest extends TestCase {


	public function testRetrieveTransaction()
    {
        $response = $this->call('GET', '/transactions');
        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $this->assertJson($content);

        //The following line should be uncommented after fixing /transaction/{id} routes
        //$response = $this->call('GET', '/transactions/4b66157a-daa9-11e3-a88f-3c077173');
        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $this->assertJson($content);
        s($content);


    }
}

