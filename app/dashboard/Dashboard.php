<?php

namespace Dashboard;

use EE\Exception;
use Config;
use Models\Base\PublicEntity;
use Models\Base\PublicCollection;
use Models\Payment;
use Queue;
use Requests;
use Trace;
use Trace\TraceCode;

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
            throw new Exception\LogicException('dashboard url not defined');
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

            // The response can be in jsonp or json.
            // Remove jsonp related callback if so.
            //
            // @note: New-line cannot be in single quotes;
            $ix = strpos($body, "\n");

            if ($ix !== false)
            {
                // Response is jsonp.
                // Cut till newline from beginning.
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
                    'body'          => $body,
                    'transaction'   => $data['message'],
                    'mode'          => $data['mode']);

                Trace::error(TraceCode::DASHBOARD_INTEGRATION_ERROR, $array);

                throw new Exception\IntegrationException(
                    'Dashboard returned a non-json response',
                    $array);
            }

            if ((isset($content['success']) === false) or
                ($content['success'] === false))
            {
                $errors = [];

                if (isset($content['errors']))
                    $errors = $content['errors'];

                $array = array(
                    'body'          => $content,
                    'transaction'   => $data['message'],
                    'mode'          => $data['mode'],
                    'errors'        => $errors);

                Trace::error(TraceCode::DASHBOARD_INTEGRATION_ERROR, $array);

                throw new Exception\IntegrationException(
                    'Dashboard returned false status in response',
                    $array);
            }
        }

        $job->delete();
    }

    public static function send($type, $resource)
    {
        $app = \App::getFacadeRoot();

        $collection = $resource;

        if (is_a($resource, 'Models\\Base\\PublicEntity') === true)
        {
            $collection = new PublicCollection;
            $collection->push($resource);
        }

        foreach ($collection->all() as $item)
        {
            $app['dashboard']->queueRecord($type, $item);
        }
    }

    public function queueRecord($type, $entity)
    {
        if ($entity instanceof PublicEntity)
        {
            if ($entity instanceof Payment\Entity)
            {
                $array = $entity->toArrayDashboard();
            }
            else
            {
                $array = $entity->toArray();
            }

            $data = array_merge(
                    $array,
                    ['merchant_id' => $entity->getMerchantId()]);

            $mode = \BasicAuth::getMode();

            Queue::push('Dashboard\\'.ucwords($type).'@postRequest', array(
                'mode'      => $mode,
                'message'   => $data
            ));
        }
    }
}
