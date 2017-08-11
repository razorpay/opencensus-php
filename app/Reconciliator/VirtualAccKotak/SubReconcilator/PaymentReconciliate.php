<?php

namespace RZP\Reconciliator\VirtualAccKotak;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const TXN_REF_NO              = 'txn_ref_no';
    const AMOUNT                  = 'amount';
    const SEND_CUST_ACNAME        = 'send_cust_acname';

    /**
     * Identify the bank transfer using UTR, and thus find payment
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId($row)
    {
        if (isset($row[self::TXN_REF_NO]) === true)
        {
            $utr = $row[self::TXN_REF_NO];
        }
        else
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'UTR not present in recon file',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            return null;
        }

        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByUtrOrFail($utr);

        return $bankTransfer->getPaymentId();
    }

    /**
     * Gets amount transferred.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::AMOUNT]) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Convering to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    /**
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getAmount() !== $this->getGatewayPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getAmount(),
                    'row'             => $row,
                    'gateway'         => get_called_class(),
                ]);

            return false;
        }

        return true;
    }

    /**
     * No gateway payment entity for bank transfer payments,
     * but bank_transfer entity is logically equivalent
     *
     * @param $paymentId
     * @return  BankTransfer\Entity
     */
    protected function getGatewayPayment($paymentId)
    {
        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByPaymentId($paymentId);

        return $bankTransfer;
    }

    /**
     * Customer info is just the name associated with the account
     *
     * @param  array $row
     * @return array
     */
    protected function getNbCustomerDetails($row)
    {
        return [
            Base\self::CUSTOMER_NAME => $this->getCustomerName($row),
        ];
    }

    protected function getCustomerName($row)
    {
        if (empty($row[self::SEND_CUST_ACNAME]) === false)
        {
            return $row[self::SEND_CUST_ACNAME];
        }

        return null;
    }
}
