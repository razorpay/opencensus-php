<?php

/**
* Tests that capturing of transactions is working fine.
* creates a transaction using card 13 and then attempts to capture it.
* Is successful if captured successfully.
*/

require_once('helpers/Transaction.php');
use Laracasts\TestDummy\Factory;
class CaptureTest extends Transaction {

    public function setUp()
    {
        parent::setUp();

        //Start DB transaction so as to rollback once done
        DB::beginTransaction();
        Eloquent::unguard();
        $key = Factory::create('Models\DAL\Key');
        Eloquent::reguard();        
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }

    /**
    * Tests for capture of transaction
    */

    public function testCapture()
    {
        //create an auth transaction using card 12
        $response = $this->createTransaction(12);

        //get its transaction id
        $tid=$response['id'];

        //call for capture of transactions
        $response = $this->action('GET', 'TransactionController@capture');
        $content=$response->getContent();

        //ensure output is json
        $this->assertJson($content);
        $capture=json_decode($content);

        //Check if transaction id matches, and captured sucessfully
        $this->assertEquals($capture[0]->id, $tid);
        $this->assertTrue($capture[0]->captured);
    }

}