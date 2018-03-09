<?php

namespace RZP\Tests\Unit\Models\Card;

use Mockery;
use RZP\Tests\TestCase;
use RZP\Models\Card\IIN;

class IinTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->input = [
            'iin'           => '502166',
            'category'      => null,
            'network'       => 'Maestro',
            'type'          => 'debit',
            'country'       => 'IN',
            'issuer_name'   => null,
            'trivia'        => 'random'
        ];

        $this->iin = new IIN\Entity();
    }

    public function test3dsFlow()
    {
        $this->input['flows'] = ['3ds' => '1'];

        $iin = $this->iin->build($this->input);

        $this->assertTrue($iin->supports(IIN\Flow::_3DS));
        $this->assertFalse($iin->supports(IIN\Flow::_3DS | IIN\Flow::OTP));
    }

    public function testAllFlow()
    {
        $this->input['flows'] = ['3ds' => '1', 'otp' => '1', 'debit_pin' => '1'];

        $iin = $this->iin->build($this->input);

        $this->assertTrue($iin->supports(IIN\Flow::_3DS));
        $this->assertTrue($iin->supports(IIN\Flow::_3DS | IIN\Flow::OTP));

        $iin = $iin->toArrayAdmin();

        $this->assertContains('3ds', $iin['flows']);
        $this->assertContains('otp', $iin['flows']);
        $this->assertContains('debit_pin', $iin['flows']);
    }
}
