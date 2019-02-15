<?php

namespace RZP\Reconciliator\UpiAxis\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Upi\Axis\Fields;
use RZP\Gateway\Upi\Axis\Action;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base\Reconciliate;
use Razorpay\Spine\Exception\DbQueryException;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const RRN                     = 'rrn';
    const VPA                     = 'vpa';
    const IFSC                    = 'ifsc';
    const TXN_ID                  = 'txnid';
    const COLUMN_PAYMENT_AMOUNT   = 'amount';
    const RESPCODE                = 'respcode';
    const RESPONSE                = 'response';
    const CREDITVPA               = 'creditvpa';
    const ACCOUNT_CUST_NAME       = 'account_cust_name';
    const COLUMN_PAYMENT_ID       = ['order_id', 'orderid'];
    const COLUMN_TRANSACTION_DATE = ['transaction_date', 'txn_date'];

    const ACCOUNT_DETAILS_VPA   = 'vpa';
    const ACCOUNT_DETAILS_IFSC  = 'ifsc';
    const ACCOUNT_DETAILS_NAME  = 'name';

    const SUCCESS = 'Success';

    protected function getPaymentId(array $row)
    {
        //
        // check if the recon status is failed. Return refund
        // Id as null so that such rows don't get processed.
        //
        if ($this->getReconPaymentStatus($row) === Payment\Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            return null;
        }

        $paymentId = array_first(self::COLUMN_PAYMENT_ID, function ($pid) use ($row)
        {
            return (isset($row[$pid]) === true);
        });

        if (UniqueIdEntity::verifyUniqueId($row[$paymentId], false) === false)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::UNEXPECTED_PAYMENT,
                    'row'        => $row,
                    'payment_id' => $row[$paymentId],
                    'gateway'    => $this->gateway
                ]);

            //
            // Setting this unprocessed row as success as we receive such direct settlements daily.
            // And as these payments are expected, not counting them as failure.
            //
            $this->setFailUnprocessedRow(false);

            return null;
        }

        return $row[$paymentId] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::RRN] ?? null;
    }

    protected function getReconVpa($row)
    {
        return $row[self::VPA] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::TXN_ID] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $rowStatus = $row[self::RESPONSE] ?? null;

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
        if (isset($row[self::COLUMN_PAYMENT_AMOUNT]) === false)
        {
            return 0;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT]);
    }

    protected function getGatewayPayment($paymentId)
    {
        try
        {
            return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
        }

        catch (DbQueryException $ex)
        {
            $this->trace->traceException($ex);

            return null;
        }
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $npciRefId = $gatewayPayment->getNpciReferenceId();

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $this->trace->info(TraceCode::RECON_INFO_ALERT, [
                'message'           => 'Npci Reference id is not same as in recon',
                'info_code'         => Base\InfoCode::DATA_MISMATCH,
                'payment_id'        => $this->payment->getId(),
                'amount'            => $this->payment->getBaseAmount(),
                'payment_status'    => $this->payment->getStatus(),
                'api_reference1'    => $npciRefId,
                'recon_reference1'  => $referenceNumber,
                'gateway'           => $this->gateway
            ]);

            return;
        }

        // We will only update the RRN if it is empty
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }

    protected function getAccountDetails($row)
    {
        $accountDetails = [];

        if (empty($row[self::VPA]) === false)
        {
            $accountDetails[self::ACCOUNT_DETAILS_VPA] = $row[self::VPA];
        }

        if (empty($row[self::IFSC]) === false)
        {
            $accountDetails[self::ACCOUNT_DETAILS_IFSC] = $row[self::IFSC];
        }

        if (empty($row[self::ACCOUNT_CUST_NAME]) === false)
        {
            $accountDetails[self::ACCOUNT_DETAILS_NAME] = $row[self::ACCOUNT_CUST_NAME];
        }

        return $accountDetails;
    }

    protected function persistAccountDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        $payerVpa = $gatewayPayment->getVpa();

        $reconVpa = $rowDetails[Reconciliate::ACCOUNT_DETAILS]['vpa'] ?? null;

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
                'info_code'         => Base\InfoCode::VPA_MISMATCH,
                'payment_id'        => $this->payment->getId(),
                'payment_status'    => $this->payment->getStatus(),
                'api_vpa'           => $payerVpa,
                'recon_vpa'         => $reconVpa,
                'gateway'           => $this->gateway
            ]);
        }
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => ($this->reconciled === true) ? 'DUPLICATE_ROW' : 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayTransactionId);
    }

    /**
     * This returns the array of attributes to be saved while force authorizing the payment.
     *
     * @param $row
     * @return array
     */
    protected function getInputForForceAuthorize($row)
    {
        return [
            Fields::RRN   => $this->getReferenceNumber($row),
            Entity::VPA   => $this->getReconVpa($row),
        ];
    }
}
