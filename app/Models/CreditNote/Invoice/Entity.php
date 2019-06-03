<?php

namespace RZP\Models\CreditNote\Invoice;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = 'customer_id';
    const CREDITNOTE_ID     = 'creditnote_id';
    const INVOICE_ID        = 'invoice_id';
    const AMOUNT            = 'amount';
    const REFUND_ID         = 'refund_id';
    const STATUS            = 'status';

    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    const STATUS_REFUNDED   = 'refunded';

    protected $entity = 'creditnote_invoice';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::CREDITNOTE_ID,
        self::INVOICE_ID,
        self::REFUND_ID,
        self::STATUS,
        self::AMOUNT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::CREDITNOTE_ID,
        self::INVOICE_ID,
        self::REFUND_ID,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::AMOUNT,
    ];

    protected $public = [
        self::STATUS,
        self::REFUND_ID,
        self::AMOUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function creditNote()
    {
        return $this->belongsTo('RZP\Models\CreditNote\Entity');
    }

    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }

    public function refund()
    {
        return $this->belongsTo('RZP\Models\Payment\Refund\Entity');
    }

    public function getInvoiceId()
    {
        return $this->getAttribute(self::INVOICE_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }
}
