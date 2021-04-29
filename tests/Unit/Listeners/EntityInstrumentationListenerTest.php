<?php

use RZP\Events\EntityInstrumentationEvent;
use RZP\Listeners\EntityInstrumentationListener;
use RZP\Tests\Traits\TestsMetrics;
use Tests\Unit\TestCase;

class EntityInstrumentationListenerTest extends TestCase
{
    use TestsMetrics;

    public function testTriggersTraceCount()
    {
        $eventName = 'eventName';
        $entityName = 'entityName';
        $dimensions = [
            'entity' => $entityName
        ];

        $metricsMock = $this->createMetricsMock();
        $metricsMock->expects($this->once())
            ->method('count')
            ->with($eventName, 1, $dimensions);

        event(new EntityInstrumentationEvent($eventName, $entityName));
    }
}
