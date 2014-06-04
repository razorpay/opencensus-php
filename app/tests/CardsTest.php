<?php

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase transactions are used, also tests if transactions are automatically
 * captured on successful transactions. Hold Transactions are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

require_once('helpers/Transaction.php');
use Laracasts\TestDummy\Factory;
class CardsTest extends Transaction {

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
     * Tests for individual cards
     */

    /**
     * @group testReponseTimeOut
     * @group testFailure
     */
    public function testCard0()
    {
        $this->createTransaction(0);
    }

    /**
     * @group testSuccess
     * @group testCC
     */
    public function testCard1()
    {
        $this->createTransaction(1);
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCard2()
    {
        $this->createTransaction(2);
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCard3()
    {
        $this->createTransaction(3);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testCard4()
    {
        $this->createTransaction(4);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testCard5()
    {
        $this->createTransaction(5);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard6()
    {
        $this->createTransaction(6);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard7()
    {
        $this->createTransaction(7);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard8()
    {
        $this->createTransaction(8);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseParesNotSuccess
     */
    public function testCard9()
    {
        $this->createTransaction(9);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testCard10()
    {
        $this->createTransaction(10);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testCard11()
    {
        $this->createTransaction(11);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard12()
    {
        $this->createTransaction(12);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testCard13()
    {
        $this->createTransaction(13);
    }
}
