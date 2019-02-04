<?php

namespace RZP\Tests\Unit\Services;

use Redis;

use RZP\Tests\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardVaultTest extends TestCase
{
    use PaymentTrait;


    public function setUp()
    {
        parent::setUp();

        $this->mockCardVault();

        $this->cardVault = $this->app['card.cardVault'];
    }

    public function testCardVaultFunctions()
    {
        $cardNumber = '4012001038443335';

        $token = $this->cardVault->tokenize(['card' => '4012001038443335']);

        $this->assertEquals('NDAxMjAwMTAzODQ0MzMzNQ==', $token);

        $card = $this->cardVault->detokenize($token);

        $this->assertEquals($cardNumber, $card);

        $response = $this->cardVault->validateToken($token);

        $this->assertTrue($response['success']);

        $response = $this->cardVault->validateToken('fail');

        $this->assertFalse($response['success']);
    }
}
