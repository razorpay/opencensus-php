<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Models\QrCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Account;
use RZP\Models\QrCode\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Constants\Entity as ConstantEntity;

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

        try
        {
            $input[Entity::REQUEST_SOURCE] = $this->getRequestSourceViaAuth();

            $qrCode = (new Core)->buildQrCode($input);

            $this->publishQrCodeEvent($qrCode, Event::CREATED);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        }

        $this->handleReminderForQrCode($qrCode);

        $this->trace->info(TraceCode::QR_CODE_CREATED, $qrCode->toArrayPublic());

        return $qrCode->toArrayPublic();
    }

    public function createForCheckout($input)
    {
        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, $input);

        (new Validator())->validateInput('createForCheckout', $input);

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

                        $qrCode = $this->createForOrder($input, $order);

                        break;
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
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CREATE_REQUEST_FAILED, $input);

            throw $ex;
        }

        $this->handleReminderForQrCode($qrCode);

        $this->trace->info(TraceCode::QR_CODE_CHECKOUT_CREATED, $qrCode->toArrayPublic());

        return $qrCode->toArrayPublic();
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

    private function computeInputForQrOnCheckout(array $input, $order = null)
    {
        $createArray = [
            Entity::REQ_PROVIDER    => QrCode\Type::UPI_QR,
            Entity::REQ_USAGE_TYPE  => UsageType::SINGLE_USE,
            Entity::FIXED_AMOUNT    => true,
            Entity::REQUEST_SOURCE  => $this->getRequestSourceViaAuth(),
        ];

        if ($order !== null)
        {
            $createArray[Entity::REQ_AMOUNT] = $order->getAmountDue();
        }
        else
        {
            $createArray[Entity::REQ_AMOUNT] = $input[Entity::REQ_AMOUNT];
            $createArray[Entity::CLOSE_BY]   = Carbon::now(Timezone::IST)
                                                     ->addSeconds(Constants::NO_ORDER_CHECKOUT_QR_DEFAULT_EXPIRY_WINDOW)
                                                     ->getTimestamp();
        }

        return $createArray;
    }

    public function closeQrCode(string $id, $closeReason = CloseReason::ON_DEMAND)
    {
        $this->trace->info(TraceCode::QR_CODE_CLOSE_REQUEST, ['id' => $id]);

        try
        {
            $qrCode = (new Repository())->findByPublicIdAndMerchant($id, $this->merchant);

            if ($qrCode->isClosed() === true)
            {
                return $qrCode->toArrayPublic();
            }

            $qrCode = (new Core)->close($qrCode, $closeReason);

            $this->publishQrCodeEvent($qrCode, Event::CLOSED);

            return $qrCode->toArrayPublic();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::QR_CODE_CLOSE_REQUEST_FAILED, [
                'id' => $id
            ]);

            throw $ex;
        }
    }

    public function fetchMultiple($input)
    {
        $input[Entity::ENTITY_TYPE] = 'qr_code';

        $qrCodes = (new Repository)->fetch($input, $this->merchant->getId());

        return $qrCodes->toArrayPublic();
    }

    public function fetch($id)
    {
        $qrCode = (new Repository)->findByPublicIdAndMerchant($id, $this->merchant);

        if ($qrCode->source !== null)
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
