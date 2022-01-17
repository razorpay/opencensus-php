<?php


namespace RZP\Models\Batch\Processor\Emandate\Cancel;

use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;
use RZP\Models\FileStore\Type;
use RZP\Models\Payment\Gateway;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\Status;

class EnachRbl extends Base
{
    protected $gateway = Gateway::ENACH_RBL;

    /**
     * @throws GatewayErrorException
     * @throws BadRequestException
     */
    protected function getDataFromRow($entry): array
    {
        $filePath = $entry['xml'];

        $xmlObject = simplexml_load_file($filePath);

        $undrlygAccptncDtls = $xmlObject->MndtAccptncRpt->UndrlygAccptncDtls;

        $paymentId = (string) $undrlygAccptncDtls->OrgnlMsgInf->MsgId;

        $accepted = (string) $undrlygAccptncDtls->AccptncRslt->Accptd;

        $errorReason = (string) $undrlygAccptncDtls->AccptncRslt->RjctRsn->Prtry;

        $umrn = (string) $undrlygAccptncDtls->OrgnlMndt->OrgnlMndtId;

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
        if (Status::isRegistrationSuccess($status) === true) {
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

    /**
     * @throws LogicException
     */
    protected function parseFileAndCleanEntries(string $filePath): array
    {
        $entries = $this->parseFile($filePath);

        return $this->cleanParsedEntries($entries);
    }

    protected function cleanParsedEntries(array $entries): array
    {
        return $entries;
    }

    protected function validateEntries(array &$entries, array $input)
    {
    }

    protected function createSetOutputFileAndSave(array &$entries, string $fileType = Type::BATCH_OUTPUT)
    {
    }
}
