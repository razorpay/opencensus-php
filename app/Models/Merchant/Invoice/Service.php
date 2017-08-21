<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createInvoiceEntities(array $input)
    {
        return (new Core())->queueCreateInvoiceEntities($input);
    }
}