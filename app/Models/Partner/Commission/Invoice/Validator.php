<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    // for banking and primary commissions (taxable + non_taxable)
    const MAX_ALLOWED_LINE_ITEMS = 4;

    protected static $invoiceGenerateRequestRules = [
        Entity::MONTH                => 'required|integer|between:1,12',
        Entity::YEAR                 => 'required|digits:4',
        Entity::REGENERATE_IF_EXISTS => 'sometimes|boolean',
        Entity::FORCE_REGENERATE     => 'sometimes|boolean',
        'merchant_ids'               => 'sometimes|array',
        'merchant_ids.*'             => 'sometimes|string|size:14',
    ];

    protected static $changeStatusRules = [
        Entity::ACTION => 'required|string|custom',
    ];

    protected static $createRules = [
        Entity::MONTH => 'required|integer|between:1,12',
        Entity::YEAR  => 'required|digits:4',
    ];

    protected static $bulkOnHoldClearRules = [
        Constants::INVOICE_IDS           => 'required|array',
        Constants::CREATE_TDS            => 'sometimes|boolean',
        Constants::UPDATE_INVOICE_STATUS => 'sometimes|boolean',
    ];

    public function validateAction($attribute, $key)
    {
        Status::validateStatus($key);
    }

    public function validateMerchantToAllowChangeAction(string $status)
    {
        return key_exists($status, Status::ALLOWED_STATUSES_FOR_MERCHANT);
    }

    public function validateLineItemsCount(int $lineItemsCount)
    {
        if ($lineItemsCount > self::MAX_ALLOWED_LINE_ITEMS)
        {
            $message = 'The invoice may not have more than ' . self::MAX_ALLOWED_LINE_ITEMS . ' items in total.';

            throw new Exception\BadRequestValidationFailureException(
                $message,
                null,
                [
                    'max_allowed_line_items'  => self::MAX_ALLOWED_LINE_ITEMS,
                    'actual_line_items_count' => $lineItemsCount,
                ]);
        }
    }
}
