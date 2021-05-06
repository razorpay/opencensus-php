<?php

namespace RZP\Tests\Unit\Trace;

use Mockery;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Tests\TestCase;
use RZP\Trace;
use RZP\Base\Fetch;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Error\CustomerErrorDescription;

class TraceTest extends TestCase
{
    public function testEmailOnTraceFailure()
    {
        $this->markTestSkipped();

        $trace = Mockery::mock($class, [$this->app])
                        ->makePartial()
                        ->shouldAllowMockingProtectedMethods();

        // Mocks environment returning function so that it thinks it's in prod.
        $trace->shouldReceive('isEnvironmentProd')->once()->andReturn(true);

        //
        // Mocks function which sends the mail about tracing failure.
        // This test case ensures the tracing failure mails are working as a
        // last resort.
        //
        $trace->shouldReceive('sendMailWithData')->once()->with(
            Mockery::type('string'),
            hasKey('type', 'message', 'code', 'file', 'line', 'trace', 'context', 'environment')
        );

        $trace->addRecord('info', 'RANDOM MESSAGE', []);
    }

    //Test to check if sensitive parameters get masked while logging
    public function testMaskLogByParameters()
    {
        $fetch = new Fetch();

        //Test to check if headers.Authorization is getting masked
        $params = ["headers" => ["Authorization" => "Bearer cnpwX2xpdmVfMk0xNFV0Q1BFWWYwa286U3dMVnBZQlUyQnFDUFNGUk50SWhNbkd4"]];

        $fetch->ignoreExtraFindParamsForLogging($params);

        $modifiedParams = ["headers" => []];

        $this->assertEquals($params, $modifiedParams);

        //Test to check if headers.Authorization1 is not getting masked
        $params = ["headers" => ["Authorization1" => "Bearer cnpwX2xpdmVfMk0xNFV0Q1BFWWYwa286U3dMVnBZQlUyQnFDUFNGUk50SWhNbkd4"]];

        $fetch->ignoreExtraFindParamsForLogging($params);

        $modifiedParams = ["headers" => ["Authorization1" => "Bearer cnpwX2xpdmVfMk0xNFV0Q1BFWWYwa286U3dMVnBZQlUyQnFDUFNGUk50SWhNbkd4"]];

        $this->assertEquals($params, $modifiedParams);

        //Test to check if headers is not getting masked
        $params = ["headers" => "cnpwX2xpdmVfMk0xNFV0Q1BFWWYwa286U3dMVnBZQlUyQnFDUFNGUk50SWhNbkd4"];

        $fetch->ignoreExtraFindParamsForLogging($params);

        $modifiedParams = ["headers" => "cnpwX2xpdmVfMk0xNFV0Q1BFWWYwa286U3dMVnBZQlUyQnFDUFNGUk50SWhNbkd4"];

        $this->assertEquals($params, $modifiedParams);
    }
}
