<?php

namespace RZP\Reconciliator\NetbankingDbs\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingDbs\Reconciliate;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REF_NO] ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Reconciliate::TRANSACTION_DATE] ?? null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReconPaymentAmount(array $row)
    {
        if (isset($row[Reconciliate::TRANSACTION_AMOUNT]) == false)
        {
            return 0;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::TRANSACTION_AMOUNT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Reconciliate::TRANSACTION_STATUS];

        if ($status === Reconciliate::TRANSACTION_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer'           =>       [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
