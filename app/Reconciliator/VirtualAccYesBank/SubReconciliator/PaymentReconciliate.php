<?php

namespace RZP\Reconciliator\VirtualAccYesBank\SubReconciliator;

use Cache;
use Config;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\BankTransfer;
use RZP\Models\Payment\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_UTR                = 'transaction_ref_no';
    const COLUMN_AMOUNT             = 'amount';
    const COLUMN_PAYER_NAME         = 'rmtr_full_name';
    const COLUMN_PAYEE_ACCOUNT      = 'bene_account_no';
    const COLUMN_PAYER_IFSC         = 'rmtr_account_ifsc';
    const COLUMN_TRANS_STATUS       = 'trans_status';
    const COLUMN_VALIDATION_STATUS  = 'validation_status';

    const RECON_STATUS_VALIDATED_OK = 'validated: ok';

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_PAYER_NAME,
    ];

    const PII_COLUMNS = [
        'rmtr_account_no',
        'bene_account_no',
    ];

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

        $amount = $row[self::COLUMN_AMOUNT];

        $bankTransfer = $this->repo
                             ->bank_transfer
                             ->findByUtrAndPayeeAccountAndAmount($utr, $payeeAccount, (int)($amount * 100));

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
            'info_code'     => Base\InfoCode::PAYMENT_ABSENT,
            'utr'           => $row[self::COLUMN_UTR],
            'payee_account' => $row[self::COLUMN_PAYEE_ACCOUNT],
            'gateway'       => $this->gateway,
        ]);
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
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT]);
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
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
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
    public function getGatewayPayment($paymentId)
    {
        return $this->repo->bank_transfer->findByPaymentId($paymentId);
    }

    /**
     * Saving customer Name as beneficiary into the Bank Account
     * that is associated with the bank transfer.
     *
     * @param array        $customerDetails
     * @param PublicEntity $bankTransfer
     */
    protected function persistCustomerName(array $customerDetails, PublicEntity $bankTransfer)
    {
        $customerName = $customerDetails[BaseReconciliate::CUSTOMER_NAME];

        $payerBankAccount = $bankTransfer->payerBankAccount;

        $payerBankAccount->setBeneficiaryName($customerName);

        $this->repo->saveOrFail($payerBankAccount);
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

    /**
     * Customer name is not present in case of Yesbank-to-Yesbank IFT
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

    /**
     * Earlier we used to consider column 'trans_status' and
     * compare it with the value 'CREDITED'. But that led to
     * too many false alerts, so using column 'validation_status' now.
     *
     * @param array $row
     * @return null|string
     */
    protected function getReconPaymentStatus(array $row)
    {
        $reconStatus = trim($row[self::COLUMN_VALIDATION_STATUS]);

        if (strcasecmp($reconStatus, self::RECON_STATUS_VALIDATED_OK) !== 0)
        {
            return Status::FAILED;
        }
    }
}
