<?php

namespace RZP\Tests\Functional\Request;

use Illuminate\Http\Request;
use RZP\Http\Middleware\EventTrackIDHandler;
use RZP\Http\RequestHeader;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class  RequestContextHandlerTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RequestContextHandlerTestData.php';

        parent::setUp();
    }

    public function testContextTrackIdGenerated()
    {

        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $trackId = $this->app['req.context']->getTrackId();

        $this->assertNotNull($trackId);


    }

    public function testContextTraceIdGenerated()
    {
        $this->ba->batchAppAuth();

        $headers = [
            'HTTP_X_Request_TraceId'    => '1234123412341234',
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq'
        ];
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $traceId = $this->app->request->headers->get(RequestHeader::X_REQUEST_TRACE_ID);

        $this->assertNotNull($traceId);
    }
}
