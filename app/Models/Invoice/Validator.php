<?php

namespace RZP\Models\Invoice;

use RZP\Base;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{

    const CREATE_DRAFT  = 'createDraft';
    const CREATE_ISSUED = 'createIssued';

    protected static $createRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',

        // Entity::DUE_BY              => 'sometimes|integer',
        // Entity::SCHEDULED_AT        => 'sometimes|integer',

        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|integer',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
        Entity::DRAFT               => 'sometimes|boolean',
    ];

    // This is redundant and same as $createRules but keeping it as it keeps code
    // at other places clean
    protected static $createDraftRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',

        // Entity::DUE_BY              => 'sometimes|integer',
        // Entity::SCHEDULED_AT        => 'sometimes|integer',

        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|integer',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
        Entity::DRAFT               => 'sometimes|boolean',
    ];

    protected static $createIssuedRules = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|integer',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes|array',
        Entity::CUSTOMER_ID         => 'sometimes|public_id|size:19',
        Entity::LINE_ITEMS          => 'sometimes|array',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::DESCRIPTION         => 'required_with:amount|string|max:2048',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
        Entity::DRAFT               => 'sometimes|in:0',
    ];

    protected static $editDraftRules  = [
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|integer',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes',
        Entity::CUSTOMER_ID         => 'sometimes|string|size:19',
        Entity::AMOUNT              => 'sometimes|integer|min:100|max:50000000',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
    ];

    protected static $editIssuedRules  = [
        Entity::DATE                => 'sometimes|integer',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::RECEIPT             => 'sometimes|string|min:1|max:40',
    ];

    protected static $editDraftValidators = [
        Entity::AMOUNT,
    ];

    protected static $createIssuedValidators = [
        Entity::LINE_ITEMS,
    ];

    public function validateAmount(array $input)
    {
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        $invoice = $this->entity;

        if ($invoice->lineItems()->count())
        {
            throw new BadRequestValidationFailureException(
                'Amount cannot be updated if line_items present'
            );
        }
    }

    /**
     * Validates: - Either line_items or amount, description should exists in input
     *            - But not both
     *            - If line_items exists then count should be between 1-10
     *
     * @param array $input
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateLineItems(array $input)
    {
        $lineItemsExists = isset($input[Entity::LINE_ITEMS]);

        $amountExists    = isset($input[Entity::AMOUNT]);
        $descExists      = isset($input[Entity::DESCRIPTION]);

        if (($lineItemsExists) ^ ($amountExists and $descExists) === false)
        {
            throw new BadRequestValidationFailureException(
                'Provide either line_items or amount, description.'
            );
        }

        if (isset($input[Entity::LINE_ITEMS]) === false)
        {
            return;
        }

        $lineItemsCount = count($input[Entity::LINE_ITEMS]);

        if ($lineItemsCount === 0)
        {
            throw new BadRequestValidationFailureException(
                'Invoice must contain at least one line item.'
            );
        }

        if ($lineItemsCount > 10)
        {
            throw new BadRequestValidationFailureException(
                'Invoice cannot have more than 10 line items.'
            );
        }
    }

    public function validateSource($attribute, $value)
    {
        Source::checkSource($value);
    }

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function validateMerchantHasKeys()
    {
        $merchant = $this->entity->merchant;

        $keys = $merchant->keys;

        foreach ($keys as $key)
        {
            if ($key->isExpiredOrExpiring() === false)
            {
                return;
            }
        }

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_API_KEY_NOT_PRESENT,
            null,
            [
                'merchant_id' => $merchant->getId(),
            ]);
    }

    public function validateSendNotificationRequest(string $medium)
    {
        $invoice = $this->entity;

        if (NotifyMedium::isMediumValid($medium) === false)
        {
            throw new BadRequestValidationFailureException($medium . ' is not a valid communication medium');
        }

        if (($medium === NotifyMedium::EMAIL) and empty($invoice->getCustomerEmail()))
        {
            throw new BadRequestValidationFailureException(
                'Email can not be sent since email address has not been provided'
            );
        }

        if (($medium === NotifyMedium::SMS) and empty($invoice->getCustomerContact()))
        {
            throw new BadRequestValidationFailureException(
                'SMS can not be sent since contact number has not been provided'
            );
        }
    }

    public function validateOperation(string $operation)
    {
        switch ($operation) {
            case 'update':
                $allowedStatuses = [
                    Status::DRAFT,
                    Status::ISSUED,
                ];
                break;

            default:
                $allowedStatuses = [
                    Status::DRAFT,
                ];
                break;
        }

        $invoiceStatus = $this->entity->getStatus();

        if (in_array($invoiceStatus, $allowedStatuses, true) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVOICE_OPERATION_NOT_ALLOWED,
                null,
                [
                    'status'     => $invoiceStatus
                ]
            );
        }
    }

    /**
     * Validates if an invoice can be issued or not
     */
    public function validateInvoiceIssue()
    {
        // Checks:
        // - Invoice should have amount set to a non-zero value
        // - Either description (minimal invoice) or non-zero line items should exist

        $invoice = $this->entity;

        $invoiceAmount         = $invoice->getAmount();
        $invoiceDesc           = $invoice->getDescription();
        $invoiceLineItemsCount = $invoice->lineItems()->count();

        if (
            ($invoiceAmount > 0) and
            (($invoiceDesc != null) or ($invoiceLineItemsCount > 0))
        )
        {
            return;
        }

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVOICE_ISSUE_NOT_ALLOWED,
            null,
            [
                'invoice_id'        => $invoice->getId(),
                'amount'            => $invoiceAmount,
                'description'       => $invoiceDesc,
                'line_items_count'  => $invoiceLineItemsCount,
            ]
        );
    }

    // protected static $createValidators = [
    //     Entity::DISCOUNT_FLAT,
    //     Entity::DISCOUNT_PERCENT,
    // ];

    // public function validateDiscountFlat($input)
    // {
    //     if (isset($input[Entity::DISCOUNT_FLAT]) === false)
    //     {
    //         return;
    //     }
    //
    //     if (isset($input[Entity::DISCOUNT_PERCENT]) === true)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Both discount_flat and discount_percent should not be set.'
    //         );
    //     }
    //
    //     $totalAmount = $discountableAmount = $input[Entity::TOTAL_AMOUNT];
    //
    //     if (isset($input[Entity::TOTAL_TAX]) === true)
    //     {
    //         $discountableAmount = $totalAmount - $input[Entity::TOTAL_TAX];
    //     }
    //
    //     if ($input[Entity::DISCOUNT_FLAT] > $discountableAmount)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Discount cannot be greater than the total amount of the invoice',
    //             null,
    //             [
    //                 'total_amount'  => $input[Entity::TOTAL_AMOUNT],
    //                 'discount_flat' => $input[Entity::DISCOUNT_FLAT],
    //             ]
    //         );
    //     }
    // }
    //
    // public function validateDiscountPercent($input)
    // {
    //     if (isset($input[Entity::DISCOUNT_PERCENT]) === false)
    //     {
    //         return;
    //     }
    //
    //     if (isset($input[Entity::DISCOUNT_FLAT]) === true)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Both discount_flat and discount_percent should not be set.'
    //         );
    //     }
    // }
}
