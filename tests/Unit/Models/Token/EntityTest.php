<?php

namespace RZP\Tests\Unit\Models\Token;

use RZP\Tests\Functional\TestCase;

class EntityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = new \RZP\Models\Customer\Token\Entity;
    }

    function testGetAadhaarVid()
    {
        $token = $this->fixtures->create('token', ['aadhaar_vid' => '1234567890123456']);

        $this->assertSame(16, strlen($token->getAadhaarVid()));
    }
}
