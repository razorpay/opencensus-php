<?php

/**
 * Tests that support transactions (capture/refund) are working fine.
 * creates a hold transaction using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

require_once('helpers/Transaction.php');
use Laracasts\TestDummy\Factory;
class SupportTest extends Transaction {

    public function setUp()
    {
        parent::setUp();

        //Start DB transaction so as to rollback once done
        DB::beginTransaction();

        //Seed the db with required data
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
    * Main test function, that is run by phpunit for testign the support transactions, calls other functions
    */

    public function testSupport(){

        //GIVEN
        //create an auth transaction using card 12
        $response = $this->createTransaction(1);

        //get its transaction id
        $id=$response->id;

        $this->capture($id);
        $this->refund($id);
    }

    /**
     * Tests capture transactions, attempts to capture all past transactions & ensures that the transaction specified by id is captured.
     */
    private function capture($id)
    {
        //WHEN
        //call for capture of transactions
        $response = $this->action('POST', 'TransactionController@postCapture', array('id'=>$id));
        $content=$response->getContent();

        //THEN
        //ensure output is json
        $this->assertJson($content);
        $capture=json_decode($content);

        //Check if transaction id matches, and captured sucessfully
        $this->assertEquals($capture->id, $id);
        $this->assertEquals($capture->status, 'captured');
    }

    /**
     * Tests refund transactions, attempts to refund transaction specified by the id & ensures that it is refunded.
     */
    private function refund($id)
    {
        //WHEN
        //call for refund of transactions
        $response = $this->action('POST', 'TransactionController@postRefund',  array('id' => $id));
        $content=$response->getContent();

        //THEN
        //ensure output is json
        $this->assertJson($content);
        $refund=json_decode($content);

        //Check if transaction id matches, and refunded sucessfully
        $this->assertEquals($refund->id, $id);
        $this->assertEquals($refund->status, 'refunded');
    }

}
