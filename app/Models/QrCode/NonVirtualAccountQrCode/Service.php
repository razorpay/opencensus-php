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
        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, $input);

        $errorMessage = null;

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

        }
        catch (\Exception $ex)
        {
            $errorMessage = $ex->getMessage();

            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        }
        finally
        {
            (new Metric())->pushCreateMetrics($input, $errorMessage);
        }

        $this->handleReminderForQrCode($qrCode);

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

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
}
