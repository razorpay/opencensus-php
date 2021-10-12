<?php

namespace RZP\Reconciliator\VirtualAccKotak\SubReconciliator;

use Cache;
use Config;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\BankTransfer;
use RZP\Constants\Timezone;
use RZP\Models\VirtualAccount\Provider;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_UTR           = 'txn_ref_no';
    const COLUMN_AMOUNT        = 'amount';
    const COLUMN_PAYER_NAME    = 'payer_name';
    const COLUMN_PAYEE_ACCOUNT = 'payee_account';
    const COLUMN_PAYER_ACCOUNT = 'payer_account';
    const COLUMN_PAYER_IFSC    = 'payer_ifsc';
    const COLUMN_MODE          = 'mode';
    const COLUMN_DATE          = 'date';
    const COLUMN_TIME          = 'time';

    const TIME_FORMAT = 'd/m/Y H:i:s';

    // 30 minutes
    const BUFFER_TIME = 1800;

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_PAYER_NAME,
    ];

    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->messenger->setSkipSlack(true);
    }

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

        $amount = $row[self::COLUMN_PAYMENT_AMOUNT];

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

    /**
     * Bank Transfer will not be found in two cases:
     *  1) Payment was made to a reserved acc, in which case we can ignore it
     *  2) Kotak did not inform us of the payment via API
     *     2.1) If the payment is recent, we may simply receive the API in a few minutes.
     *          Don't raise alerts for payments less than 30 minutes old (but trace it anyway).
     *     2.2) If payment is old, it's a genuine unexpected bank transfer.
     *          Raise alert, handle it some other way.
     *
     * @param  array  $row
     */
    protected function alertUnexpectedBankTransferIfApplicable(array $row)
    {
        $this->trace->info(TraceCode::BANK_TRANSFER_UNEXPECTED, [
            'message'       => 'Unexpected bank transfer, alert skipped',
            'info_code'     => Base\InfoCode::PAYMENT_ABSENT,
            'utr'           => $row[self::COLUMN_UTR],
            'row'           => $row,
        ]);

        //
        //  Disabling slack alerts for now, for a single large VA recon file
        //

        // Don't alert for recent payments
        /*if (($this->isRecentBankTransfer($row) === false) and
            ($this->isAlreadyAlerted($row) === false))
        {
            $this->app['slack']->queue(
                TraceCode::BANK_TRANSFER_UNEXPECTED,
                $row,
                [
                    'channel'  => Config::get('slack.channels.virtual_accounts_log'),
                    'username' => 'Scrooge',
                    'icon'     => ':x:'
                ]
            );

            $cacheKey = $this->getCacheKey($row);

            Cache::put($cacheKey, $row[self::COLUMN_UTR], 360);
        }*/
    }

    /**
     * Gets amount transferred.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getReconPaymentAmount(array $row)
    {
        $paymentAmount = floatval($row[self::COLUMN_AMOUNT]) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
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

    /**
     * Customer name is not present in case of Kotak-to-Kotak IFT
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
     * Because there is a delay between when a bank transfer is actually made by
     * the customer and when the API call is received by us, it is quite possible
     * for a bank transfer to appear in a recon file before it is processed via
     * API. This will appear to us as an unexpected bank transfer, and will raise
     * an unnecessary alert, only to be fixed later when the API request is received.
     *
     * To avoid this, we ignore very recent bank transfers, i.e. <30 minutes old.
     *
     * @param  array $row
     * @return bool
     */
    protected function isRecentBankTransfer(array $row): bool
    {
        $time = $this->getTimeOfBankTransfer($row);

        $now = Carbon::now()->getTimestamp();

        if (($now - $time) < self::BUFFER_TIME)
        {
            return true;
        }

        return false;
    }

    /**
     * MIS files are combined for the current day. This means if we don't keep a
     * check on which alerts we've already received, we'll receive the same set
     * every hour when the file is sent again.
     *
     * @param  array $row
     * @return bool
     */
    protected function isAlreadyAlerted(array $row): bool
    {
        $cacheKey = $this->getCacheKey($row);

        return (Cache::get($cacheKey) !== null);
    }

    protected function getCacheKey(array $row): string
    {
        $cacheKey = 'slack.bank_transfer_processing_unexpected.' . $row[self::COLUMN_PAYEE_ACCOUNT];

        return $cacheKey;
    }

    /**
     * @param array $row
     * @return int
     */
    protected function getTimeOfBankTransfer(array $row): int
    {
        $dateTime = $row[self::COLUMN_DATE] . ' ' .  $row[self::COLUMN_TIME];

        $carbon = Carbon::createFromFormat(self::TIME_FORMAT, $dateTime, Timezone::IST);

        return $carbon->getTimestamp();
    }
}
