<?php

namespace RZP\Tests\Functional\Helpers;

use RZP\Jobs;
use RZP\Models\Merchant\Webhook\Inferno;

trait WebhookTrait
{
    protected function createInfernoMock(array $withMethods = ['fire']): Inferno
    {
        $infernoMock = $this->getMockBuilder(Inferno::class)
                            ->setMethods($withMethods)
                            ->getMock();

        $this->app->instance('webhook.inferno', $infernoMock);

        return $infernoMock;
    }

    /**
     * Sets mocked inferno instance expectations for fire() method.
     *
     * @param Inferno $infernoMock
     * @param array   $testDataKeys
     */
    protected function setInfernoExpectations(array $testDataKeys, Inferno $infernoMock = null)
    {
        $infernoMock = $infernoMock ?? $this->createInfernoMock();

        $times = 0;

        // 1st argument of fire method is a job class of type Jobs\Webhook
        $arg1Callback = function ($job)
        {
            return $job instanceof Jobs\WebHook;
        };

        //
        // Iterates over the test data keys and sets callback to assert the 2nd argument
        // of every consecutive call of inferno's fire() method.
        //
        foreach ($testDataKeys as $testDataKey)
        {
            $times = $times + 1;

            $arg2Callback = function ($actualWebhook) use ($testDataKey)
            {
                $expectedEvent = $this->testData[$testDataKey];
                $actualEvent   = json_decode($actualWebhook['event'], true);

                $this->assertArraySelectiveEquals($expectedEvent, $actualEvent);

                return true;
            };

            $with[] = [$this->callback($arg1Callback), $this->callback($arg2Callback)];
        }

        $infernoMock->expects($this->exactly($times))
                    ->method('fire')
                    ->withConsecutive(...$with);
    }
}
