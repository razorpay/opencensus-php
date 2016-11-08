<?php

namespace RZP\Models\Transaction;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Base\RuntimeManager;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Models\Transaction\FeeBreakup as FeeBreakup;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;


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

    public function postMigrateOlderTransactions()
    {
        $this->increaseAllowedSystemLimits();

        $response = $this->processEntries();

        return $response;
    }

    protected function processEntries()
    {
        $txns = $this->repo->transaction->getTransactionsToBeMigrated();

        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($txns as $txn)
        {
            $merchant = $txn->merchant;

            $payment = $this->repo->payment->findOrFail($txn->getEntityId());

            $pricingRuleId = $txn->getPricingRule();

            if (isset($pricingRuleId) === false)
            {
                continue;
            }

            $pricing = $this->repo->pricing->findOrFail($pricingRuleId);

            $feesSplit = new Base\PublicCollection;

            $this->feeCalculator = new FeeCalculator($payment);

            $amount = $txn->getAmount();

            // Original Amount = Payment Amount - Fee
            // Fee = RZp Fee + ST
            if ($merchant->isFeeBearerCustomer() === true)
            {
                $amount = $payment->getAmount() - $payment->getFee();
            }

            $fees = $this->feeCalculator->calculateRzpFee($pricing, $amount);

            $totalTax = $this->calculateServiceTaxes($fees, $payment->getCaptureTimestamp());

            $feesSplit = $this->feeCalculator->getFeesSplit();

            $shouldSaveFeeDetails = $this->matchTaxesAndFeesWithOriginal($txn, $fees, $totalTax);

            if ($shouldSaveFeeDetails === true)
            {
                $this->saveFeeDetails($txn, $feesSplit, $payment->getCaptureTimestamp());

                $migratedTxns[] = $txn->getPublicId();
            }
            else
            {
                $notMigratedTxns[] = $txn->getPublicId();
            }
        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    protected function calculateServiceTaxes($fee, $capturedTime)
    {
        $taxComponents = [];

        // Checking the capture time with the ST cutoff time
        if ($capturedTime < self::SERVICE_TAX_CUTOFF_TIMESTAMP)
        {
            $taxComponents[FeeBreakupName::SERVICE_TAX] = self::SERVICE_TAX_PERCENTAGE_BEFORE_CUTOFF;
        }
        else
        {
            $taxComponents[FeeBreakupName::SERVICE_TAX] = self::SERVICE_TAX_PERCENTAGE_AFTER_CUTOFF;
        }

        // Checking the capture time with the SB cutoff time
        if ($capturedTime >= self::SWACH_BHARAT_CUTOFF_TIMESTAMP)
        {
            $taxComponents[FeeBreakupName::SWACHH_BHARAT_CESS] = self::SWACHH_BHARAT_CESS_PERCENTAGE;
        }

        // Checking the capture time with the KK cutoff time
        if ($capturedTime >= self::KRISHI_KALYAN_CUTOFF_TIMESTAMP)
        {
            $taxComponents[FeeBreakupName::KRISHI_KALYAN_CESS] = self::KRISHI_KALYAN_CESS_PERCENTAGE;
        }

        return $this->feeCalculator->calculateServiceTaxes($fee, $taxComponents);
    }

    protected function matchTaxesAndFeesWithOriginal($txn, $rzpFee, $taxes)
    {
        $originalFee = $txn->getFee();

        $originalTax = $txn->getServiceTax();

        $originalRzpFee = $originalFee - $originalTax;

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

    protected function saveFeeDetails($txn, $feesSplit, $captureTime)
    {
        if (empty($feesSplit) === true)
        {
            return;
        }

        // If ZeroPricing Plan then we save only the RZP Fee.
        if ($txn->getFee() === 0)
        {
            return;
        }

        $this->repo->transaction(function() use ($txn, $feesSplit, $captureTime)
        {
            foreach ($feesSplit as $feeSplit)
            {
                $feeSplit->transaction()->associate($txn);

                $feeSplit->setCreatedAt($captureTime);

                $this->repo->saveOrFail($feeSplit);
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

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }
}
