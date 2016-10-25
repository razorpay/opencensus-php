<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Models\Pricing\FeeBreakup as FeeBreakup;
use RZP\Models\Pricing\FeeBreakup\Type as FeeBreakupType;
use RZP\Models\Pricing\FeeBreakup\Name as FeeBreakupName;


class DataMigration extends Base\Service
{
    // All transaction before Date: 01/06/2015 00:00 (1433097000) ST = 12.36%
    // All transaction after Date: 01/06/2015 00:00 (1433097000) ST = 14%
    const SERVICE_TAX_CUTOFF_TIMESTAMP                      = 1433097000;
    const SERVICE_TAX_PERCENTAGE_BEFORE_CUTOFF              = 1236;
    const SERVICE_TAX_PERCENTAGE_AFTER_CUTOFF               = 1400;

    // All transaction after Date: 15/11/2015 00:00 (1447525800) SB = 0.05%
    const SWACH_BHARAT_CUTOFF_TIMESTAMP                     = 1447525800;
    const SWACHH_BHARAT_CESS_PERCENTAGE                     = 50;

    // All transaction after Date: 01/06/2016 00:00 (1464719400) KK = 0.05%
    const KRISHI_KALYAN_CUTOFF_TIMESTAMP                    = 1464719400;
    const KRISHI_KALYAN_CESS_PERCENTAGE                     = 50;

    protected $feeCalculator;

    public function postMigrateOlderTransactions($input)
    {
        list($batch1From, $batch1To, $batch2From, $batch2To) = $this->getTimestamps($input);

        $response = $this->processEntries($batch1From, $batch1To);

        $batchResponse2 = $this->processEntries($batch2From, $batch2To);

        if (empty($batchResponse2) === false)
        {
            $response = $response + $batchResponse2;
        }

        return $response;
    }

    protected function processEntries($from, $to)
    {
        $txns = $this->repo->transaction->getTransactionsToBeMigrated($from, $to);

        $response = [];

        foreach ($txns as $txn)
        {
            $merchant = $txn->merchant;

            $payment = $this->repo->payment->findOrFail($txn->getEntityId());

            $pricingRuleId = $txn->getPricingRule();

            $pricing = $this->repo->pricing->findOrFail($pricingRuleId);

            $feesSplit = new PublicCollection;

            $this->feeCalculator = new FeeCalculator($payment);

            $amount = $txn->getAmount();

            // Original Amount = Payment Amount - Fee
            // Fee = RZp Fee + ST
            if ($merchant->isFeeBearerCustomer() === true)
            {
                $amount = $payment->getAmount() - $payment->getFee();
            }

            $fees = $this->feeCalculator->getUnroundedFees(
                                            $amount,
                                            $pricing->getPercentRate(),
                                            $pricing->getFixedRate(),
                                            $feesSplit);

            $this->calculateServiceTaxes($fees, $feesSplit, $payment->getCaptureTimestamp());

            $shouldSaveFeeDetails = $this->matchTaxesAndFeesWithOriginal($txn, $feesSplit);

            if ($shouldSaveFeeDetails === true)
            {
                $this->saveFeeDetails($txn, $feesSplit, $payment->getCaptureTimestamp());

                $response[$txn->getPublicId()] = $feesSplit->toArrayPublic();
            }
        }

        return $response;
    }

