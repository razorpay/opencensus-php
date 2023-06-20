<?php

namespace RZP\Models\Batch\Processor\Nach\Acknowledge;

use RZP\Error\ErrorCode;
use RZP\Gateway\Netbanking;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Payment\Gateway;
use RZP\Exception\BadRequestException;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\Status;

class NachIcici extends Base
{
    protected $gateway = Gateway::NACH_ICICI;

    protected function getDataFromRow(array $entry): array
    {
        $filePath = $entry['xml'];

        $xmlObject = simplexml_load_file($filePath);

        $acceptDetails = $xmlObject->MndtAccptncRpt->UndrlygAccptncDtls;

        $paymentId = (string)$acceptDetails->OrgnlMsgInf->MsgId;

        $umrn = (string)$acceptDetails->OrgnlMndt->OrgnlMndt->MndtId;

        $accepted = (string)$acceptDetails->AccptncRslt->Accptd;

        $errorDesc = (string)$acceptDetails->AccptncRslt->RjctRsn->Prtry;

        $tokenStatus = $this->getTokenStatus($accepted, $paymentId, $errorDesc);

        return [
            self::GATEWAY_TOKEN => $umrn,
            self::TOKEN_STATUS  => $tokenStatus,
            self::PAYMENT_ID    => $paymentId,
        ];
    }

    protected function getTokenStatus($status, $paymentId, $error): string
    {
        if (Status::isRegistrationSuccess($status) === true)
        {
            return Token\RecurringStatus::INITIATED;
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_NACH_REGISTRATION_FAILED,
                null,
                [
                    'gateway'    => $this->gateway,
                    'payment_id' => $paymentId,
                    'error'      => $error,
                ]
            );
        }
    }

    protected function validateParsedData($data)
    {
        foreach ($data as $key => $value)
        {
            if (empty($value) === true)
            {
                throw new BadRequestException(
                    ErrorCode::GATEWAY_ERROR_INVALID_DATA,
                    $key,
                    [
                        'gateway'       => $this->gateway,
                        'invalid_field' => $key,
                        'field_value'   => $value,
                    ]
                );
            }
        }
    }

    protected function cleanParsedEntries(array $entries): array
    {
        return $entries;
    }

    protected function validateEntries(array & $entries, array $input)
    {
        return;
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = Type::BATCH_OUTPUT)
    {
        return;
    }
}
