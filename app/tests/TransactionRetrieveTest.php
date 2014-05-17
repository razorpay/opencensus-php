<?php

/**
 * Tests that retreieving of transactions is working fine.
 * creates a transaction using testdummy & attempts to retrieve it
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;
class TransactionRetrieveTest extends TestCase {

	public function setUp()
    {
    	parent::setUp();

        //Start DB transaction so as to rollback once done
        DB::beginTransaction();

        //Seed the db with required data
        Eloquent::unguard();
        $merchant=Factory::create('Models\DAL\Merchant', ['id' => 1]);
        $key = Factory::create('Models\DAL\Key', ['merchant_id'=>1]);
        $transaction=Factory::create('Models\DAL\Transaction', ['merchant_id'=>1]);
        Eloquent::reguard();
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }

	/**
	* Tests the /transactions & /transactions/$id route. 
	* Should return valid json with list of all transactions in case 1
	* Should return valid json with details of transaction specified by id in case 2
	*/

	public function testRetrieveTransaction()
    {	

    	//Testing retrieval of all transactions with /transactions
    	//GIVEN - Nothing

    	//WHEN
        $response = $this->call('GET', '/transactions');
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);
        $transactions=json_decode($content);

        //Testing retrieval of specific transactions with /transactions/$id
        //GIVEN
        $id=$transactions[0]->id;

        //WHEN
        $response = $this->call('GET', "/transactions/$id");
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);
        $transaction = json_decode($content, true);
        $this->assertEquals($transaction['id'], $id);
    }
    
}


