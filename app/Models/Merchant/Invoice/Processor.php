<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Processor extends Base\Core
{
    protected $merchant;

    protected $merchantId;

    protected $beginTimestamp = 0;

    protected $endTimestamp = 0;

    protected $txns = null;

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
        try
        {
            $merchantId = $this->merchant->getId();

            $invoiceExists = $this->checkInvoiceExists($merchantId);

            if ($invoiceExists === true)
            {
                $this->trace->info(TraceCode::MERCHANT_INVOICE_ENTITY_CREATION_SKIPPED,
                    [
                        'merchant'  => $merchantId,
                        'month'     => $this->month,
                        'year'      => $this->year,
                    ]);

                return;
            }

            // get transactions
            $this->txns = $this->repo
                         ->transaction
                         ->fetchCapturedTransactionsBetweenTimestamp(
                                $merchantId, $this->beginTimestamp, $this->endTimestamp);

            // sum over fees & tax for different commission types
            $this->calculateFeesForInvoice();

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
        $entities = $this->repo->merchant_invoice->fetchInvoiceReportData($merchantId, $this->month, $this->year);

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
                if ($this->shouldCreateEntity($type, $values[Entity::AMOUNT], $values[Entity::TAX]) === false)
                {
                    continue;
                }

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
    protected function calculateFeesForInvoice()
    {
        foreach ($this->txns as $txn)
        {
            $tax = $txn->getTax();

            $fee = $txn->getFee() - $tax;

            $payment = $txn->source;

            if ($payment->isCard() === true)
            {
                if ($txn->getAmount() <= FeeCalculator::CARD_TAX_CUT_OFF)
                {
                    $type = Type::CARD_LTE_2K;

                    // For the following month, we had levied GST on some CARD_LTE_2K/
                    // This was later reverted to the merchant through Adjustment.
                    // As right now we aren't showing adjustments in invoices,
                    // we want to force-set this to 0 to avoid confusion for merchants
                    if (($this->month === 7) and ($this->year === 2017))
                    {
                        $tax = 0;
                    }
                }
                else
                {
                    $type = Type::CARD_GT_2K;
                }
            }
            else
            {
                $type = Type::NON_CARD;
            }

            $this->invoiceBreakup[$type][Entity::AMOUNT] += $fee;

            $this->invoiceBreakup[$type][Entity::TAX] += $tax;

            if ($txn->isPostpaid() === true)
            {
                $this->invoiceBreakup[$type][Entity::AMOUNT_DUE] += ($fee + $tax);
            }
        }
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
        //    'adjustment'    => ['amount' => 0, 'tax' => 0, 'amount_due' => 0],
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

    protected function shouldCreateEntity(string $type, int $amount, int $tax): bool
    {
        if (($type === Type::ADJUSTMENT) and
            ($amount === 0) and
            ($tax === 0))
        {
            return false;
        }

        return true;
    }
}