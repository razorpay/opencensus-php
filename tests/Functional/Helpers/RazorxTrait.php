<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;

trait RazorxTrait
{
    protected function mockRazorX(string $functionName, string $featureName, string $variant, $merchantId = '1cXSLlUU8V9sXl')
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId($merchantId, $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];
    }

    public function mockRazorxForFallback()
    {

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) {
                    if ($feature === RazorxTreatment::REFUND_FALLBACK_ENABLED_ON_MERCHANT) {
                        return 'on';
                    }
                    return 'off';
                }));

    }
}
