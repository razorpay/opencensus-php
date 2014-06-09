<?php

namespace Dashboard;

use Queue;
use Requests;

class Dashboard
{
    /**
     * Dashboard URL
     */
    protected static $url;

    /**
     * Resource specifier
     * For example, transactions, cards, etc.
     */
    protected static $resource;

    /**
     * Fields to be sent as part of the request
     */
    protected static $fields = array();

    public function __construct()
    {
        static::$url = Config::getUrl();
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
            static::$url . '/' . $data['resource'],
            array(),
            $data['message']
        );

        if (json_decode($response->body)->status === FALSE);
            \Dashboard\Transaction::getInstance()->queueRecord($data['message']);

        $job->delete();
    }
}