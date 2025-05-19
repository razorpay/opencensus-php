<?php

namespace RZP\Services\Device;

use RZP\Trace\TraceCode;

class Base
{
    const CODE = "code";
    const ERROR = "error";

    protected $app;

    protected $trace;

    protected $mode;

    protected $config;

    protected $request;

    protected $username;

    protected $password;

    const TASK_ID  = 'X-Task-Id';
    const AUTHORIZATION  = 'Authorization';
    const RESPONSE = 'response';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->mode = $app['rzp.mode'] ?? 'live';

        $this->request = $app['request'];

        $this->config = $app['config']->get('applications.ezetap_device_gatway');

        $this->username = $this->config['username'];

        $this->password = $this->config['password'];
    }

    const TIMEOUT = 60;

    const CONNECT_TIMEOUT = 10;

    protected function sendRequest(string $method, string $url, array $data = [])
    {

        $body = json_encode([
            "username" => $this->username,
            "password" => $this->password
        ]);

        $ch = curl_init($url);

        // Set custom request to GET
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Attach body (even though it's non-standard for GET)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        // Set proper header format
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            self::TASK_ID . ': ' . $this->app['request']->getTaskId(),
        ]);

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Set timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

        // Log request
        $this->trace->info(TraceCode::DEVICE_SERVICE_REQUEST, [
            'url' => $url,
            'method' => 'GET',
            'body' => $body
        ]);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // Check for curl execution errors
        if ($response === false) {
            $this->trace->error(TraceCode::DEVICE_SERVICE_ERROR, [
                'error' => $error,
                'url' => $url
            ]);
            curl_close($ch);
            return [];
        }

        $this->trace->info(TraceCode::RESPONSE, [
            'response' => $response,
            'httpCode' => $httpCode
        ]);

        curl_close($ch);

        if ($httpCode !== 200)
        {
            $this->trace->error(TraceCode::DEVICE_SERVICE_ERROR, [
                'response' => $response,
                'httpCode' => $httpCode
            ]);

            return [];
        }

        // Decode response
        $decodedResponse = json_decode($response, true);

        // Check if JSON decode failed
        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->trace->error(TraceCode::DEVICE_SERVICE_ERROR, [
                'error' => 'JSON decode error: ' . json_last_error_msg(),
                'response' => $response
            ]);
            return [];
        }
        $this->trace->info(TraceCode::RESPONSE, [
            'response' => $decodedResponse
        ]);
        return $decodedResponse;
    }
}
