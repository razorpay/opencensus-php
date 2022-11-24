<?php

namespace Unit\Services;

use \WpOrg\Requests\Response;

use RZP\Constants\Mode;
use RZP\Tests\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\RuntimeException;

class LedgerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @expectException \RZP\Exception\BadRequestException
     */
    public function testExceptionForBadRequest()
    {
        $response = new \WpOrg\Requests\Response();
        $obj = new \stdClass();
        $obj->msg = "Validation failure";
        $obj->code = 400;
        $response->status_code = 400;
        $response->body = json_encode($obj);
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage("Validation failure");
        $this->app['ledger']->parseResponse($response, true);
    }

    /**
     * @expectException \RZP\Exception\RuntimeException
     */
    public function testResponseForServerError()
    {
        $response = new \WpOrg\Requests\Response();
        $obj = new \stdClass();
        $obj->msg = "Something is wrong";
        $obj->code = 500;
        $response->status_code = 500;
        $response->body = json_encode($obj);
        $this->app['rzp.mode'] = Mode::LIVE;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unexpected response code received from Ledger service.");
        $this->app['ledger']->parseResponse($response, true);
    }

    public function testResponseForSuccessCase()
    {
        $response = new \WpOrg\Requests\Response();
        $obj = new \stdClass();
        $obj->msg = "Ledger response";
        $obj->code = 200;
        $response->status_code = 200;
        $response->body = json_encode($obj);
        $this->app['rzp.mode'] = Mode::LIVE;
        $response = $this->app['ledger']->parseResponse($response, true);
        $this->assertEquals($response['body']['code'], 200);
        $this->assertEquals($response['body']['msg'], "Ledger response");

    }
}
