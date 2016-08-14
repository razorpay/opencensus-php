<?php

namespace RZP\Models\Invoice\InvoiceItem;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;

/**
 * This class exists only to serve as a pivot entity and nothing else.
 *
 * Class Entity
 * @package RZP\Models\Invoice\InvoiceItem
 */
class Entity extends Base\PublicEntity
{
    const INVOICE_ID    = 'invoice_id';
    const ITEM_ID       = 'item_id';
}