<?php

namespace RZP\Models\Transaction;

use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Transaction;
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


    public function postMigrateOlderTransactions()
    {
        $txns = $this->repo->transaction->getTransactionsToBeMigrated();

        foreach ($txns as $txn)
        {
            $payment = $this->repo->payment->findOrFail($txn->getEntityId());

            $pricingRuleId = $txn->getPricingRule();

            $pricing = $this->repo->pricing->findOrFail($pricingRuleId);

            list($fees, $feesSplit) = $this->getRzpFeesUsingPercentOfOriginalAmount($txn->getAmount(), $pricing->getPercentRate(), $pricing->getFixedRate());

            $feesSplit = $this->calculateServiceTaxes($fees, $feesSplit, $payment->getCaptureTimestamp());

            $this->saveFeeDetails($txn, $feesSplit, $payment->getCaptureTimestamp());
        }

        return $txns->toArrayPublic();
    }

    protected function calculateServiceTaxes($fee, $feesSplit, $capturedTime)
    {
        // Checking the capture time with the ST cutoff time
        if ($capturedTime < self::SERVICE_TAX_CUTOFF_TIMESTAMP)
        {
            $serviceTaxValue = (int) ceil(($fee * self::SERVICE_TAX_PERCENTAGE_BEFORE_CUTOFF)/10000);
            $serviceTaxFeeBreakup = $this->createFeeBreakup(FeeBreakupName::SERVICE_TAX, self::SERVICE_TAX_PERCENTAGE_BEFORE_CUTOFF, $serviceTaxValue, FeeBreakupType::PERCENTAGE);
        }
        else
        {
            $serviceTaxValue = (int) ceil(($fee * self::SERVICE_TAX_PERCENTAGE_AFTER_CUTOFF)/10000);
            $serviceTaxFeeBreakup = $this->createFeeBreakup(FeeBreakupName::SERVICE_TAX, self::SERVICE_TAX_PERCENTAGE_AFTER_CUTOFF, $serviceTaxValue, FeeBreakupType::PERCENTAGE);
        }

        array_push($feesSplit, $serviceTaxFeeBreakup);

        // Checking the capture time with the SB cutoff time
        if ($capturedTime >= self::SWACH_BHARAT_CUTOFF_TIMESTAMP)
        {
            $swachhBharatCessValue = (int) ceil(($fee * self::SWACHH_BHARAT_CESS_PERCENTAGE)/10000);
            $swachhBharatCessFeeBreakup = $this->createFeeBreakup(FeeBreakupName::SWACHH_BHARAT_CESS, self::SWACHH_BHARAT_CESS_PERCENTAGE, $swachhBharatCessValue, FeeBreakupType::PERCENTAGE);

            array_push($feesSplit, $swachhBharatCessFeeBreakup);
        }

        // Checking the capture time with the KK cutoff time
        if ($capturedTime >= self::KRISHI_KALYAN_CUTOFF_TIMESTAMP)
        {
            $krishiKalyanCessValue = (int) ceil(($fee * self::KRISHI_KALYAN_CESS_PERCENTAGE)/10000);
            $krishiKalyanCessFeeBreakup = $this->createFeeBreakup(FeeBreakupName::KRISHI_KALYAN_CESS, self::KRISHI_KALYAN_CESS_PERCENTAGE, $krishiKalyanCessValue, FeeBreakupType::PERCENTAGE);

            array_push($feesSplit, $krishiKalyanCessFeeBreakup);
        }

        return $feesSplit;
    }

    protected function getRzpFeesUsingPercentOfOriginalAmount($amount, $percent, $fixed)
    {
        $percentageAmount = (int) ceil(($amount * $percent)/10000);
        $totalAmount = $percentageAmount + $fixed;

        $feesSplit = array();

        if (empty($percent) === false)
        {
            $rzpPercentageFeeBreakup = $this->createFeeBreakup(FeeBreakupName::RZP, $percent, $percentageAmount, FeeBreakupType::PERCENTAGE);

            array_push($feesSplit, $rzpPercentageFeeBreakup);
        }

        if (empty($fixed) === false)
        {
            $rzpFixedFeeBreakup = $this->createFeeBreakup(FeeBreakupName::RZP, 0, $fixed, FeeBreakupType::FIXED);

            array_push($feesSplit, $rzpFixedFeeBreakup);
        }

        return array($totalAmount, $feesSplit);
    }

    protected function createFeeBreakup($name, $percent, $amount, $type)
    {
        $params = [
            FeeBreakup\Entity::NAME         => $name,
            FeeBreakup\Entity::PERCENTAGE   => $percent,
            FeeBreakup\Entity::AMOUNT       => $amount,
            FeeBreakup\Entity::TYPE         => $type,
        ];

        $feeBreakup = (new FeeBreakup\Entity)->build($params);

        return $feeBreakup;
    }

    protected function saveFeeDetails($txn, $feesSplit, $captureTime)
    {
        if (empty($feesSplit) == true)
        {
            return;
        }

        $this->repo->transaction(function() use ($txn, $feesSplit, $captureTime)
        {
            foreach ($feesSplit as $feeSplit)
            {
                $feeSplit->transaction()->associate($txn);

                $feeSplit->setCreatedAt($captureTime);

                $this->repo->fee_breakup->saveOrFail($feeSplit);
            }
        });
    }
}
