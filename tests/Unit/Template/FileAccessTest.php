<?php

namespace RZP\Tests\Unit\Trace;

use RZP\Tests\TestCase;

use RZP\Models\PaymentLink\Entity as PaymentLink;
use RZP\Models\PaymentLink\Template\Hosted as TemplateHosted;
use RZP\Models\PaymentLink\Template\UdfSchema as TemplateUdfSchema;

class FileAccessTest extends TestCase
{
    public function testGetFilePathUdfSchema()
    {
        $paymentLink = new PaymentLink();
        $paymentLink->setUdfJsonschemaId('test');
        $accessor = new TemplateUdfSchema($paymentLink);

        $path = $accessor->driver->getFilePath();

        $this->assertStringEndsWith('resources/jsonschema/test-default.json', $path);
        $this->assertTrue($accessor->exists());
    }

    public function testGetFilePathHostedPage()
    {
        $accessor = new TemplateHosted('test');

        $path = $accessor->driver->getFilePath();

        $this->assertStringEndsWith('resources/views/hostedpage/test-default.blade.php', $path);
    }

    public function testFileExistsFalse()
    {
        $paymentLink = new PaymentLink();
        $paymentLink->setUdfJsonschemaId('test_invalid');
        $accessor = new TemplateUdfSchema($paymentLink);

        $this->assertFalse($accessor->exists());
    }

    public function testGetUdfSchemaContent()
    {
        $paymentLink = new PaymentLink();
        $paymentLink->setUdfJsonschemaId('test');
        $accessor = new TemplateUdfSchema($paymentLink);

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
