<?php

class TransactionRetrieveTest extends TestCase {

	/**
	* Tests the /transactions route. Should return valid json with list of transactions
	*/

	public function testRetrieveTransaction()
    {
        $response = $this->call('GET', '/transactions');
        $content = $response->getContent();
        $this->assertJson($content);
    }
}

