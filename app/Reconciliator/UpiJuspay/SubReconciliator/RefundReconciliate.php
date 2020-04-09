<?php

namespace RZP\Reconciliator\UpiJuspay\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use Razorpay\Spine\Exception\DbQueryException;


class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const RRN                     = 'rrn';
    const VPA                     = 'vpa';
    const TXN_ID                  = 'txnid';
    const RESPONSE                = 'response';
    const REFUND_ID               = 'refundid';
    const CREDIT_VPA              = 'creditvpa';
    const CUSTOMER_NAME           = 'acnt_custname';
    const ACCOUNT_NUMBER          = 'accountnumber';
    const COLUMN_MOBILE_NO        = 'mobile_no';
    const COLUMN_REFUND_AMOUNT    = 'refund_amount';

    const SUCCESS = 'refund accepted successfully';

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_MOBILE_NO,
        self::VPA,
        self::CUSTOMER_NAME,
        self::ACCOUNT_NUMBER,
        self::CREDIT_VPA,
    ];

    protected function getRefundId(array $row)
    {
        return $row[self::REFUND_ID] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::TXN_ID] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::RRN] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = $row[self::RESPONSE] ?? null;

        if (strtolower($rowStatus) === self::SUCCESS)
        {
            return Payment\Refund\Status::PROCESSED;
        }

        return Payment\Refund\Status::FAILED;
    }

    public function getGatewayRefund(string $refundId)
    {
        $paymentId = $this->refund->payment->getId();
        try
        {
            return $this->repo->mozart->findByPaymentIdAndMapByAction($paymentId, [Action::REFUND])->first();
        }
        catch (DbQueryException $ex)
        {
            $this->trace->traceException($ex);

            return null;
        }
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayRefund)
    {
        $data = json_decode($gatewayRefund['raw'], true);

        $dbGatewayTransactionId = $data['gatewayTransactionId'] ?? null;

        $dbGatewayTransactionId = trim($dbGatewayTransactionId);

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getBaseAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $data['gatewayTransactionId'] = $gatewayTransactionId;

        $raw = json_encode($data);

        $gatewayRefund->setRaw($raw);
    }

    /**
     * Doing nothing here as npci_id (rrn) is not being stored
     * for mozart refund entity.
     *
     * @param string $referenceNumber
     * @param PublicEntity $gatewayRefund
     */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        // do nothing
        return;
    }
}
