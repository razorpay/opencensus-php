<?php

namespace RZP\Services;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Requests;
use Requests_Response as Response;

class HarvesterClient extends AbstractEventClient
{
    protected $urlPattern;

    protected $config;

    protected $mock;

    protected $queryBaseUrl;

    protected $queryPath;

    protected $accessToken;

    protected $trace;

    const TRACK_EVENT_URL_PATTERN = 'track/merchants';

    const QUERY_BASE_URL = 'https://api.razorpay.com';      // TODO : Retrieve from env variables

    const QUERY_API_PATH = '/v1/analytics/pokedex';

    const HARVESTER_ACCESS_TOKEN = 'prePublishedApiKey';      // TODO : Retrieve from env variables

    public function __construct($app)
    {
        parent::__construct();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->queryBaseUrl = self::QUERY_BASE_URL;     // TODO : Retrieve this from environment variables

        $this->queryPath = self::QUERY_API_PATH;

        $this->accessToken = self::HARVESTER_ACCESS_TOKEN;      // TODO : Retrieve this from environment variables

        $this->config = $app['config']->get('applications.harvester');

        $this->trace = $app['trace'];

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

    public function query($data = ''): Response
    {
        // TODO : Validate data

        // Create request here
        return $this->sendRequest($this->queryPath, $data, true, 3);

    }

    protected function sendRequest(string $urlPath, $data, bool $retry = false, int $maxRetryTimes = 0): Response
    {
        $this->trace->info(
            TraceCode::HARVESTER_REQUEST,
            [
                'path'    => $urlPath,
                'data'    => $data
            ]);

        // Create request object
        $request= [
            'url'           => $this->queryBaseUrl . $urlPath,
            'method'        => 'POST',
            'body'          => $data,
            'content-type'  => 'application/json',
            ];

        // Add configs and env variables to request here
        $headers = [
            'AuthKey'       => $this->accessToken,
            'Accept'        => 'application/json'
        ];

        $options['timeout'] = self::REQUEST_TIMEOUT;

        $request['headers'] = $headers;

        $request['options'] = $options;

        // Add retry logic here
        $retryCount = 0;
        $maxRetryTimes += 1;
        $response = null;

        while ($retryCount < $maxRetryTimes)
        {
            try
            {
                $response = $this->getResponse($request);

            }catch(\Requests_Exception $e)
            {
                $this->trace->info(
                    TraceCode::HARVESTER_RETRY,
                    [
                        'message' => $e->getMessage(),
                        'type'    => $e->getType(),
                        'data'    => $e->getData()
                    ]);
            }

            $retryCount++;

            if ($retry === false or $response->status_code === 200)
            {
                break;
            }
        }

        // Check for errors
        $this->checkErrors($response);

        return $response;
    }

    protected function checkErrors(Response $response)
    {
        // Check for errors and log them

        // Send email/slack message for $response->status_code != 200
    }

    protected function getResponse($request)
    {
        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']);

        return $response;
    }
}
