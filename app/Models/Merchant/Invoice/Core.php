<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Jobs\MerchantInvoice as MerchantInvoiceJob;
use RZP\Jobs\MerchantInvoiceCorrection as MerchantInvoiceCorrectionJob;

class Core extends Base\Core
{
    /**
     * Array of merchant ids for which invoice should not be generated.
     * Disabled for Airtel Payments Bank currently.
     */
    const INVOICE_EXCLUDED_MERCHANTS = [
        'AqUQQH9neAMkUG'
    ];

    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $invoiceEntity = new Entity;

        $invoiceEntity->merchant()->associate($merchant);

        $invoiceEntity->build($input);

        $invoiceEntity->generateInvoiceNumber($input['month'], $input['year']);

        $this->repo->saveOrFail($invoiceEntity);

        return $invoiceEntity;
    }

    public function queueCorrectionInvoiceInvoice(array $input)
    {
        $this->trace->info(
                    TraceCode::MERCHANT_INVOICE_CORRECTION_REQUEST,
                    $input);

        (new Validator)->validateInput('correction_queue', $input);

        $invoiceDate = Carbon::createFromDate(
            $input['year'],
            $input['month'],
            1,
            Timezone::IST);

        $merchantIds = [];

        if (isset($input['merchant_ids']) === true)
        {
            $merchantIds = $input['merchant_ids'];
        }

        $batch  = 100;

        $offset = 0;

        $i = 0;

        do
        {
            $merchants = $this->repo
                              ->merchant
                              ->fetchActivatedMerchantsBeforeTimestamp(
                                  $batch,
                                  $offset,
                                  $invoiceDate->endOfMonth()->timestamp,
                                  $merchantIds);

            $count = $merchants->count();

            $offset += $count;

            foreach ($merchants as $merchant)
            {

                MerchantInvoiceCorrectionJob::dispatch(
                                                $merchant->getId(),
                                                $invoiceDate->month,
                                                $invoiceDate->year,
                                                $this->mode)
                                            // Assign a delay between 0 and 900 so that tasks are distributed
                                            // over 15 minute period
                                            ->delay($i++ % 901);
            }

        } while ($count === $batch);
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
        RuntimeManager::setMaxExecTime(900);

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

        // When true, payment transactions will be cosiderd from the month
        // specified by `$invoiceDate` where created and captured in same month
        // All other transction will we considered on created date
        $isCorrection = (isset($input['correction']) === true) ?
                                (bool) $input['correction'] : false;

        // Get merchants
        $merchantIds = [];

        if (isset($input['merchant_ids']) === true)
        {
            $merchantIds = $input['merchant_ids'];
        }

        //
        // merchant_ids_excluded is an array of merchant ids coming from input,
        // for which invoice shouldn't be generated.
        //
        $merchantIdsExcluded = (isset($input['merchant_ids_excluded']) === true) ?
                               (array_merge($input['merchant_ids_excluded'], self::INVOICE_EXCLUDED_MERCHANTS)) :
                               self::INVOICE_EXCLUDED_MERCHANTS;

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_CREATE_REQUEST,
            [
                'month'                 => $invoiceDate->month,
                'yeat'                  => $invoiceDate->year,
                'is_correction'         => $isCorrection,
                'merchant_ids'          => $merchantIds,
                'merchant_ids_excluded' => $merchantIdsExcluded,
            ]);

        $endTimestamp = $invoiceDate->endOfMonth()->timestamp;

        $batch = 100;

        $skip = 0;

        $count = 100;

        $i = 0;
        while ($batch === $count)
        {
            $merchants = $this->repo
                              ->merchant
                              ->fetchActivatedMerchantsBeforeTimestamp(
                                  $batch,
                                  $skip,
                                  $endTimestamp,
                                  $merchantIds,
                                  $merchantIdsExcluded);

            $count = $merchants->count();

            $skip += $count;

            foreach ($merchants as $merchant)
            {
                MerchantInvoiceJob::dispatch(
                                        $merchant->getId(),
                                        $invoiceDate->month,
                                        $invoiceDate->year,
                                        $this->mode,
                                        $isCorrection)
                                  // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                                  ->delay($i++ % 901);
            }
        }

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_DISPATCH_COUNT,
            [
                'count' => $skip,
            ]);

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

    public function updateGstinForInvoice(array $input, Merchant\Entity $merchant): int
    {
        $currentGstin = $merchant->getGstin();

        $invoiceNumber = trim($input[Entity::INVOICE_NUMBER]);

        $merchantId = $merchant->getId();

        $entities = $this->repo->merchant_invoice->fetchByInvoiceNumber($merchantId, $invoiceNumber);

        $count = $entities->count();

        if ($count === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_MERCHANT_INVOICE_NUMBER,
                null,
                [
                    'merchant_id' => $merchantId,
                    'invoice_number' => $invoiceNumber
                ]);
        }

        $this->repo->transaction(function() use ($entities, $currentGstin)
        {
            foreach ($entities as $entity)
            {
                $oldEntity = clone($entity);
                $entity->setGstin($currentGstin);

                $this->app['workflow']
                    ->setEntityAndId($entity->getEntity(), $entity->getId())
                    ->handle($oldEntity, $entity);

                $this->repo->saveOrFail($entity);
            }
        });

        return $count;
    }
}
