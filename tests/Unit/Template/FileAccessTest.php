<?php

namespace RZP\Tests\Unit\Trace;

use RZP\Tests\TestCase;

use RZP\Models\PaymentLink\Template\Hosted as TemplateHosted;
use RZP\Models\PaymentLink\Template\UdfSchema as TemplateUdfSchema;

class FileAccessTest extends TestCase
{
    public function testGetFilePathUdfSchema()
    {
        $accessor = new TemplateUdfSchema('test');

        $path = $accessor->driver->getFilePath();

        $this->assertStringEndsWith('resources/jsonschema/test-default.json', $path);
        $this->assertTrue($accessor->exists());

        $accessor = new TemplateUdfSchema('test', 'custom_name');

        $path = $accessor->driver->getFilePath();

        $this->assertStringEndsWith('resources/jsonschema/test-custom_name.json', $path);
    }

    public function testGetFilePathHostedPage()
    {
        $accessor = new TemplateHosted('test');

        $path = $accessor->driver->getFilePath();

        $this->assertStringEndsWith('resources/views/hostedpage/test-default.blade.php', $path);
    }

    public function testFileExistsFalse()
    {
        $accessor = new TemplateUdfSchema('test_invalid');

        $this->assertFalse($accessor->exists());
    }

    public function testGetUdfSchemaContent()
    {
        $accessor = new TemplateUdfSchema('test');

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

        $this->assertJsonStringEqualsJsonString($expectedJson, $accessor->getSchema());
    }
}
