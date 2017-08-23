<?php

namespace RZP\Services;

use Carbon\Carbon;
use Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class HarvesterClient extends AbstractEventClient
{
    protected $urlPattern;

    protected $config;

    protected $mock;

    const TRACK_EVENT_URL_PATTERN = 'track/merchants';

    public function __construct($app)
    {
        parent::__construct();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->config = $app['config']->get('applications.harvester');

        $this->mock = $this->config['mock'];
    }

    /**
     * Method to build all the events together
     */
    public function buildRequestAndSend()
    {
        return parent::buildRequestAndSend();
    }

    /**
     * Function to push events in an array.
     * These will be consumed later on at the time of script exit
     *
     * @param Base\Entity $entity
     * @param string $eventName
     * @param array $properties
     */
    public function trackEvents(Base\Entity $entity, string $eventName, array $properties = [])
    {
        $this->removeSensitiveInformation($properties);

        $channel = $entity->getEntity();

        $event = [
            'event'         => $eventName,
            'timestamp'     => Carbon::now(self::TIMEZONE)->timestamp,
            'properties'    => $properties
        ];

        $this->appendEvent($event, $channel);
    }

    /**
     * Appends an event to the list of already submitted events.
     * These events would be grouped together and sent to harvester later
     *
     * @param array $event
     * @param string $channel
     */
    protected function appendEvent(array $event, string $channel)
    {
        if (isset($this->events[$channel]) === false)
        {
            $this->events[$channel] = [];
        }

        $this->events[$channel][] = $event;
    }
}
