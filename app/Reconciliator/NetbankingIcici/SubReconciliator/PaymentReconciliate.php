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

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN';
    const COLUMN_BANK_PAYMENT_ID = 'BID';
    const COLUMN_PAYMENT_DATE    = 'Date';
    const COLUMN_PAYMENT_AMOUNT  = 'Amount';

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
            return trim($row[self::COLUMN_PAYMENT_REF_NO]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (empty($row[self::COLUMN_BANK_PAYMENT_ID]) === false)
        {
            return trim($row[self::COLUMN_BANK_PAYMENT_ID]);
        }

        return null;
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        if ($this->payment->getGateway() === Payment\Gateway::PAYLATER)
        {
            $data = json_decode($gatewayPayment['raw'], true);

            $dbReferenceNumber = $data['bank_payment_id'] ?? null;
        }
        else
        {
            $dbReferenceNumber = trim($gatewayPayment->getBankPaymentId());
        }

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode:: RECON_MISMATCH,
                [
                    'info_code'              => $infoCode,
                    'payment_id'             => $this->payment->getId(),
                    'amount'                 => $this->payment->getAmount(),
                    'db_reference_number'    => $dbReferenceNumber,
                    'recon_reference_number' => $referenceNumber,
                    'gateway'                => $this->gateway
                ]
            );
        }

        if ($this->payment->getGateway() === Payment\Gateway::PAYLATER)
        {
            $data['bank_payment_id'] = $referenceNumber;

            $raw = json_encode($data);

            $gatewayPayment->setRaw($raw);
        }
        else
        {
            $gatewayPayment->setBankPaymentId($referenceNumber);
        }
    }

    public function getGatewayPayment($paymentId)
    {
        if ($this->payment->getGateway() === Payment\Gateway::PAYLATER)
        {
            return $this->repo->mozart->findByPaymentIdAndAction($paymentId, Payment\Action::CAPTURE);
        }
        else
        {
            return $this->repo->netbanking->findByPaymentIdActionAndStatus($paymentId, Action::AUTHORIZE, [Icici\Confirmation::YES]);
        }
    }

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
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount(trim($row[self::COLUMN_PAYMENT_AMOUNT]) ?? null);
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        //
        // Enabling force Auth for all payments because verify API of NB-icici
        // gives wrong status in case of payment retries (i.e. multiple payments are created at
        // gateway/bank side and verify api return the status of failed payment, even though we
        // have received the payment in MIS file, which means payment got success at bank's side)
        //
        $this->allowForceAuthorization = true;
    }

    /**
     * Note :  This method is not in use now, because we have enabled force auth for
     * all payments (see the method 'setAllowForceAuthorization()'). Still keeping
     * it here ,just for reference purpose.
     *
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

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' => [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
