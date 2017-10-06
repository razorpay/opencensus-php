<?php

namespace RZP\Models\BankTransfer;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\VirtualAccount;
use RZP\Models\Feature\Constants as Feature;

class Validator extends Base\Validator
{
    /**
     * @var Entity
     */
    protected $entity;

    const IFSC_LENGTH = 11;

    protected static $createRules = [
        Entity::PAYER_NAME     => 'sometimes|string|max:100',
        Entity::PAYER_ACCOUNT  => 'sometimes|string|max:20',
        Entity::PAYER_IFSC     => 'sometimes|string',
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

    public function validatePaymentForRefund(Payment\Entity $payment)
    {
        $bankTransfer = $payment->bankTransfer;

        if (empty($bankTransfer->getPayerAccount()) === true)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer);
        }
    }

    public function validateRefundIsAllowed()
    {
        $bankTransfer = $this->entity;

        // Refunds currently not permitted for IMPS payments
        if ($bankTransfer->getMode() === Mode::IMPS)
        {
            $ifsc = $bankTransfer->getPayerIfsc();

            $bankCode = substr($ifsc, 0, -10);

            if (BankCodes::hasIfscMapping($bankCode) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $bankTransfer);
            }
        }
    }

    public function validateReassignment(
        Merchant\Entity $merchant,
        string $provider)
    {
        $bankTransfer = $this->entity;

        if (($bankTransfer->payment->isAuthorized() === false) or
            ($bankTransfer->payment->hasTransaction() === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment must be in authorized state',
                'status',
                [
                    'bank_transfer_id' => $bankTransfer->getPublicId(),
                    'status'           => $bankTransfer->payment->getStatus(),
                    'transaction_id'   => $bankTransfer->payment->getTransactionId(),
                ]);
        }

        $defaultMerchantId = Processor::getDefaultMerchantId();

        if ($bankTransfer->getMerchantId() !== $defaultMerchantId)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Only payments made to demo merchant can be reassigned',
                'merchant_id',
                [
                    'bank_transfer_id'    => $bankTransfer->getPublicId(),
                    'merchant_id'         => $bankTransfer->getMerchantId(),
                    'default_merchant_id' => $defaultMerchantId,
                ]);
        }

    }

    public function validateReassignmentTarget(
        Merchant\Entity $merchant,
        VirtualAccount\Entity $virtualAccount)
    {
        if ($merchant->isFeatureEnabled(Feature::VIRTUAL_ACCOUNTS) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Target merchant does not have virtual accounts enabled',
                'merchant_id',
                [
                    'merchant_id'         => $merchant->getPublicId(),
                    'virtual_account_id'  => $virtualAccount->getPublicId(),
                ]);
        }

        if (($virtualAccount === null) or
            ($virtualAccount->getMerchantId() !== $merchant->getPublicId()))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Merchant does not have a virtual account to receive the payment',
                'merchant_id',
                [
                    'merchant_id'         => $merchant->getPublicId(),
                    'virtual_account_id'  => $virtualAccount->getPublicId(),
                ]);
        }
    }
}
