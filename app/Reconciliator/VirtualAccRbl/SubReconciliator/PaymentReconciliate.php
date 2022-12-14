<?php

namespace RZP\Reconciliator\VirtualAccRbl\SubReconciliator;

use Cache;
use Config;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\BankTransfer;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_UTR                = ReconciliationFields::UTR_NUMBER;
    const COLUMN_RRN_NUMBER         = ReconciliationFields::RRN_NUMBER;
    const COLUMN_AMOUNT             = ReconciliationFields::AMOUNT;
    const COLUMN_PAYER_NAME         = ReconciliationFields::SENDER_ACCOUNT_NAME;
    const COLUMN_PAYEE_ACCOUNT      = ReconciliationFields::BENEFICIARY_ACCOUNT_NUMBER;
    const COLUMN_TRANSACTION_TYPE   = ReconciliationFields::TRANSACTION_TYPE;

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_PAYER_NAME,
    ];

    const PII_COLUMNS = [
        ReconciliationFields::SENDER_ACCOUNT_NUMBER,
        ReconciliationFields::SENDER_ACCOUNT_NAME,
        ReconciliationFields::BENEFICIARY_ACCOUNT_NUMBER,
        ReconciliationFields::BENEF_NAME,
        ReconciliationFields::SENDER_INFORMATION,
        'sender_acct_no',
        'sender_info',
        'beneficiary_num',
    ];

    /**
     * Identify the bank transfer using UTR, and thus find payment
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId(array $row)
    {
        $utr = $this->getReconUtr($row);

        if (empty($utr) === true)
        {
            $this->trace->info(
                TraceCode::RECON_ALERT,
                [
                    'message'       => 'UTR not present in recon file',
                    'column_utr'    => $row[self::COLUMN_UTR] ?? null,
                    'column_rrn'    => $row[self::COLUMN_RRN_NUMBER] ?? null,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        $payeeAccount = $row[self::COLUMN_PAYEE_ACCOUNT];

        $amount = $row[self::COLUMN_AMOUNT];

        $bankTransfer = $this->repo->bank_transfer->findByUtrAndPayeeAccountAndAmount($utr, $payeeAccount, (int)($amount * 100));

        if ($bankTransfer === null)
        {
            $this->alertUnexpectedBankTransferIfApplicable($utr, $payeeAccount);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        return $bankTransfer->getPaymentId();
    }

    /**
     * Get the UTR for different type of
     * txns i.e. IMPS, UPI, NEFT etc
     *
     * @param array $row
     * @return mixed|null
     */
    protected function getReconUtr(array $row)
    {
        $utr = $row[self::COLUMN_UTR] ?? null;

        $txnType = strtolower($row[self::COLUMN_TRANSACTION_TYPE] ?? null);

        switch ($txnType)
        {
            case 'n':
            case 'neft':
            case 'i':
            case 'ft':
            case 'r':
            case 'rtgs':
                // do nothing, as we have already set the UTR above.
                break;

            // In case of IMPS and UPI, we need to get the
            // actual UTR from the other column RRN_NUMBER.
            case 'imps':
                $utr = null;
                $utrPrefix = substr($row[self::COLUMN_RRN_NUMBER], 0, 4);
                switch ($utrPrefix) {
                    case 'UPI/':
                        // we receive UTR number in this format : UPI/006752404360/PAYMENT FROM PHONEPE/8199080070@Y
                        // 006752404360 is the UTR
                        $pieces = explode('/', $row[self::COLUMN_RRN_NUMBER]);
                        $upiUtr = $pieces[1];

                        if (strlen($upiUtr) === 12)
                        {
                            $utr = $upiUtr;
                        }
                        break;

                    case 'IMPS':

                        $utrType = substr($row[self::COLUMN_RRN_NUMBER], 0, 5);

                        if ($utrType === 'IMPS ')
                        {
                            // we receive UTR narration in this format: IMPS 006713653919 FROM MR ROSS GELLER
                            // 006713653919 is the UTR
                            $value  = trim(preg_replace('/\s+/', ' ', $row[self::COLUMN_RRN_NUMBER]));
                            $pieces = explode(' ', $value);
                        }
                        else if ($utrType === 'IMPS/')
                        {
                            // we receive UTR narration in this format: IMPS/234712686455/RAJANIKANT/UBI/TYPE YOUR
                            // 006713653919 is the UTR
                            $pieces = explode('/', $row[self::COLUMN_RRN_NUMBER]);
                        }

                        $impsUtr = $pieces[1];

                        if (strlen($impsUtr) === 12)
                        {
                            $utr = $impsUtr;
                        }
                        break;

                    default:
                        // we receive UTR narration in this format: 006713653919-ACCOUNT_VALIDATION
                        // 006713653919 is the UTR
                        $pieces = explode('-', $row[self::COLUMN_RRN_NUMBER]);
                        $defaultUtr = $pieces[0];

                        if (strlen($defaultUtr) === 12)
                        {
                            $utr = $defaultUtr;
                        }
                }
        }

        return $utr;
    }

    protected function alertUnexpectedBankTransferIfApplicable($utr, $payeeAccount)
    {
        $this->trace->info(
            TraceCode::BANK_TRANSFER_UNEXPECTED,
            [
                'message'       => 'Unexpected bank transfer',
                'info_code'     => Base\InfoCode::PAYMENT_ABSENT,
                'utr'           => $utr,
                'payee_account' => $payeeAccount,
                'gateway'       => $this->gateway,
                'batch_id'      => $this->batchId,
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
                    'batch_id'        => $this->batchId,
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

        if ($payerBankAccount !== null)
        {
            $payerBankAccount->setBeneficiaryName($customerName);

            $this->repo->saveOrFail($payerBankAccount);
        }
    }

    /**
     * Customer info is just the name associated with the account
     * We do not want to save customer name in case of UPI txn, as
     * we get some random txn id as customer name, so returning empty
     * customer details so that it will not be saved.
     *
     * @param  array $row
     * @return array
     */
    protected function getCustomerDetails($row)
    {
        $txnType = strtolower($row[self::COLUMN_TRANSACTION_TYPE] ?? null);

        if ($txnType === 'imps')
        {
            $utrPrefix = substr($row[self::COLUMN_RRN_NUMBER], 0, 4);

            if ($utrPrefix === 'UPI/')
            {
                return [];
            }
        }

        return [
            Base\Reconciliate::CUSTOMER_NAME => $this->getCustomerName($row),
        ];
    }

    protected function getCustomerName(array $row)
    {
        return $row[self::COLUMN_PAYER_NAME] ?? null;
    }
}
