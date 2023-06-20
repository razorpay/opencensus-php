<?php

namespace RZP\Models\Batch\Processor\Nach\Acknowledge;

use RZP\Error\ErrorCode;
use RZP\Gateway\Netbanking;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Payment\Gateway;
use RZP\Exception\BadRequestException;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\Status;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\ErrorCodes;

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

        $tokenStatus = $this->getTokenStatus($accepted);

        return [
            self::GATEWAY_TOKEN      => $umrn,
            self::TOKEN_STATUS       => $tokenStatus,
            self::PAYMENT_ID         => $paymentId,
            self::GATEWAY_ERROR_CODE => $errorDesc,
            self::TOKEN_ERROR_CODE   => $this->getTokenErrorMessage($tokenStatus, [self::GATEWAY_ERROR_CODE => $errorDesc])
        ];
    }

    protected function getTokenStatus($status): string
    {
        if (Status::isRegistrationSuccess($status) === true)
        {
            return Token\RecurringStatus::INITIATED;
        }
        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $tokenStatus, array $entry)
    {
        if ($tokenStatus === Token\RecurringStatus::INITIATED)
        {
            return null;
        }
        return $this->getApiErrorCode($entry);
    }

    protected function getApiErrorCode(array $content): string
    {
        return ErrorCodes::getRegisterInternalErrorCode($content[self::GATEWAY_ERROR_CODE]);
    }

    protected function validateParsedData(array $parsedData)
    {
        if ((empty($parsedData[self::PAYMENT_ID]) === true) or (empty($parsedData[self::TOKEN_STATUS]) === true))
        {
            throw new BadRequestException(
                ErrorCode::GATEWAY_ERROR_INVALID_DATA,
                null,
                [
                    'data'       => $parsedData,
                ],
                'Either payment id or token status is not valid'
            );
        }

        if((($parsedData[self::TOKEN_STATUS] === Token\RecurringStatus::INITIATED) and (empty($parsedData[self::GATEWAY_TOKEN]) === true))
            or (($parsedData[self::TOKEN_STATUS] === Token\RecurringStatus::REJECTED) and (empty($parsedData[self::TOKEN_ERROR_CODE]) === true)))
        {
            throw new BadRequestException(
                ErrorCode::GATEWAY_ERROR_INVALID_DATA,
                null,
                [
                    'data'       => $parsedData,
                ],
                'umrn or error code not there as per token status'
            );
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
