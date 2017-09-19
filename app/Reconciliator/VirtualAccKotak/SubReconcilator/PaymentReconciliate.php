<?php

namespace RZP\Reconciliator\VirtualAccKotak;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount\Provider;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_UTR           = 'txn_ref_no';
    const COLUMN_AMOUNT        = 'amount';
    const COLUMN_PAYER_NAME    = 'send_cust_acname';
    const COLUMN_PAYEE_ACCOUNT = 'e_coll_ac_no';
    const COLUMN_PAYER_ACCOUNT = 'send_cust_ac_no';
    const COLUMN_PAYER_IFSC    = 'snd_brn_ifsc';

    /**
     * Identify the bank transfer using UTR, and thus find payment
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId(array $row)
    {
        if (isset($row[self::COLUMN_UTR]) === true)
        {
            $utr = $row[self::COLUMN_UTR];
        }
        else
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_ALERT,
                    'message'       => 'UTR not present in recon file',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            return null;
        }

        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByUtr($utr);

        // Bank Transfer will not be found in two cases:
        // 1) Payment was made to a reserved acc, in which case we can ignore it
        // 2) Kotak did not inform us of the payment via API, in which case we
        //    create a payment now
        if ($bankTransfer === null)
        {
            if ($this->isPaymentToReservedAccount($row) === true)
            {
                return null;
            }

            $bankTransfer = $this->createBankTransferPayment($row);
        }

        return $bankTransfer->getPaymentId();
    }

    /**
     * Gets amount transferred.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getGatewayPaymentAmount(array $row)
    {
        $paymentAmount = floatval($row[self::COLUMN_AMOUNT]) * 100;

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
    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_NAME => $this->getCustomerName($row),
        ];
    }

    protected function getCustomerName(array $row)
    {
        if (empty($row[self::COLUMN_PAYER_NAME]) === false)
        {
            return $row[self::COLUMN_PAYER_NAME];
        }

        return null;
    }

    protected function isPaymentToReservedAccount(array $row)
    {
        $payeeAccount = $row[self::COLUMN_PAYEE_ACCOUNT];

        if (Provider::isReservedAccount($payeeAccount, Provider::KOTAK) === true)
        {
            $this->trace->info(TraceCode::BANK_TRANSFER_RESERVED_ACCOUNT, $row);

            return true;
        }

        return false;
    }

    /**
     * An unexpected bank transfer is present in the MIS file.
     * one which is not present in the DB, because Kotak failed
     * to hit the bank_transfer_process API for a payment.
     *
     * This is a problem, as Kotak ought to be retrying, but we
     * can handle it here by creating the payment now.
     *
     * @param  array  $row
     * @return BankTransfer\Entity
     */
    protected function createBankTransferPayment(array $row)
    {
        $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Unexpected bank transfer',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

        $payeeIfsc = Provider::DEFAULT_DETAILS[Provider::KOTAK]['ifsc_code'];
        $time      = Carbon::now()->getTimestamp();

        $data = [
            BankTransfer\Entity::PAYER_NAME     => $row[self::COLUMN_PAYER_NAME],
            BankTransfer\Entity::PAYER_ACCOUNT  => $row[self::COLUMN_PAYER_ACCOUNT],
            BankTransfer\Entity::PAYER_IFSC     => $row[self::COLUMN_PAYER_IFSC],
            BankTransfer\Entity::AMOUNT         => $row[self::COLUMN_AMOUNT],
            BankTransfer\Entity::REQ_UTR        => $row[self::COLUMN_UTR],
            BankTransfer\Entity::PAYEE_ACCOUNT  => $row[self::COLUMN_PAYEE_ACCOUNT],
            BankTransfer\Entity::PAYEE_IFSC     => $payeeIfsc,
            BankTransfer\Entity::TIME           => $time,
            // Hack, until mode is added to the recon files
            BankTransfer\Entity::MODE           => 'neft',
        ];

        return (new BankTransfer\Core)->process($data);
    }
}
