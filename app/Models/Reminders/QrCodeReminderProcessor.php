<?php

namespace RZP\Models\Reminders;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Core;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Service;
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

            if ($qrCode->isClosed() === false)
            {
                (new Core)->close($qrCode, CloseReason::EXPIRED);
            }
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

    /**
     * @throws BadRequestException If we want to stop the entire reminder flow
     */
    public function processForStatusCheck(string $id, array $input)
    {
        $this->trace->info(TraceCode::QR_CODE_STATUS_CHECK_CALLBACK_INIT, [
            'id'    => $id,
            'input' => $input,
        ]);

        $response = (new Service())->initQrStatusCheck($id, $input);

        return $this->parseResponseForStatusCheck($response);
    }

    /**
     * Reminders service needs a 400 with a specific error code to stop reminders callback. Otherwise, a 2xx will make
     * reminders to continue sending reminders. This function handles this depending on how the internal business layer
     * returns its response.
     * @param bool $response
     * @return true[] If we want to continue the reminders callback
     * @throws BadRequestException with ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE if we want to stop the reminders
     */
    protected function parseResponseForStatusCheck(bool $response): array
    {
        // If the response is returned true from the business layer, this means we want to stop reminders callback
        // Reminders service needs a 400 error code with ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE for this
        if ($response === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE, null,
                [
                    'error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE
                ]);
        }

        // If the response is false, this means we want to continue getting reminders callback
        // Reminders service needs a 2xx for this
        return ['success' => $response];
    }
}

