<?php

namespace RZP\Models\Batch\Processor\Nach\Cancel;

use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Payment\Gateway;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\Status;

class NachCiti extends Base
{
    protected $gateway = Gateway::NACH_CITI;

    protected function getDataFromRow($entry): array
    {
        $filePath = $entry['xml'];

        $xmlObject = simplexml_load_file($filePath);

        $undrlygAccptncDtls = $xmlObject->MndtAccptncRpt->UndrlygAccptncDtls;

        $tokenId = (string) $undrlygAccptncDtls->OrgnlMsgInf->MsgId;

        $accepted = (string) $undrlygAccptncDtls->AccptncRslt->Accptd;

        $errorReason = (string) $undrlygAccptncDtls->AccptncRslt->RjctRsn->Prtry;

        $umrn = (string) $undrlygAccptncDtls->OrgnlMndt->OrgnlMndtId;

        $tokenStatus = $this->getTokenStatus($accepted, $tokenId, $errorReason);

        return [
            self::TOKEN_ID      => $tokenId,
            self::TOKEN_STATUS  => $tokenStatus,
            self::GATEWAY_TOKEN => $umrn,
        ];
    }

    /**
     * @throws BadRequestException
     * @throws GatewayErrorException
     */
    protected function getTokenStatus($status, $tokenId, $error): string
    {
        if (Status::isRegistrationSuccess($status) === true)
        {
            return Token\RecurringStatus::CANCELLED;
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                [
                    'gateway'  => $this->gateway,
                    'token_id' => $tokenId,
                    'error'    => $error,
                ]
            );
        }
    }

    protected function cleanParsedEntries(array $entries): array
    {
        return $entries;
    }

    protected function validateEntries(array & $entries, array $input)
    {
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = Type::BATCH_OUTPUT)
    {
    }
}
