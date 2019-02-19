<?php

namespace RZP\Models\Transaction;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Base\RuntimeManager;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Transaction\FeeBreakup as FeeBreakup;


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

    public function createFeeBreakupForTransaction($input)
    {
        $this->increaseAllowedSystemLimits();

        $transactionIds = $input['transactionIds'];

        $response = [];

        foreach ($transactionIds as $transactionId)
        {
            $transaction = $this->repo->transaction->findByPublicId($transactionId);

            $merchant = $transaction->merchant;

            $payment = $this->repo->payment->findOrFail($transaction->getEntityId());

            $this->feeCalculator = Calculator\Base::make($payment);

            $taxTime = $this->getTaxTime($payment);

            $feesSplit = new Base\PublicCollection;

            if ($transaction->getFee() === 0)
            {
                $response[$transactionId] = 'Transaction Fees is 0';

                continue;
            }

            $pricingPlanId = $merchant->getPricingPlanId();

            $pricing = $this->repo->pricing->getPricingPlanById($pricingPlanId);

            list($fee, $serviceTax, $feesSplit) = $this->feeCalculator->calculate($pricing);

            $isValidPricing = $this->matchTaxesAndFeesWithOriginal($transaction, $fee - $serviceTax, $serviceTax);

            if ($isValidPricing === true)
            {
                $this->saveFeeDetails($transaction, $feesSplit, $taxTime);

                $response[$transactionId] = 'Successfully migrated';
            }
            else
            {
                $response[$transactionId] = 'Fees Mismatch';
            }
        }
        return $response;
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

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(1000);
    }
}
