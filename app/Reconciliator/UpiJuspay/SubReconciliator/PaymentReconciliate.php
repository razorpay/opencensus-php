<?php

namespace RZP\Reconciliator\UpiJuspay\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\Base\SubReconciliator\Upi;

class PaymentReconciliate extends Upi\UpiPaymentServiceReconciliate
{
    const RRN                     = 'RRN';
    const VPA                     = 'VPA';
    const IFSC                    = 'IFSC';
    const TXN_ID                  = 'TXNID';
    const RESPCODE                = 'RESPCODE';
    const RESPONSE                = 'RESPONSE';
    const CREDITVPA               = 'CREDITVPA';
    const MERCHANT_ID             = 'MERCHANT_ID';
    const COLUMN_MOBILE_NO        = 'MOBILE_NO';
    const ACCOUNT_CUST_NAME       = 'ACCOUNT_CUST_NAME';
    const COLUMN_PAYMENT_ID       = 'ORDERID';
    const COLUMN_PAYMENT_AMOUNT   = 'AMOUNT';
    const MASKED_ACCOUNT_NUMBER   = 'MASKEDACCOUNTNUMBER';

    const ACCOUNT_DETAILS_VPA   = 'vpa';
    const ACCOUNT_DETAILS_IFSC  = 'ifsc';
    const ACCOUNT_DETAILS_NAME  = 'name';

    const SUCCESS = ['success', 'deemed'];


    const BLACKLISTED_COLUMNS = [
        self::ACCOUNT_CUST_NAME,
        self::VPA,
        self::COLUMN_MOBILE_NO,
        self::CREDITVPA,
        self::MASKED_ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        //
        // check if the recon status is failed. Return refund
        // Id as null so that such rows don't get processed.
        //
        $paymentId = $row[self::COLUMN_PAYMENT_ID] ?? null;

        if ($this->getReconPaymentStatus($row) === Payment\Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED ,
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

            return null;
        }

        return $paymentId;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::TXN_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::RRN] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        try
        {
            return  $this->repo->upi->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
        }
        catch (DbQueryException $ex)
        {
            $this->trace->traceException($ex);

            return null;
        }
    }

    protected function getReconPaymentStatus(array $row)
    {
        $rowStatus = strtolower($row[self::RESPONSE] ?? null);

        if (in_array($rowStatus, self::SUCCESS) === true)
        {
            return Payment\Status::AUTHORIZED;
        }

        return Payment\Status::FAILED;
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
                    'base_amount'               => $this->payment->getBaseAmount(),
                    'payment_status'            => $this->payment->getStatus(),
                    'db_reference_number'       => $npciRefId,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
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
        $dbGatewayTransactionId = trim($gatewayPayment->getNpciTransactionId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Gateway Transaction Id in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'base_amount'               => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setNpciTransactionId($gatewayTransactionId);
    }
}
