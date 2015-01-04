<?php

namespace Tests\Unit\Models\Card;

use Models\Card;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->input = [
            'number' => '4012001036275556',
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

        $this->input['number'] = '42';
        $this->card->build($this->input);
    }

    /**
     * Checks that 4 digit cvv is accepted and does not throw an exception
     */
    public function test4DigitCVV()
    {
        $this->input['cvv'] = '1234';

        $this->card->build($this->input);
    }

    public function testTwoLetterExpiryYear()
    {
        $this->input['expiry_year'] = '17';

        $card = $this->card->build($this->input);

        $this->assertInternalType('int', $card['expiry_month']);
        $this->assertEquals($card['expiry_year'], 2017);
    }

    public function testDualDigitCardExpiryMonth()
    {
        $this->input['expiry_month'] = '01';

        $card = $this->card->build($this->input);

        $this->assertInternalType('int', $card['expiry_month']);
        $this->assertEquals($card['expiry_month'], 1);
    }
}
