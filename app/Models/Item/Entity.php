<?php

namespace RZP\Models\Item;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    // TODO: Should we move this to under Invoice?
    // I want to keep it de-coupled with invoice
    // so that later we can use this as an independent
    // entity if we want. For example, for product inventory

    const ID            = 'id';
    const NAME          = 'name';
    const DESCRIPTION   = 'description';
    //
    // This is the tax computed from either
    // tax_flat or tax_percent received from the request
    //
    const TAX_COMPUTED  = 'computed_tax';
    const TAX_FLAT      = 'flat_tax';
    const TAX_PERCENT   = 'tax_percent';
    const AMOUNT        = 'amount';
    const CURRENCY      = 'currency';
    //
    // This is something like an SKU
    //
    const LISTING_ID    = 'listing_id';

    const INR           = 'INR';

    protected static $sign = 'item';

    protected $entity = 'item';

    protected $table = Table::ITEM;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::DESCRIPTION       => null,
        self::TAX_COMPUTED      => 0,
        self::TAX_FLAT          => 0,
        self::TAX_PERCENT       => 0,
        self::LISTING_ID        => null,
        self::CURRENCY          => self::INR,
    ];

    protected $public = [
        self::NAME,
        self::DESCRIPTION,
        self::TAX_COMPUTED,
        self::TAX_FLAT,
        self::TAX_PERCENT,
        self::AMOUNT,
        self::LISTING_ID,
        self::CURRENCY,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::LISTING_ID,
        self::TAX_PERCENT,
        self::TAX_FLAT,
        self::CURRENCY,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    // -------------------------- Getters --------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    // -------------------------- Getters Ends --------------------------
}