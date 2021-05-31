<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;

class MandateHQ
{
    const REQUEST_TIMEOUT = 60;

    const MANDATE_HQ_URLS = [
        'register_mandate'              => 'v1/mandates/register',
        'create_pre_debit_notification' => 'v1/mandates/%s/notifications',
        'report_payment'                => 'v1/mandates/%s/payments',
    ];

    protected $baseUrl;

    protected $config;

    protected $key;

    protected $secret;

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.mandate_hq');

        $this->baseUrl = $this->config['url'];

        $this->key = $this->config['username'];

        $this->secret = $this->config['password'];
    }

    public function registerMandate($input)
    {
        return $this->sendRequest(self::MANDATE_HQ_URLS['register_mandate'], 'post', $input);
    }

    public function createPreDebitNotification($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['create_pre_debit_notification'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    public function reportPayment($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['report_payment'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    /**
     * @param      $url
     * @param      $method
     * @param      $inputData
     *
     * @return array|mixed
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function sendRequest($url, $method, array $inputData = [])
    {
        $url = $this->baseUrl . $url;

        $data = '';

        if (empty($inputData) === false)
        {
            $data = json_encode($inputData);
        }

        $headers['Accept'] = 'application/json';

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

        $response = $this->sendMandateHQRequest($request);

        if ($response->status_code !== 200)
        {
            throw new Exception\ServerErrorException(
                'Mandate HQ error',
                ErrorCode::SERVER_ERROR_MANDATE_HQ_REQUEST_FAILED,
                [
                    'response_body'        => $response->body ?? null,
                    'response_status_code' => $response->status_code,
                ]
            );
        }

        $decodedResponse = json_decode($response->body, true);

        $decodedResponse = $decodedResponse ?? [];

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        return $decodedResponse;
    }

    protected function sendMandateHQRequest($request)
    {
        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['options']);
        }
        catch(\Requests_Exception $e)
        {
            throw new Exception\ServerErrorException(
                'Mandate HQ error',
                ErrorCode::SERVER_ERROR_MANDATE_HQ_REQUEST_FAILED,
                [],
                $e
            );
        }

        return $response;
    }
}
