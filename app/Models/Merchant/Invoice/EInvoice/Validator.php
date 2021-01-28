<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MONTH           => 'required|integer|between:1,12',
        Entity::YEAR            => 'required|digits:4',
        Entity::TYPE            => 'required|string|in:PG,BANKING',
        Entity::GSTIN           => 'required|string|size:15',
        Entity::DOCUMENT_TYPE   => 'required|string|in:INV,CRN,DBN',
        Entity::INVOICE_NUMBER  => 'required|string',
    ];
}