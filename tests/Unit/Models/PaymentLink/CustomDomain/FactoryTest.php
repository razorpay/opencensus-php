<?php

namespace Unit\Models\PaymentLink\CustomDomain;

use Illuminate\Support\Facades\Config;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Models\PaymentLink\CustomDomain\Factory;
use RZP\Models\PaymentLink\CustomDomain;

class FactoryTest extends BaseTest
{
    public function testGetDomainClient()
    {
        $this->instanceCalls(
            "getDomainClient",
            CustomDomain\DomainClient::class,
            CustomDomain\Mock\DomainClient::class
        );
    }

    public function testGetPropagationClient()
    {
        $this->instanceCalls(
            "getPropagationClient",
            CustomDomain\PropagationClient::class,
            CustomDomain\Mock\PropagationClient::class
        );
    }

    public function testGetAppClient()
    {
        $this->instanceCalls(
            "getAppClient",
            CustomDomain\AppClient::class,
            CustomDomain\Mock\AppClient::class
        );
    }

    public function testGetWebHookHandler()
    {
        $this->instanceCalls(
            "getWebHookHandler",
            CustomDomain\WebhookProcessor\Processor::class,
            CustomDomain\Mock\Processor::class
        );
    }

    private function instanceCalls($method, $class, $mock)
    {
        Config::set(Factory::CDS_MOCK_KEY, false);

        $ins = Factory::$method();

        $this->assertEquals(new $class, $ins);

        Config::set(Factory::CDS_MOCK_KEY, true);

        $ins = Factory::$method();

        $this->assertEquals(new $mock(), $ins);
    }
}
