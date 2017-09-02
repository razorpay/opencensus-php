<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\MerchantInvoice as MerchantInvoiceJob;
use RZP\Models\Adjustment;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $invoiceEntity = new Entity;

        $invoiceEntity->merchant()->associate($merchant);

        $invoiceEntity->build($input);

        $invoiceEntity->generateInvoiceNumber($input['month'], $input['year']);

        $this->repo->saveOrFail($invoiceEntity);

        return $invoiceEntity;
    }

    public function createAdjustmentInvoiceEntity(Adjustment\Entity $adjustment, array $input): Entity
    {
        $currentDate = Carbon::now(Timezone::IST);

        $merchant = $adjustment->merchant;

        $gstin = $merchant->getGstin();

        $params = [
            Entity::MONTH           => $currentDate->month,
            Entity::YEAR            => $currentDate->year,
            Entity::GSTIN           => $gstin,
            Entity::TYPE            => Type::ADJUSTMENT,
            Entity::DESCRIPTION     => $input[Entity::DESCRIPTION],
            Entity::AMOUNT          => ($input[Entity::AMOUNT] ?? 0),
            Entity::TAX             => ($input[Entity::TAX] ?? 0),
        ];

        $invoiceEntity = $this->create($params, $merchant);

        return $invoiceEntity;
    }

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

    public function createMulitpleInvoiceEntities(array $input)
    {
        (new Validator)->validateInput('bulk_create', $input);

        foreach ($input['invoice_entities'] as $row)
        {
            $this->trace->info(TraceCode::MERCHANT_INVOICE_BULK_CREATE, $row);

            $row[Entity::TYPE] = Type::ADJUSTMENT;

            $merchant = $this->repo->merchant->findOrFail($row[Entity::MERCHANT_ID]);

            unset($row[Entity::MERCHANT_ID]);

            $this->create($row, $merchant);
        }
    }
}