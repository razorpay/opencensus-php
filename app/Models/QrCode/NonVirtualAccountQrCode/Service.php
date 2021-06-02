<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\QrCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Listeners\ApiEventSubscriber;

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
}
