<?php

namespace RZP\Reconciliator\UpiIcici\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Upi\Icici\Action;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Gateway\Upi\Icici\Status as UpiStatus;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    use Base\BharatQrTrait;

    const SUB_MERCHANT_NAME = 'submerchantname';
    const MERCHANT_TRAN_ID  = 'merchanttranid';
    const SERVICE_TAX       = 'service_tax';
    const BANK_TRANS_ID     = 'banktranid';
    const COMMISSION        = 'commission';
    const STATUS            = 'status';
    const AMOUNT            = 'amount';
    const PAYER_VPA         = 'payerva';

    const BLACKLISTED_COLUMNS = [
        self::PAYER_VPA,
    ];

    const SHOULD_ADD_ENTITY_ID_COLUMN = true;

    protected function getPaymentId(array $row)
    {
        if (strpos($row[self::SUB_MERCHANT_NAME], 'BHARAT QR') !== false)
        {
            return $this->getPaymentIdFromBharatQr($row[self::BANK_TRANS_ID], $row);
        }

        return $row[self::MERCHANT_TRAN_ID];
    }

    protected function getReconPaymentStatus(array $row)
    {
        // If status is not set, assuming status to be failed
        $status = $row[self::STATUS];

        if ($status === UpiStatus::SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else if ($this->isPaymentStatusFailed($status) === true)
        {
            return Status::FAILED;
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_CRITICAL_ALERT,
                'message'    => 'Recon status is neither success, rejected or failed',
                'payment_id' => $this->payment->getId(),
                'gateway'    => $this->gateway
            ]);

        return Status::FAILED;
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

    protected function getReferenceNumber($row)
    {
        return $row[self::BANK_TRANS_ID] ?? null;
    }

    public function getGatewayPayment($paymentId)
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
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
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

    protected function getInputForForceAuthorize($row)
    {
        return [
            'vpa'                   => $row[self::PAYER_VPA],
            'gateway_payment_id'    => $row[self::BANK_TRANS_ID],
        ];
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    private final function isPaymentStatusFailed(string $status)
    {
        return (($status === UpiStatus::REJECT) or ($status === UpiStatus::FAILURE));
    }


    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        //
        // Enabling force Auth for all payments because verify API of upi-icici
        // gives wrong status in case of payment retries (i.e. multiple payments are created at
        // gateway/bank side and verify api return the status of failed payment, even though we
        // have received the payment in MIS file, which means payment got success at bank's side)
        //
        $this->allowForceAuthorization = true;
    }
}
