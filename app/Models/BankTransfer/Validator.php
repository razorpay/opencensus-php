<?php

namespace RZP\Models\BankTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\VirtualAccount\Provider;

class Validator extends Base\Validator
{
    /**
     * @var Entity
     */
    protected $entity;

    const IFSC_LENGTH = 11;

    protected static $createRules = [
        Entity::PAYER_NAME     => 'nullable|string|max:100',
        Entity::PAYER_ACCOUNT  => 'nullable|string|max:40',
        Entity::PAYER_IFSC     => 'nullable|string',
        Entity::PAYEE_ACCOUNT  => 'required|string|max:20',
        Entity::PAYEE_IFSC     => 'required|string|size:'.self::IFSC_LENGTH,
        Entity::MODE           => 'required|custom',
        Entity::REQ_UTR        => 'required|string|max:30',
        Entity::TIME           => 'required',
        Entity::AMOUNT         => 'required|numeric|min:0',
        Entity::DESCRIPTION    => 'nullable|string|max:255',
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

    public function validatePaymentForRefund(Payment\Entity $payment)
    {
        $bankTransfer = $payment->bankTransfer;

        if ($bankTransfer->payerBankAccount === null)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer);
        }

        if ($bankTransfer->getPayeeIfsc() === Provider::IFSC[Provider::YESBANK])
        {
            throw new Exception\LogicException(
                    'Not refunding YesBank payments at the moment.',
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer->toArray());
        }
    }

    public function validateRefundIsAllowed()
    {
        $bankTransfer = $this->entity;

        if ($bankTransfer->getMode() === Mode::IMPS)
        {
            $ifsc = $bankTransfer->getPayerIfsc();

            $bankCode = substr($ifsc, 0, -10);

            // Refunds are not permitted for 2 cases:
            //  1. IMPS bank code is not mapped to ay valida IFSC
            //  2. Payer bank account is unknown
            if ((BankCodes::hasIfscMapping($bankCode) === false) or
                ($bankTransfer->getPayerBankAccountId() === null))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer);
            }
        }
    }
}
