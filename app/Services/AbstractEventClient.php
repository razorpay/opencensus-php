<?php

namespace RZP\Services;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Jobs\RequestJob;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use RZP\Trace\TraceCode;

abstract class AbstractEventClient extends Base\Core
{
    protected $mock = false;

    protected $config = [];

    protected $events = [];

    protected $hmacAlgo = 'sha1';

    const CONTENT_TYPE = 'application/json';

    const REQUEST_TIMEOUT = 20;

    const TIMEZONE = Timezone::IST;

    // SQS limit is 256 KB, but keeping the event size limit
    // at 225 KB, so as to adjust any unforseen data additions.
    const MAX_EVENT_DATA_SIZE = 230400;

    /**
     * List of sensitive keys to exclude from sengding to external services
     */
    const SENSITIVE_KEYS = [
        'CARD_NUMBER'           => 'card.number',
        'GATEWAY_CARD_NUMBER'   => 'terminal_gateway_input.card.number',
        'CVV'                   => 'card.cvv',
        'CARD_ID'               => 'card.id',
        'GATEWAY_CVV'           => 'terminal_gateway_input.card.cvv',
        'CARD_EXP_MONTH'        => 'card.expiry_month',
        'CARD_EXP_YEAR'         => 'card.expiry_year',
        'PAYMENT_CARD_ID'       => 'payment.card_id',
        'VPC_ACCESSCODE'        => 'request.content.vpc_AccessCode',
        'VPC_CARDEXP'           => 'request.content.vpc_CardExp',
    ];

    /**
     * Method to build all the events together
     *
     * @return mixed
     */
    protected function buildRequestAndSend()
    {
        try
        {
            if ($this->mock === true)
            {
                return false;
            }

            $eventData = $this->getEventTrackerData();

            if (empty($eventData) === true)
            {
                return false;
            }

            foreach ($eventData as $eventDataChunk)
            {
                $signature = $this->generateSignature(json_encode($eventDataChunk), $this->config['secret']);

                $headers = [
                    'content-type'  => self::CONTENT_TYPE,
                    'x-signature'   => $signature,
                    'x-identifier'  => $this->config['identifier']
                ];

                $url = $this->config['url'] . $this->urlPattern;

                $this->sendEventRequest($headers, $url, $eventDataChunk);
            }

            $this->flushEvents();
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'class'     => get_class($this),
                'message'   => $e->getMessage(),
            ];

            $this->trace->error(TraceCode::EVENT_POST_FAILED, $errorContext);
        }
    }

    /**
     * Dispatch a job request
     *
     * @param array $headers
     * @param string $url
     * @param array $eventData
     */
    protected function sendEventRequest(array $headers, string $url, array $eventData)
    {
        try
        {
            $request  = [
                'method'    => 'post',
                'url'       => $url,
                'headers'   => $headers,
                'content'   => json_encode($eventData),
                'options'   => [
                    'timeout'   => self::REQUEST_TIMEOUT
                ]
            ];

            RequestJob::dispatch($request);
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'class'     => get_class($this),
                'message'   => $e->getMessage(),
            ];

            $this->trace->error(TraceCode::EVENT_QUEUE_SEND_FAILED, $errorContext);
        }
    }

    /**
     * Sets in the default empty array for events
     */
    protected function getEventContext()
    {
        return [];
    }

    /**
     * Flush all the events
     */
    protected function flushEvents()
    {
        $this->events = [];
    }

    /**
     * Get HMAC message
     *
     * @param string $message
     * @return string $message
     */
    protected function getHmacMessage(string $message)
    {
        return $message;
    }

    /**
     * Formats and builds the event data
     * before posting to the service.
     *
     * @return array $eventData
     */
    protected function getEventTrackerData()
    {
        $eventChunksData = [];

        if (empty($this->events) === false)
        {
            $eventChunks = $this->getEventChunks();

            foreach ($eventChunks as $eventChunk)
            {
                $eventData = [
                    'mode'      => $this->mode ?? $this->app['rzp.mode'],
                    'events'    => $eventChunk
                ];

                // For lumberjack old authentication
                if (isset($this->config['key']) === true)
                {
                    $eventData['key'] = $this->config['key'];
                }

                if (isset($eventChunk['context']) == true and
                    empty($eventChunk['context']) == false)
                {
                    $eventData['context'] = $eventChunk['context'];
                }
                else
                {
                    $context = $this->getEventContext();

                    if ((isset($context) === true) and
                        (empty($context) === false)) {
                        $eventData['context'] = $context;
                    }
                }

                $eventChunksData[] = $eventData;
            }
        }

        return $eventChunksData;
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

        foreach ($this->events as $event)
        {
            $eventChunksData[$counter][] = $event;

            $totalEventsLength = strlen(json_encode($eventChunksData[$counter]));

            if ($totalEventsLength > self::MAX_EVENT_DATA_SIZE)
            {
                array_pop($eventChunksData[$counter]);

                $counter++;

                $eventChunksData[$counter][] = $event;
            }
        }

        return $eventChunksData;
    }

    /**
     * Generates hmac signature for authenticating request, hex signature
     *
     * @param string $key
     * @return string $signature
     */
    protected function generateSignature(string $message, string $secret)
    {
        $signature = hash_hmac($this->hmacAlgo, $message, $secret);

        return $signature;
    }

    /**
     * Remove all sorts of sensitive information
     *
     * @param array $properties
     */
    protected function removeSensitiveInformation(array & $properties)
    {
        foreach (self::SENSITIVE_KEYS as $name => $key)
        {
            unset($properties[$key]);
        }
    }
}
