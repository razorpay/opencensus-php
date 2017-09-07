<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createInvoiceEntities(array $input)
    {
        return (new Core)->queueCreateInvoiceEntities($input);
    }

    public function createMulitpleInvoiceEntities(array $input)
    {
        (new Core)->createMulitpleInvoiceEntities($input);
    }

    public function updateGstin(string $merchantId, array $input)
    {
        (new Validator)->validateInput('edit_gstin', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $currentGstin = $merchant->getGstin();

        $this->repo->merchant_invoice->updateGstin($merchantId, $input[Entity::INVOICE_NUMBER], $currentGstin);

        return [];
    }
}