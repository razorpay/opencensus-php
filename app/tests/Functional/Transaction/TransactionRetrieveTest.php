<?php

namespace Tests\Functional\Transaction;

use Laracasts\TestDummy\Factory;
use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

/**
 * Tests that retreieving of transactions is working fine.
 * creates a transaction using testdummy & attempts to retrieve it
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class TransactionRetrieveTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $transaction = $this->createEntity('transaction', ['merchant_id' => 1]);

        $this->request = array(
            'method' => 'GET',
            'url' => '/transactions');
    }

    protected function retrieveTransactionsDefault()
    {
        $response = $this->makeRequest($this->request);

        $content = $response->getContent();

        return json_decode($content);
    }

    /**
    * Tests the /transactions & /transactions/$id route.
    * Should return valid json with list of all transactions in case 1
    * Should return valid json with details of transaction specified by id in case 2
    *
    * @group testRetrieveTransaction
    */
    public function testRetrieveTransaction()
    {
        //GIVEN - Nothing
        //WHEN
        $response = $this->makeRequest($this->request);

        $content = $response->getContent();

        //THEN
        $this->assertJson($content);

        $transactions = json_decode($content);
    }

    /**
     * @group testRetrieveTransactionWithId
     */
    public function testRetrieveTransactionWithId()
    {
        $transactions = $this->retrieveTransactionsDefault();

        //GIVEN
        $id = $transactions->data[0]->id;

        //WHEN
        $request = $this->request;
        $request['url'] .= '/'.$id;

        $response = $this->makeRequest($request);

        $content = $response->getContent();

        //THEN
        $this->assertJson($content);

        $transaction = json_decode($content);

        $this->assertEquals($id, $transaction->id);
    }

    /**
     * @group testRetrieveTransactionWithStatusAndCount
     */
    public function testRetrieveTransactionWithStatusAndCount()
    {
        $transactions = $this->retrieveTransactionsDefault();

        //GIVEN
        $status = $transactions->data[0]->status;
        $id = $transactions->data[0]->id;

        $request = $this->request;
        $request['content'] = array('count' => 1, 'status' => $status);
        //WHEN
        $response = $this->makeRequest($request);
//        $response = $this->call('GET', "/transactions/?count=1&status=".$status);
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);

        $transaction = json_decode($content);

        $this->assertEquals($id, $transaction->data[0]->id);
    }

    /**
     * @group testRetrieveTransactionWithCreateAt
     */
    public function testRetrieveTransactionsWithCreatedAt()
    {
        $transactions = $this->retrieveTransactionsDefault();
        $id = $transactions->data[0]->id;

        //GIVEN
        $created_at = $transactions->data[0]->created_at;

        //WHEN
        $response = $this->call('GET', "/transactions/?created=".$created_at, array(), array(), $this->auth);
        $content = $response->getContent();

        //THEN
        $this->assertJson($content);
        $transaction = json_decode($content);

        $this->assertEquals($id, $transaction->data[0]->id);
    }
}
