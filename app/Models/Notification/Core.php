<?php

namespace RZP\Models\Notification;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Action;
use RZP\Models\UpiMandate\Metrics;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\UpiMandate\Frequency;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Gateway\Upi\Base\RecurringTrait;
use RZP\Exception\GatewayErrorException;
use RZP\Models\UpiMandate\SequenceNumber;
use RZP\Jobs\UpiAutopayNotificationProcess;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\UpiMandate\Metrics as UpiMandateMetrics;

class Core extends Base\Core
{
    use RecurringTrait;

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input, Order\Entity $order)
    {
        $this->trace->info(
            TraceCode::UPI_RECURRING_NOTIFICATION_CREATE_REQUEST,
            [
                'input'        => $input,
            ]
        );

        $this->addDefaultsForNotificationInput($input);

        $notification = (new Entity)->build($input);

        $notification->merchant()->associate($this->merchant);

        $notification->setStatus('created');

        // Update used count and sequence number in mandate table
        $gatewayReq = $this->updateSequenceNumberOrUsedCount($notification);

        $notification->setGatewayRequest($gatewayReq);

        $this->repo->saveOrFail($notification);

        $this->trace->info(
            TraceCode::UPI_RECURRING_NOTIFICATION_CREATED,
            [
                'merchant_id'       => $this->merchant->getPublicId(),
                'order_id'          => $order->getPublicId(),
                'token_id'          => $input['token_id'],
                'notification_id'   => $notification->getPublicId(),
                'gatewayReq'        => $notification->getGatewayRequest(),

            ]
        );

        $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_NOTIFICATION_CREATED,
            [
                'flow'   => 'decoupled',
                'is_tpv' => $this->merchant->isTPVRequired()
            ]);

        return $notification;
    }

    public function createNotification(array $input, $order)
    {
        $inputParams = $input['notification'];
        $inputParams['order_id'] = $order->getId();
        $inputParams['merchant_id'] = $this->merchant->getMerchantId();
        $inputParams['token_id']= Entity::stripDefaultSign($inputParams['token_id']);

        return $this->create($inputParams, $order);
    }

    public function createNotificationUsingOrder(array $input, $order)
    {
        $notification = $this->createNotification($input, $order);

        // pushing event to queue for pre-debit notification and delaying it for 60 sec
        try{
            UpiAutopayNotificationProcess::dispatch($this->mode, $notification->getId())->delay(60);

        } catch (\Throwable $e) {

            $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_NOTIFICATION_PUSH_FAILED, [
                'is_tpv'     => $this->merchant->isTPVRequired(),
                'error_code' => $e->getCode()
            ]);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_RECURRING_NOTIFICATION_PUSH_FAILED);

            throw $e;
        }

        return [
            "token_id"          => 'token_'.$notification->getTokenId(),
            "payment_after"     => $notification->getPaymentAfter(),
            "id"                => 'notification_'.$notification->getId(),
        ];
    }

    public function validateNotificationData($input, $merchant, $token)
    {
        $this->validateToken($token);

        $this->validatePaymentMethod($input, $token);

        $upiMandate = $this->repo->upi_mandate->findByTokenId($token->getId());

        $this->validateOrderAmount($input, $upiMandate);

        $this->validatePaymentAfter($input, $upiMandate);

        $this->validateMandateFrequency($upiMandate);

    }

    protected function validatePaymentMethod($input, $token)
    {
        if((isset($input['method']) === true) and ($input['method'] !== 'upi'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Notification is not supported for other methods');
        }
    }

    protected function validateMandateFrequency($upiMandate)
    {
        $lastSuccessDebitTimeStamp = $upiMandate['gateway_data']['lsd'] ?? null;

        // validation for fixed frequencies
        if(($upiMandate['frequency'] !== Frequency::AS_PRESENTED) and
            ($upiMandate->getFrequency() !== Frequency::DAILY) and
            ($lastSuccessDebitTimeStamp !== null) and
            (in_array($this->app['env'],['automation','bvt']) === false))
        {
            $sequenceNumber = new SequenceNumber($lastSuccessDebitTimeStamp, Carbon::now(Timezone::IST)->getTimestamp());
            $recurType = $upiMandate['recurring_type'];
            $recurVal = $upiMandate['recurring_value'];
            $frequency = $upiMandate['frequency'];

            if($sequenceNumber->isValidExecutionDate($frequency) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MANDATE_ALREADY_HONOURED);
            }

            if($sequenceNumber->isValidCycle($recurType, $recurVal, $frequency) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Debit not allowed at this time. Debit needs to be charged within the cycle & 26 hours before the last date',
                    null,
                    []);
            }
        }
    }

    public function validateToken($token)
    {
        if(($token === null) or ($token->getRecurringStatus() !== 'confirmed'))
        {
            throw new Exception\BadRequestValidationFailureException(
                "token is not in confirmed status");
        }

        if($token->getMethod() !== 'upi')
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid UPI token');
        }
    }

    public function validateOrderAmount($input, $upiMandate)
    {
        $orderAmount = $input['amount'];
        if($orderAmount > $upiMandate->getMaxAmount())
        {
            throw new Exception\BadRequestValidationFailureException(
                'order amount exceeds maximum amount allowed limit');
        }
    }

    public function validatePaymentAfter($input, $upiMandate)
    {
        $paymentAfter = $input['notification']['payment_after'];

        $validPaymentAfter = Carbon::now(Timezone::IST)->addHours(25)->getTimestamp();

        if(($paymentAfter !== null) and
            ($paymentAfter < $validPaymentAfter))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST);
        }

        if(($paymentAfter !== null and $paymentAfter > $upiMandate->getEndTime()) or
            ($validPaymentAfter > $upiMandate->getEndTime()))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_DEBIT_DATE);
        }
    }

    // In case of customer fee bearer we need to handle this in notification flow
    public function processNotification($notification)
    {
        $this->upiMandate = $this->repo->upi_mandate->findByTokenId($notification->getTokenId());

        $this->merchant = $this->repo->merchant->findOrFail($notification->getMerchantId());

        // select terminal same used for initial registration
        $token = $this->repo->token->findByIdAndMerchant($notification->getTokenId(), $this->merchant);

        $terminalIds = [
            $token->getTerminalId()
        ];

        $terminal = (new TerminalProcessor)->getTerminalFromTerminalIds($terminalIds);

        $this->updateNotificationEntityWithGatewayRequest($terminal[0], $notification);

        $gatewayRequest = $this->prepareGatewayRequest($terminal[0], $notification);

        $this->mutex->acquireAndRelease($notification->getId(),
            function() use ($gatewayRequest, $notification) {
                try
                {
                    $gatewayRequest['upi_mandate'] = $this->upiMandate->toArray();

                    // hit razorx service to get the variant
                    $isVariantOn = $this->getSplitzVariantForUpiAutopay($gatewayRequest);

                    if ($isVariantOn === true)
                    {
                        $gatewayResponse = $this->app['upi.payments']->action(Payment\Action::NOTIFY, $gatewayRequest, $gatewayRequest['gateway']);
                    }
                    else {
                        $gatewayResponse = $this->app['gateway']->call(
                            $gatewayRequest['gateway'],
                            $gatewayRequest['action'],
                            $gatewayRequest,
                            $this->mode,
                            $gatewayRequest['terminal']);
                    }

                    $this->processPreDebitGatewayResponse($gatewayRequest, $gatewayResponse, $notification);

                    return;
                }
                catch (Exception\GatewayErrorException $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::INFO,
                        TraceCode::GATEWAY_PAYMENT_ERROR,
                        [
                            'order_id'          => $notification->getOrderId(),
                            'gateway'           => $gatewayRequest['gateway'],
                            'action'            => $gatewayRequest['action'],
                            'terminal_id'       => $gatewayRequest['terminal']->getId(),
                            'notification_id'   => $notification->getId(),
                        ]);

                    $this->processNotificationGatewayFailure($notification, $exception);

                    return;
                }
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);
    }

    protected function getSplitzVariantForUpiAutopay($gatewayRequest): bool
    {
        try {
            $gateway = $gatewayRequest['payment']['gateway'];
            $merchantId = $gatewayRequest['merchant']['id'];
            if (isset($merchantId) === true)
            {
                $feature = 'upi_autopay_rearch'. '_' . $gateway . '_v1_exp_id';
                $properties = [
                    'id'            => UniqueIdEntity::generateUniqueId(),
                    'experiment_id' => $this->app['config']->get('app.'.$feature),
                    'request_data'  => json_encode(['merchant_id' => $merchantId]),
                ];
                $response = $this->app['splitzService']->evaluateRequest($properties);

                $variant = $response['response']['variant']['name'] ?? '';

                return ($variant === 'variant_on' or $gateway === Payment\Gateway::UPI_RZPAPB or $gateway === Payment\Gateway::UPI_YESBANK);

            }
        } catch (\Throwable $e) {

            $this->trace->error(TraceCode::UPI_AUTOPAY_GATEWAY_REARCH_SPLITZ_FAILED, [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return false;
    }

    public function eventOrderNotificationDelivered($notification)
    {
        if($notification->getStatus() !== Status::DELIVERED)
        {
            return;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN => $notification
        ];

        $this->app['events']->dispatch('api.order.notification.delivered', $eventPayload);
    }

    public function eventOrderNotificationFailed($notification)
    {
        if($notification->getStatus() !== Status::FAILED)
        {
            return;
        }

        $notification['delivered_at'] = null;

        $eventPayload = [
            ApiEventSubscriber::MAIN => $notification
        ];

        $this->app['events']->dispatch('api.order.notification.failed', $eventPayload);
    }

    protected function updateSequenceNumberOrUsedCount($notification)
    {
        $this->upiMandate = $this->repo->upi_mandate->findByTokenId($notification['token_id']);

        $usedCount = $this->upiMandate->getUsedCount();

        $this->upiMandate->incrementUsedCount();

        $newSequenceNo = $this->upiMandate->getSequenceNumberAttribute();

        $this->repo->upi_mandate->saveOrFail($this->upiMandate);

        if ($this->upiMandate->getFrequency() === Frequency::AS_PRESENTED)
        {
            $newSequenceNo = $this->upiMandate->getUsedCount();
        }

        $this->trace->info(TraceCode::UPI_RECURRING_MANDATE_SEQ_NO_CHANGE, [
            'oldUsedCount'          => $usedCount,
            'newSeqNo'              => $newSequenceNo,
            'newUsedCount'          => $this->upiMandate->getUsedCount(),
        ]);

        return $gatewayRequest = [
            Constants::SEQUENCE_NUMBER => $newSequenceNo
        ];
    }

    protected function addDefaultsForNotificationInput(array & $input)
    {
        if (isset($input['payment_after']) === false)
        {
            $input['payment_after'] = Carbon::now(Timezone::IST)->addHours(25)->getTimestamp();
        }
    }

    protected function updateNotificationEntityWithGatewayRequest($terminal, $notification)
    {
        $gateway = $terminal->getGateway();

        $gatewayRequest = $notification->getGatewayRequest();

        $gatewayRequest[Constants::MERCHANT_TRAN_ID] = $this->createMerchantTranId($notification);
        $gatewayRequest[Constants::FLOW ] = $this->upiMandate['gateway_data']['flow'];
        $gatewayRequest[Constants::PAYMENT_SUCCESS] = false;

        // Updating payment after at the time of notification delivery as there can be some delay in queue
        $paymentAfter = Carbon::createFromTimestamp($notification->getPaymentAfter(), Timezone::IST);
        $notificationCreatedAt = Carbon::createFromTimestamp($notification->getCreatedAt(), Timezone::IST);
        $diff = $paymentAfter->diffInHours($notificationCreatedAt);
        $newPaymentAfter = Carbon::now(Timezone::IST)->addHours($diff)->getTimestamp();

        $notification->setGatewayMerchantId($terminal->getGatewayMerchantId());
        $notification->setGateway($gateway);
        $notification->setGatewayRequest($gatewayRequest);
        $notification->setPaymentAfter($newPaymentAfter);
        $this->repo->saveOrFail($notification);
    }

    public function updateNotificationEntityIfApplicable($orderId, $attempts = null, $internalStatus = null)
    {
        $notificationCount = $this->fetchNotificationCount($orderId);

        if($notificationCount > 0)
        {
            $notification = $this->findNotification($orderId);

            $gatewayRequest = $notification->getGatewayRequest();

            if(($internalStatus !== null) and ($internalStatus === Payment\UpiMetadata\InternalStatus::AUTHORIZED))
            {
                $gatewayRequest[Constants::PAYMENT_SUCCESS] = true;
            }

            if($attempts !== null)
            {
                $gatewayRequest[Constants::PAYMENT_ATTEMPTS] = $attempts;
            }

            $notification->setGatewayRequest($gatewayRequest);

            $this->repo->saveOrFail($notification);
        }
    }

    protected function prepareGatewayRequest($terminal, $notification)
    {
        $order = $this->repo->order->findByIdAndMerchant($notification->getOrderId(), $this->merchant);

        // It is not actual payment entity, we are sending notification entity details
        // in payment var to support mozart request
        $payment = [
            'amount'    => $order->getAmount(),
            'id'        => $notification->getId(),
            'vpa'       => $this->upiMandate['gateway_data']['vpa'],
            'order_id'  => $notification->getOrderId(),
            'gateway'   => $terminal->getGateway(),
            'recurring' => true,
            'method'    => 'upi',
            'currency'  => 'INR'
        ];

        $upi = [
            'expiry_time'   => 10,
            'gateway_data'  => [
                'id'            => $notification['gateway_request']['merchantTranId'],
                'sno'           => $notification['gateway_request']['seqNo'],
                'ext'           => $notification['payment_after']
            ]
        ];

        $gatewayInput = [
            'action'        => Action::PRE_DEBIT,
            'gateway'       => $terminal->getGateway(),
            'terminal'      => $terminal,
            'payment'       => $payment,
            'merchant'      => $this->merchant,
            'upi'           => $upi,
            'upi_mandate'   => $this->upiMandate,
            'notification'  => $notification
        ];

        return $gatewayInput;
    }

    protected function processPreDebitGatewayResponse($gatewayRequest, $gatewayResponse, $notification)
    {
        $this->updateNotificationEntityWithGatewayResponse($gatewayRequest, $gatewayResponse['data'], $notification);

        $this->trace->info(TraceCode::PAYMENT_UPI_RECURRING_GATEWAY_RESPONSE, [
            'notificationId'=> $notification->getId(),
            'action'        => 'pre-debit',
            'mandate_id'    => $notification['gateway_request']['merchantTranId'],
            'mode'          => 'auto',
            'response'      => $gatewayResponse['data'],
            'sno'           => $gatewayRequest['upi_mandate']['sequence_number'],
            'mandate'       => $gatewayRequest['upi_mandate'],
        ]);

        if($gatewayResponse['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $gatewayResponse['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $gatewayResponse['error']['gateway_error_code'] ?? 'gateway_error_code',
                $gatewayResponse['error']['gateway_error_description'] ?? 'gateway_error_desc',
                null,
                null,
                'pre_debit');

            $this->processNotificationGatewayFailure($notification, $exception);

            return;
        }

        $this->processNotificationGatewaySuccess($notification);
    }

    protected function updateNotificationEntityWithGatewayResponse($gatewayRequest, $response, $notification)
    {

        if((isset($response['status_desc'])) and
            (empty($response['status_desc']) === false))
        {
            $payerResponseCodeDes = $this->upiRecurringUpdateGatewayStatus($response['status_desc'], $response['status_code']);

            $this->trace->info(TraceCode::UPI_RECURRING_PAYER_RESPONSE_CODE, [
                'payer_response_code'       => $payerResponseCodeDes,
            ]);

            $provider = $this->getProviderBank($gatewayRequest['payment']['vpa']);

            $notification->setProvider($provider);
            $notification->setVpa($gatewayRequest['payment']['vpa']);
            $notification->setNpciTxnId($response['upi']['npci_txn_id']);
            $notification->setBankRRN($response['upi']['npci_reference_id']);
            $notification->setGatewayResponse($payerResponseCodeDes);
        }
    }

    /**
     * @return Entity
     */
    public function findDeliveredNotification($orderId)
    {
        return $this->repo->notification->findDeliveredNotificationByOrderId($orderId);
    }

    /**
     * @return Entity
     */
    public function findNotification($orderId)
    {
        return $this->repo->notification->findByOrderId($orderId);
    }

    /**
     * @return int
     */
    public function fetchNotificationCount($orderId)
    {
        return $this->repo->notification->fetchNotificationCount($orderId);
    }

    protected function getProviderBank($vpa)
    {
        if ($vpa === null)
        {
            return;
        }

        $vpaParts = explode('@', $vpa);

        $provider = $vpaParts[1];

        if ($provider === null)
        {
            return;
        }

        return ProviderCode::getBankCode($provider);
    }

    protected function processNotificationGatewaySuccess($notification)
    {
        $notification->setStatus(Status::DELIVERED);

        $this->repo->saveOrFail($notification);
        // send webhook

        $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_NOTIFICATION_DELIVERED, [
            'flow'    => 'decoupled',
            'gateway' => $notification->getGateway(),
            'is_tpv'  => $notification->merchant->isTPVRequired()
        ]);

        $this->eventOrderNotificationDelivered($notification);
    }

    public function processNotificationGatewayFailure($notification, $exception = null)
    {
        $notification->setStatus(Status::FAILED);

        $this->repo->saveOrFail($notification);

        $this->trace->count(Metrics::UPI_AUTOPAY_NOTIFICATION_FAILED, [
            'error_code' => $exception->getCode(),
            'flow'       => 'decoupled',
            'gateway'    => $notification->getGateway(),
            'is_tpv'     => $notification->merchant->isTPVRequired()
        ]);

        // send webhook
        $this->eventOrderNotificationFailed($notification);
    }

    protected function createMerchantTranId($notification)
    {
        $env = 0;
        $action = 'notify';
        $retryCount = 0;
        return $notification->getId() . $env . $action . $retryCount;
    }

}
