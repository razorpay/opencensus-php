<?php

namespace RZP\Tests\Functional\AutoGenerateApiDocs;

use RZP\Tests\Functional\TestCase;
use RZP\Services\AutoGenerateApiDocs;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class AutoGenerateApiDocsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/AutoGenerateApiDocsTestData.php';

        parent::setUp();
    }

    public function testAutoGeneratesApiDocs()
    {
        $testFileDir = './_docstest/';

        $expectedOpenApiSpec  =
            '{
                "openapi": "3.0.3",
                "info": {
                    "description": "This is a sample API doc server",
                    "version": "1.0.0",
                    "title": "API documentation"
                },
                "servers": [{
                    "url": "https:\/\/api-web.dev.razorpay.in\/"
                }],
                "paths": {
                    "\/bbps_bill_payments": {
                        "get": {
                            "summary": "Bbps Bill Payments",
                            "description": "<br>**Permitted user roles:** owner,admin<br>**Permitted Auth :** proxy<br>**Team Ownership:** unknown_unknown",
                            "responses": {
                                "200": {
                                    "description": "",
                                    "content": {
                                        "application\/json": {
                                            "schema": {
                                                "type": "object",
                                                "properties": {
                                                    "iframe_embed_url": {
                                                        "type": "string"
                                                    }
                                                },
                                                "example": {
                                                    "iframe_embed_url": "https:\/\/www.wikipedia.org"
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }';

        $expectedOpenApiSpec =   json_decode($expectedOpenApiSpec, true);

        $this->fixtures->merchant->addFeatures(['feature_bbps']);

        $this->ba->proxyAuth();

        $this->startTest();

        (new AutoGenerateApiDocs\CombineApiDetailsFile(1, $testFileDir, $testFileDir . 'combined.json' ))->combine();

        (new AutoGenerateApiDocs\GenerateOpenApiSpecifications( $testFileDir . 'combined.json', $testFileDir, '/open_api_spec.json'))->generate();

        $openApiSpec          = file_get_contents($testFileDir . 'open_api_spec.json');

        $this->assertNotEmpty($openApiSpec);

        $openApiSpec = json_decode($openApiSpec, true);

        $this->assertArraySelectiveEquals($openApiSpec, $expectedOpenApiSpec);
    }
}
