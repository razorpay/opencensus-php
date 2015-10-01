<?php

namespace Tests\Unit\EE\Exception;

use EE\Exception;
use EE\Error\ErrorCode;
use EE\Error\PublicErrorDescription;
use Mockery;
use Models\Card;
use Tests\TestCase;

class ExceptionTest extends TestCase
{
    protected $testDataFilePath;

    public function setUp()
    {
        parent::setUp();
    }

    public function testException()
    {
        $exception = new Exception\LogicException('logical flaw occurred');

        $handler = $this->app['exception.handler'];

        $response = $handler->genericExceptionHandler($exception);

        $content = $response->getContent();

        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertEquals($content['error']['code'], ErrorCode::SERVER_ERROR);
        $this->assertEquals($content['error']['description'], PublicErrorDescription::SERVER_ERROR);
        $this->assertEquals($content['exception']['message'], 'logical flaw occurred');
        $this->assertEquals($content['exception']['code'], ErrorCode::SERVER_ERROR_LOGICAL_ERROR);
    }

    protected function assertJsonAndGetContent($content)
    {
        $this->assertJson($content);

        return json_encode($content, true);
    }
}