<?php

namespace RZP\Reconciliator\NetbankingYesb\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Mozart\NetbankingYesb\Constants;
use RZP\Gateway\Mozart\NetbankingYesb\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        $reconStatus = $this->getReconPaymentStatus($row);

        if ($reconStatus === Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED,
                    'payment_id' => $row[ReconFields::PAYMENT_ID] ?? null,
                    'gateway'    => $this->gateway
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

            return null;
        }

        return $row[ReconFields::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::BANK_REFERENCE_ID] ?? null;
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
        if (empty($row[ReconFields::AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconFields::AMOUNT]);
        }
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->mozart->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbReferenceNumber = $data['bank_payment_id'] ?? null;

        //
        // Sometimes we have db reference number saved as string 'null'.
        // (we encountered few cases in Atom). We don't want to raise data
        // mismatch alert in such cases. so adding a check to compare
        // string 'null'
        //
        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== 'null') and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $data['bank_payment_id'] = $referenceNumber;

        $raw = json_encode($data);

        $gatewayPayment->setRaw($raw);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = null;

        if ($row[ReconFields::TRANSACTION_STATUS] === Constants::RECON_STATUS_FAILED)
        {
            $status = Status::FAILED;
        }
        elseif ($row[ReconFields::TRANSACTION_STATUS] === Constants::RECON_STATUS_SUCCESS)
        {
            $status = Status::AUTHORIZED;
        }

        return $status;
    }

    protected function getArn($row)
    {
        return $this->getReferenceNumber($row);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => $this->getReferenceNumber($row),
            'acquirer'           => [
                Payment\Entity::REFERENCE1 => $this->getReferenceNumber($row),
            ]
        ];
    }
}
