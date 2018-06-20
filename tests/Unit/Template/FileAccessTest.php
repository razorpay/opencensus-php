<?php

namespace RZP\Tests\Unit\Trace;

use RZP\Tests\TestCase;

use RZP\Models\PaymentLink\Template\FileAccess;

class FileAccessTest extends TestCase
{
    public function testGetFilePathUdfSchema()
    {
        $accessor = new FileAccess('udf_schema', 'test');

        $path = $accessor->getFilePath();

        $this->assertStringEndsWith('resources/jsonschema/test-default.json', $path);
        $this->assertTrue($accessor->exists());

        $accessor = new FileAccess('udf_schema', 'test', 'custom_name');

        $path = $accessor->getFilePath();

        $this->assertStringEndsWith('resources/jsonschema/test-custom_name.json', $path);
    }

    public function testGetFilePathHostedPage()
    {
        $accessor = new FileAccess('hosted_page', 'test');

        $path = $accessor->getFilePath();

        $this->assertStringEndsWith('resources/views/hostedpage/test-default.blade.php', $path);
    }

    public function testFileExistsFalse()
    {
        $accessor = new FileAccess('udf_schema', 'test_invalid');

        $this->assertFalse($accessor->exists());
    }

    public function testGetUdfSchemaContent()
    {
        $accessor = new FileAccess('udf_schema', 'test');

        $expected = [
            "title"      => "Test Schema",
            "type"       => "object",
            "required"   => [
                "customer_id",
                "customer_name"
            ],
            "properties" => [
                "customer_id"   => [
                    "type"    => "integer",
                    "title"   => "Customer Code/ID",
                    "minimum" => 1000000,
                    "maximum" => 999999999
                ],
                "customer_name" => [
                    "type"    => "string",
                    "title"   => "Customer Name",
                    "length"  => 100,
                    "default" => ""
                ]
            ]
        ];

        $expectedJson = json_encode($expected, JSON_PRETTY_PRINT);

        $this->assertJsonStringEqualsJsonString($expectedJson, $accessor->get());
    }
}
