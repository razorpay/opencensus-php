<?php

namespace RZP\Models\Invoice\InvoiceItem;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Item;

class Core extends Base\Core
{
    protected $invoice;

    public function __construct()
    {
        parent::__construct();
    }

    public function mapItemToInvoice(Invoice\Entity $invoice, Item\Entity $item)
    {
        $invoiceItem = new Entity();

        $invoiceItem->invoice()->associate($invoice);

        $invoiceItem->item()->associate($item);

        $this->repo->saveOrFail($invoiceItem);
    }
}