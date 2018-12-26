<?php

namespace RZP\Reconciliator\NetbankingIcici\SubReconciliator;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Netbanking\Icici;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN';
    const COLUMN_BANK_PAYMENT_ID = 'BID';
    const COLUMN_PAYMENT_DATE    = 'Date';
    const COLUMN_PAYMENT_AMOUNT  = 'Amount';

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (empty($row[self::COLUMN_BANK_PAYMENT_ID]) === false)
        {
            return $row[self::COLUMN_BANK_PAYMENT_ID];
        }

        return null;
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $dbReferenceNumber = trim($gatewayPayment->getBankPaymentId());

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $this->trace->info(
                TraceCode:: RECON_MISMATCH,
                [
                    'info_code'              => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'payment_id'             => $this->payment->getId(),
                    'amount'                 => $this->payment->getAmount(),
                    'db_reference_number'    => $dbReferenceNumber,
                    'recon_reference_number' => $referenceNumber,
                    'gateway'                => $this->gateway
                ]
            );
        }

        $gatewayPayment->setBankPaymentId($referenceNumber);
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     [Icici\Confirmation::YES]);
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT] ?? null);
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = $this->validatePaymentForForceAuthorize($payment);

        //
        // Calling parent function here because for this gateway allowing force authorize is conditional.
        // If payment is not around midnight but payment is given for force_authorize in API call,
        // we force authorize the payment. And if payment is around midnight, we don't check force_authorize input.
        //
        if ($this->allowForceAuthorization === false)
        {
            parent::setAllowForceAuthorization($payment);
        }
    }

    /**
     * This methods checks if payment is made from 11:50 pm to midnight.
     * Only payments made during this time will be force authorized.
     * This is done because tracking api of netbanking ICICI takes payment date into consideration
     * and for payments made during midnight, date saved in ICICI db can be of next day's date which leads to
     * wrong status of payment in tracking/verify response.
     *
     * @param Payment\Entity $payment
     *
     * @return bool
     */
    protected function validatePaymentForForceAuthorize(Payment\Entity $payment)
    {
        $createdTime = $payment->getCreatedAt();

        $createdDate =  Carbon::createFromTimestamp($createdTime, Timezone::IST);

        $nextDate = Carbon::createFromTimestamp($createdTime, Timezone::IST)->endOfDay();

        $difference = $nextDate->diffInSeconds($createdDate);

        if ($difference <= 600)
        {
            return true;
        }

        return false;
    }
}
