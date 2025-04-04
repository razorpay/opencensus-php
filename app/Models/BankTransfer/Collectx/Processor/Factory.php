<?php

namespace RZP\Models\BankTransfer\Collectx\Processor;

use RZP\Error\ErrorCode;
use RZP\Models\BankTransfer\Entity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;

class Factory
{
    /**
     * @throws BadRequestValidationFailureException
     */
    public static function getCollectxTransferProcessor($input, $provider, $requestPayload): BankTransfer|UpiTransfer
    {
        $mode = $input[Entity::MODE];

        if (in_array($mode, BankTransferConstants::COLLECTX_UPI_TRANSFER_MODES))
        {
            return new UpiTransfer($input, $provider, $requestPayload);
        }
        else if (in_array($mode, BankTransferConstants::COLLECTX_BANK_TRANSFER_MODES))
        {
            return new BankTransfer($input, $provider, $requestPayload);
        }
        else
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::INVALID_MODE_COLLECTX_TRANSFER,
                $mode);
        }
    }

    public static function getCollectxBankTransferProcessor($input, $provider, $requestPayload): BankTransfer
    {
        return new BankTransfer($input, $provider, $requestPayload);
    }

    public static function getCollectxUpiTransferProcessor($input, $provider, $requestPayload): UpiTransfer
    {
        return new UpiTransfer($input, $provider, $requestPayload);
    }
}
