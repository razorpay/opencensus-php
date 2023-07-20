<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Downtime\Entity as DowntimeEntity;

class DowntimeManagerService
{
    private $config;
    private $trace;
    private $baseUrl;
    const REQUEST_TIMEOUT = 30;
    const JSON_METHOD = ['POST', 'PUT', 'PATCH'];

    private $srConfig;
    private $srBasePath;
    private $srHost;

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.downtime_manager');

        $this->trace = $app['trace'];

        $this->baseUrl =  $this->config['url'];

        $this->srConfig = $app['config']->get('applications.success_rate');
        $this->srHost = $this->srConfig['host'];
        $this->srBasePath = $this->srConfig['basePath'];
        $this->app = $app;
    }

    public function notifyDowntime(DowntimeEntity $downtime, String $status)
    {
        try{
            if ($downtime->getMerchantId() === null)
            {

                $downtimePayload = $this->getDowntimePayload($downtime, $status);

                $traceData = [
                    'downtime'          => $downtime->toArrayPublic(),
                    'downtimeStatus'    => $status,
                    'payload'           => $downtimePayload
                ];

                $this->trace->info(
                    TraceCode::SENDING_DOWNTIME_TO_DOWNTIME_MANAGER,
                    $traceData
                );

                $response = Requests::request(
                    $this->config['url'].'/send-notifications',
                    ['Content-Type' => 'application/json'],
                    $downtimePayload,
                    'POST',
                    ['auth' => $this->getRequestAuth()]
                );

                $traceData['status_code'] = $response->status_code;
                $traceData['body'] = $response->body;

                $this->trace->info(
                    TraceCode::SENT_DOWNTIME_TO_DOWNTIME_MANAGER,
                    $traceData
                );
            }
        }
        catch (\Exception $exception) {
            $this->trace->info(
                TraceCode::CALL_TO_DOWNTIME_MANAGER_FAILED,
                [
                    'downtime'   => $downtime->toArrayPublic(),
                    'exception' => $exception->getMessage(),
                ]
            );
        }
    }

    public function sendAnyRequest($url, $method, $data)
    {
        return $this->sendRequest($url, $method, $data);
    }

    public function sendRequest($url, $method, $data = null, $service = null)
    {
        $baseUrl = $this->getBaseUrl($service);
        $url = $baseUrl . '/' . $url;

        if ($data === null)
        {
            $data = '';
        }

        if ($service === 'SR') {
            $merchant   = $this->app['basicauth']->getMerchant();
            $headers['merchant_id'] = $merchant->getMerchantId();
        }

        $headers['Content-Type'] = 'application/json';

        $options = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $this->getRequestAuth($service),
        );

        $request = array(
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data
        );

        $response = $this->sendDowntimeManagerRequest($request);

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_RESPONSE, [
            'response' => $response->body
        ]);

        if(empty($response->body) === false)
        {
            $decodedResponse = json_decode($response->body, true);
        }

        $decodedResponse = $decodedResponse ?? [];
        $decodedResponse['status_code'] = $response->status_code;

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_RESPONSE, $decodedResponse);

        //check if $response is a valid json
        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException(
                'External Operation Failed');
        }

        $this->checkErrors($decodedResponse);

        return $decodedResponse;
    }


    protected function sendDowntimeManagerRequest($request)
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
        catch(\Exception $e)
        {
            throw $e;
        }

        return $response;
    }

    protected function traceRequest($request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::DOWNTIME_MANAGER_REQUEST, $request);
    }

    protected function checkErrors($response)
    {
        if (isset($response['error']))
        {
            $errorCode = $response['error']['code'];

            throw new BadRequestException($errorCode);
        }
    }

    private function getDowntimePayload(DowntimeEntity $downtime, String $status)
    {
        return json_encode([
            'id'  => $downtime->getId(),
            'type'  => "PLATFORM",
            'method' => $downtime->getMethod(),
            'severity' => $downtime->getSeverity(),
            'status' => $status,
            'scheduled' => $downtime->isScheduled(),
            'event_time' => $downtime->getUpdatedAt(),
            'begin' => $downtime->getBegin(),
            'end' => $downtime->getEnd(),
            'created_at' => $downtime->getCreatedAt(),
            'updated_at' => $downtime->getUpdatedAt(),
            'instrument' => [
                'issuer' => $downtime->getIssuer(),
                'network' => $downtime->getNetwork(),
                'vpa_handle'  => $downtime->getVpaHandle(),
                'psp' => $downtime->getPSP()
            ]]);
    }

    private function getBaseUrl($service = null)
    {
        if ($service == 'SR') {
            return $this->srHost . $this->srBasePath;
        }
        return $this->baseUrl;
    }

    private function getRequestAuth($service = null)
    {
        if ($service == 'SR') {
            return [$this->srConfig['user'], $this->srConfig['password']];
        }
        return [$this->config['user'], $this->config['password']];
    }

}
