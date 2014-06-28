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

    protected $cards = array(
        'shortCardNumber' => [
                'PAN' => '4012',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'nonNumericCardNumber' => [
                'PAN' => '2123abc34098ddd',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'cardNumberWithSpaces' => [
                'PAN' => '40 1 2001 0384 43 33 5',
                'response' => 0,
                'type' => 'CC',
                'response' => 1,
            ],
        'longCardNumber' => [
                'PAN' => '4012001036275556243234234',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field'               => 'number',
            ],
        'jcbCardNumber' => [
                'PAN' => '34',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'amexCardNumber' => [
                'PAN' => '40',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'nonLuhnCardNumber' => [
                'PAN' => '1234567890123456',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'wrongCardExpiryMonth' => [
                'PAN' => '1234567890123456',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'wrongCardExpiryYear' => [
                'PAN' => '1234567890123456',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'wrongCardExpiryDate' => [
                'PAN' => '1234567890123456',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'wrongEmailInTransaction' => [
                'PAN' => '1234567890123456',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        'wrongContactInTransaction' => [
                'PAN' => '1234567890123456',
                'response' => 0,
                'type' => 'CC',
                'exception' => 'EE\Exception\CardErrorException',
                'internal_error_code' => ErrorCode::CARD_ERROR_INVALID_NUMBER,
                'public_error_code'   => PublicErrorCode::CARD_ERROR_INVALID_NUMBER,
                'field' => 'number',
            ],
        );

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

    public function testAmexCardNumber()
    {
        $this->runNumberTest();
    }

    public function testJcbCardNumber()
    {
        $this->runNumberTest();
    }

    public function testNonLuhnCardNumber()
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

        $card = $this->cards[$name];
        $this->createTransaction($card);
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
