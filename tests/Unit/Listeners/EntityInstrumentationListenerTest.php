<?php

namespace Unit\Listeners;

use Illuminate\Testing\Constraints\ArraySubset;
use RZP\Events\EntityInstrumentationEvent;
use RZP\Constants\Metric;
use RZP\Tests\Functional\CustomAssertions;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use Tests\Unit\TestCase;

class EntityInstrumentationListenerTest extends TestCase
{
    use TestsMetrics;
    use HasRequestCases;
    use CustomAssertions;

    public function testTriggersTraceCountWithoutRequestContext()
    {
        $eventName = 'eventName';
        $entityName = 'entityName';
        $dimensions = [
            Metric::LABEL_ENTITY_NAME => $entityName,
            Metric::LABEL_ROUTE => Metric::LABEL_NONE_VALUE,
            Metric::LABEL_RZP_INTERNAL_APP_NAME => Metric::LABEL_NONE_VALUE
        ];

        $metricsMock = $this->createMetricsMock();
        $metricsMock->expects($this->once())
            ->method('count')
            ->with($eventName, 1, new ArraySubset($dimensions));

        event(new EntityInstrumentationEvent($eventName, $entityName));
    }

    public function testTriggersTraceCountWithRouteName()
    {
        $routeName = 'checkout_public';
        $this->invokeRequestCaseAndBindNewContext('directRoute', $routeName);

        $eventName = 'eventName';
        $entityName = 'entityName';
        $dimensions = [
            Metric::LABEL_ENTITY_NAME => $entityName,
            Metric::LABEL_ROUTE => $routeName,
            Metric::LABEL_RZP_INTERNAL_APP_NAME => Metric::LABEL_NONE_VALUE
        ];

        $metricsMock = $this->createMetricsMock();
        $metricsMock->expects($this->once())
            ->method('count')
            ->with($eventName, 1, new ArraySubset($dimensions));

        event(new EntityInstrumentationEvent($eventName, $entityName));
    }

    public function testTriggersTraceCountWithInternalApp()
    {
        $routeName = 'invoice_expire_bulk';
        $this->invokeRequestCaseAndBindNewContext(
            'privilegeRouteWithInternalAppAuth',
            $routeName
        );

        $eventName = 'eventName';
        $entityName = 'entityName';
        $dimensions = [
            Metric::LABEL_ENTITY_NAME => $entityName,
            Metric::LABEL_ROUTE => $routeName,
            Metric::LABEL_RZP_INTERNAL_APP_NAME => 'merchant_dashboard'
        ];

        $metricsMock = $this->createMetricsMock();
        $metricsMock->expects($this->once())
            ->method('count')
            ->with($eventName, 1, new ArraySubset($dimensions));

        event(new EntityInstrumentationEvent($eventName, $entityName));
    }
}
