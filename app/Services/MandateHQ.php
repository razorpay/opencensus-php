<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;

class MandateHQ
{
    const REQUEST_TIMEOUT = 60;

    const MANDATE_HQ_URLS = [
        'register_mandate'              => 'mandate/mandate_request',
        'confirm_mandate'               => 'issuer/%s/confirm',
        'create_pre_debit_notification' => 'issuer/mandates/%s/notify',
        'post_debit_notify'             => 'issuer/mandates/%s/post_debit_notify',
        'verify_notification'           => 'issuer/%s/verify',
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
        $response = $this->sendRequest(self::MANDATE_HQ_URLS['register_mandate'], 'post', $input);

        $error = $response['error'] ?? [];

        $success = $error['success'] ?? true;

        if ($success === false)
        {
            throw new Exception\ServerErrorException(
                'Mandate HQ error',
                ErrorCode::SERVER_ERROR_MANDATE_HQ_REQUEST_FAILED,
                [
                    'error' => $error
                ]
            );
        }

        return $response;
    }

    public function confirmMandate($mandateRegisterId)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['confirm_mandate'], $mandateRegisterId);

        return $this->sendRequest($url, 'post', []);
    }

    public function verifyNotification($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['verify_notification'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    public function createPreDebitNotification($mandateId, $input)
    {
        $url = sprintf(self::MANDATE_HQ_URLS['create_pre_debit_notification'], $mandateId);

        return $this->sendRequest($url, 'post', $input);
    }

    public function postDebitNotify($mandateId, $notificationId)
    {
        $input = ['notification_id' => $notificationId];

        $url = sprintf(self::MANDATE_HQ_URLS['post_debit_notify'], $mandateId);

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
