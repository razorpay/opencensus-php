<?php

namespace RZP\Events;

use App;

use RZP\Constants\Metric;

class EntityInstrumentationEvent extends Event
{
    public $eventId;
    public $eventName;
    public $dimensions;

    public function __construct($eventName, $entityName)
    {
        $this->eventId = uniqid();
        $this->eventName = $eventName;
        $this->dimensions = $this->getDimensions($entityName);
    }

    protected function getDimensions($entityName)
    {
        $dimensions = [
            Metric::LABEL_ENTITY_NAME => $entityName
        ];

        $app = App::getFacadeRoot();
        $requestContext = $app['request.ctx'];
        if (empty($requestContext) === false)
        {
            $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] = $requestContext->getInternalAppName();
            $dimensions[Metric::LABEL_ROUTE] = $requestContext->getRoute();
        }

        $dimensions[Metric::LABEL_ROUTE] = $dimensions[Metric::LABEL_ROUTE] ?? Metric::LABEL_NONE_VALUE;
        $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] =
            $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] ?? Metric::LABEL_NONE_VALUE;

        return $dimensions;
    }
}
