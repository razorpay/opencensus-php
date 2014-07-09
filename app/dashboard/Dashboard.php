<?php

namespace Dashboard;

use Queue;
use Config;
use Requests;

class Dashboard
{
    /**
     * Resource specifier
     * For example, transactions, cards, etc.
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
    }

    protected function getUrl()
    {
        return $this->config['url'];
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

    public function queueRecord($data = array())
    {
        $data = static::validateAndBuild($data);

        Queue::push('Dashboard\Dashboard@postRequest', array(
            'resource'  =>  static::$resource,
            'message'   =>  $data
        ));
    }

    public function postRequest($job, $data)
    {
        $response = Requests::post(
            $this->getUrl() . $data['resource'],
            array(),
            $data['message']
        );

        //
        // For debugging purposes,
        // persist failed requests
        //
        if (!is_object(json_decode($response->body)) ||
            json_decode($response->body)->status === FALSE)
                (new Repository)->persistAfterFail($data['message']);

        $job->delete();
    }
}