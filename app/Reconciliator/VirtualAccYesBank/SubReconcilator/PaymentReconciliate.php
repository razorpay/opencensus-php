<?php

namespace RZP\Reconciliator\VirtualAccYesBank;

use Cache;
use Config;
use Razorpay\IFSC\IFSC;

use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\BankTransfer;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_UTR           = 'transaction_ref_no';
    const COLUMN_AMOUNT        = 'amount';
    const COLUMN_PAYER_NAME    = 'rmtr_full_name';
    const COLUMN_PAYEE_ACCOUNT = 'bene_account_no';
    const COLUMN_PAYER_IFSC    = 'rmtr_account_ifsc';

    /**
     * Identify the bank transfer using UTR, and thus find payment
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId(array $row)
    {
        if (isset($row[self::COLUMN_UTR]) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_ALERT,
                    'message'       => 'UTR not present in recon file',
                    'row'           => $row,
                    'gateway'       => $this->gateway
                ]);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        $utr = $row[self::COLUMN_UTR];

        $payeeAccount = $row[self::COLUMN_PAYEE_ACCOUNT];

        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByUtrAndPayeeAccount($utr, $payeeAccount);

        if ($bankTransfer === null)
        {
            $this->alertUnexpectedBankTransferIfApplicable($row);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        return $bankTransfer->getPaymentId();
    }

    protected function alertUnexpectedBankTransferIfApplicable(array $row)
    {

        $this->trace->info(TraceCode::BANK_TRANSFER_UNEXPECTED, [
            'message'       => 'Unexpected bank transfer',
            'info_code'     => 'PAYMENT_ABSENT',
            'utr'           => $row[self::COLUMN_UTR],
            'row'           => $row,
        ]);

        $this->app['slack']->queue(
            TraceCode::BANK_TRANSFER_UNEXPECTED,
            $row,
            [
                'channel'  => Config::get('slack.channels.virtual_accounts_log'),
                'username' => 'Scrooge',
                'icon'     => ':x:'
            ]
        );

    }

    /**
     * Gets amount transferred.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getReconPaymentAmount(array $row)
    {
        if(isset($row[self::COLUMN_AMOUNT]) === true)
        {
            return Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
        }

        return null;
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
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway,
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

    protected function persistAccountDetails(array $rowDetails, PublicEntity $bankTransfer)
    {
        if ((empty($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true) or
            (IFSC::validate($rowDetails[BaseReconciliate::ACCOUNT_DETAILS][self::COLUMN_PAYER_IFSC]) === false))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_ALERT,
                    'message'       => 'IFSC given in the recon file is either empty or invalid',
                    'row'           => $rowDetails,
                    'gateway'       => $this->gateway
                ]);

            return null;
        }

        $ifsc = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS][self::COLUMN_PAYER_IFSC];

        $payerBankAccount = $bankTransfer->payerBankAccount;

        if ($payerBankAccount->getIfscCode() !== $ifsc)
        {
            $bankTransfer->setPayerIfsc($ifsc);

            $this->repo->saveOrFail($bankTransfer);

            $payerBankAccount->setIfsc($ifsc);

            $this->repo->saveOrFail($payerBankAccount);
        }
    }

    /**
     *
     * @param  array $row
     * @return string
     */
    protected function getCustomerName(array $row)
    {
        if (empty($row[self::COLUMN_PAYER_NAME]) === false)
        {
            return $row[self::COLUMN_PAYER_NAME];
        }

        return null;
    }

    protected function getAccountDetails($row)
    {
        return [
            self::COLUMN_PAYER_IFSC => $row[self::COLUMN_PAYER_IFSC],
        ];
    }
}
