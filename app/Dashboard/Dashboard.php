<?php

namespace RZP\Dashboard;

use App;
use Queue;
use Trace;
use Config;
use RZP\Http\Request\Requests;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Jobs\Dashboard as DashboardJob;

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

    protected $trace;

    /**
     * Fields to be sent as part of the request
     */
    protected static $fields = array();

    public function __construct()
    {
        $this->config = Config::get('applications.dashboard');

        $this->trace = Trace::getFacadeRoot();

        if ($this->config === null)
        {
            throw new Exception\LogicException('dashboard url not defined');
        }
    }

    public static function validateAndBuild($data = array())
    {
        foreach ($data as $key => $value)
        {
            if (in_array($key, static::$fields, true) === false)
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

            $statusCode = $response->status_code ;
            $body       = $response->body;

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

            $traceData = [
                'status_code' => $statusCode,
                'body'        => $body,
                'transaction' => $data['message'],
                'mode'        => $data['mode'],
            ];

            if ($content === null)
            {
                $this->trace->error(TraceCode::DASHBOARD_INTEGRATION_ERROR, $traceData);

                throw new Exception\IntegrationException('Dashboard returned a non-json response', null, $traceData);
            }

            if ((isset($content['success']) === false) or
                ($content['success'] === false))
            {
                $errors = $content['errors'] ?? [];

                $traceData['errors'] = $errors;

                $this->trace->error(TraceCode::DASHBOARD_INTEGRATION_ERROR, $traceData);

                throw new Exception\IntegrationException('Dashboard returned false status in response', null, $traceData);
            }
        }

        $job->delete();
    }

    public static function send($type, $resource)
    {
        $app = App::getFacadeRoot();

        $collection = $resource;

        if (is_a($resource, 'RZP\\Models\\Base\\PublicEntity') === true)
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

            $app = App::getFacadeRoot();

            $mode = $app['basicauth']->getMode();

            DashboardJob::dispatch(['mode' => $mode, 'message' => $data, 'type' => $type]);
        }
    }
}
