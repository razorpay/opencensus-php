<?php

use Tests\Functional\TestCase;
use Tests\Functional\Transaction\TransactionAuthFlowTrait;

class TransactionValidationTest extends TestCase
{
    use TransactionAuthFlowTrait;

    public function setUp()
    {
        parent::setUp();

        $key = $this->createEntity('key');

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/helpers/cardNumbers.php');
    }

    public function testShortCardNumber()
    {
        $this->startTest();
    }

    public function testNonNumericCardNumber()
    {
        $this->startTest();
    }

    public function testLongCardNumber()
    {
        $this->startTest();
    }

    public function testNonLuhnCardNumber()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryMonth()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryYear()
    {
        $this->startTest();
    }

    public function testInvalidCardExpiryDate()
    {
        $this->startTest();
    }

    public function invalidEmailInTransaction()
    {
        $this->startTest();
    }

    public function testCardNumberWithSpaces()
    {
        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $name = lcfirst(substr($func, 4));

        $testData = $this->testData[$name];

        $this->replaceDefualtValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData);
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
