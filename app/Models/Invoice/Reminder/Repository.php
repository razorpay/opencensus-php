<?php

namespace RZP\Models\Invoice\Reminder;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'invoice_reminder';

    /**
     * @param string $invoiceId
     *
     * @return mixed
     */
    public function getByInvoiceId(string $invoiceId)
    {
        return $this->newQuery()
                    ->where(Entity::INVOICE_ID, '=', $invoiceId)
                    ->first();
    }
}
