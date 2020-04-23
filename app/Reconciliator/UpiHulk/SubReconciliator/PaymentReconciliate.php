<?php

namespace RZP\Reconciliator\UpiHulk\SubReconciliator;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Upi\Hulk\Action;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const NPCI_RESPONSE_CODE    = 'NPCI_RESPONSE_CODE';
    const PAYEE_VIRTUAL_ADDR    = 'PAYEE_VIRTUAL_ADDR';
    const PAYER_VIRTUAL_ADDR    = 'PAYER_VIRTUAL_ADDR';
    const PAYER_AC_NAME         = 'PAYER_AC_NAME';
    const PAYER_IFSC_CODE       = 'PAYER_IFSC_CODE';
    const UPI_TRANSACTION_ID    = 'UPI_TRANSACTION_ID';
    const TRANSACTIONAMOUNT     = 'TRANSACTIONAMOUNT';
    const CUSTOMER_REF_NO       = 'CUSTOMER_REF_NO';
    const TRN_STATUS_DESC       = 'TRN_STATUS_DESC';
    const SETTLEMANT_DATE       = 'SETTLEMANT_DATE';

    const SUCCESS = 'SUCCESS';

    const BLACKLISTED_COLUMNS = [
        self::PAYER_AC_NAME,
        self::PAYER_VIRTUAL_ADDR,
    ];

    protected function getPaymentId(array $row)
    {
        $npciTxnId = $row[self::UPI_TRANSACTION_ID] ?: null;

        if ($npciTxnId === null)
        {
            return null;
        }

        $this->formatUpiRrn($npciTxnId);

        $gatewayPayments = $this->repo->upi->findAllByNpciTxnId($npciTxnId);

        // Very rare scenario, but still would like to know if this happens in future
        if ($gatewayPayments->count() > 1)
        {
            $message = 'multiple gateway payments for same txn id';
        }
        else if ($gatewayPayments->count() === 0)
        {
            $message = 'no payment found for txn id';
        }

        if (empty($message) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'            => TraceCode::RECON_INFO_ALERT,
                    'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_reference_id'  => $npciTxnId,
                    'gateway'               => $this->gateway,
                    'message'               => $message,
                ]);

            return null;
        }

        return $gatewayPayments[0][Entity::PAYMENT_ID];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::CUSTOMER_REF_NO] ?: null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $rowStatus = $row[self::TRN_STATUS_DESC] ?: null;

        if ($rowStatus === self::SUCCESS)
        {
            return Payment\Status::AUTHORIZED;
        }

        return Payment\Status::FAILED;
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
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::TRANSACTIONAMOUNT]);
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $npciRefId = $gatewayPayment->getNpciReferenceId();

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'message'                   => 'Npci Reference id is not same as in recon',
                    'info_code'                 => $infoCode,
                    'payment_id'                => $this->payment->getId(),
                    'payment_status'            => $this->payment->getStatus(),
                    'db_reference_number'       => $npciRefId,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            if ($this->payment->hasBeenAuthorized())
            {
                return false;
            }
        }

        // We will only update the RRN if the payment was never authorized
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    protected function getGatewaySettledAt(array $row)
    {
        $settledAt = $row[self::SETTLEMANT_DATE] ?: null;

        if (empty($settledAt) === true)
        {
            return null;
        }

        return Carbon::createFromFormat('d-m-y H:i:s', $settledAt, Timezone::IST)->getTimestamp();
    }

    protected function getAccountDetails($row)
    {
        $accountDetails = [];

        if (empty($row[self::PAYER_VIRTUAL_ADDR]) === false)
        {
            $accountDetails['vpa'] = $row[self::PAYER_VIRTUAL_ADDR];
        }

        if (empty($row[self::PAYER_IFSC_CODE]) === false)
        {
            $accountDetails['ifsc'] = $row[self::PAYER_IFSC_CODE];
        }

        if (empty($row[self::PAYER_AC_NAME]) === false)
        {
            $accountDetails['name'] = $row[self::PAYER_AC_NAME];
        }

        return $accountDetails;
    }

    protected function persistAccountDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        $payerVpa = $gatewayPayment->getVpa();
        $reconVpa = $rowDetails[Reconciliate::ACCOUNT_DETAILS]['vpa'] ?: null;

        if (($payerVpa === null) and
            (empty($reconVpa) === false))
        {
            $gatewayPayment->fill($rowDetails[Reconciliate::ACCOUNT_DETAILS]);

            $gatewayPayment->generatePspData($rowDetails);

            return;
        }

        if (strtolower($payerVpa) !== strtolower($reconVpa))
        {
            $this->trace->info(TraceCode::RECON_INFO_ALERT, [
                'message'           => 'Payer VPA is not same as in recon',
                'payment_id'        => $this->payment->getId(),
                'payment_status'    => $this->payment->getStatus(),
                'api_vpa'           => $payerVpa,
                'recon_vpa'         => $reconVpa,
                'gateway'           => $this->gateway
            ]);
        }
    }
}
