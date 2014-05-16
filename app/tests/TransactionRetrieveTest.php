<?php
use Laracasts\TestDummy\Factory;
class TransactionRetrieveTest extends TestCase {

	public function setUp()
    {
        //Start DB transaction so as to rollback once done
        DB::beginTransaction();
        Eloquent::unguard();
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
        $response = $this->call('GET', '/transactions');
        $content = $response->getContent();
        $this->assertJson($content);
    }

    
}


