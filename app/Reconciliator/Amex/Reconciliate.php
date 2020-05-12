<?php

namespace RZP\Reconciliator\Amex;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Entity;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\Amex\SubReconciliator\PaymentReconciliate;

class Reconciliate extends Base\Reconciliate
{

    const KEY_COLUMN_NAMES = [
        'type' => self::PAYMENT,
    ];

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /*
     * in amex recon file there are some rows that are present before actual payments
     * we need to jump to that line to start processing, this line number keeps changing
     * so getting the column header from where to start
     */
    public function getKeyColumnNames(array $fileDetails = [])
    {
        return self::KEY_COLUMN_NAMES;
    }

    /**
     * Preprocessing file to check, if we receive payment id in
     * in 'reference_number' column or not. In majority of cases,
     * we do, but for other cases, we preprocess it to replace
     * that column with payment ids.
     *
     * @param array $fileContents
     */
    protected function preProcessFileContents(array &$fileContents)
    {
        foreach ($fileContents as &$row)
        {
            if (isset($row[PaymentReconciliate::COLUMN_GATEWAY_PAYMENT_ID]) === true)
            {
                $paymentIdValue = $row[PaymentReconciliate::COLUMN_GATEWAY_PAYMENT_ID];
                $paymentId = $this->getPaymentIdV2($paymentIdValue);

                if ($paymentId !== null) {
                    continue;
                }

                $row[PaymentReconciliate::COLUMN_GATEWAY_PAYMENT_ID] = $this->getPaymentIdV1($row) ?? $paymentIdValue;
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
            $ref = $row[PaymentReconciliate::COLUMN_GATEWAY_PAYMENT_ID];

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
                    'batch_id'              => $this->messenger->batch->getId(),
                ]);
        }

        return $paymentId;
    }
}
