<?php

namespace RZP\Reconciliator\NetbankingJsb\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Mozart\NetbankingJsb\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = ReconFields::PAYMENT_AMOUNT;

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
        return $row[ReconFields::BANK_REFERENCE_NUMBER] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->mozart->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $data = json_decode($gatewayPayment['raw'], true);

        $dbReferenceNumber = $data['bank_payment_id'] ?? null;

        if ((empty($dbReferenceNumber) === false) and
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
        return (strtolower($row[ReconFields::STATUS]) === 'success') ? Status::AUTHORIZED : Status::FAILED;
    }

    protected function getArn($row)
    {
        return $this->getReferenceNumber($row);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer'  =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
