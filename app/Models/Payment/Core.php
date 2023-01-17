<?php

namespace RZP\Models\Payment;

use Cache;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Jobs\PaymentReminder;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Order\Entity;
use RZP\Models\Payment;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\PaymentLinkService;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Exception\ServerErrorException;
use RZP\Models\Payment\Config as PaymentConfig;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Payment\Processor\Constants;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function retrieveRefund($refundId, $merchantId, $paymentId = null)
    {
        if ($paymentId !== null)
        {
            Payment\Entity::verifyIdAndStripSign($paymentId);
        }

        Refund\Entity::verifyIdAndStripSign($refundId);

        return $this->repo->refund->findOrFailPublicByParams($refundId, $merchantId, $paymentId);
    }

    public function retrieveById($id)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findOrFail($id);

        return $payment;
    }

    public function retrievePaymentById($id)
    {
        return $this->repo->payment->findOrFail($id);
    }

    public function retrieveRefundById($refundId)
    {
        return $this->repo->refund->findOrFail($refundId);
    }

    public function updateReceiverData()
    {
        $payments = $this->repo->payment->fetchBankTransferPaymentWithoutReceiver();

        $successCount = 0;

        $failureCount = 0;

        foreach ($payments as $payment)
        {
            try
            {
                $payment->setReceiverId($payment['bank_account_id']);

                $payment->setReceiverType(Receiver::BANK_ACCOUNT);

                $this->repo->saveOrFail($payment);

                $this->trace->info(
                    TraceCode::PAYMENT_RECEIVER_UPDATED,
                    ['payment_id' => $payment->getId()]
                );

                $successCount++;
            }
            catch (\Throwable $e)
            {
                $failureCount++;

                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::PAYMENT_RECEIVER_UPDATE_FAILURE,
                    ['payment_id' => $payment->getId()]
                );
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ];
    }

    public function updateMdr(string $lastUpdatedPaymentId = null, int $lastUpdatedPaymentCapturedAt)
    {
        $paymentsToUpdateQuery = $this->repo->payment->buildUpdateMdrQuery($lastUpdatedPaymentId, $lastUpdatedPaymentCapturedAt);

        $successCount = 0;

        $paymentsToUpdateQuery->chunk(500, function ($payments, $successCount)
        {
            $this->repo->transaction(function () use ($payments)
            {
                foreach ($payments as $payment)
                {
                    $txn = $payment->transaction;

                    $processor = new Processor($payment->merchant);

                    $processor->calculateAndSetMdrFeeIfApplicable($payment, $txn);

                    $this->repo->saveOrFail($txn);

                    $this->repo->saveOrFail($payment);
                }
            });

            $successCount = $successCount + 500;

            $this->trace->info(TraceCode::PAYMENT_MDR_UPDATE_SUCCESS, [
                'success_count' => $successCount,
            ]);

            $lastUpdatedPayment           = $payments->last();
            $lastUpdatedPaymentId         = $lastUpdatedPayment->getId();
            $lastUpdatedPaymentCapturedAt = $lastUpdatedPayment->getCapturedAt();

            Cache::forever($this->mode . '_' . 'payment_mdr_update_data',
                $lastUpdatedPaymentId . ':' . $lastUpdatedPaymentCapturedAt);

            if ($successCount > 15000)
            {
                return false;
            }
        });
    }

    public function updatePaymentOnHold(Payment\Entity $payment, bool $onHold)
    {
        return $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($payment, $onHold)
            {
                $this->repo->transaction(
                    function() use ($payment, $onHold)
                    {
                        $this->repo->payment->lockForUpdateAndReload($payment);

                        $payment->setOnHold($onHold);

                        $this->repo->saveOrFail($payment);

                        $txn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

                        $txn->setOnHold($onHold);

                        $this->repo->saveOrFail($txn);

                        $this->trace->info(
                            TraceCode::PAYMENT_ON_HOLD,
                            [
                                'payment_id'  => $payment->getId(),
                                'hold_status' => $payment->getOnHold()
                            ]
                        );
                    });
            });
    }

    public function updateRefundAt($paymentId, $refundAt)
    {
        /**
         * @var $payment Payment\Entity
         */
        $payment = $this->repo->payment->findByPublicId($paymentId);

        $payment->setRefundAt($refundAt);

        $this->repo->payment->saveOrFail($payment);

        return $payment;
    }

    public function pushPaymentToKafka($payment, $startTime, $isReminderTimeoutPayment, $isReminderVerifyPayment)
    {
        // null => nothing pushed to kafka
        $isPushedToKafka = Constants::NOTHING_VIA_SCHEDULER;

        $topic = env('REGISTER_PAYMENT_SCHEDULER_EVENT', 'register-payment-scheduler-event');

        $data = [];

        $producerKey = $payment->getId() . '_' . Constants::REGISTER_PAYMENT_IN_SCHEDULER;

        if($isReminderVerifyPayment === true)
        {
            // for gpay, namespace would be provider_action
            if (empty($payment->getGooglePayMethods()) === false)
            {
                $namespace = Payment\Entity::GOOGLE_PAY . '_verify';
            }
            else
            {
                $namespace = $payment->getMethod() . '_' . $payment->getGateway() . '_verify';
            }

            $verifyReminderData = [
                Constants::NAMESPACE     => $namespace,
                Constants::ENTITY_ID     => $payment->getId(),
                Constants::ENTITY_TYPE   => 'payments',
                Constants::REMINDER_DATA => [
                    Constants::VERIFY_AT      => Carbon::now()->getTimestamp()
                ],
                Constants::VERIFY_SERVICE => 'api'
            ];

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_MESSAGE,
                [
                    'verify_message'    => $verifyReminderData
                ]
            );

            array_push($data, $verifyReminderData);
        }

        if($isReminderTimeoutPayment === true)
        {
            if (empty($payment->getGooglePayMethods()) === false)
            {
                $namespace = Payment\Entity::GOOGLE_PAY . Constants::TIMEOUT_SUFFIX;
            }
            else
            {
                $namespace = $payment->getMethod() . Constants::TIMEOUT_SUFFIX;
            }

            $timeoutWindow = $payment->getTimeoutWindow();
            $payment_timeout_at = Carbon::now()->addSeconds($timeoutWindow)->getTimestamp();

            $timeoutReminderData = [
                Constants::NAMESPACE     => $namespace,
                Constants::ENTITY_ID     => $payment->getId(),
                Constants::ENTITY_TYPE   => 'payments',
                Constants::REMINDER_DATA => [
                    Constants::TIMEOUT_AT      =>   $payment_timeout_at
                ],
                Constants::TIMEOUT_SERVICE => 'api'
            ];

            $this->trace->info(
                TraceCode::PAYMENT_TIMEOUT_MESSAGE,
                [
                    'timeout_message'    => $timeoutReminderData
                ]
            );

            array_push($data, $timeoutReminderData);
        }

        if ($isReminderVerifyPayment === true && $isReminderTimeoutPayment === true)
        {
            $isPushedToKafka = Constants::VERIFY_AND_TIMEOUT_VIA_SCHEDULER;
        }
        elseif ($isReminderTimeoutPayment === true)
        {
            $isPushedToKafka = Constants::TIMEOUT_VIA_SCHEDULER;
        }
        elseif ($isReminderVerifyPayment === true)
        {
            $isPushedToKafka = Constants::VERIFY_VIA_SCHEDULER;
        }

        if ($isPushedToKafka === Constants::NOTHING_VIA_SCHEDULER)
        {
            return $isPushedToKafka;
        }

        $message = [
            Constants::KAFKA_MESSAGE_TASK_NAME => Constants::REGISTER_PAYMENT_IN_SCHEDULER,
            Constants::KAFKA_MESSAGE_DATA      => $data,
        ];

        try
        {
            $this->pushToKafka($payment, $topic, $message, $producerKey, $startTime);

            $this->trace->info(
                TraceCode::PAYMENT_KAFKA_PUSH_SUCCESS,
                [
                    'payment_id'    => $payment->getId(),
                    'topic'         => $topic,
                ]
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_KAFKA_PUSH_SUCCESS, $payment);

            (new Payment\Metric())->pushKafkaPushSuccessForFailedPaymentMetrics(get_diff_in_millisecond($startTime));
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_KAFKA_PUSH_FAILED
            );

            // 2 => kafka push failed, marking for retry
            // commenting out this, as can't have index on reference6
            // we will have to explore the async approach via queue
            //$isPushedToKafka = 2;
            $isPushedToKafka = null;

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_KAFKA_PUSH_FAILED, $payment, $e);

            (new Payment\Metric())->pushKafkaPushFailedForFailedPaymentMetrics(get_diff_in_millisecond($startTime));
        }

        return $isPushedToKafka;
    }

    public function pushPaymentToKafkaForDeRegistrations($payment, $startTime): void
    {
        $data = [];

        if(in_array($payment->getIsPushedToKafka(), Constants::VALID_FOR_TIMEOUT_DEREGISTRATION))
        {
            $data[] = [
                Constants::NAMESPACE    => $payment->getMethod() . Constants::TIMEOUT_SUFFIX,
                Constants::PAYMENT_ID   => $payment->getId(),
                Constants::ACTIVE       => false
            ];
        }

        if(count($data) == 0)
        {
            return;
        }

        $producerKey = $payment->getId() . '_' . Constants::DEREGISTER_PAYMENT_IN_SCHEDULER;

        $this->trace->info(
            TraceCode::PAYMENT_SCHEDULER_DEREGISTER_INIT,
            [
                'payment_id'         => $payment->getId(),
                'is_pushed_to_kafka' => $payment->getIsPushedToKafka()
            ]
        );

        $topic = env('REGISTER_PAYMENT_SCHEDULER_EVENT', 'register-payment-scheduler-event');

        $message = [
            Constants::KAFKA_MESSAGE_TASK_NAME => Constants::DEREGISTER_PAYMENT_IN_SCHEDULER,
            Constants::KAFKA_MESSAGE_DATA      => $data,
        ];

        try
        {
            $this->pushToKafka($payment, $topic, $message, $producerKey, $startTime);

            $this->trace->info(
                TraceCode::PAYMENT_SCHEDULER_DEREGISTER_PUSH_SUCCESS,
                [
                    'payment_id'    => $payment->getId(),
                    'topic'         => $topic,
                    'message'       => $message
                ]
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_SCHEDULER_DEREGISTER_PUSH_SUCCESS, $payment);

            (new Payment\Metric())->pushKafkaPushSuccessForPaymentSchedulerDeRegistrationMetrics(get_diff_in_millisecond($startTime));
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_SCHEDULER_DEREGISTER_PUSH_FAILED
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_SCHEDULER_DEREGISTER_PUSH_FAILED, $payment, $e);

            (new Payment\Metric())->pushKafkaPushFailedForPaymentSchedulerDeRegistrationMetrics(get_diff_in_millisecond($startTime));
        }
    }

    public function getGrievanceEntityDetails(string $id)
    {

        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findOrFail($id);

        $merchant = $payment->merchant;

        $amount = $payment->getAmountComponents($payment->isDCC());

        return [
            'entity'         => 'payment',
            'entity_id'      => $payment->getPublicId(),
            'merchant_id'    => $payment->merchant->getId(),
            'merchant_label' => $merchant->getBillingLabel(),
            'merchant_logo'  => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'subject'        => 'Payment Successful of '.$amount[0].$amount[1].'.'.$amount[2],
        ];
    }

    public function pushFailedPaymentForRevival($payment)
    {
        if ($payment->hasOrder()) {
            $queueName = $this->app['config']->get('queue.missed_orders_pl_create.' . $this->mode);

            $pa = $this->repo->payment_analytics->findLatestByPayment($payment->getId());

            $data = [
                'queueId' => 'standard',
                'channelType' => 'payment_failed_retry',
                'request' => [
                    'mode' => $this->mode,
                    'merchant_id' => $payment->getMerchantId(),
                    'push_time' => Carbon::now()->unix(),
                    'payment' => [
                        'id' => $payment->getId(),
                        'internal_error_code' => $payment->getInternalErrorCode(),
                        'contact' => $payment->getContact(),
                        'email' => $payment->getEmail(),
                        'integration' => $pa != null ? $pa->getIntegration() : null,
                    ],
                    'order' => [
                        'id' =>  $payment->order->getId(),
                        'product_type' => $payment->order->getProductType(),
                        'is_invoice_order' => !empty($payment->order->invoice),
                        'is_partial_payment_allowed' => $payment->order->isPartialPaymentAllowed(),
                        'amount' => $payment->order->getAmount(),
                        'currency' => $payment->order->getCurrency(),
                        'notes' => $payment->order->getNotes(),
                    ]
                ]
            ];

            try {
                $this->app['queue']->connection('sqs')->pushRaw(json_encode($data), $queueName);

                $this->trace->info(TraceCode::FAILED_PAYMENT_PL_CREATION_SQS_PUSH_SUCCESS, [
                    'data' => $data,
                    'queueName' => $queueName,
                ]);

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_FAILED_SQS_PUSH_SUCCESS, $payment);
            }
            catch (\Exception $ex) {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FAILED_PAYMENT_PL_CREATION_SQS_PUSH_FAILURE,
                    [
                        'queueName' => $queueName,
                        'data' => $data,
                    ]
                );

                $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_FAILED_SQS_PUSH_FAILED, $payment, $ex);
            }
        }
        else {
            $this->trace->info(TraceCode::FAILED_PAYMENT_PL_CREATION_DEBUG, [
                'merchant_id' => $payment->getMerchantId(),
                'payment_id' => $payment->getId()
            ]);
        }
    }

    /**
     * @param $payment
     * @param mixed $topic
     * @param array $message
     * @param string $producerKey
     * @param $startTime
     * @return void
     */
    public function pushToKafka($payment, mixed $topic, array $message, string $producerKey, $startTime): void
    {
        $this->trace->info(
            TraceCode::PAYMENT_KAFKA_PUSH_VIA_SQS,
            [
                'payment_id' => $payment->getId(),
                'topic'      => $topic,
            ]
        );

        PaymentReminder::dispatch([
            'topic' => $topic,
            'message' => stringify($message),
            'producer_key' => $producerKey,
            'start_time' => $startTime
        ], $this->mode);
    }
}
