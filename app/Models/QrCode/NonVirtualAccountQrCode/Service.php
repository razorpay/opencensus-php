<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants\HyperTrace;
use RZP\Models\Checkout\Order\Entity as CheckoutOrder;
use RZP\Models\Order\Entity as Order;
use RZP\Models\QrCode;
use RZP\Models\QrPayment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\QrCode\Metric;
use RZP\Models\Merchant\Account;
use RZP\Models\QrCode\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\QrPayment\Service as QrPaymentService;
use RZP\Trace\Tracer;

class Service extends QrCode\Service
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create($input, $virtualAccount = null)
    {
        $startTimeMs = microtime(true) * 1000;

        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, $input);

        $errorMessage = null;

        $gateway = null;

        $metric = new Metric();

        try
        {
            $input[Entity::REQUEST_SOURCE] = $input[Entity::REQUEST_SOURCE] ?? $this->getRequestSourceViaAuth();

            if ((new Generator())->checkIfDedicatedTerminalSplitzExperimentEnabled($this->merchant->getId()) === true)
            {
                (new Validator)->validateQrOnDedicatedTerminal($input);
            }

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE], function () use ($input) {
                return (new Core)->buildQrCode($input);
            });

            $this->publishQrCodeEvent($qrCode, Event::CREATED);

            $gateway = $qrCode->getGatewayFromQrString();

            $input[Entity::GATEWAY] = $gateway;
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        }
        finally
        {
            $metric->pushCreateMetrics($input, $errorMessage);
        }

        $this->handleReminderForQrCode($qrCode);

        // Since this is inside NonVirtualAccountQrCode/Service, it is safe to assume that only qrV2 are checked here
        if (($qrCode->getUsageType() === UsageType::SINGLE_USE) and
            ($qrCode->getProvider() === QrCode\Type::UPI_QR) and
            (($gateway === \RZP\Models\Payment\Gateway::UPI_ICICI) or
             ($gateway === \RZP\Models\Payment\Gateway::UPI_YESBANK)) and
            ((new Generator())->checkIfDedicatedTerminalSplitzExperimentEnabled($qrCode->getMerchantId()) === true))
        {
            $this->triggerQrStatusCheckPostCreate($qrCode);
        }

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

        $metric->pushCreateLatencyMetrics($input, $startTimeMs, $qrCode->getGatewayLatencyForQrCreate());

        return $qrCode->toArrayPublic();
    }

    public function createForCheckout($input)
    {
        $this->trace->info(TraceCode::QR_CODE_CHECKOUT_CREATE_REQUEST, $input);

        (new Validator())->validateInput('createForCheckout', $input);

        $errorMessage = null;

        try
        {
            if (array_key_exists(Entity::ENTITY_TYPE, $input))
            {
                switch ($input[Entity::ENTITY_TYPE])
                {
                    case ConstantEntity::ORDER:
                        $order = $this->repo->order->findByPublicIdAndMerchant($input[Entity::ENTITY_ID], $this->merchant);

                        if ($order->isPaid() === true)
                        {
                            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_DISALLOWED_FOR_ORDER);
                        }

                        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE_FOR_CHECKOUT_SERVICE], function () use ($input, $order) {
                            return $this->createForOrder($input, $order);
                        });

                        break;
                    case ConstantEntity::CHECKOUT_ORDER:
                        $checkoutOrder = $this->repo->checkout_order->findByPublicIdAndMerchant(
                            $input[Entity::ENTITY_ID], $this->merchant
                        );

                        $qrCode = Tracer::inspan(
                            ['name' => HyperTrace::QR_CODE_CREATE_FOR_CHECKOUT_SERVICE],
                            function () use ($input, $checkoutOrder) {
                                return $this->createForCheckoutOrder($input, $checkoutOrder);
                        });
                }
            }
            else
            {
                $createArray = $this->computeInputForQrOnCheckout($input);

                $qrCode = (new Core)->buildQrCode($createArray);
            }
        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        } finally {
            $input = array_merge($input, [
                Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
                Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
                Entity::REQUEST_SOURCE  => RequestSource::CHECKOUT,
            ]);

            (new Metric())->pushCreateMetrics($input, $errorMessage);
        }

        $this->handleReminderForQrCode($qrCode);

        // TODO: Add status check here, use a separate config for checkout QRs maybe?

        if ($qrCode->isCheckoutQrCode()) {
            (new QrPayment\Service())->setQrCodeStatusAndPaymentIdInCache($qrCode);
        }

        $this->trace->info(TraceCode::QR_CODE_CHECKOUT_CREATED, $qrCode->toArrayPublic());

        return $qrCode->toArrayPublic();
    }

    /**
     * Create a QrCode entity for a checkout order.
     *
     * @param array $input
     * @param CheckoutOrder $checkoutOrder
     *
     * @return Entity
     *
     * @throws BadRequestException
     */
    private function createForCheckoutOrder(array $input, CheckoutOrder $checkoutOrder): Entity
    {
        if ($checkoutOrder->isClosed())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_DISALLOWED_FOR_ORDER);
        }

        $qrCode = $this->repo->qr_code->findActiveQrCodeByCheckoutOrder($checkoutOrder);

        if ($qrCode !== null)
        {
            return $qrCode;
        }

        $createArray = $this->computeInputForQrOnCheckout($input, $checkoutOrder);

        return (new Core())->buildQrCode($createArray, $checkoutOrder);
    }

    private function createForOrder($input, $order)
    {
        return $this->mutex->acquireAndRelease(
            $order->getId(),
            function() use ($order, $input)
            {
                $qrCode = $this->repo->qr_code->findActiveQrCodeByOrder($order);

                if ($qrCode !== null)
                {
                    return $qrCode;
                }

                $createArray = $this->computeInputForQrOnCheckout($input, $order);

                return (new Core)->buildQrCode($createArray, $order);
            },
            60,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);
    }

    /**
     * @param array $input
     * @param CheckoutOrder|Order|null $order
     *
     * @return array
     */
    private function computeInputForQrOnCheckout(array $input, $order = null): array
    {
        $createArray = [
            Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
            Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
            Entity::FIXED_AMOUNT    => true,
            Entity::REQUEST_SOURCE  => RequestSource::CHECKOUT,
        ];

        if ($order !== null)
        {
            if ($order instanceof Order) {
                $createArray[Entity::REQ_AMOUNT] = $order->getAmountDue();
            }

            if ($order instanceof CheckoutOrder) {
                $createArray[Entity::REQ_AMOUNT] = $order->getFinalAmount();
                $createArray[Entity::CLOSE_BY] = $order->getExpireAt();
            }
        }
        else
        {
            $createArray[Entity::REQ_AMOUNT] = $input[Entity::REQ_AMOUNT];
            $createArray[Entity::CLOSE_BY]   = Carbon::now(Timezone::IST)
                                                     ->addSeconds(Constants::NO_ORDER_CHECKOUT_QR_DEFAULT_EXPIRY_WINDOW)
                                                     ->getTimestamp();
        }

        $additionalAttributes = [Entity::CUSTOMER_ID, Entity::DESCRIPTION, Entity::NAME, Entity::NOTES];

        foreach ($additionalAttributes as $attribute) {
            if (!empty($input[$attribute])) {
                $createArray[$attribute] = $input[$attribute];
            }
        }

        return $createArray;
    }

    public function closeQrCode(string $id, $closeReason = CloseReason::ON_DEMAND)
    {
        $this->trace->info(TraceCode::QR_CODE_CLOSE_REQUEST, ['id' => $id]);

        $errorMessage = null;

        $variant = $this->app->razorx->getTreatment($this->merchant->getId(), RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE, $this->mode);

        if ((strtolower($variant) === RazorxTreatment::RAZORX_VARIANT_ON)
            and ($this->merchant->isFeatureEnabled(FeatureConstants::CLOSE_QR_ON_DEMAND) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);
        }

        try
        {
            $qrCode = (new Repository())->findByPublicIdAndMerchant($id, $this->merchant);

            if ($qrCode->isClosed() === true)
            {
                return $qrCode->toArrayPublic();
            }

            if ((strtolower($variant) === RazorxTreatment::RAZORX_VARIANT_ON) and (str_contains($qrCode['qr_string'], '@icici') === false))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ON_DEMAND_QR_CODE_DISABLED);
            }

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODES_CLOSE_QR_CODE], function () use ($qrCode, $closeReason) {
                return (new Core)->close($qrCode, $closeReason);
            });

            $this->publishQrCodeEvent($qrCode, Event::CLOSED);

            return $qrCode->toArrayPublic();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CLOSE_REQUEST_FAILED, [
                'id' => $id
            ]);

            $errorMessage = $ex->getMessage();

            throw $ex;
        }
        finally
        {
            $requestSource = $qrCode ? $qrCode->getRequestSource() : null;

            (new Metric())->pushCloseMetrics($closeReason, $errorMessage, $requestSource);
        }
    }

    public function fetchMultiple($input)
    {
        if (array_key_exists(QrPayment\Entity::PAYMENT_ID, $input))
        {
            if (count($input) > 1)
            {
                $this->trace->info(TraceCode::QR_CODE_FETCH_MULTIPLE_KEYS_SUPPLIED_WITH_PAYMENT_ID, $input);
            }

            $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODES_FETCH_MULTIPLE_PAYMENT_ID], function () use ($input) {
                return (new Repository())->fetchQrCodeForPaymentId($input[QrPayment\Entity::PAYMENT_ID], $this->merchant->getId());
            });

            return $qrCode->toArrayPublic();
        }

        $input[Entity::ENTITY_TYPE] = 'qr_code';

        $qrCodes = Tracer::inspan(['name' => HyperTrace::QR_CODES_FETCH_MULTIPLE_FETCH_ALL], function () use ($input) {
            return (new Repository)->fetch($input, $this->merchant->getId());
        });

        return $qrCodes->toArrayPublic();
    }

    public function fetch($id)
    {
        $qrCode = Tracer::inspan(['name' => HyperTrace::QR_CODES_FETCH], function () use ($id) {
            return (new Repository)->findByPublicIdAndMerchant($id, $this->merchant);
        });

        if ($this->merchant->isFeatureEnabled(FeatureConstants::UPIQR_V1_HDFC) !== true
            and $qrCode->source !== null )
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NON_EXISTING_QR_CODE_ID, Entity::ID, [$id]);
        }

        return $qrCode->toArrayPublic();
    }

    public function publishQrCodeEvent($entity, $event)
    {
        try
        {
            $eventPayload = [
                ApiEventSubscriber::MAIN => $entity
            ];

            Event::checkEvent($event);

            $event = 'api.qr_code.' . $event;

            $this->app['events']->dispatch($event, $eventPayload);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::QR_CODE_WEBHOOK_PUBLISH_FAILED, [
                'entity' => $entity->toArrayPublic(),
                'event'  => $event
            ]);
        }
    }

    public function handleReminderForQrCode($qrCode)
    {
        if (empty($qrCode->getCloseBy()))
        {
            return;
        }

        try
        {
            $request = [
                'entity_id'     => $qrCode->getId(),
                'namespace'     => Constants::REMINDER_NAMESPACE,
                'entity_type'   => Constants::REMINDER_ENTITY_NAME,
                'reminder_data' => [ENTITY::CLOSE_BY => $qrCode->getCloseBy()],
                'callback_url'  => $this->getCallbackUrlForReminder($qrCode),
            ];

            $merchantId = Account::SHARED_ACCOUNT;

            $response = $this->app['reminders']->createReminder($request, $merchantId);

            $this->trace->info(TraceCode::QR_CODE_REMINDER_RESPONSE, $response);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_REMINDER_CREATION_FAILED, $request);
        }
    }

    public function getCallbackUrlForReminder($qrCode)
    {
        $baseUrl     = Constants::REMINDER_BASE_URL;

        $mode        = $this->mode;

        $entity      = Constants::REMINDER_ENTITY_NAME;

        $namespace   = Constants::REMINDER_NAMESPACE;

        $qrCodeId    = $qrCode->getPublicId();

        return sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $qrCodeId);
    }

    public function getStatusCheckCallbackUrlForReminder($qrCode)
    {
        $baseUrl     = Constants::REMINDER_BASE_URL;

        $mode        = $this->mode;

        $entity      = Constants::REMINDER_ENTITY_NAME;

        $namespace   = Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK;

        $qrCodeId    = $qrCode->getPublicId();

        return sprintf('%s/%s/%s/%s/%s', $baseUrl, $mode, $entity, $namespace, $qrCodeId);
    }

    private function getRequestSourceViaAuth()
    {
        if ($this->auth->isPublicAuth())
        {
            return RequestSource::CHECKOUT;
        }
        elseif ($this->auth->isProxyAuth())
        {
            return RequestSource::DASHBOARD;
        }
        else
        {
            return RequestSource::API;
        }
    }

    public function triggerQrStatusCheckPostCreate(Entity $qrCode): void
    {
        try
        {
            $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_INIT, ['id' => $qrCode->getId()]);

            // Find the env variable QR_CODE_STATUS_CHECK_SPLITZ_EXPERIMENT_ID to find experiment IDs for different envs
            if ($this->evaluateQrCodeEligibilityViaSplitzForStatusCheck($qrCode) === false)
            {
                return;
            }

            $request = [
                'entity_id'     => $qrCode->getId(),
                'namespace'     => Constants::REMINDER_NAMESPACE_FOR_STATUS_CHECK,
                'entity_type'   => Constants::REMINDER_ENTITY_NAME,
                // Add 180 secs to signify sending reminder after 3 mins of create
                'reminder_data' => [ENTITY::CREATED_AT => $qrCode->getCreatedAt()],
                'callback_url'  => $this->getStatusCheckCallbackUrlForReminder($qrCode),
            ];

            $merchantId = Account::SHARED_ACCOUNT;

            $response = $this->app['reminders']->createReminder($request, $merchantId);

            $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_REMINDER_RESPONSE, $response);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex,
                Trace::CRITICAL,
                TraceCode::QR_CODE_STATUS_CHECK_REMINDER_CREATION_FAILED,
                $request);
        }
    }

    public function evaluateQrCodeEligibilityViaSplitzForStatusCheck(Entity $qrCode): bool
    {
        try
        {
            $properties = [
                'id'            => $qrCode->getMerchantId(),
                'experiment_id' => $this->app['config']->get('app.qr_code_status_check_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $qrCode->getMerchantId()]),
            ];
            $response   = $this->app['splitzService']->evaluateRequest($properties);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'merchant_id'   => $qrCode->getMerchantId(),
                '$response'     => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if (($key == "result") and
                        ($value == "on"))
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::QR_CODE_STATUS_CHECK_SPLITZ_EVALUATE_ERROR
            );
        }

        return false;
    }

    /**
     * Check if it has been 12 hours since QR code creation, we will not do QR Code status check post this.
     * We expect the recon flow to now bring in any payment data later if needed.
     *
     * @param Entity $qrCode The QR Code entity to check
     * @return bool Returns true if the time has exceeded
     */
    protected function checkIfQrCodeStatusCheckTimeHasExceeded(Entity $qrCode): bool
    {
        $qrCodeCreatedAt     = Carbon::createFromTimestamp($qrCode->getCreatedAt());
        $currentTime         = Carbon::now();

        // if the current time and time of creation are more than 12 hours apart, we stop status check.
        if ($currentTime->diffInHours($qrCodeCreatedAt) > 12)
        {
            return true;
        }

        return false;
    }

    /**
     * When we receive a callback from Reminders, we check if we actually need to perform a status check on the QR or
     * not. We perform the following checks-
     * 1. If a payment already exists against the QR or not.
     * 2. If the QR has expired or not.
     * 3. If it has been too long since the creation of the QR or not.
     *
     * @param string $qrCodeId The ID of the QR code to be validated
     * @return bool Returns true if the QR needs to be dispatched for Status Check
     */
    public function validateQrForStatusCheckInit(string $qrCodeId): bool
    {
        /**
         * @var $qrCode Entity
         */
        $qrCode = $this->repo->qr_code->find($qrCodeId);

        if (empty($qrCode) === true)
        {
            $this->trace->info(
                TraceCode::QR_CODE_NOT_FOUND,
                [
                    'id' => $qrCodeId,
                ]
            );

            return false;
        }
        $this->app['basicauth']->setMerchantById($qrCode->getMerchantId());

        // Check if there are no payments associated
        if ($qrCode->getPaymentsCountReceived() > 0) {

            $this->trace->info(
                TraceCode::PAYMENT_ALREADY_EXISTS_FOR_QR_CODE,
                [
                    'qr_code_id' => $qrCodeId,
                ]
            );

            return false;
        }

        if ($qrCode->isClosed() === true)
        {
            $this->trace->info(
                TraceCode::QR_CODE_CLOSED,
                [
                    'qr_code_id' => $qrCodeId,
                ]
            );

            return false;
        }

        if ($this->checkIfQrCodeStatusCheckTimeHasExceeded($qrCode) === true)
        {
            $this->trace->info(
                TraceCode::QR_CODE_STATUS_CHECK_TIME_EXCEEDED,
                [
                    'qr_code_id' => $qrCodeId,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $id The ID of the QR code to be checked
     * @param array $input The input received in the Reminders service callback request
     * @return bool To be consumed by the Reminders service. True indicates no reminders are needed further
     */
    public function initQrStatusCheck(string $id, array $input): bool
    {
        Entity::silentlyStripSign($id);

        $response = false;

        // If the validations fail, we return true to reminders to stop sending further reminders
        if ($this->validateQrForStatusCheckInit($id) === false)
        {
            $response = true;
        }
        else
        {
            $response = (new Core())->dispatchQrCodeToStatusCheckQueue($id);
        }

        $this->trace->info(TraceCode::QR_STATUS_CHECK_RESPONSE, ['id' => $id, 'response' => $response]);

        return $response;
    }

    public function triggerQrStatusCheckForPaymentFetch(string $id): void
    {
        $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_INIT_IN_PAYMENT_FETCH, ['id' => $id]);

        Entity::silentlyStripSign($id);

        /**
         * @var $qrCode Entity
         */
        $qrCode = $this->repo->qr_code->find($id);

        // If the QR code is not found, no point of dispatching it for status check
        if ($qrCode === null)
        {
            $this->trace->info(TraceCode::QR_CODE_NOT_FOUND, ['id' => $id]);
            return;
        }

        // If it has not yet been 3 minutes between QR Code create and now, don't dispatch for status check
        if (abs((Carbon::now(Timezone::IST)->timestamp) - $qrCode->getCreatedAt()) <= 180)
        {
            $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_TIME_TOO_EARLY, ['id' => $id]);
            return;
        }

        if (($qrCode->getUsageType() === UsageType::SINGLE_USE) and
            ($qrCode->getProvider() === QrCode\Type::UPI_QR) and
            (($qrCode->getGatewayFromQrString() === \RZP\Models\Payment\Gateway::UPI_ICICI) or
             ($qrCode->getGatewayFromQrString() === \RZP\Models\Payment\Gateway::UPI_YESBANK)) and
            ((new Generator())->checkIfDedicatedTerminalSplitzExperimentEnabled($qrCode->getMerchantId()) === true))
        {
            // Find the env variable QR_CODE_STATUS_CHECK_SPLITZ_EXPERIMENT_ID to find experiment IDs for different envs
            if ($this->evaluateQrCodeEligibilityViaSplitzForStatusCheck($qrCode) === false)
            {
                return;
            }

            // After dispatch, when the worker picks the message up, the worker performs other validations too
            // Since the dispatch step has a unique job check, we won't be dispatching multiple messages for the same
            // QR code at once.
            (new Core())->dispatchQrCodeToStatusCheckQueue($id);
        }
    }
}
