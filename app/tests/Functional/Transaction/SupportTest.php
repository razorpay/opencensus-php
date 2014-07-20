<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;

/**
 * Tests that support transactions (capture/refund) are working fine.
 * creates a hold transaction using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class SupportTest extends TestCase
{
    use TransactionAuthFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $this->testData = include(__DIR__.'/helpers/cards.php');
    }

    /**
     * Tests the support transactions, calls capture & refund
     * @group testSupport
     * @group testCapture
     * @group testRefund
     */
    public function testSupport()
    {
        //
        // GIVEN
        // create an auth transaction using card 1
        //
        $testData = $this->testData['creditCardSuccess'];
        $this->replaceDefualtValues($testData['request']['content']);

        $txn = $this->runRequestResponseFlow($testData);

        // get its transaction id
        $id = $txn['id'];

        // get amount
        $amount = $txn['amount'];

        $this->capture($id, $amount);

        $this->refund($id);
    }

    /**
     * Tests capture transactions, attempts to capture all past transactions & ensures that the transaction specified by id is captured.
     */
    private function capture($id, $amount)
    {
        // WHEN
        // call for capture of transactions
        $response = $this->call('POST', '/transactions/'.$id.'/capture', array('amount'=>$amount), array(), $this->auth);
        $content = $response->getContent();

        // THEN
        // ensure output is json
        $this->assertJson($content);
        $capture = json_decode($content, true);

        // Check if transaction id matches, and captured sucessfully
        $this->assertEquals($id, $capture['id']);
        $this->assertEquals('captured', $capture['status']);
    }

    /**
     * Tests refund transactions, attempts to refund transaction specified by the id & ensures that it is refunded.
     */
    private function refund($id)
    {
        //WHEN
        //call for refund of transactions
        $response = $this->call('POST', '/transactions/'.$id.'/refund',  array('id' => $id), array(), $this->auth);
        $content=$response->getContent();

        //THEN
        //ensure output is json
        $this->assertJson($content);
        $refund = json_decode($content, true);

        //Check if transaction id matches, and refunded sucessfully
        $this->assertEquals($id, $refund['id']);
        $this->assertEquals('refunded', $refund['status']);
    }
}