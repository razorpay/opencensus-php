<?php

namespace RZP\Tests\Functional\Request;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class IdempotentTest  extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IdempotentTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateInvoiceWithIdempotentKey()
    {
        //X-Idempotent-Key
        $headers = [
            'HTTP_X_Idempotent_Key'    => 'idempotentId',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $responseFirst = $this->startTest();

        // Calling the same request again.
        $responseSecond = $this->startTest();

        $this->assertEquals($responseFirst['id'],$responseSecond['id']);
    }

    public function testCreateInvoiceWithIdempotentKeyInOneRequest()
    {
        //X-Idempotent-Key
        $headers = [
            'HTTP_X_Idempotent_Key'    => 'idempotentId',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $responseFirst = $this->startTest();

        // calling different request no idempotent Key
        $this->testData[__FUNCTION__]['request']['content']['receipt'] = '00000000000002';

        $this->testData[__FUNCTION__]['request']['server'] = [];

        $responseSecond = $this->startTest();

        $this->assertNotEquals($responseFirst['id'],$responseSecond['id']);
    }
}
