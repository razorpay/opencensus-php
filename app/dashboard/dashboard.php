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
        $diff = array_diff_key($data, static::$fields);
        foreach ($diff as $key => $value)
            unset($data[$key]);

        foreach (static::$fields as $key => $value)
            if ($key !== $value)
            {
                $data[$value] = $data[$key];
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

        $job->delete();
    }
}