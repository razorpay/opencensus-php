<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Models\Base;
use RZP\Models\Base\Traits\HasBalance;

class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const MONTH             = 'month';
    const YEAR              = 'year';
    const GROSS_AMOUNT      = 'gross_amount';
    const TAX_AMOUNT        = 'tax_amount';
    const STATUS            = 'status';
    const BALANCE_ID        = 'balance_id';
    const NOTES             = 'notes';
    const TNC               = 'tnc';

    protected $entity = 'commission_invoice';

    protected $generateIdOnCreate = true;

}
