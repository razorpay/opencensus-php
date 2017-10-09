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

    public function updateGstin(string $merchantId, array $input): array
    {
        (new Validator)->validateInput('edit_gstin', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $count = (new Core)->updateGstinForInvoice($input, $merchant);

        return ['count' => $count];
    }
}