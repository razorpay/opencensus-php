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

    const QUERY_API_PATH = '/v1/analytics/pokedex';

    public function __construct($app)
    {
        parent::__construct();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->config = $app['config']->get('applications.harvester');

        $this->trace = $app['trace'];

        $this->mock = $this->config['mock'];

        $this->queryBaseUrl = $this->config['url'];

        $this->queryPath = self::QUERY_API_PATH;

        $this->accessToken = $this->config['analytics_token'];
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

        $request = [
            'url'           => $this->queryBaseUrl . $urlPath,
            'method'        => 'POST',
            'content'       => $data,
            'content-type'  => 'application/json',
            ];

        $headers = [
            'x-signature'   => $this->accessToken,
            'Accept'        => 'application/json'
        ];

        $options['timeout'] = self::REQUEST_TIMEOUT;

        $request['headers'] = $headers;

        $request['options'] = $options;

        $retryCount = 0;
        $response = null;

        while ($retryCount <= $maxRetryTimes)
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

            if (($retry === false) or ($response != null and $response->status_code === 200))
            {
                break;
            }
        }

        $this->checkErrors($urlPath, $data ,$response);

        return $response;
    }

    protected function checkErrors($urlPath, $data, Response $response)
    {
        if ($response->status_code != 200)
        {
            $this->trace->error(
                TraceCode::HARVESTER_FAILURE,
                [
                    'url'       => $urlPath,
                    'data'      => $data,
                    'status'    => $response->status_code,
                    'body'      => $response->body
                ]);
        }

        // TODO : Send email/slack message for $response->status_code != 200
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
