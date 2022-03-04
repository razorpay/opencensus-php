<?php

namespace RZP\Models\Payment;

use Cache;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Diag\EventCode;
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

    public function pushPaymentToKafka($payment, $startTime)
    {
        //1 => successfully pushed to kafka
        $isPushedToKafka = 1;

        $producerKey = $payment->getId();

        $topic = env('REGISTER_PAYMENT_SCHEDULER_EVENT', 'register-payment-scheduler-event');

        // for gpay, namespace would be provider_action
        if (empty($payment->getGooglePayMethods()) === false)
        {
            $namespace = Payment\Entity::GOOGLE_PAY . '_verify';
        }
        else
        {
            $namespace = $payment->getMethod() . '_' . $payment->getGateway() . '_verify';
        }

        $data = [
            Constants::NAMESPACE    => $namespace,
            Constants::ENTITY_ID    => $payment->getId(),
            Constants::ENTITY_TYPE  => 'payments',
            Constants::REMINDER_DATA => [
                Constants::VERIFY_AT      => Carbon::now()->getTimestamp()
            ],
            Constants::VERIFY_SERVICE => 'api'
        ];

        $message = [
            Constants::KAFKA_MESSAGE_TASK_NAME => Constants::REGISTER_PAYMENT_IN_SCHEDULER,
            Constants::KAFKA_MESSAGE_DATA      => $data,
        ];

        try
        {
            (new KafkaProducer($topic, stringify($message), $producerKey))->Produce();

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

    public function createPaymentLinkToReviveOrder(Payment\Entity $payment)
    {
        if ($payment->getContact() == Payment\Entity::DUMMY_PHONE )
        {
            return null;
        }

        if ($payment->order->isPartialPaymentAllowed()) {
            return null;
        }

        $paymentFailedConfig = (new Config\Core())->getPaymentFailedConfig($payment->getMerchantId());

        $plExpireAfterHours = 24;

        if (
            $paymentFailedConfig !== false &&
            isset($paymentFailedConfig['retry_payment_links']) == true &&
            isset($paymentFailedConfig['retry_payment_links']['expiry_after']) == true)
        {
            $plExpireAfterHours = $paymentFailedConfig['retry_payment_links']['expiry_after'];
        }

        $expiryAt = Carbon::now()->addHours($plExpireAfterHours)->getTimestamp();

        $title = sprintf("Complete you order on %s", $payment->merchant->getDisplayNameElseName());

        $createUpiLink = $this->app->razorx->getTreatment($payment->getMerchantId(), Merchant\RazorxTreatment::PL_MISSED_ORDER_UPI_LINK, $this->mode);

        $data = [
            'order_id' => $payment->getOrderId(),
            'upi_link' => $createUpiLink,
            'amount' => $payment->order->getAmount(),
            'currency' => $payment->order->getCurrency(),
            'expire_by' => $expiryAt,
            'description' => "Retry your failed payment now",
            'reference_id' => $payment->order->getReceipt(),
            'customer' => [
                "contact" => $payment->getContact(),
                "email" => $payment->getEmail(),
            ],
            "notify" => [
                "sms" => true,
                "email" => false
            ],
            "notes" => $payment->order->getNotes(),
            "options" => [
                "hosted_page" => [
                    "title" => $title,
                    "label" => [
                        "description" => "",
                    ],
                    "show_expire_countdown" => true,
                ],
            ],
        ];

        $this->trace->info(TraceCode::FAILED_PAYMENT_PL_CREATION_SUCCESS, [ 'response' => $data]);

        try {
            $response = (new PaymentLinkService($this->app))->sendDirectRequestParams("v1/retry_payment_links", "POST", $payment->merchant, $data);

            $this->trace->info(TraceCode::FAILED_PAYMENT_PL_CREATION_SUCCESS, [ 'response' => $response]);

            return $response;
        } catch (ServerErrorException $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_PAYMENT_PL_CREATION_FAILED
            );
            return null;
        }
    }

    public function pushFailedPaymentToKafkaForPLCreation($payment)
    {
        $producerKey = $payment->getId();

        $topic = env('REGISTER_PAYMENT_FAILED_SCHEDULER_EVENT');

        $namespace = 'payment_failed_retry';

        $paymentFailedConfig = (new Config\Core())->getPaymentFailedConfig($payment->getMerchantId());

        if ($paymentFailedConfig === false or
            (isset($paymentFailedConfig['retry_payment_links']) === false and
                (isset($paymentFailedConfig['retry_payment_links']['send_after']) === false))
        ) {
            return false;
        }

        $data = [
            Constants::NAMESPACE    => $namespace,
            Constants::ENTITY_ID    => $payment->getId(),
            Constants::ENTITY_TYPE  => 'payments',
            Constants::REMINDER_DATA => [
                Constants::CREATE_PL_AT      => Carbon::now()->addSeconds($paymentFailedConfig['retry_payment_links']['send_after'])->getTimestamp()
            ],
        ];

        $message = [
            Constants::KAFKA_MESSAGE_TASK_NAME => Constants::REGISTER_PAYMENT_FAILED_IN_SCHEDULER,
            Constants::KAFKA_MESSAGE_DATA      => $data,
        ];

        $this->trace->info(TraceCode::FAILED_PAYMENT_PL_CREATION_SUCCESS, ['msg' => $message]);

        try
        {
            (new KafkaProducer($topic, stringify($message), $producerKey))->Produce();
            $this->trace->info(
                TraceCode::PAYMENT_FAILED_KAFKA_PUSH_SUCCESS,
                [
                    'payment_id'    => $payment->getId(),
                    'topic'         => $topic,
                ]
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_FAILED_KAFKA_PUSH_SUCCESS, $payment);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAILED_PAYMENT_PL_CREATION_FAILED
            );

            $this->trace->info(
                TraceCode::PAYMENT_FAILED_KAFKA_PUSH_FAILED,
                [
                    'payment_id'    => $payment->getId(),
                    'topic'         => $topic,
                ]
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_FAILED_KAFKA_PUSH_FAILED, $payment, $e);
        }
        return true;
    }
}
