<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Processor extends Base\Core
{
    protected $merchant;

    protected $merchantId;

    protected $beginTimestamp = 0;

    protected $endTimestamp = 0;

    protected $gstin = null;

    protected $invoiceBreakup;

    protected $month;

    protected $year;

    public function __construct(string $merchantId, int $month, int $year)
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $this->month = $month;

        $this->year = $year;

        $this->initiliazeVars();
    }

    public function createInvoiceEntities()
    {
        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_REQUEST,
            [
                'merchant_id'   => $this->merchantId,
                'month'         => $this->month,
                'year'          => $this->year,
            ]);

        try
        {
            $invoiceExists = $this->checkInvoiceExists($this->merchantId);

            if ($invoiceExists === true)
            {
                $this->trace->info(TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_SKIPPED,
                    [
                        'merchant'  => $this->merchantId,
                        'month'     => $this->month,
                        'year'      => $this->year,
                    ]);

                return;
            }

            // sum over fees & tax for different commission types
            foreach ($this->invoiceBreakup as $type => $values)
            {
                $this->calculateFeesForInvoiceByType($type);
            }

            // create entities
            $this->createInvoiceBreakup();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_FAILED,
                [
                    'merchant_id'   => $this->merchant->getId(),
                    'month'         => $this->month,
                    'year'          => $this->year,
                ]);
        }
    }

    protected function checkInvoiceExists(string $merchantId): bool
    {
        $entities = $this->repo->merchant_invoice->fetchFeesDataForInvoice($merchantId, $this->month, $this->year);

        if ($entities->count() > 0)
        {
            return true;
        }

        return false;
    }

    protected function createInvoiceBreakup()
    {
        $this->repo->transaction(function()
        {
            foreach ($this->invoiceBreakup as $type => $values)
            {
                $params = [
                    Entity::MONTH   => $this->month,
                    Entity::YEAR    => $this->year,
                    Entity::GSTIN   => $this->gstin,
                    Entity::TYPE    => $type,
                    Entity::AMOUNT  => $values[Entity::AMOUNT],
                    Entity::TAX     => $values[Entity::TAX],
                ];

                (new Core)->create($params, $this->merchant);
            }
        });
    }

    /**
     * Populate the map of Type of Commission with its Amount and Tax values
     */
    protected function calculateFeesForInvoiceByType(string $type)
    {
        $txns = $this->repo
                     ->transaction
                     ->fetchFeesAndTaxForTransactionsByType(
                            $this->merchantId,
                            $this->beginTimestamp,
                            $this->endTimestamp,
                            $type);

        if (empty($txns) === true)
        {
            return;
        }

        $txnData = $txns->getAttributes();

        $fees = $txnData['fee'];

        $tax = $txnData['tax'];

        $this->invoiceBreakup[$type][Entity::AMOUNT] = $fees - $tax;
        $this->invoiceBreakup[$type][Entity::TAX] = $tax;
    }

    protected function initiliazeVars()
    {
        $this->merchant = $this->repo->merchant->findOrFailPublicWithRelations($this->merchantId, ['merchantDetail']);

        $beginDate = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST);

        $this->beginTimestamp = $beginDate->startOfMonth()->timestamp;

        $this->endTimestamp = $beginDate->endOfMonth()->timestamp;

        // Get GSTIN
        $this->gstin = $this->merchant->getGstin();

        // Initialize commission values
        $this->invoiceBreakup = [];

        $commissionTypes = Type::getAllTypes();

        // [
        //    'card_lte_2k'   => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'card_gt_2k'    => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        //    'non_card'      => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
        // ]
        foreach ($commissionTypes as $key)
        {
            $this->invoiceBreakup[$key] = [
                Entity::AMOUNT => 0,
                Entity::TAX => 0,
                Entity::AMOUNT_DUE => 0
            ];
        }
    }
}