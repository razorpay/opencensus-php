<?php

namespace RZP\Tests\Unit\Models\Card;

use Mockery;
use RZP\Models\Card;
use RZP\Tests\TestCase;

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
        ];

        $this->card = new Card\Entity();
    }

    public function testShortCardNumber()
    {
        $this->setExpectedException('RZP\Exception\BadRequestValidationFailureException');

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

    public function test0PrefixedCVV()
    {
        $this->input['cvv'] = '0234';

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

    public function testCardNetworkDetection()
    {
        $this->app['rzp.mode'] = 'test';

        $core = new Card\Core;

        $map = array(
            ['6073849700004947', '888', 'RuPay', 'debit'],
            ['341111111111111', '8888', 'American Express', 'credit'],
            ['5010000000000007', '888', 'Maestro', 'debit'],
        );

        foreach ($map as $values)
        {
            $this->input['number'] = $values[0];
            $this->input['cvv'] = $values[1];

            $this->card->build($this->input);
            $core->fillNetworkDetails($this->card, $this->input);

            $this->assertEquals($this->card->getNetwork(), $values[2]);
            $this->assertEquals($this->card->getType(), $values[3]);
        }

    }
}
