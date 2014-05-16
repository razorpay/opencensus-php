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
        $key = Factory::create('Models\DAL\Key');
        $transaction=Factory::create('Models\DAL\Transaction');
        Eloquent::reguard();
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }

	/**
	* Tests the /transactions route. Should return valid json with list of transactions
	*/

	public function testRetrieveTransaction()
    {	
    	//GIVEN

    	//WHEN
        $response = $this->call('GET', '/transactions');
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);
    }

    
}


