<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const INVOICE_NUMBER    = 'invoice_number';
    const MONTH             = 'month';
    const YEAR              = 'year';
    const GSTIN             = 'gstin';
    const TYPE              = 'type';
    const AMOUNT            = 'amount';
    const TAX               = 'tax';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    protected $entity = 'merchant_invoice';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::AMOUNT,
        self::TAX,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::INVOICE_NUMBER,
        self::MONTH,
        self::YEAR,
        self::GSTIN,
        self::TYPE,
        self::AMOUNT,
        self::TAX,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::TAX,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
        self::TAX       => 'int',
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }
}