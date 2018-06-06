<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;

class Correction extends Base\Core
{
    protected $merchantId;

    protected $endTimestamp   = 0;

    protected $beginTimestamp = 0;

    protected $invoiceBreakup;

    protected $month;

    protected $year;

    public function __construct(string $merchantId, int $month, int $year)
    {
        parent::__construct();

        $this->year       = $year;

        $this->month      = $month;

        $this->merchantId = $merchantId;
    }

    public function calculateAndLogInvoiceCorrection()
    {
        //
        // If the given year month are same as start time of invoice (July 2017) then correction will be true
        // Here we have to consider payment transaction where created is in given month and
        // also captured in same month And other transaction will be picked up where
        // created in given month.
        //
        $isCorrection = (($this->year === Constants::START_YEAR) and
                         ($this->month === Constants::START_MONTH)) ? true : false;

        $correctionAmounts = $this->getCorrectionAmounts($isCorrection);

        $this->log($correctionAmounts);
    }

    protected function log(array $amounts)
    {
        $amountFields = $this->getAmountFields($amounts);

        if(empty($amountFields) === true)
        {
            return;
        }

        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($this->merchantId);

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_CORRECTION_DETAILS,
            [
                'merchant_id'   => $this->merchantId,
                'name'          => $merchant->getName(),
                'month'         => $this->month,
                'year'          => $this->year,
            ] + $amountFields);
    }

    /**
     * Removes the type from the amount list for which amount and tax are 0
     *
     * @param array $amounts
     *
     * @return array
     */
    protected function getAmountFields(array & $amounts): array
    {
        foreach ($amounts as $type => $amount)
        {
            if (($amount[Entity::TAX] !== 0) or
                ($amount[Entity::AMOUNT] !== 0))
            {
                return $this->formatAmount($amounts);
            }
        }

        return [];
    }

    protected function formatAmount(array $amounts): array
    {
        $record = [];

        foreach ($amounts as $type => $amount)
        {
            $taxKey = $type . '_tax';

            $feeKey = $type . '_fee';

            $record[$feeKey] = $amount[Entity::AMOUNT];

            $record[$taxKey] = $amount[Entity::TAX];
        }

        return $record;
    }

    protected function getExistingInvoiceAmounts(): array
    {
        $invoiceAmounts = [];

        $invoiceData = $this->repo
                            ->merchant_invoice
                            ->fetchFeesDataForInvoice(
                                $this->merchantId,
                                $this->month,
                                $this->year);

        foreach ($invoiceData as $record)
        {
            $type = $record->getType();

            $type = ($type === Type::NON_CARD) ? Type::OTHERS : $type;

            $invoiceAmounts[$type] = [
                Entity::TAX    => $record->getTax(),
                Entity::AMOUNT => $record->getAmount(),
            ];
        }

        return $invoiceAmounts;
    }

    /**
     * Gives the correction amount and tax for the months invoice
     *
     * @param bool $isCorrection indicated whether to apply correction logic
     *                           while calculating new invoice amount
     *
     * @return array
     */
    protected function getCorrectionAmounts(bool $isCorrection): array
    {
        $correctionAmounts = [];

        $existingInvoiceAmounts = $this->getExistingInvoiceAmounts();

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_CORRECTION_OLD_AMOUNT,
            [
                'merchant_id' => $this->merchantId,
                'month'       => $this->month,
                'year'        => $this->year
            ] + $existingInvoiceAmounts);

        $processor = new Processor($this->merchantId, $this->month, $this->year);

        $commissionTypes = Type::getAllTypes();

        foreach ($commissionTypes as $type)
        {
            $start = microtime(true);

            $newAmounts = $processor->calculateFeesForInvoiceByType($type, $isCorrection);

            $this->trace->info(
                TraceCode::MERCHANT_INVOICE_CORRECTION_NEW_AMOUNT,
                [
                    'merchant_id' => $this->merchantId,
                    'type'        => $type,
                    'month'       => $this->month,
                    'year'        => $this->year
                ] + $newAmounts);

            $correctionAmounts[$type] = $this->calculateCorrectionAmounts($newAmounts, $existingInvoiceAmounts[$type]);

            $end = microtime(true);

            $this->trace->info(
                TraceCode::MERCHANT_INVOICE_CORRECTION_TRACE,
                [
                    'merchant_id' => $this->merchantId,
                    'type'        => $type,
                    'month'       => $this->month,
                    'year'        => $this->year,
                    'time_taken'  => $end - $start,
                ]);
        }

        return $correctionAmounts;
    }

    /**
     * Calculated difference between old and new invoice amounts
     *
     * @param array $newAmount
     * @param array $oldAmount
     *
     * @return array
     */
    protected function calculateCorrectionAmounts(array $newAmount, array $oldAmount)
    {
        return [
            Entity::TAX    => $newAmount[Entity::TAX] - $oldAmount[Entity::TAX],
            Entity::AMOUNT => $newAmount[Entity::AMOUNT] - $oldAmount[Entity::AMOUNT]
        ];
    }
}
