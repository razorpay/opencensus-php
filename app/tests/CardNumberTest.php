<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase transactions are used, also tests if transactions are automatically
 * captured on successful transactions. Hold Transactions are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;

require_once('helpers/Transaction.php');

class CardNumberTest extends Transaction
{
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
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/cardNumbers.php');
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }


    /**
     * @group testSuccess
     * @group testNumber2
     */
    public function testShortCardNumber()
    {
        $this->runNumberTest();
    }

    public function testNonNumericCardNumber()
    {
        $this->runNumberTest();
    }

    public function testLongCardNumber()
    {
        $this->runNumberTest();
    }

    public function testNonLuhnCardNumber()
    {
        $this->runNumberTest();
    }

    public function testInvalidCardExpiryMonth()
    {
        $this->runNumberTest();
    }

    public function testInvalidCardExpiryYear()
    {
        $this->runNumberTest();
    }

    public function testInvalidCardExpiryDate()
    {
        $this->runNumberTest();
    }

    public function invalidEmailInTransaction()
    {
        $this->runNumberTest();
    }

    public function testCardNumberWithSpaces()
    {
        $this->runNumberTest();
    }

    public function runNumberTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $name = lcfirst(substr($func, 4));

        $testData = $this->testData[$name];

        $this->replaceDefualtValues($testData['request']['content']);

        $this->runTestFlow($testData);
    }

    protected function getTransactionArray($number)
    {
        //
        // default transaction object
        //
        $transaction = [
            'amount'          =>  '100',
            'currency'        =>  'INR',
            'card' => array(
                'number'            => $number,
                'name'              => 'Harshil',
                'expiry_month'      =>'12',
                'expiry_year'       => '2014',
                'cvv'               => '566',
                'address_line1'     => '21, Rameshwar',
                'address_line2'     => 'jaipurwa',
                'address_city'      => 'jaipur',
                'address_state'     =>  'Rajasathan',
                'address_country'   =>  'India',
                'address_zip'       =>  '123345',
            ),
            'udf' => array(
                'email'     =>  'lol@lko.com',
                'contact'   =>  '991889902'
            ),
        ];

        return $transaction;
    }
}
