<?php

namespace RZP\Models\Notification;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Action;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\UpiMandate\Frequency;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Gateway\Upi\Base\RecurringTrait;
use RZP\Models\UpiMandate\SequenceNumber;
use RZP\Jobs\UpiAutopayNotificationProcess;
use RZP\Models\Payment\Processor\TerminalProcessor;

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

        $this->repo->saveOrFail($notification);

        $this->trace->info(
            TraceCode::UPI_RECURRING_NOTIFICATION_CREATED,
            [
                'merchant_id'       => $this->merchant->getPublicId(),
                'order_id'          => $order->getPublicId(),
                'token_id'          => $input['token_id'],
                'notification_id'   => $notification->getPublicId(),
            ]
        );
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

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::UPI_RECURRING_NOTIFICATION_PUSH_FAILED);

            throw $e;
        }

        return [
            "token_id"          => 'token_'.$notification->getTokenId(),
            "payment_after"     => $notification->getPaymentAfter(),
            "notification_id"   => 'notification_'.$notification->getId(),
        ];
    }

    public function validateNotificationData($input, $merchant)
    {
        $tokenId = $input['notification']['token_id'];

        $token = $this->repo->token->findByPublicIdAndMerchant($tokenId, $merchant);

        $this->validateToken($token);

        $upiMandate = $this->repo->upi_mandate->findByTokenId($token->getId());

        $this->validateOrderAmount($input, $upiMandate);

        $this->validatePaymentAfter($input, $upiMandate);

        $this->validateMandateFrequency($upiMandate);

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

        $this->merchant = $this->repo->merchant->findOrFail($notification->getMerchantId());

        // select terminal same used for initial registration
        $token = $this->repo->token->findByIdAndMerchant($notification->getTokenId(), $this->merchant);

        $terminalIds = [
            $token->getTerminalId()
        ];

        $terminal = (new TerminalProcessor)->getTerminalFromTerminalIds($terminalIds);

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            [
                'terminal'        => $terminal,
            ]
        );

        // Update used count and sequence number in mandate table
        $this->updateSequenceNumberOrUsedCount($token->getId());

        $this->updateNotificationEntityWithGatewayRequest($terminal[0], $notification);

        $gatewayRequest = $this->prepareGatewayRequest($terminal[0], $notification);

        $this->mutex->acquireAndRelease($notification->getId(),
            function() use ($gatewayRequest, $notification) {
                try
                {
                    $gatewayRequest['upi_mandate'] = $this->upiMandate->toArray();

                    $gatewayResponse = $this->app['gateway']->call(
                        $gatewayRequest['gateway'],
                        $gatewayRequest['action'],
                        $gatewayRequest,
                        $this->mode,
                        $gatewayRequest['terminal']);

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

                    $this->processNotificationGatewayFailure($notification);

                    return;
                }
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);
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

    protected function updateSequenceNumberOrUsedCount($tokenId)
    {
        $this->upiMandate = $this->repo->upi_mandate->findByTokenId($tokenId);

        $sequenceNo = $this->upiMandate['sequence_number'];
        $usedCount = $this->upiMandate->getUsedCount();

        $this->upiMandate->incrementUsedCount();

        $this->trace->info(TraceCode::UPI_RECURRING_MANDATE_SEQ_NO_CHANGE, [
            'oldSeqNo'              => $sequenceNo,
            'oldUsedCount'          => $usedCount,
            'newSeqNo'              => $this->upiMandate['sequence_number'],
            'newUsedCount'          => $this->upiMandate->getUsedCount(),
        ]);

        $this->repo->upi_mandate->saveOrFail($this->upiMandate);
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

        $gatewayRequest = [
            'seqNo'             => $this->upiMandate['sequence_number'],
            'merchantTranId'    => $this->createMerchantTranId($notification),
            'flow'              => $this->upiMandate['gateway_data']['flow']
        ];

        $notification->setGatewayMerchantId($terminal->getGatewayMerchantId());
        $notification->setGateway($gateway);
        $notification->setGatewayRequest($gatewayRequest);
        $this->repo->saveOrFail($notification);
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
            'gateway'   => $terminal->getGateway(),
            'recurring' => true,
            'method'    => 'upi'
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
            $this->processNotificationGatewayFailure($notification);

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
            $notification->setGatewayResponse($payerResponseCodeDes);
        }
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

        $this->eventOrderNotificationDelivered($notification);
    }

    public function processNotificationGatewayFailure($notification)
    {
        $notification->setStatus(Status::FAILED);

        $this->repo->saveOrFail($notification);

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
