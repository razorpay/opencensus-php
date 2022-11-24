<?php

namespace RZP\Tests\Unit\EE\Exception;

use Mockery;
use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Card;
use RZP\Tests\TestCase;
use RZP\Error\ErrorCode;
use RZP\Error\ErrorClass;
use RZP\Error\PublicErrorDescription;
use RZP\Error\CustomerErrorDescription;
use Symfony\Component\ErrorHandler\Error\FatalError;

class ExceptionTest extends TestCase
{
    protected $testDataFilePath;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBadRequestExceptionWithCustomerDescription()
    {
        $this->mockExceptionHandlerReturnTestingFalse();

        $this->mockPublicAuth();

        $this->app['config']->set('app.debug', false);

        $exception = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED);

        $response = $this->app['exception.handler']->render(null, $exception);

        $content = $response->getContent();

        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertEquals($content['error']['code'], ErrorCode::BAD_REQUEST_ERROR);
        $this->assertEquals(
            $content['error']['description'],
            CustomerErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED);
    }

    public function testValidationExceptionPublicAuth()
    {
        $this->mockExceptionHandlerReturnTestingFalse();

        $this->mockPublicAuth();

        $this->app['config']->set('app.debug', false);

        $exception = new Exception\BadRequestValidationFailureException('Dummy exception');

        $response = $this->app['exception.handler']->render(null, $exception);

        $content = $response->getContent();

        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertEquals($content['error']['code'], ErrorCode::BAD_REQUEST_ERROR);
        $this->assertEquals($content['error']['description'], 'Dummy exception');
    }

    public function testLogicalException()
    {
        $this->mockExceptionHandlerReturnTestingFalse();

        $exception = new Exception\LogicException('logical flaw occurred');

        $response = $this->app['exception.handler']->render(null, $exception);

        $content = $response->getContent();

        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertEquals($content['error']['code'], ErrorCode::SERVER_ERROR);
        $this->assertEquals($content['error']['description'], PublicErrorDescription::SERVER_ERROR);
        $this->assertEquals($content['exception']['message'], 'logical flaw occurred');
        $this->assertEquals($content['exception']['code'], ErrorCode::SERVER_ERROR_LOGICAL_ERROR);
    }

    public function testRecoverableException()
    {
        $this->mockExceptionHandlerReturnTestingFalse();

        $exception = new Exception\BadRequestValidationFailureException('Dummy exception');

        $handler = $this->app['exception.handler'];

        $response = $handler->baseExceptionHandler($exception, $exception->getCode());

        $content = $response->getContent();

        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertEquals($content['error']['code'], ErrorCode::BAD_REQUEST_ERROR);
        $this->assertEquals($content['error']['description'], 'Dummy exception');
    }

    public function testExceptionWithToStringError()
    {
        $exception = new FatalError(
            '... Swift_Message::__toString() ...', 0, [], 0, 0);

        $handler = $this->app['exception.handler'];

        $response = $handler->render(null, $exception);

        $content = $response->getContent();

        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertEquals($content['error']['code'], ErrorCode::SERVER_ERROR);
        $this->assertEquals($content['error']['internal_error_code'], ErrorCode::SERVER_ERROR_TO_STRING_EXCEPTION);
    }

    public function testBadRequestErrorPublicDescriptions()
    {
        $reflector = new \ReflectionClass(ErrorCode::class);

        $codesWithoutDescription = null;

        foreach ($reflector->getConstants() as $code => $value)
        {
            if ($this->needsPublicDescription($code) === false)
            {
                continue;
            }

            try
            {
                $exception = new Error($code);

                $this->assertNotNull($exception->getCustomerDescription());
            }
            catch(Exception\InvalidArgumentException $e)
            {
                $codesWithoutDescription[] = $code;
            }
        }

        $codeList = [];
        if ($codesWithoutDescription !== null)
        {
            $codeList = implode('\n', $codesWithoutDescription);
        }

        $this->assertEmpty($codesWithoutDescription, "Codes without description: " . $codeList);
    }

    protected function needsPublicDescription(string $code): bool
    {
        // Only BAD_REQUEST_ error codes use public descriptions in 'message'
        if (str_before($code, '_') !== 'BAD')
        {
            return false;
        }

        // Message is built at runtime (see ExtraFieldsException)
        if ($code === ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED)
        {
            return false;
        }

        return true;
    }

    protected function assertJsonAndGetContent($content)
    {
        $this->assertJson($content);

        return json_encode($content, true);
    }

    protected function mockPublicAuth()
    {
        $class = RZP\Http\BasicAuth\BasicAuth::class;

        $handler = Mockery::mock($class)->makePartial();

        $handler->shouldReceive('isPublicAuth')
                ->once()
                ->andReturnUsing(function ()
                {
                    return true;
                })->mock();

        $handler->shouldReceive('getMerchant')
                ->andReturnUsing(function ()
                {
                    return null;
                })->mock();

        $handler->shouldReceive('getProduct')
                ->andReturnUsing(function ()
                {
                    return null;
                })->mock();

        $handler->shouldReceive('getDevice')
            ->andReturnUsing(function ()
            {
                return null;
            })->mock();

        $this->app->instance('basicauth', $handler);
    }

    protected function mockExceptionHandlerReturnTestingFalse()
    {
        $class = Exception\Handler::class;

        $handler = $this->getMockBuilder($class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['isTesting'])
            ->getMock();

        $this->app->instance('exception.handler', $handler);
    }
}
