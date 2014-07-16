<?php

use Tests\Functional\TestCase;
use Tests\Functional\Transaction\TransactionAuthFlowTrait;

class TransactionValidationTest extends TestCase
{
    use TransactionAuthFlowTrait;

    public function setUp()
    {
        parent::setUp();

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

    public function testInvalidEmailInTransaction()
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
}
