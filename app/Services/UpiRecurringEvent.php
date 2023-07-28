<?php

namespace RZP\Services;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\KafkaProducer;
use RZP\Exception\BaseException;
use Razorpay\Trace\Logger as Trace;

class UpiRecurringEvent extends Base\Core
{

    const EVENT_TYPE                   = 'upi-recurring-events';

    const EVENT_VERSION                = 'v1';


    public function pushUpiRecurringEvents(array $eventCode, Payment\Entity $payment = null,
        \Throwable $ex = null,
        array $customProperties = []): void
    {
        try
        {
            if (isset($eventCode) === false)
            {
                return;
            }

            $eventData = $this->fetchEventData($payment, $ex, $customProperties);

            $traceEventData = $eventData;

            $this->trace->info(TraceCode::UPI_RECURRING_EVENT, [
                'eventData'      => $traceEventData,
                'exception'      => $ex,
                'eventCode'      => $eventCode
            ]);

            $context = [
                'task_id' => $this->app['request']->getTaskId(),
                'request_id' => $this->app['request']->getId(),
            ];

            $this->trackUpiRecurringEvent(self::EVENT_TYPE, self::EVENT_VERSION, $eventCode, $eventData, $metaData = null, $readKey = [] , $writeKey = null, $context);

        }
        catch (\Throwable $exc)
        {
            $this->trace->traceException(
                $exc,
                Trace::ERROR,
                TraceCode::UPI_RECURRING_EVENT,
                [
                    'eventCode'     => $eventCode ?? 'none',
                ]);
        }
    }

    public function trackUpiRecurringEvent(string $eventType,
                                    string $eventVersion,
                                    array $event,
                                    array $properties,
                                    array $metaData = null,
                                    array $readKey = [] ,
                                    string $writeKey = null,
                                    array  $context = null)
    {
        $topicName = 'events' . '.' .  $eventType . '.' . $eventVersion . '.' .  $this->mode;

        $event = [
            'event_name'          => $event['name'],
            'event_type'          => $eventType,
            'event_group'         => $event['group'],
            'version'             => $eventVersion,
            'event_timestamp'     => (int)(microtime(true)),
            'producer_timestamp'  => (int)(microtime(true)),
            'source'              => 'upi-recurring',
            'mode'                => 'live',
            'context'             => $context,
            'properties'          => $properties,
        ];


        if (($eventVersion === 'v2') === true)
        {
            $event['metadata']       = $metaData;
            $event['read_key']       = $readKey;
            $event['write_key']      = $writeKey;
        }

        (new KafkaProducer($topicName, stringify($event)))->Produce();
    }

    protected function fetchEventData(Payment\Entity $payment = null,
                                      \Throwable $ex = null,
                                      array $customProperties = []): array
    {
        if (empty($payment))
        {
            return $customProperties;
        }

        if (isset($ex))
        {
            return $this->addErrorDetails($ex , $customProperties);
        }

        $eventData = [];
        $eventData += $customProperties;

        if ($payment !== null)
        {
            $eventData['payment'] = [
                'id'             => $payment->getPublicId(),
                'amount'         => $payment->getAmount(),
                'currency'       => $payment->getCurrency(),
                'method'         => $payment->getMethod(),
                'issuer'         => $payment->getIssuer(),
                'type'           => $payment->getTransactionType(),
                'gateway'        => $payment->getGateway(),
                'recurring'      => $payment->isRecurring(),
                'recurring_type' => $payment->getRecurringType(),
            ];

            // upi properties
            if ($payment->isUpi() === true)
            {
                $upiType = null;

                $upiMetadata = $payment->fetchUpiMetadata();

                if(is_null($upiMetadata) === false)
                {
                    $upiType = $upiMetadata['flow'];
                }

                $eventData['payment'] += [
                    'vpa'       => $payment->getVpa(),
                    'upi_type'  => $upiType ?? null
                ];
            }

            if ($payment->hasOrder() === true)
            {
                $order = $payment->order;

                $eventData['order'] = [
                    'id'       => $order->getPublicId(),
                    'amount'   => $order->getAmount(),
                    'currency' => $order->getCurrency()
                ];
            }
        }

        return $eventData;
    }

    // add error details to the diag properties based on excpetion
    protected function addErrorDetails(\Throwable $ex , array $eventData) : array
    {

        $errorAttributes = [];

        if ($ex->getError() !== null)
        {
            if ($ex instanceof BaseException)
            {
                $errorAttributes['error_code'] = $ex->getCode();
            }
            else
            {
                $errorAttributes['error_code'] = ErrorCode::SERVER_ERROR;
            }
        }

        $eventData += [
            'status'                    => 'FAILED',
            'error_code'                => $errorAttributes['error_code'],
        ];

        return $eventData;
    }
}
