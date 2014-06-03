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
     * @group testCard0
     */
    public function testCard0()
    {
        $this->createTransaction(0);
    }

    public function testCard1()
    {
        $this->createTransaction(1);
    }

    public function testCard2()
    {
        $this->createTransaction(2);
    }

    public function testCard3()
    {
        $this->createTransaction(3);
    }

    public function testCard4()
    {
        $this->createTransaction(4);
    }

    public function testCard5()
    {
        $this->createTransaction(5);
    }
    
    public function testCard6()
    {
        $this->createTransaction(6);
    }
    
    public function testCard7()
    {
        $this->createTransaction(7);
    }
    
    public function testCard8()
    {
        $this->createTransaction(8);
    }
    
    public function testCard9()
    {
        $this->createTransaction(9);
    }
    
    public function testCard10()
    {
        $this->createTransaction(10);
    }
    
    public function testCard11()
    {
        $this->createTransaction(11);
    }
    
    public function testCard12()
    {
        $this->createTransaction(12);
    }
    
    public function testCard13()
    {
        $this->createTransaction(13);
    }
}