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

    public function sendDowntimeRequestToSlack(DowntimeEntity $downtime, String $downTimeStatus)
    {
        $traceData = [
            'downtime'          => $downtime->toArrayPublic(),
            'downtimeStatus'    => $downTimeStatus,
        ];

        // Sending message only if downtime is a platform downtime
        // and not a merchant downtime
        if ($downtime->getMerchantId() === null)
        {
            $payload = $this->getDowntimeNotificationPayload($downtime, $downTimeStatus);
            $url = $this->getDowntimeNotificationUrl();

            $this->trace->info(
                TraceCode::SENDING_DOWNTIME_PAYLOAD_TO_SLACK_APP,
                $traceData
            );

            try {
                $this->sendRequestToSlack( $url, 'POST', $payload);
            }
            catch (\Throwable $exception) {
                $this->trace->info(
                    TraceCode::CALL_TO_SLACK_APP_FAILED,
                    [
                        'payload'   => $payload,
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

    public function sendPendingPayoutNotificationRequestToSlack($payload)
    {
        $url = $this->getPendingPayoutsNotficiationUrl();
        $payload = json_encode($payload);

        try {
            $this->trace->info(
                TraceCode::SENDING_PENDING_PAYOUT_NOTIFICATION_TO_SLACK_APP,
                [
                    'payload' => $payload,
                    'url'     => $url,
                    'method'  => 'POST'
                ]
            );

            $this->sendRequestToSlack( $url, 'POST', $payload);
        }
        catch (\Throwable $exception) {
            $this->trace->error(
                TraceCode::CALL_TO_SLACK_APP_FAILED,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

//    public function sendCouponExpiryAlert($payload)
//    {
//        $url = $this->getUrlForCouponAlerts();
//        $payload = json_encode($payload);
//
//        try {
//            $this->trace->info(
//                TraceCode::SENDING_COUPON_EXPIRY_NOTIFICATION_TO_SLACK_APP,
//                [
//                    'payload' => $payload,
//                    'url'     => $url,
//                    'method'  => 'POST'
//                ]
//            );
//
//            $this->sendRequestToSlack( $url, 'POST', $payload);
//        }
//        catch (\WpOrg\Requests\Exception $exception) {
//            $this->trace->error(
//                TraceCode::CALL_TO_SLACK_APP_FAILED,
//                [
//                    'exception' => $exception->getMessage(),
//                ]
//            );
//
//            throw $exception;
//        }
//    }

    public function getSubscribedMerchantList()
    {
        try {
            $this->trace->info(
                TraceCode::FETCHING_LIST_OF_MERCHANTS_SUBSCRIBED_FROM_SLACK_APP
                );

            $url = $this->getUrlForMerchantList();

            $response = $this->sendRequestToSlack( $url, 'GET');

            $responseBody = $this->jsonToArray($response->body, true);

            $this->trace->info(
                TraceCode::SUBSCRIBER_LIST_RECEIVED_FROM_SLACK_APP,
                [
                    'response' => $responseBody
            ]
            );
        }
        catch (\Throwable $exception) {
            $this->trace->error(
                TraceCode::FAILED_FETCHING_SUBSCRIBED_MERCHANT_LIST,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }


        return $responseBody;
    }

    public function sendRequestToSlack($url, $method, $payload = array())
    {
            try {
                $this->trace->info(
                    TraceCode::SENDING_REQUEST_TO_SLACK_APP,
                    [
                        'payload' => $payload,
                        'url'     => $url,
                        'method'  => $method
                    ]
                );

                $response = Requests::request(
                    $url,
                    ['Content-Type' => 'application/json'],
                    $payload,
                    $method,
                    ['auth' => $this->getRequestAuth()]
                );

                $this->trace->info(
                    TraceCode::SENT_REQUEST_TO_SLACK_APP,
                    [
                        'payload' => $payload,
                        'response'=> $response,
                        'url'     => $url,
                        'method'  => $method
                    ]
                );
            }
            catch (\Throwable $exception) {
                $this->trace->info(
                    TraceCode::CALL_TO_SLACK_APP_FAILED,
                    [
                        'payload'   => $payload,
                        'exception' => $exception->getMessage(),
                    ]
                );

                throw $exception;
            }

            return $response;
    }

    private function getRequestAuth()
    {
        return [$this->config['user'], $this->config['password']];
    }

    private function getDowntimeNotificationPayload(DownTimeEntity $downtime, String $downTimeStatus)
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

    private function getDowntimeNotificationUrl()
    {
        return $this->config['url'] . '/broadcast/rzp_downtime';
    }

    private function getUrlForMerchantList()
    {
        return $this->config['url'] . '/subscribed-merchants';
    }

//    private function getUrlForCouponAlerts(){
//        return $this->config['url'] . '/coupon_expiry_alerts';
//    }

    private function getPendingPayoutsNotficiationUrl()
    {
        return $this->config['url'] . '/notify-pending-payout';
    }

    protected function jsonToArray($json)
    {
        if (empty($json) === true)
        {
            return [];
        }

        $decodeJson = json_decode($json, true);

        switch (json_last_error())
        {
            case JSON_ERROR_NONE:
                return $decodeJson;
            default:

                $this->trace->error(
                    TraceCode::SLACK_APP_SERVICE_ERROR,
                    ['json' => $json]);

                throw new Exception\RuntimeException(
                    'Failed to convert json to array',
                    ['json' => $json]);
        }
    }
}
