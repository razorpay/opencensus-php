<?php

namespace RZP\Models\Transaction;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Payment;
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

    public function migrateOlderTransactions()
    {
        $this->increaseAllowedSystemLimits();

        $response = $this->processEntries();

        return $response;
    }

    public function settleOlderTransactions($input)
    {
        $this->increaseAllowedSystemLimits();

        $case = $input['case'];

        $txnIds = $input['transactionIds'];

        $validator = 'settle' .ucfirst($case) .'Transactions';

        return $this->$validator($txnIds);
    }

    /**
     * Case 1   Service Tax = 12.36% We migrate the transactions as it is.
     * @param  [type] $transactionIds [description]
     * @return [type]                 [description]
     */
    protected function settleCase1Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            if (empty($fees) === true)
            {
                $notMigratedTxns[] = $transactionId;

                continue;
            }

            foreach ($feesSplit as & $feeSplit)
            {
                if ($feeSplit[Transaction\FeeBreakup\Entity::NAME] === FeeBreakupName::SERVICE_TAX)
                {
                    $feeSplit[Transaction\FeeBreakup\Entity::AMOUNT] = $transaction->getServiceTax();
                }
            }

            $this->saveFeeDetails($transaction, $feesSplit, $taxTime);

            $migratedTxns[] = $transactionId;
        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    /**
     * Case 2   ST applied = 12.36 %, actual ST = 14%
     * @param  [type] $transactionIds [description]
     * @return [type]                 [description]
     */
    protected function settleCase2Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            if (empty($fees) === true)
            {
                $notMigratedTxns[] = $transactionId;

                continue;
            }

            foreach ($feesSplit as & $feeSplit)
            {
                if ($feeSplit[Transaction\FeeBreakup\Entity::NAME] === FeeBreakupName::SERVICE_TAX)
                {
                    $feeSplit[Transaction\FeeBreakup\Entity::PERCENTAGE] = 1236;

                    $feeSplit[Transaction\FeeBreakup\Entity::AMOUNT] = $transaction->getServiceTax();
                }
            }

            $this->saveFeeDetails($transaction, $feesSplit, $taxTime);

            $migratedTxns[] = $transactionId;
        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    /**
     * Case 2   ST applied = 14%, actual ST = 14.5%
     * @param  [type] $transactionIds [description]
     * @return [type]                 [description]
     */
    protected function settleCase3Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            if (empty($fees) === true)
            {
                $notMigratedTxns[] = $transactionId;

                continue;
            }

            foreach ($feesSplit as & $feeSplit)
            {
                if ($feeSplit[Transaction\FeeBreakup\Entity::NAME] === FeeBreakupName::SERVICE_TAX)
                {
                    $feeSplit[Transaction\FeeBreakup\Entity::PERCENTAGE] = 1400;

                    $feeSplit[Transaction\FeeBreakup\Entity::AMOUNT] = $transaction->getServiceTax();
                }
            }

            $filtered = $feesSplit->reject(function ($item)
            {
                return $item[Transaction\FeeBreakup\Entity::NAME] === FeeBreakupName::SWACHH_BHARAT_CESS;
            });

            $this->saveFeeDetails($transaction, $filtered, $taxTime);

            $migratedTxns[] = $transactionId;
        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    protected function processEntries()
    {
        $txns = $this->repo->transaction->getTransactionsToBeMigrated();

        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($txns as $txn)
        {
            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($txn, true);

            if (empty($fees) === true)
            {
                continue;
            }

            $shouldSaveFeeDetails = $this->matchTaxesAndFeesWithOriginal($txn, $fees, $totalTax);

            if ($shouldSaveFeeDetails === true)
            {
                $this->saveFeeDetails($txn, $feesSplit, $taxTime);

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

    /**
     * [calculateFeesAndTaxes description]
     * @param  [type] $txn                 [description]
     * @param  [type] $isFeeBearerCustomer If feeBearerCustomer at time of the transaction
     * @return [type]                      [description]
     */
    protected function calculateFeesAndTaxes($txn, $isFeeBearerCustomer)
    {
        $merchant = $txn->merchant;

        $payment = $this->repo->payment->findOrFail($txn->getEntityId());

        $pricingRuleId = $txn->getPricingRule();

        if (isset($pricingRuleId) === false)
        {
            return ['','','',''];
        }

        $pricing = $this->repo->pricing->findOrFail($pricingRuleId);

        $feesSplit = new Base\PublicCollection;

        $this->feeCalculator = new FeeCalculator($payment);

        $amount = $txn->getAmount();

        // Original Amount = Payment Amount - Fee
        // Fee = RZp Fee + ST
        if ($isFeeBearerCustomer === true and $merchant->isFeeBearerCustomer() === true)
        {
            $amount = $payment->getAmount() - $payment->getFee();
        }

        $fees = $this->feeCalculator->calculateRzpFee($pricing, $amount);

        $taxTime = $this->getTaxTime($payment);

        $totalTax = $this->calculateServiceTaxes($fees, $taxTime);

        $feesSplit = $this->feeCalculator->getFeesSplit();

        return [$fees, $totalTax, $feesSplit, $taxTime];
    }

    protected function getTaxTime($payment)
    {
        $gateway = $payment->getGateway();

        $networkCode = null;
        $paymentCard = $payment->card;

        // If payment method is wallet or net banking.
        if ($paymentCard !== null)
        {
            $networkCode = $paymentCard->getNetworkCode();
        }

        if (Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode) === false)
        {
            return $payment->getAuthorizeTimestamp();
        }

        return $payment->getCaptureTimestamp();
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
