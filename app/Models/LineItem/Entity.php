<?php

namespace RZP\Models\LineItem;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    const NAME                  = 'name';
    const DESCRIPTION           = 'description';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const MERCHANT_ID           = 'merchant_id';
    // This is something like an SKU
    const LISTING_ID            = 'listing_id';
    const INVOICE_ID            = 'invoice_id';

    const QUANTITY              = 'quantity';

    protected static $sign = 'li';

    protected $entity = 'line_item';

    protected $table = Table::LINE_ITEM;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::DESCRIPTION       => null,
        self::LISTING_ID        => null,
        self::QUANTITY          => 1,
    ];

    protected $visible = [
        self::ID,
        self::PUBLIC_ID,
        self::INVOICE_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::LISTING_ID,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::MERCHANT_ID,
        self::QUANTITY,
    ];

    protected $public = [
        self::ID,
        self::INVOICE_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::LISTING_ID,
        self::CURRENCY,
        self::QUANTITY,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::LISTING_ID,
        self::QUANTITY,
        self::CURRENCY,
    ];

    protected $casts = [
        self::AMOUNT    => 'int',
        self::QUANTITY  => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::INVOICE_ID,
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

    public function getQuantity()
    {
        return $this->getAttribute(self::QUANTITY);
    }

    // -------------------------- Getters Ends --------------------------

    protected function setPublicInvoiceIdAttribute(array & $array)
    {
        if (isset($array[self::INVOICE_ID]))
        {
            $invoiceId = $this->getAttribute(self::INVOICE_ID);

            $array[self::INVOICE_ID] = Invoice\Entity::getSignedId($invoiceId);
        }
        else
        {
            $app = App::getFacadeRoot();

            $app['trace']->error(
                TraceCode::INVOICE_ID_ABSENT,
                [
                    'line_item_id' => $this->getId()
                ]);
        }
    }

    // -------------------- Relations ---------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }

    // -------------------- End Relations -----------------------
}
