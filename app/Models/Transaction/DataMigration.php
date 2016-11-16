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

    // Merchant Id: Pricing Plan Id map as per operations_log
    const MERCHANT_PRICING_PLAN_ID_MAP = [
        '4nPFe8aZZJg673' =>  '5DrqKIuYD6ya5e',
        '5ftA5JAyAiCbe7' =>  '1In3Yh5Mluj605',
        '5ifQ003mh9Ehvm' =>  '5j3iRDM7lZZxgt',
        '5jQ8zERcXo8yWL' =>  '5szgxrF9q71nBS',
        '5jsVBeKswCFiMP' =>  '1In3Yh5Mluj605',
        '5ScC7HFSVEut9v' =>  '1In3Yh5Mluj605',
        '5SqDRAKE2a3p6d' =>  '5U0f4CoOEDtAqV',
    ];

    protected $feeCalculator;

    public function migrateOlderTransactions()
    {
        $this->increaseAllowedSystemLimits();

        $response = $this->processEntries();

        return $response;
    }

    public function addPricingRuleForeOlderTransactions()
    {
        $this->increaseAllowedSystemLimits();

        $migratedTxns = [];
        $notMigratedTxns = [];

        $transactions = $this->repo->transaction->getTransactionsToSetPricingId();

        foreach ($transactions as $transaction)
        {
            $merchant = $transaction->merchant;

            $payment = $this->repo->payment->findOrFail($transaction->getEntityId());

            $this->feeCalculator = new FeeCalculator($payment);

            if (array_key_exists($merchant->getId(), self::MERCHANT_PRICING_PLAN_ID_MAP) === false)
            {
                $notMigratedTxns[] = $transaction->getPublicId();

                $this->trace->info(TraceCode::PRICING_RULE_DOES_NOT_EXISTS,
                [
                    'transaction'        => $transaction->toArrayPublic(),
                    'merchant_id'        => $merchant->getId(),
                ]);

                continue;
            }

            $pricingPlanId = self::MERCHANT_PRICING_PLAN_ID_MAP[$merchant->getId()];

            $pricing = $this->repo->pricing->getPricingPlanById($pricingPlanId);

            // Case 1: Merchant is fee bearer now and was fee bearer at the time of transaction
            // Case 2: Merchant is non fee bearer now and was non fee bearer at the time of transaction
            // Case 3: Merchant is fee bearer now but non fee bearer at time of transaction
            // Case 4: Merchant is non fee bearer now but fee bearer at time of transaction
            // Case 5: If non of the above case is there, then pricing rule id is not correct.


            // Case 1 & 2
            list($fee, $serviceTax, $pricingRuleId, $feesSplit) = $this->feeCalculator->calculate($pricing);

            $isValidPricingPlan = $this->isValidFees($transaction, $fee - $serviceTax);

            if ($isValidPricingPlan === true)
            {
                $this->savePricingRule($transaction, $pricingRuleId);

                $migratedTxns[] = $transaction->getPublicId();

                continue;
            }

            $pricing = $this->repo->pricing->findOrFail($pricingRuleId);

            // Case 3
            $amount = $transaction->getAmount();

            $fee = $this->feeCalculator->calculateRzpFee($pricing, $amount);

            $isValidPricingPlan = $this->isValidFees($transaction, $fee);

            if ($isValidPricingPlan === true)
            {
                $this->savePricingRule($transaction, $pricingRuleId);

                $migratedTxns[] = $transaction->getPublicId();

                continue;
            }

            // Case 4
            $amount = $transaction->getAmount() - $transaction->getFee();

            $fee = $this->feeCalculator->calculateRzpFee($pricing, $amount);

            $isValidPricingPlan = $this->isValidFees($transaction, $fee);

            if ($isValidPricingPlan === true)
            {
                $this->savePricingRule($transaction, $pricingRuleId);

                $migratedTxns[] = $transaction->getPublicId();

                continue;
            }

            // Case 5
            $notMigratedTxns[] = $transaction->getPublicId();

        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    protected function savePricingRule($transaction, $pricingRuleId)
    {
        $transaction->setPricingRule($pricingRuleId);

        $this->repo->saveOrFail($transaction);
    }

    // Case 1: Service Tax = 12.36 %. Since it is older transaction we migrate without checking the fees
    //         (a): Merchant is non Fee Bearer at time of transaction
    //         (b): Merchant is Fee Bearer at time of transaction
    // Case 2: Applied Service Tax = 12.36 %, Actual = 14 % We set the amount and percentage as per 12.36
    //         (a): Merchant is non Fee Bearer at time of transaction
    //         (b): Merchant is Fee Bearer at time of transaction
    // Case 3: Applied Service Tax = 14 %, Actual = 14.5 % We set the amount and percentage as per 14. And also remove SB Tax from Fee Split
    //         (a): Merchant is non Fee Bearer at time of transaction
    //         (b): Merchant is Fee Bearer at time of transaction
    // Case 4: Service Tax Percentage is correct but there is fees mismatch (Fee Bearer change)
    //         (a): Merchant is non Fee Bearer at time of transaction
    //         (b): Merchant is Fee Bearer at time of transaction
    public function settleOlderTransactions($input)
    {
        $this->increaseAllowedSystemLimits();

        $case = $input['case'];

        $txnIds = $input['transactionIds'];

        $function = 'settle' .ucfirst($case) .'Transactions';

        return $this->$function($txnIds);
    }

    // Case 1: Service Tax = 12.36 %. Since it is older transaction we migrate without checking the fees
    protected function settleCase1Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            $pricing = $this->getPricingRule($transaction);

            if ($pricing === null)
            {
                $notMigratedTxns[] = $transaction->getPublicId();

                continue;
            }

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            $isValidFees = $this->isValidFees($transaction, $fees);

            if ($isValidFees === false)
            {
                list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, true);

                $isValidFees = $this->isValidFees($transaction, $fees);
            }

            if ($isValidFees === true)
            {
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
            else
            {
                $notMigratedTxns[] = $transactionId;
            }
        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    // Case 2: Applied Service Tax = 12.36 %, Actual = 14 %
    // We set the amount and percentage as per 12.36
    protected function settleCase2Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            $pricing = $this->getPricingRule($transaction);

            if ($pricing === null)
            {
                $notMigratedTxns[] = $transaction->getPublicId();

                continue;
            }

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            $isValidFees = $this->isValidFees($transaction, $fees);

            if ($isValidFees === false)
            {
                list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, true);

                $isValidFees = $this->isValidFees($transaction, $fees);
            }

            if ($isValidFees === true)
            {
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
            else
            {
                $notMigratedTxns[] = $transactionId;
            }

        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    // Case 3: Applied Service Tax = 14 %, Actual = 14.5 %
    // We set the amount and percentage as per 14. And also remove SB Tax from Fee Split
    protected function settleCase3Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            $pricing = $this->getPricingRule($transaction);

            if ($pricing === null)
            {
                $notMigratedTxns[] = $transaction->getPublicId();

                continue;
            }

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            $isValidFees = $this->isValidFees($transaction, $fees);

            if ($isValidFees === false)
            {
                list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, true);

                $isValidFees = $this->isValidFees($transaction, $fees);
            }

            if ($isValidFees === true)
            {
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
            else
            {
                $notMigratedTxns[] = $transactionId;
            }

        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    // Case 4: Fee Bearer change
    protected function settleCase4Transactions($transactionIds)
    {
        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            $pricing = $this->getPricingRule($transaction);

            if ($pricing === null)
            {
                $notMigratedTxns[] = $transaction->getPublicId();

                continue;
            }

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, false);

            $isValidFees = $this->isValidFees($transaction, $fees);

            if ($isValidFees === false)
            {
                list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, true);

                $isValidFees = $this->isValidFees($transaction, $fees);
            }

            if ($isValidFees === true)
            {
                $this->saveFeeDetails($transaction, $filtered, $taxTime);

                $migratedTxns[] = $transactionId;
            }
            else
            {
                $notMigratedTxns[] = $transactionId;
            }
        }

        $response = [
                'migrated'      => $migratedTxns,
                'not_migrated'  => $notMigratedTxns,
        ];

        return $response;
    }

    protected function processEntries()
    {
        $transactions = $this->repo->transaction->getTransactionsToBeMigrated();

        $migratedTxns = [];
        $notMigratedTxns = [];

        foreach ($transactions as $transaction)
        {
            $pricing = $this->getPricingRule($transaction);

            if ($pricing === null)
            {
                $notMigratedTxns[] = $transaction->getPublicId();

                continue;
            }

            $merchant = $transaction->merchant;

            list($fees, $totalTax, $feesSplit, $taxTime) = $this->calculateFeesAndTaxes($transaction, $merchant->isFeeBearerCustomer());

            $shouldSaveFeeDetails = $this->matchTaxesAndFeesWithOriginal($transaction, $fees, $totalTax);

            if ($shouldSaveFeeDetails === true)
            {
                $this->saveFeeDetails($transaction, $feesSplit, $taxTime);

                $migratedTxns[] = $transaction->getPublicId();
            }
            else
            {
                $notMigratedTxns[] = $transaction->getPublicId();
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
    protected function calculateFeesAndTaxes($transaction, $isFeeBearerCustomer)
    {
        $merchant = $transaction->merchant;

        $pricingRuleId = $transaction->getPricingRule();

        $payment = $this->repo->payment->findOrFail($transaction->getEntityId());

        $pricing = $this->repo->pricing->findOrFail($pricingRuleId);

        $feesSplit = new Base\PublicCollection;

        $this->feeCalculator = new FeeCalculator($payment);

        $amount = $transaction->getAmount();

        // Original Amount = Payment Amount - Fee
        // Fee = RZp Fee + ST
        if ($isFeeBearerCustomer === true)
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

    protected function isValidFees($txn, $rzpFee)
    {
        $originalFee = $txn->getFee();

        $originalTax = $txn->getServiceTax();

        $originalRzpFee = $originalFee - $originalTax;

        if ($rzpFee !== $originalRzpFee)
        {
            $this->trace->info(TraceCode::TRANSACTION_MIGRATION_FEE_MISTMATCH,
                [
                    'transaction'        => $txn->toArrayPublic(),
                    'originalRzpFee'     => $originalRzpFee,
                    'calculatedRzpFee'   => $rzpFee,
                    'difference'         => $originalRzpFee - $rzpFee,
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

    protected function getPricingRule($transaction)
    {
        $pricingRuleId = $transaction->getPricingRule();

        if (empty($pricingRuleId) === true)
        {
            $this->trace->info(TraceCode::PRICING_RULE_DOES_NOT_EXISTS,
                [
                    'transaction'       => $transaction->toArrayPublic(),
                    'pricing_rule_id'   => $pricingRuleId,
                ]);

            return null;
        }

        $pricing = null;

        try
        {
            $pricing = $this->repo->pricing->findOrFail($pricingRuleId);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::PRICING_RULE_DOES_NOT_EXISTS,
                [
                    'transaction'       => $transaction->toArrayPublic(),
                    'pricing_rule_id'   => $pricingRuleId,
                ]);
        }

        return $pricing;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }
}
