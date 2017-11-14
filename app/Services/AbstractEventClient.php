<?php

namespace RZP\Services;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Jobs\RequestJob;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Method;
use Illuminate\Foundation\Bus\DispatchesJobs;

abstract class AbstractEventClient extends Base\Core
{
    use DispatchesJobs;

    protected $mock = false;

    protected $config = [];

    protected $events = [];

    const HMAC_ALGO = 'sha1';

    const CONTENT_TYPE = 'application/json';

    const REQUEST_TIMEOUT = 20;

    const TIMEZONE = Timezone::IST;

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
     */
    protected function buildRequestAndSend()
    {
        try
        {
            if (($this->mock === true) or
                ($this->mode === Mode::TEST))
            {
                return false;
            }

            $eventData = $this->getEventTrackerData();

            if (empty($eventData) === true)
            {
                return false;
            }

            $headers = [
                'content-type'  => self::CONTENT_TYPE,
                'x-signature'   => $this->generateSignature(json_encode($eventData)),
                'x-identifier'  => $this->config['identifier']
            ];

            $url = $this->config['url'] . $this->urlPattern;

            $this->sendEventRequest($headers, $url, $eventData);
        }
        catch (Exception $e)
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

            $job = new RequestJob($request);

            $this->dispatch($job);
        }
        catch (Exception $e)
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
        $eventData = [];

        if (empty($this->events) === false)
        {
            $eventData = [
                'mode'      => $this->mode,
                'events'    => $this->events
            ];

            // For lumberjack old authentication
            if (isset($this->config['key']) === true)
            {
                $eventData['key'] = $this->config['key'];
            }

            $context = $this->getEventContext();

            if ((isset($context) === true) and
                (empty($context) === false))
            {
                $eventData['context'] = $context;
            }
        }

        return $eventData;
    }

    /**
     * Generates hmac signature for authenticating request
     *
     * @param string $key
     * @return string $signature
     */
    protected function generateSignature(string $key)
    {
        $message = $this->getHmacMessage($key);

        $secret = $this->config['secret'];

        $signature = hash_hmac(self::HMAC_ALGO, $message, $secret);

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
