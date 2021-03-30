<?php

namespace RZP\Models\Partner\Commission\Invoice;

use RZP\Models\Base;
use RZP\Exception\LogicException;

class Service extends Base\Service
{
    public function createInvoiceEntities(array $input)
    {
        return (new Core)->queueCreateInvoiceEntities($input);
    }

    public function changeStatus($id, array $input)
    {
        $invoice = $this->repo->commission_invoice->findByIdAndMerchant($id, $this->merchant);

        (new Validator)->validateInput('change_status', $input);
        
        return (new Core)->changeInvoiceStatus($invoice, $input);
    }

    public function clearOnHoldForInvoiceBulk(array $input)
    {
        (new Validator)->validateInput('bulk_on_hold_clear', $input);

        return (new Core)->clearOnHoldForInvoiceBulk($input);
    }

    public function fetch($id)
    {
        $params = [
            'expand' => ['line_items', 'line_items.taxes'],
        ];

        $invoice = $this->repo->commission_invoice->findByIdAndMerchant($id, $this->merchant, $params);

        return $invoice->toArrayPublic();
    }

    public function fetchBulk(array $input)
    {
        $invoices = $this->repo->commission_invoice->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }
}
