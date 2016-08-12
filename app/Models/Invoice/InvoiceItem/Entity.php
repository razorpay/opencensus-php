<?php

namespace RZP\Models\Invoice\InvoiceItem;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    const INVOICE_ID    = 'invoice_id';
    const ITEM_ID       = 'item_id';
    // const QUANTITY      = 'quantity';

    protected $entity = 'invoice_item';

    protected $table = Table::INVOICE_ITEM;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::INVOICE_ID,
        self::ITEM_ID,
        // self::QUANTITY,
    ];

    protected $visible = [
        self::ID,
        self::INVOICE_ID,
        self::ITEM_ID,
        // self::QUANTITY,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::INVOICE_ID,
        self::ITEM_ID,
        // self::QUANTITY,
        self::CREATED_AT,
        self::UPDATED_AT
    ];
    
    //---------- Setters ----------------
    
    // public function setQuantity($quantity)
    // {
    //     $this->setAttribute(self::QUANTITY, $quantity);
    // }
    
    // -------- End Setters -----------------
    
    // -------- Relations ------------------
    
    public function invoice()
    {
        return $this->belongsTo('RZP\Models\Invoice\Entity');
    }
    
    public function item()
    {
        return $this->belongsTo('RZP\Models\Item\Entity');
    }
    
    // ------- End Relations --------------
}