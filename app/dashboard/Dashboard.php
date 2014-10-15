<?php

namespace Dashboard;

use Queue;
use Config;
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

    protected function getUrl()
    {
        return $this->config['url'];
    }

    protected function getAuthSecret()
    {
        return $this->config['secret'];
    }

    protected function getPretend()
    {
        return $this->config['pretend'];
    }

    public static function getInstance()
    {
        return new static;
    }

    public static function validateAndBuild($data = array())
    {
        foreach($data as $key => $value)
        {
            if (!in_array($key, static::$fields))
                unset($data[$key]);
        }

        return $data;
    }

    public function postRequest($job, $data)
    {
        $mode = $data['mode'];

        $payload = static::validateAndBuild($data['message']);

        $options = array('auth'=> array('rzp_api', $this->getAuthSecret()));

        if ($this->getPretend() === false)
        {
            $response = Requests::post(
                $this->getUrl() . $mode . '/transactions/' . static::$resource,
                array(),
                $payload,
                $options
            );

            //
            // For debugging purposes,
            // persist failed requests
            //
            if (!is_object(json_decode($response->body)) ||
                json_decode($response->body)->status === FALSE)
            {
                (new Repository)->persistAfterFail($data['message']);
                
                return;
            }
        }

        $job->delete();
    }
}