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

    protected function getDimensions($entityName): array
    {
        $dimensions = [
            Metric::LABEL_ENTITY_NAME => $entityName
        ];

        $app = App::getFacadeRoot();
        if (isset($app['request.ctx']) and empty($app['request.ctx']) === false)
        {
            $requestContext = $app['request.ctx'];
            $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] = $requestContext->getInternalAppName();
            $dimensions[Metric::LABEL_ROUTE] = $requestContext->getRoute();
        }

        if (isset($app['worker.ctx']) and empty($app['worker.ctx']) === false)
        {
            $workerContext = $app['worker.ctx'];
            $jobName = get_class($app['worker.ctx']);
            if (method_exists($workerContext, 'getJobName'))
            {
                $jobName = $workerContext->getJobName();
            }
            $jobName = str_replace('\\', '_', $jobName);
            $dimensions[Metric::LABEL_ASYNC_JOB_NAME] = $jobName;
        }

        $dimensions[Metric::LABEL_ROUTE] =
            $dimensions[Metric::LABEL_ROUTE] ?? Metric::LABEL_NONE_VALUE;
        $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] =
            $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] ?? Metric::LABEL_NONE_VALUE;
        $dimensions[Metric::LABEL_ASYNC_JOB_NAME] =
            $dimensions[Metric::LABEL_ASYNC_JOB_NAME] ?? Metric::LABEL_NONE_VALUE;

        return $dimensions;
    }
}
