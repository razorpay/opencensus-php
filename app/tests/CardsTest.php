<?php

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase transactions are used, also tests if transactions are automatically
 * captured on successful transactions. Hold Transactions are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;

require_once('helpers/Transaction.php');

class CardsTest extends Transaction
{

    protected $cards = array();

    public function setUp()
    {
        parent::setUp();

        //Start DB transaction so as to rollback once done
        DB::beginTransaction();

        //Seed the db with required data
        Eloquent::unguard();
        $key = Factory::create('Models\DAL\Key');
        Eloquent::reguard();

        //
        // load list of cards with expected responses for each
        //
        $this->cards = include(__DIR__.'/helpers/cards.php');
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }


    /**
     * Tests for individual cards
     */

    /**
     * @group testReponseTimeOut
     * @group testFailure
     */
    public function testCard0()
    {
        echo "\nTesting: CC Transaction \n";
        echo "Expected Response: Timeout Transaction \n";
        $this->createTransaction($this->cards[0]);
    }

    /**
     * @group testSuccess
     * @group testCC
     */
    public function testCard1()
    {
        echo "\nTesting: CC Transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->createTransaction($this->cards[1]);
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCard2()
    {
        echo "\nTesting: CC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->createTransaction($this->cards[2]);
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCard3()
    {
        echo "\nTesting: CC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->createTransaction($this->cards[3]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testCard4()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Signature Failure error. \n";
        $this->createTransaction($this->cards[4]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testCard5()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Signature Failure error. \n";
        $this->createTransaction($this->cards[5]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard6()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->createTransaction($this->cards[6]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard7()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->createTransaction($this->cards[7]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard8()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->createTransaction($this->cards[8]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseParesNotSuccess
     */
    public function testCard9()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Pares Not Success error. \n";
        $this->createTransaction($this->cards[9]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testCard10()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->createTransaction($this->cards[10]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testCard11()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->createTransaction($this->cards[11]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard12()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Sucessfull Auth \n";
        $this->createTransaction($this->cards[12]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard13()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->createTransaction($this->cards[13]);
    }
}
