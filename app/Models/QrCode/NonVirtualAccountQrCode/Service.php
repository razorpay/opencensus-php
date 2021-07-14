<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Razorpay\Trace\Logger;
use RZP\Models\QrCode;
use RZP\Models\QrCode\Constants;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\ConnectionType;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity;
use RZP\Models\Merchant\Account;

class Service extends QrCode\Service
{
    public function create($input, $virtualAccount = null)
    {
        $this->trace->info(TraceCode::QR_CODE_CREATE_REQUEST, $input);

        try
        {
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

        $qrCodes = (new Repository)->fetch($input, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE);

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
}
