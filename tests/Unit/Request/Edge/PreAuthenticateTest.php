<?php

namespace RZP\Tests\Unit\Request\Edge;

use Razorpay\Edge\Passport\Tests\GeneratesTestPassportJwts;

use RZP\Tests\TestCase;
use RZP\Http\Edge\PreAuthenticate;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;

class PreAuthenticateTest extends TestCase
{
    use HasRequestCases;
    use GeneratesTestPassportJwts;

    public function testHandleWhenNoPassport()
    {
        $request = $this->mockPrivateRoute();

        // Asserts that no exceptions are thrown.
        (new PreAuthenticate)->handle($request);
        // Asserts that passport was not set.
        $this->assertNull(app('request.ctx.v2')->passport);
    }

    public function testHandleWhenInvalidPassport()
    {
        $request = $this->withRequestHeaders(['HTTP_X-Passport-JWT-V1' => 'invalid-value'])->mockPrivateRoute();

        // Asserts that no exceptions are thrown.
        (new PreAuthenticate)->handle($request);
        // Asserts that passport was not set.
        $this->assertNull(app('request.ctx.v2')->passport);
    }

    public function testHandle()
    {
        $request = $this->withRequestHeaders(['HTTP_X-Passport-JWT-V1' => $this->samplePassportJwt()])->mockPrivateRoute();

        // Asserts that no exceptions are thrown.
        (new PreAuthenticate)->handle($request);
        // Asserts that passport was set with valid values.
        $passport = app('request.ctx.v2')->passport;
        $this->assertTrue($passport->identified);
        $this->assertTrue($passport->authenticated);
        $this->assertSame('test', $passport->mode);
        $this->assertSame('10000000000000', $passport->consumer->id);
        $this->assertSame('merchant', $passport->consumer->type);
    }
}
