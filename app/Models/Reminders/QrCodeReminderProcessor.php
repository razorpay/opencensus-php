<?php

namespace RZP\Models\Reminders;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\QrCode\Constants as QrCodeConstants;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Core;
use RZP\Models\QrCode\NonVirtualAccountQrCode\CloseReason;
use Razorpay\Trace\Logger as Trace;

class QrCodeReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $input): array
    {
        if ($namespace === self::QR_CODE_PAYMENT_STATUS)
        {
            return $this->processForStatusCheck($id, $input);
        }

        $this->trace->info(TraceCode::QR_CODE_CLOSE_REQUEST_REMINDER, ['id' => $id]);

        try
        {
            $qrCode = $this->repo->qr_code->findByPublicId($id);

            (new Core)->close($qrCode, CloseReason::EXPIRED);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::QR_CODE_CLOSE_BY_REMINDER_REQUEST_FAILED, [
                'id' => $id
            ]);

            return ['success' => false];
        }

        return ['success' => true];
    }

    public function processForStatusCheck(string $id, array $input)
    {
        // The function does nothing for now
        // TODO: This must change when we write the core logic for QR Status check

        $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_CALLBACK_INIT, [
            'id'    => $id,
            'input' => $input,
        ]);

        return ['success' => true];
    }
}

