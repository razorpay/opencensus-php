<?php

namespace RZP\Tests\Unit\Models\Card;

use Mockery;
use RZP\Tests\TestCase;
use RZP\Models\Card\IIN;

class IinTest extends TestCase
{
    protected function setUp(): void
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
        $this->assertFalse($iin->supports(IIN\Flow::$flows[IIN\Flow::_3DS] | IIN\Flow::$flows[IIN\Flow::OTP]));
    }

    public function testAllFlow()
    {
        $this->input['flows'] = ['3ds' => '1', 'otp' => '1', 'pin' => '1'];

        $iin = $this->iin->build($this->input);

        $this->assertTrue($iin->supports(IIN\Flow::_3DS));
        $this->assertTrue($iin->supports(IIN\Flow::$flows[IIN\Flow::_3DS] | IIN\Flow::$flows[IIN\Flow::OTP]));

        $iin = $iin->toArrayAdmin();

        $this->assertContains('3ds', $iin['flows']);
        $this->assertContains('otp', $iin['flows']);
        $this->assertContains('pin', $iin['flows']);
    }
}
