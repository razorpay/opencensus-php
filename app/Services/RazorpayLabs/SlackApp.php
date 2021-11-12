<?php

namespace RZP\Services\RazorpayLabs;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Exception\ServerErrorException;
use RZP\Models\Payment\Downtime\Entity as DownTimeEntity;

class SlackApp
{
    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.rzp_labs')['slack_app'];

        $this->trace = $app['trace'];
    }

    public function sendRequestToSlack(DowntimeEntity $downtime, String $downTimeStatus)
    {
        $traceData = [
            'downtime'          => $downtime->toArrayPublic(),
            'downtimeStatus'    => $downTimeStatus,
        ];

        // Sending message only if downtime is a platform downtime
        // and not a merchant downtime
        if ($downtime->getMerchantId() === null)
        {
            try {
                $this->trace->info(
                    TraceCode::SENDING_DOWNTIME_PAYLOAD_TO_SLACK_APP,
                    $traceData
                );

                Requests::request(
                    $this->getUrl(),
                    ['Content-Type' => 'application/json'],
                    $this->getPayload($downtime, $downTimeStatus),
                    'POST',
                    ['auth' => $this->getRequestAuth()]
                );

                $this->trace->info(
                    TraceCode::SENT_DOWNTIME_PAYLOAD_TO_SLACK_APP,
                    $traceData
                );
            } catch (\Requests_Exception $exception) {
                $this->trace->info(
                    TraceCode::CALL_TO_SLACK_APP_FAILED,
                    [
                        'payload'   => $traceData,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::SKIPPING_DOWNTIME_PAYLOAD_TO_SLACK_APP,
                $traceData
            );
        }
    }

    private function getRequestAuth()
    {
        return [$this->config['user'], $this->config['password']];
    }

    private function getPayload(DownTimeEntity $downtime, String $downTimeStatus)
    {
        return json_encode([
            'entity'        => 'event',
            'event'         => 'payment.downtime.' . $downTimeStatus,
            'contains'      => [
                'payment.downtime',
            ],

            'payload'     => [
                'payment.downtime'  => [
                    'entity'    => $downtime->toArrayPublic(),
                ],
            ],
        ]);
    }

    private function getUrl()
    {
        return $this->config['url'] . '/broadcast/rzp_downtime';
    }
}
