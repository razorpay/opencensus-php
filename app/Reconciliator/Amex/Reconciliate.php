<?php

namespace RZP\Reconciliator\Amex;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\Amex\SubReconciliator\PaymentReconciliate;

class Reconciliate extends Base\Reconciliate
{

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /**
     * Preprocessing file to check, if we receive payment id in
     * in 'reference_number' column or not. In majority of cases,
     * we do, but for other cases, we preprocess it to replace
     * that column with payment ids.
     *
     * @param array $fileContents
     * @param string $reconciliationType
     */
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        foreach ($fileContents as &$row)
        {
            if (isset($row[PaymentReconciliate::COLUMN_CHARGE_REFERENCE_NUMBER]) === true)
            {
                $paymentIdValue = trim($row[PaymentReconciliate::COLUMN_CHARGE_REFERENCE_NUMBER]);
                $paymentId = $this->getPaymentIdV2($paymentIdValue);

                if ($paymentId !== null) {
                    continue;
                }

                $row[PaymentReconciliate::COLUMN_CHARGE_REFERENCE_NUMBER] = $this->getPaymentIdV1($row) ?? $paymentIdValue;
            }
        }
    }

    private function getPaymentIdV2($paymentIdValue)
    {
        $isValid = Entity::verifyUniqueId($paymentIdValue, false);

        return $isValid ? $paymentIdValue : null;
    }

    private function getPaymentIdV1($row)
    {
        $paymentId = null;

        try
        {
            $ref = trim($row[PaymentReconciliate::COLUMN_CHARGE_REFERENCE_NUMBER]);

            $accountNumber = $row[PaymentReconciliate::COLUMN_MERCHANT_ACCOUNT_NUMBER];

            $paymentId = $this->repo->amex
                              ->findPaymentForGateway(
                                  $ref,
                                  $accountNumber)
                              ->getPaymentId();

        }
        catch (DbQueryException $ex)
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_reference_id'  => $ref,
                    'gateway'               => $this->gateway,
                    'batch_id'              => $this->batchId,
                ]);
        }

        return $paymentId;
    }
}
