<?php

namespace RZP\Services\Harvester;

use RZP\Http\Request\Requests;
use RZP\Exception;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\Harvester\Constants;
use RZP\Trace\TraceCode;
use RZP\Services\AbstractEventClient;
use RZP\Exception\IntegrationException;
use RZP\Models\Merchant\Core as MerchantCore;

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

    const QUERY_API_PATH        = 'analytics/pokedex';
    const QUERY_API_PATH_V2     = 'v1/pql/analytics';
    const PINOT_QUERY_API_PATH  = 'twirp/rzp.harvester.v1.PqlService/SqlQuery';

    const RETRY = true;

    const RETRY_TIMES = 3;

    public function __construct($app)
    {
        parent::__construct();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->config = $app['config']->get('applications.harvester');

        $this->trace = $app['trace'];

        $this->mock = $this->config['mock'];

        $this->queryBaseUrl = $this->config['url'];

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

    /**
     * Function breaks events into smaller chunks
     * as sqs has a limit of 256 kb data size
     *
     * @return array $eventChunkData
     */
    protected function getEventChunks()
    {
        $counter = 0;

        $eventChunksData = [];

        foreach ($this->events as $channel => $events)
        {
            foreach ($events as $event)
            {
                $eventChunksData[$counter][$channel][] = $event;

                $totalEventsLength = strlen(json_encode($eventChunksData[$counter][$channel]));

                if ($totalEventsLength > self::MAX_EVENT_DATA_SIZE)
                {
                    array_pop($eventChunksData[$counter][$channel]);

                    $counter++;

                    $eventChunksData[$counter][$channel][] = $event;
                }
            }
        }

        return $eventChunksData;
    }

    public function query($data = '', $timeout = self::REQUEST_TIMEOUT)
    {
        $queryPath = self::QUERY_API_PATH_V2;

        $config    = $this->app['config']->get('applications.harvester_v2');

        return $this->sendRequest($queryPath, $data, $config, self::RETRY, self::RETRY_TIMES, $timeout);
    }

    public function getDataFromPinot($content, $timeout = self::REQUEST_TIMEOUT)
    {
        $queryPath  = self::PINOT_QUERY_API_PATH;

        $config     = $this->app['config']->get('applications.harvester_v2');

        $headers    = [ 'content-type'  => 'application/json' ];

        $result     =  $this->sendRequest($queryPath, $content, $config, self::RETRY, self::RETRY_TIMES, $timeout, $headers);

        return $result['result'];
    }

    public function parsePinotDefaultType(array $results, string $tableName)
    {
        if (empty(Constants::PINOT_TABLE_SCHEMA_MAP[$tableName]) === true)
        {
            throw new Exception\LogicException('Table schema not defined for table : ' . $tableName);
        }

        $parsedResults = [];

        $tableSchema   = Constants::PINOT_TABLE_SCHEMA_MAP[$tableName];

        foreach ($results as $columnName => $value)
        {
            $parsedResults[$columnName] = $this->getParsedValueOfColumnForPinotTable($tableSchema, $columnName, $value);
        }

        return $parsedResults;
    }

    protected function getParsedValueOfColumnForPinotTable(array $tableSchema, string $columnName, $value)
    {
        if (empty($tableSchema[$columnName]) === true)
        {
            return $value;
        }

        $columnType     =  $tableSchema[$columnName];

        $defaultValue   = Constants::PINOT_DATA_TYPE_DEFAULT_MAPPING[$columnType];

        if ( ($columnType == Constants::PINOT_DATA_TYPE_LONG) and
             (in_array($value, $defaultValue) === true))
        {
            return null;
        }
        elseif (($columnType != Constants::PINOT_DATA_TYPE_LONG) and $value === $defaultValue )
        {
            return null;
        }

        return $value;
    }

    protected function sendRequest(string $urlPath, $data, $config, bool $retry = false, int $maxRetryTimes = 0, $timeout = self::REQUEST_TIMEOUT, $customHeaders = [])
    {
        $startTime       = microtime(true);

        $hostUrl         = $config['url'];

        $accessToken     = $config['analytics_token'];

        $request = [
            'url'           => $hostUrl . $urlPath,
            'method'        => 'POST',
            'content'       => json_encode($data),
            'content-type'  => 'application/json',
        ];


        // The location of the trace is very critical here.
        // Be careful moving this code. Don't trace headers containing signature
        $this->trace->info(
            TraceCode::HARVESTER_REQUEST,
            [
                'host'    => $hostUrl,
                'path'    => $urlPath,
                'data'    => $data,
                'request' => $request
            ]);

        $headers = [
            'x-signature'   => $accessToken,
            'Accept'        => 'application/json',
        ];

        $headers = array_merge($headers, $customHeaders);

        $options['timeout'] = $timeout;

        $request['headers'] = $headers;

        $request['options'] = $options;

        $retryCount = 0;

        $response = null;

        while ($retryCount <= $maxRetryTimes)
        {
            try
            {
                $response = $this->getResponse($request);
            }
            catch(\WpOrg\Requests\Exception $e)
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

            if (($retry === false) or ($response !== null and $response->status_code === 200))
            {
                break;
            }
        }

        $this->checkErrors($urlPath, $data ,$response);

        $this->trace->info(
        TraceCode::HARVESTER_RESPONSE_TIME,
        [
            'response_time' => microtime(true)- $startTime,
        ]);

        return json_decode($response->body, true);
    }

    protected function checkErrors($urlPath, $data, $response)
    {
        if ($response === null)
        {
            $this->trace->error(
                TraceCode::HARVESTER_FAILURE,
                [
                    'url'       => $urlPath,
                    'data'      => $data,
                ]);

            throw new IntegrationException(
                ErrorCode::SERVER_ERROR_HARVESTER_INVALID_RESPONSE,
                null,
                [
                    'url'       => $urlPath,
                    'data'      => $data,
                ]);
        }

        if (($response !== null) and ($response->status_code !== 200))
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
