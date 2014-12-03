<?php

namespace Tests\Unit\Models\Card;

use Models\Card;
use Tests\TestCase;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase payments are used, also tests if payments are automatically
 * captured on successful payments. Hold Payments are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class ValidationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

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
        $this->card = new Card\Entity();
    }

    public function testShortCardNumber()
    {
        $this->setExpectedException('EE\Exception\BadRequestValidationFailureException');
        $this->card->build($this->input);
    }
    public function test4DigitCVV()
    {
        $this->input['cvv'] = 1234;
        $this->input['number'] = '4012001036275556';
        $this->card->build($this->input);
    }
}
