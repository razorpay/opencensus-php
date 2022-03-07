<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Payment\Downtime\Entity as DowntimeEntity;

class DowntimeManagerService
{
    private $config;
    private $trace;

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.downtime_manager');

        $this->trace = $app['trace'];
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

    private function getDowntimePayload(DowntimeEntity $downtime, String $status)
    {
        return json_encode([
            'id'  => $downtime->getId(),
             'type'  => "PLATFORM",
            'method' => $downtime->getMethod(),
            'severity' => $downtime->getSeverity(),
            'status' => $status,
            'event_time' => $downtime->getUpdatedAt(),
            'instrument' => [
                'issuer' => $downtime->getIssuer(),
                'network' => $downtime->getNetwork(),
                'vpa_handle'  => $downtime->getVpaHandle(),
                'psp' => $downtime->getPSP()
            ]]);
    }

    private function getRequestAuth()
    {
        return [$this->config['user'], $this->config['password']];
    }

}
