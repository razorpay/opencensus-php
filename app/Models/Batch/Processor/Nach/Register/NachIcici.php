<?php

namespace RZP\Models\Batch\Processor\Nach\Register;

use RZP\Gateway\Netbanking;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Payment\Gateway;
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

        $umrn = (string)$acceptDetails->OrgnlMndt->OrgnlMndtId;

        $accepted = (string)$acceptDetails->AccptncRslt->Accptd;

        $gatewayErrorCode = (string)$acceptDetails->AccptncRslt->RjctRsn->Prtry;

        return [
            self::GATEWAY_TOKEN         => $umrn,
            self::TOKEN_STATUS          => $this->getTokenStatus($accepted, []),
            self::PAYMENT_ID            => $paymentId,
            self::TOKEN_ERROR_CODE      => $this->getTokenErrorMessage($accepted, [self::GATEWAY_ERROR_CODE => $gatewayErrorCode]),
            self::GATEWAY_ERROR_CODE    => $gatewayErrorCode,
        ];
    }

    protected function getTokenStatus(string $gatewayTokenStatus, array $content): string
    {
        if (Status::isRegistrationSuccess($gatewayTokenStatus) === true)
        {
            return Token\RecurringStatus::CONFIRMED;
        }
        return Token\RecurringStatus::REJECTED;
    }

    protected function getTokenErrorMessage(string $gatewayTokenStatus, array $entry)
    {
        $status = $this->getTokenStatus($gatewayTokenStatus, $entry) ;

        if ($status === Token\RecurringStatus::CONFIRMED)
        {
            return null;
        }
        else
        {
            return $this->getApiErrorCode($entry);
        }
    }

    protected function getApiErrorCode(array $content): string
    {
        return ErrorCodes::getRegisterInternalErrorCode($content[self::GATEWAY_ERROR_CODE]);
    }

    protected function getGatewayErrorDesc(array $content): string
    {
        return ErrorCodes::getRegisterPublicErrorDescription($content[self::GATEWAY_ERROR_CODE]);
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