    protected function calculateServiceTaxes($fee, $feesSplit, $capturedTime)
    {
        $serviceTaxPercentage = 0;
        $krishiKalyanCessPercentage = 0;
        $swachhBharatCessPercentage = 0;

        // Checking the capture time with the ST cutoff time
        if ($capturedTime < self::SERVICE_TAX_CUTOFF_TIMESTAMP)
        {
            $serviceTaxPercentage = self::SERVICE_TAX_PERCENTAGE_BEFORE_CUTOFF;
        }
        else
        {
            $serviceTaxPercentage = self::SERVICE_TAX_PERCENTAGE_AFTER_CUTOFF;
        }

        // Checking the capture time with the SB cutoff time
        if ($capturedTime >= self::SWACH_BHARAT_CUTOFF_TIMESTAMP)
        {
            $swachhBharatCessPercentage = self::SWACHH_BHARAT_CESS_PERCENTAGE;
        }

        // Checking the capture time with the KK cutoff time
        if ($capturedTime >= self::KRISHI_KALYAN_CUTOFF_TIMESTAMP)
        {
            $krishiKalyanCessPercentage = self::KRISHI_KALYAN_CESS_PERCENTAGE;
        }

        $this->feeCalculator->calculateServiceTaxes(
                                $fee,
                                $feesSplit,
                                $serviceTaxPercentage,
                                $swachhBharatCessPercentage,
                                $krishiKalyanCessPercentage);
    }

    protected function matchTaxesAndFeesWithOriginal($txn, $feesSplit)
    {
        $originalFee = $txn->getFee();

        $originalTax = $txn->getServiceTax();

        $originalRzpFee = $originalFee - $originalTax;

        list($rzpFee, $taxes) = $this->getFeesSplit($feesSplit);

        if ($taxes !== $originalTax)
        {
            $this->trace->info(TraceCode::TRANSACTION_MIGRATION_TAX_MISTMATCH,
                [
                    'transaction'       => $txn->toArrayPublic(),
                    'originalTax'       => $originalTax,
                    'calculatedTax'     => $taxes,
                ]);

            return false;
        }

        if ($rzpFee !== $originalRzpFee)
        {
            $this->trace->info(TraceCode::TRANSACTION_MIGRATION_FEE_MISTMATCH,
                [
                    'transaction'        => $txn->toArrayPublic(),
                    'originalRzpFee'     => $originalRzpFee,
                    'calculatedRzpFee'   => $rzpFee,
                ]);


            return false;
        }

        return true;
    }

    protected function getFeesSplit($feesSplit)
    {
        $rzpFee = 0;
        $taxes = 0;

        foreach ($feesSplit as $feeSplit)
        {
            if ($feeSplit['name'] === FeeBreakupName::RZP)
            {
                $rzpFee += $feeSplit['amount'];
            }
            else
            {
                $taxes += $feeSplit['amount'];
            }
        }
        return [$rzpFee, $taxes];
    }

    protected function saveFeeDetails($txn, $feesSplit, $captureTime)
    {
        if (empty($feesSplit) === true)
        {
            return;
        }

        $this->repo->transaction(function() use ($txn, $feesSplit, $captureTime)
        {
            foreach ($feesSplit as $feeSplit)
            {
                // If SB or KB is 0 then we don't save it.
                if (($feeSplit->getType() === FeeBreakupType::PERCENTAGE) and
                    ($feeSplit->getPercentage() === 0))
                {
                    continue;
                }

                $feeSplit->transaction()->associate($txn);

                $feeSplit->setCreatedAt($captureTime);

                $this->repo->fee_breakup->saveOrFail($feeSplit);
            }
        });
    }

    protected function getTimestamps($input)
    {
        assertTrue(isset($input['month']));
        assertTrue(isset($input['year']));

        $year = (int) $input['year'];
        $month = (int) $input['month'];

        assertTrue($month > 0);
        assertTrue($month <= 12);

        $from = Carbon::today('Asia/Kolkata')
                        ->month($month)
                        ->year($year)
                        ->startOfMonth()
                        ->timestamp;

        $mid = Carbon::today('Asia/Kolkata')
                        ->month($month)
                        ->day(15)
                        ->year($year)
                        ->startOfDay()
                        ->timestamp;

        $end = Carbon::today('Asia/Kolkata')
                        ->month($month)
                        ->year($year)
                        ->endOfMonth()
                        ->timestamp;

        return [ $from, $mid, $mid + 1, $end];
    }
}
