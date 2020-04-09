<?php

namespace RZP\Reconciliator\UpiJuspay\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\Reconciliate;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const RRN                     = 'rrn';
    const VPA                     = 'vpa';
    const IFSC                    = 'ifsc';
    const TXN_ID                  = 'txnid';
    const RESPCODE                = 'respcode';
    const RESPONSE                = 'response';
    const CREDITVPA               = 'creditvpa';
    const MERCHANT_ID             = 'merchant_id';
    const COLUMN_MOBILE_NO        = 'mobile_no';
    const ACCOUNT_CUST_NAME       = 'account_cust_name';
    const COLUMN_PAYMENT_ID       = 'orderid';
    const COLUMN_PAYMENT_AMOUNT   = 'amount';
    const MASKED_ACCOUNT_NUMBER   = 'maskedaccountnumber';

    const ACCOUNT_DETAILS_VPA   = 'vpa';
    const ACCOUNT_DETAILS_IFSC  = 'ifsc';
    const ACCOUNT_DETAILS_NAME  = 'name';

    const SUCCESS = 'success';

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
            return $this->repo->mozart->findByPaymentIdAndMapByAction($paymentId, [Action::AUTHORIZE])->first();
        }
        catch (DbQueryException $ex)
        {
            $this->trace->traceException($ex);

            return null;
        }
    }

    protected function getReconPaymentStatus(array $row)
    {
        $rowStatus = $row[self::RESPONSE] ?? null;

        if (strtolower($rowStatus) === self::SUCCESS)
        {
            return Payment\Status::AUTHORIZED;
        }

        return Payment\Status::FAILED;
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $npciRefId = $data['rrn'] ?? null;

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => Base\InfoCode::DATA_MISMATCH,
                    'message'                   => 'Npci Reference id is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $npciRefId,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $data['rrn'] = $referenceNumber;

        $raw = json_encode($data);

        $gatewayPayment->setRaw($raw);
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
        if (empty($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true)
        {
            return;
        }

        $data = json_decode($gatewayPayment['raw'], true);

        $payerVpa = $data['payerVpa'] ?? null;

        $reconVpa = $rowDetails[Reconciliate::ACCOUNT_DETAILS]['vpa'] ?? null;

        if ((empty($payerVpa) === false) and
            (strtolower($payerVpa) !== strtolower($reconVpa)))
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

        $data['payerVpa'] = $reconVpa;

        $raw = json_encode($data);

        $gatewayPayment->setRaw($raw);
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbGatewayTransactionId = $data['gatewayTransactionId'] ?? null;

        $dbGatewayTransactionId = trim($dbGatewayTransactionId);

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => Base\InfoCode::DATA_MISMATCH,
                    'message'                   => 'Gateway Transaction Id in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $data['gatewayTransactionId'] = $gatewayTransactionId;

        $raw = json_encode($data);

        $gatewayPayment->setRaw($raw);
    }
}
