<?php

namespace RZP\Models\Batch\Processor\Emandate\Cancel;

use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Payment\Gateway;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\Status;

class EnachNpciNetbanking extends Base
{
    protected $gateway = Gateway::ENACH_NPCI_NETBANKING;

    protected function getDataFromRow($xmlObject): array
    {
        $undrlygAccptncDtls = $xmlObject['UndrlygAccptncDtls'];

        $paymentId = $undrlygAccptncDtls['OrgnlMsgInf']['MsgId'];

        $accepted = $undrlygAccptncDtls['AccptncRslt']['Accptd'];

        $errorReason = $undrlygAccptncDtls['AccptncRslt']['RjctRsn']['Prtry'];

        $umrn = $undrlygAccptncDtls['OrgnlMndt']['OrgnlMndtId'];

        $tokenStatus = $this->getTokenStatus($accepted, $paymentId, $errorReason);

        return [
            self::PAYMENT_ID    => $paymentId,
            self::TOKEN_STATUS  => $tokenStatus,
            self::GATEWAY_TOKEN => $umrn,
        ];
    }

    /**
     * @throws BadRequestException
     * @throws GatewayErrorException
     */
    protected function getTokenStatus($status, $paymentId, $error): string
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
                    'gateway'    => $this->gateway,
                    'payment_id' => $paymentId,
                    'error'      => $error,
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
