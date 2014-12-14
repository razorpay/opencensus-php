<?php

namespace Dashboard;

use EE\Exception;
use Config;
use Queue;
use Requests;

class Dashboard
{
    /**
     * Resource specifier
     * For example, payments, cards, etc.
     */
    protected static $resource;

    /**
     * Configuration array
     * @var array
     */
    protected $config = array();

    /**
     * Fields to be sent as part of the request
     */
    protected static $fields = array();

    public function __construct()
    {
        $this->config = Config::get('applications.dashboard');

        if ($this->config === null)
        {
            throw new \LogicException('dashboard url not defined');
        }
    }

    public static function validateAndBuild($data = array())
    {
        foreach ($data as $key => $value)
        {
            if (in_array($key, static::$fields) === false)
                unset($data[$key]);
        }

        return $data;
    }

    public function postRequest($job, $data)
    {
        $mode = $data['mode'];

        $payload = static::validateAndBuild($data['message']);

        $options = array('auth'=> array('rzp_api', $this->config['secret']));

        if ($this->config['pretend'] === false)
        {
            $response = Requests::post(
                $this->config['url'] . $mode . '/transactions/' . static::$resource,
                array(),
                $payload,
                $options
            );

            $body = $response->body;

            // New-line cannot be in single quotes;
            $ix = strpos($body, "\n");

            if ($ix !== false)
            {
                $prefix = substr($body, 0, $ix + 1);

                if ($prefix === ")]}',\n")
                {
                    $body = substr($body, $ix + 1);
                }
            }

            $content = json_decode($body, true);

            if ($content === null)
            {
                $array = array(
                    'body' => $body,
                    'transaction' => $data['message']);

                throw new Exception\IntegrationException(
                    'Dashboard returned a non-json response',
                    $array);
            }

            if ((isset($content['status']) === false) or
                ($content['status'] === false))
            {
                throw new Exception\IntegrationException(
                    'Dashboard returned false status in response',
                    ['transaction' => $data['message']]);
            }
        }

        $job->delete();
    }

    public static function send($resource, $payload)
    {
        $app = \App::getFacadeRoot();
        $app['dashboard']->queueRecord($resource, $payload);
    }

    public function queueRecord($resource, $data)
    {
        if (is_a($data, 'Models\\Base\\PublicCollection') === true)
        {
            foreach ($data as $entity)
            {
                // Recursively call this function for each entity in collection
                $this->queueRecord($resource, $entity);
            }
        }
        else if (is_a($data, 'Models\\Base\\PublicEntity') === true)
        {
            $data = array_merge(
                    $data->toArray(),
                    ['merchant_id' => $data->getMerchantId()]);

            $mode = \BasicAuth::getMode();

            Queue::push('Dashboard\\'.ucwords($resource).'@postRequest', array(
                'mode'  => $mode,
                'message'   =>  $data
            ));
        }
    }
}