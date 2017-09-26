<?php

namespace RZP\Models\BankTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;

class Validator extends Base\Validator
{
    const IFSC_LENGTH = 11;

    protected static $createRules = [
        Entity::PAYER_NAME     => 'sometimes|string|max:100',
        Entity::PAYER_ACCOUNT  => 'required|string|max:20',
        Entity::PAYER_IFSC     => 'required|string',
        Entity::PAYEE_ACCOUNT  => 'required|string|max:20',
        Entity::PAYEE_IFSC     => 'required|string|size:'.self::IFSC_LENGTH,
        Entity::MODE           => 'required|custom',
        Entity::REQ_UTR        => 'required|string|max:30',
        Entity::TIME           => 'required',
        Entity::AMOUNT         => 'required|integer|min:0',
        Entity::DESCRIPTION    => 'sometimes|string|max:100',
    ];

    protected static $createValidators = [
        Entity::PAYER_IFSC,
    ];

    protected function validateMode($attribute, $mode)
    {
        if (Mode::isValid($mode) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid bank transfer mode',
                $attribute,
                $mode);
        }
    }

    protected function validatePayerIfsc($input)
    {
        // We currently aren't getting the actual payer_ifsc for IMPS payments.
        if ((strlen($input[Entity::PAYER_IFSC]) !== self::IFSC_LENGTH) and
            ($input[Entity::MODE] !== Mode::IMPS))
        {
            throw new Exception\BadRequestValidationFailureException(
                'IFSC is of invalid length',
                Entity::PAYER_IFSC,
                $input[Entity::PAYER_IFSC]);
        }
    }

    public function validateRefundIsAllowed()
    {
        $bankTransfer = $this->entity;

        // Refunds currently not permitted for IMPS payments
        if ($bankTransfer->getMode() === Mode::IMPS)
        {
            $ifsc = $bankTransfer->getPayerIfsc();

            $bankCode = substr($ifsc, 0, 3);

            if (BankCodes::hasIfscMapping($bankCode) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer);
            }
        }
    }
}
