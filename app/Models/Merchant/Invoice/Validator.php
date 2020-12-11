<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Exception;
use RZP\Base;

class Validator extends Base\Validator
{
    const BANKING_INVOICE_GENERATE = 'banking_invoice_generate';
    const VERIFY                   = 'verify';

    protected static $createRules = [
        Entity::MONTH       => 'required|integer|between:1,12',
        Entity::YEAR        => 'required|digits:4',
        Entity::TYPE        => 'required|string',
        Entity::DESCRIPTION => 'sometimes|string|nullable',
        Entity::AMOUNT      => 'required|integer',
        Entity::TAX         => 'required|integer',
        Entity::AMOUNT_DUE  => 'sometimes|integer|min:0',
        Entity::GSTIN       => 'sometimes|string|size:15|nullable',
    ];

    protected static $editGstinRules = [
        Entity::INVOICE_NUMBER  => 'required|string',
    ];

    protected static $verifyRules = [
        Entity::MONTH               => 'sometimes|integer|between:1,12',
        Entity::YEAR                => 'sometimes|digits:4',
    ];

    protected static $createQueueRules = [
        Entity::MONTH               => 'sometimes|integer|between:1,12',
        Entity::YEAR                => 'sometimes|digits:4',
        'merchant_ids'              => 'sometimes|array',
        'merchant_ids.*'            => 'sometimes|string|size:14',
        'merchant_ids_excluded'     => 'sometimes|array',
        'merchant_ids_excluded.*'   => 'sometimes|string|size:14',
    ];

    protected static $bulkCreateRules = [
        'invoice_entities'      => 'required|array',
        'invoice_entities.*'    => 'required|array',
    ];

    protected static $createValidators = [
        Entity::TYPE,
    ];

    protected static $bankingInvoiceGenerateRules = [
        Entity::YEAR                => 'required|digits:4',
        Entity::MONTH               => 'required|digits_between:1,2',
        Entity::SEND_EMAIL          => 'sometimes|boolean',
        Entity::TO_EMAILS           => 'required_if:send_email,1|array',
        Entity::TO_EMAILS . '.*'    => 'filled|email',
    ];

    protected static $pdfControlRules = [
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'required|string|size:14',
        Entity::YEAR     => 'required|digits:4',
        Entity::MONTH    => 'required|digits_between:1,2',
        'action'         => 'required|string|in:delete,create',
        'reason'         => 'required|string',
    ];

    protected static $generationControlRules = [
        'action'          => 'required|string|in:add,remove,show',
        'merchant_ids'    => 'required_if:action,add,remove|array',
        'merchant_ids.*'  => 'required|string|size:14',
        'reason'          => 'required_if:action,add,remove|string'
    ];

    protected function validateType($input)
    {
        if (Type::isValid($input[Entity::TYPE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid commission type: ', $input[Entity::TYPE]);
        }
    }
}
