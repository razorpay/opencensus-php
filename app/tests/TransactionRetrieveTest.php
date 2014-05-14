<?php

class TransactionRetrieveTest extends TestCase {


	public function testRetrieveTransaction()
    {
        $response = $this->call('GET', '/transactions');
        $content = $response->getContent();
        $this->assertJson($content);

    }
}

