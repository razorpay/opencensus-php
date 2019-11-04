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
use RZP\Models\Merchant\Balance;
use RZP\Models\Admin\Org\Preferences;
use RZP\Jobs\MerchantInvoice as MerchantInvoiceJob;
use RZP\Jobs\MerchantInvoiceCorrection as MerchantInvoiceCorrectionJob;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant, Balance\Entity $balance = null): Entity
    {
        $invoiceEntity = new Entity;

        $invoiceEntity->merchant()->associate($merchant);

        $invoiceEntity->build($input);

        // TODO: Balance should be sent from every caller of this function.
        // Remove `null` default in function argument.
        if (empty($balance) === true)
        {
            $balance = $merchant->primaryBalance;
        }

        $invoiceEntity->balance()->associate($balance);

        $balanceType = $balance->getType();

        $invoiceEntity->generateInvoiceNumber($input['month'], $input['year'], $balanceType);

        $this->repo->saveOrFail($invoiceEntity);

        return $invoiceEntity;
    }

    public function queueCreateInvoiceEntities(array $input)
    {
        RuntimeManager::setMaxExecTime(900);

        (new Validator)->validateInput('create_queue', $input);

        // Get invoice date
        if ((isset($input['month']) === true) and
            (isset($input['year']) === true))
        {
            $invoiceDate = Carbon::createFromDate($input['year'], $input['month'], 1, Timezone::IST);
        }
        else
        {
            $invoiceDate = Carbon::now(Timezone::IST)->subMonth();
        }

        //
        // When true, payment transactions will be considered from the month
        // specified by `$invoiceDate` where created and captured in same month
        // All other transaction will we considered on created date
        //
        $isCorrection = (isset($input['correction']) === true) ?
                        (bool) $input['correction'] :
                        false;

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
                               (array_merge($input['merchant_ids_excluded'],
                                            Merchant\Preferences::NO_MERCHANT_INVOICE_MIDS)) :
                               Merchant\Preferences::NO_MERCHANT_INVOICE_MIDS;

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_CREATE_REQUEST,
            [
                'month'                 => $invoiceDate->month,
                'year'                  => $invoiceDate->year,
                'is_correction'         => $isCorrection,
                'merchant_ids'          => $merchantIds,
                'merchant_ids_excluded' => $merchantIdsExcluded,
                'org_ids_included'      => Preferences::MERCHANT_INVOICE_WHITELISTED_ORG_ID,
            ]);

        $endTimestamp = $invoiceDate->endOfMonth()->timestamp;

        $batchSize = 10000;

        $skip = 0;

        $defaultDelay = 0;

        do
        {
            $merchantIdsToEnqueue = $this->repo
                                         ->merchant
                                         ->fetchActivatedMerchantsBeforeTimestamp(
                                             $batchSize,
                                             $skip,
                                             $endTimestamp,
                                             $merchantIds,
                                             $merchantIdsExcluded);

            $count = count($merchantIdsToEnqueue);

            $skip += $count;

            foreach ($merchantIdsToEnqueue as $merchantId)
            {
                MerchantInvoiceJob::dispatch(
                    $merchantId,
                    $invoiceDate->month,
                    $invoiceDate->year,
                    $this->mode,
                    $isCorrection)
                    // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                                  ->delay($defaultDelay++ % 901);
            }
        } while($count === $batchSize);

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_DISPATCH_COUNT,
            [
                'count' => $skip,
            ]);
    }

    public function queueCorrectionInvoiceInvoice(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_INVOICE_CORRECTION_REQUEST, $input);

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

        $batch  = 10000;

        $offset = 0;

        $defaultDelay = 0;

        do
        {
            $merchantIds = $this->repo
                                ->merchant
                                ->fetchActivatedMerchantsBeforeTimestamp(
                                    $batch,
                                    $offset,
                                    $invoiceDate->endOfMonth()->timestamp,
                                    $merchantIds);

            $count = count($merchantIds);

            $offset += $count;

            foreach ($merchantIds as $merchantId)
            {
                MerchantInvoiceCorrectionJob::dispatch(
                                                $merchantId,
                                                $invoiceDate->month,
                                                $invoiceDate->year,
                                                $this->mode)
                                            // Assign a delay between 0 and 900 so that tasks are distributed
                                            // over 15 minute period
                                            ->delay($defaultDelay++ % 901);
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

        $invoiceEntity = $this->create($params, $merchant, $adjustment->balance);

        return $invoiceEntity;
    }

    public function createMultipleInvoiceEntities(array $input)
    {
        (new Validator)->validateInput('bulk_create', $input);

        foreach ($input['invoice_entities'] as $row)
        {
            $this->trace->info(TraceCode::MERCHANT_INVOICE_BULK_CREATE, $row);

            $row[Entity::TYPE] = Type::ADJUSTMENT;

            /** @var Merchant\Entity $merchant */
            $merchant = $this->repo->merchant->findOrFail($row[Entity::MERCHANT_ID]);

            unset($row[Entity::MERCHANT_ID]);
            // TODO: Accept balance_id in invoice_entities passed as input on route merchant_invoice_add_bulk
            //Jira link : https://razorpay.atlassian.net/browse/RX-612
            $this->create($row, $merchant, $merchant->primaryBalance);
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
