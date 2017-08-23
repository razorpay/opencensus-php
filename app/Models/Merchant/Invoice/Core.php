<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\MerchantInvoice as MerchantInvoiceJob;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function queueCreateInvoiceEntities(array $input)
    {
        (new Validator)->validateInput('create_queue', $input);

        // Get invoice date
        if ((isset($input['month']) === true) and (isset($input['year']) === true))
        {
            $invoiceDate = Carbon::createFromDate($input['year'], $input['month'], 1, Timezone::IST);
        }
        else
        {
            $invoiceDate = Carbon::now(Timezone::IST)->subMonth();
        }

        // Get merchants
        $merchantIds = [];

        if (isset($input['merchant_ids']) === true)
        {
            $merchantIds = $input['merchant_ids'];
        }

        $endTimestamp = $invoiceDate->endOfMonth()->timestamp;

        $batch = 100;

        $skip = 0;

        $count = 100;

        while ($batch === $count)
        {
            $merchants = $this->repo
                              ->merchant
                              ->fetchActivatedMerchantsBeforeTimestamp($batch, $skip, $endTimestamp, $merchantIds);

            $count = $merchants->count();

            $skip += $count;

            foreach ($merchants as $merchant)
            {
                $createJob = new MerchantInvoiceJob(
                    $merchant->getId(), $invoiceDate->month, $invoiceDate->year, $this->mode);

                (new DispatchRouter)->dispatchOn($createJob, DispatchRouter::MERCHANT_INVOICE);
            }
        }
    }

    public function updateGstin(string $merchantId, array $input)
    {
        (new Validator)->validateInput('edit_gstin', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $currentGstin = $merchant->getGstin();

        $this->repo->merchant_invoice->updateGstin($merchantId, $input[Entity::INVOICE_NUMBER], $currentGstin);
    }
}