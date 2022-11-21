<?php

namespace RZP\Tests\Unit\Models\Card;

use Mockery;
use RZP\Models\Card;
use RZP\Tests\Functional\TestCase;

class ValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->input = [
            'number' => '4012001036275556',
            'expiry_month' => '1',
            'expiry_year' => '2035',
            'cvv' => '123',
            'name' => 'Abhay',
        ];

        $this->card = new Card\Entity();
    }

    public function testShortCardNumber()
    {
        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

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
        $this->input['expiry_year'] = '35';

        $card = $this->card->build($this->input);

        $this->assertIsInt($card['expiry_month']);
        $this->assertEquals($card['expiry_year'], 2035);
    }

    public function testDualDigitCardExpiryMonth()
    {
        $this->input['expiry_month'] = '01';

        $card = $this->card->build($this->input);

        $this->assertIsInt($card['expiry_month']);
        $this->assertEquals($card['expiry_month'], 1);
    }

    public function testCardNetworkDetection()
    {
        $this->app['rzp.mode'] = 'test';

        /*
         *  Understanding the need for this fixture:
         *  First, please read the PHP doc comment for detectNetwork function in Models/Card/Network.php
         *  This test is to ensure that if an Iin exist in the database, we must not fall back on regex matching
         *  for that Iin, whatsoever.
         *
         *  Even though Iin  "556763" belongs to MasterCard network, for the sake of this test we assign
         *  it to Visa and insert in the Iin table.
         */
        $this->fixtures->create('iin', [
            'iin' => 556763,
            'network' => 'Visa',
            'type' => 'credit',
            'country' => 'US'
        ]);

        $merchant = $this->fixtures->create('merchant');

        $this->card->merchant()->associate($merchant);

        $core = new Card\Core;

        $map = array(
            ['5567639700004947', '888', 'Visa', 'credit'],
            ['6073849700004947', '888', 'RuPay', 'credit'],
            ['341111111111111', '8888', 'American Express', 'credit'],
            ['5010000000000007', '888', 'Maestro', 'debit'],
            ['3538105814111110',  '888', 'JCB',  'credit'],
            ['2131005964111147',  '888', 'JCB',  'credit'],
            ['3538001111111111',  '888', 'RuPay',  'credit'],
            ['3538010000000004',  '888', 'JCB',  'credit'],
            ['3538020000000003',  '888', 'RuPay',  'credit'],
            ['2030400000121212',  '888', 'Bajaj Finserv',  'credit'],
            ['5900006817596627', '888', 'MasterCard', 'credit'],
            ['2720992121212123', '888', 'MasterCard', 'credit'],
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

    public function testMaskCardNumber()
    {
        $this->card->build([
            Card\Entity::NUMBER =>  '4111111111111111',
            Card\Entity::EXPIRY_MONTH   =>  10,
            Card\Entity::EXPIRY_YEAR    =>  29,
            Card\Entity::NAME           =>  'John Doe',
            Card\Entity::CVV            =>  Card\Entity::DUMMY_CVV,
        ]);
        $result = $this->card->getMaskedCardNumber();

        $this->assertEquals('411111XXXXXX1111', $result);

    }

    public function testStaleIINDetails()
    {
        $this->fixtures->create(
            'iin',
            [
                'iin' => 485123,
                'network' => 'Mastercard',
                'type' => 'credit',
                'issuer' => 'SBI',
                'country' => 'US'
            ]
        );

        $cardId = $this->fixtures->create(
            'card',
            [
                'iin'                => '485123',
                'network'            => 'Visa',
                'type'               => 'debit',
                'issuer'             => 'HDFC'
            ]
        )['id'];

        $card = (new Card\Repository())->find($cardId);
        $card->overrideIINDetails();
        $iinRelation = $card->iinRelation;

        $this->assertEquals($iinRelation->getNetwork(), $card->getNetwork());
        $this->assertEquals($iinRelation->getType(), $card->getType());
        $this->assertEquals($iinRelation->getIssuer(), $card->getIssuer());
    }
}
