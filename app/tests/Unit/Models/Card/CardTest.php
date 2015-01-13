<?php

namespace Tests\Unit\Models\Card;

use Mockery;
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

    public function testSupportedCardNetworks()
    {
        $this->markTestSkipped();
        $supportedCards = array(
            ['5546199799745013', 'MasterCard'],
            ['5555 5555 5555 4444', 'MasterCard'],
            ['42 4242 42 4242 4242', 'Visa'],
            ['6240008631401148', 'Unknown']);

        foreach ($supportedCards as $card)
        {
            $this->input['number'] = $card[0];
            $cardData = (new Card\Core)->createAndReturnWithSensitiveData($this->input);

            $cardRepo = Mockery::mock('Models\Card\Repository[retrieveIinDetails]');

//            $this->app->instance($cardRepo, $dashboard);

            $cardRepo->shouldReceive('retrieveIinDetails')
                     ->andReturn(null);

            $this->assertEquals($card[1], $cardData['network']);
        }
    }


}
