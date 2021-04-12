<?php

namespace RZP\Models\Payment;

use Cache;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Payment\Processor\Constants;
use RZP\Models\Payment\Processor\TerminalProcessor;

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

    public function pushFailedPaymentToKafka($payment)
    {
        //1 => successfully pushed to kafka
        $isPushedToKafka = 1;

        $topic = env('REGISTER_PAYMENT_SCHEDULER_EVENT', 'register-payment-scheduler-event');

        $data = [
            Constants::NAMESPACE    => $payment->getMethod() . '_' . $payment->getGateway() . '_verify',
            Constants::ENTITY_ID    => $payment->getId(),
            Constants::ENTITY_TYPE  => 'payments',
            Constants::REMINDER_DATA => [
                Constants::VERIFY_AT      => $payment->getCreatedAt()
            ],
            Constants::VERIFY_SERVICE => 'api'
        ];

        $message = [
            Constants::KAFKA_MESSAGE_TASK_NAME => Constants::REGISTER_PAYMENT_IN_SCHEDULER,
            Constants::KAFKA_MESSAGE_DATA      => $data,
        ];

        try
        {
            (new KafkaProducer($topic, stringify($message)))->Produce();

            $this->trace->info(
                TraceCode::FAILED_PAYMENT_KAFKA_PUSH_SUCCESS,
                [
                    'payment_id'    => $payment->getId(),
                    'topic'         => $topic,
                ]
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::FAILED_PAYMENT_KAFKA_PUSH_SUCCESS, $payment);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_PAYMENT_KAFKA_PUSH_FAILED
            );

            // 2 => kafka push failed, marking for retry
            $isPushedToKafka = 2;

            $this->app['diag']->trackPaymentEventV2(EventCode::FAILED_PAYMENT_KAFKA_PUSH_FAILED, $payment, $e);
        }

        return $isPushedToKafka;
    }
}
