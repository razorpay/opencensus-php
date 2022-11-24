<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;

class Reminders
{

    const REQUEST_TIMEOUT = 60;

    const REMINDER_ID = 'id';

    const TEST_REMINDER_ID = 'DErKK3a9tEGlph';

    const JSON_METHOD = ['POST', 'PUT', 'PATCH'];

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    protected $auth;

    const REMINDERS_URL = [
        'create_reminder'   => 'reminders',
        'update_reminder'   => 'reminders',
        'delete_reminder'   => 'reminders',
        'next_run_at'       => 'reminders/next_run_at',
        'merchant_settings' => 'merchant_settings',
    ];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.reminders');

        $this->baseUrl = $this->config['url'];

        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : null;

        $this->key = 'api';

        $this->auth = $app['basicauth'];

        $this->secret = $this->config['reminder_secret'];
    }

    public function createReminder(array $input, string $merchantId = null): array
    {
        $response = $this->sendRequest(self::REMINDERS_URL['create_reminder'], 'POST', $input, $merchantId);

        return $response;
    }

    public function updateReminder(array $input, string $id, string $merchantId = null): array
    {
        $url = self::REMINDERS_URL['update_reminder'] . '/' . $id;
        $response = $this->sendRequest($url, 'PUT', $input, $merchantId);
        return $response;
    }

    public function deleteReminder(string $id, string $merchantId = null): array
    {
        $url = self::REMINDERS_URL['delete_reminder'] . '/' . $id;
        $response = $this->sendRequest($url, 'DELETE', null, $merchantId);
        return $response;
    }

    public function nextRunAt($input)
    {
        $url = self::REMINDERS_URL['next_run_at'];
        $response = $this->sendRequest($url, 'POST', $input);
        return $response;
    }

    /**
     * Fetches reminder settings from reminder service.
     *
     * @param array $input
     *
     * @return array|mixed
     */
    public function getReminderSettings(array $input)
    {
        $url = self::REMINDERS_URL['merchant_settings'];

        try
        {
            $response = $this->sendRequest($url, 'GET', $input);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            // sending items as empty array when reminders is down.
            // We don't want to fail entity creation when reminders is not available.
            $response = [
                'count' => 0,
                'items' => []
            ];
        }

        return $response;
    }

    public function sendAnyRequest($url, $method, $data)
    {
        $response = $this->sendRequest($url, $method, $data);

        return $response;
    }

    public function sendRequest($url, $method, $data = null, $merchantId = null)
    {
        $url = $this->baseUrl . $url;

        if ($data === null)
        {
            $data = '';
        }

        $headers['Content-Type'] = 'application/json';

        $merchantId = $merchantId ?: $this->auth->getMerchantId();

        if(empty($merchantId) === false)
        {
            $headers['X-Razorpay-MerchantId'] = $merchantId;
        }

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        );

        $request = array(
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendRemindersRequest($request);

        $this->trace->info(TraceCode::REMINDERS_RESPONSE, [
            'response' => $response->body
        ]);

        if(empty($response->body) === false)
        {
            $decodedResponse = json_decode($response->body, true);
        }

        $decodedResponse = $decodedResponse ?? [];
        $decodedResponse['status_code'] = $response->status_code;

        $this->trace->info(TraceCode::REMINDERS_RESPONSE, $decodedResponse);

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }

    protected function sendRemindersRequest($request)
    {
        $this->traceRequest($request);

        $method = $request['method'];

        $content = $request['content'];

        if(in_array($method, self::JSON_METHOD))
        {
            $content = json_encode($request['content']);
        }

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $content,
                $request['method'],
                $request['options']);
        }
        catch(\WpOrg\Requests\Exception $e)
        {
            throw $e;
        }

        return $response;
    }

    protected function traceRequest($request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::REMINDERS_REQUEST, $request);
    }

    protected function checkErrors($response)
    {
        if (isset($response['error']))
        {
            $errorCode = $response['error']['code'];

            throw new BadRequestException($errorCode);
        }
    }
}
