<?php

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase transactions are used, also tests if transactions are automatically
 * captured on successful transactions. Hold Transactions are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Models\Manager\Card;

class ValidationTest extends TestCase {

    public function setUp()
    {
        parent::setUp();

        //Start DB transaction so as to rollback once done
        DB::beginTransaction();
        $this->input = [
            'number' => 42, //Intentionally invalid for first test case
            'expiry_month' => '1',
            'expiry_year' => '2017',
            'cvv' => '123',
            'name' => 'Abhay',
            'address_line1' => 105,
            'address_line2' => 105,
            'address_city' => 104,
            'address_state' => 200,
            'address_country' => 'IN',
            'address_zip' => '244713',
        ];
        $this->card = new Card();
    }

    public function tearDown()
    {
        //Undo DB Changes after test
        DB::rollback();
    }
    public function testShortCardNumber()
    {

        $this->setExpectedException('EE\Exception\CardErrorException');
        $this->card->build($this->input);
    }
    public function test4DigitCVV()
    {
        $this->input['cvv'] = 1234;
        $this->input['number'] = '4012001036275556';
        $this->card->build($this->input);
    }
}
