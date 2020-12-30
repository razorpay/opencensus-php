<?php

namespace RZP\Reconciliator\NetbankingIbk\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Entity;
use RZP\Gateway\Mozart\NetbankingIbk\ReconFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = ReconFields::AMOUNT;

    protected function getPaymentId(array $row)
    {
        return $row[ReconFields::MERCHANT_REF_NO] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconFields::BANK_REF_NO] ?? null;
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconFields::AMOUNT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[ReconFields::PAID_STATUS];

        if ($status === ReconFields::PAYMENT_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[ReconFields::DATE_TIME] ?? null;
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
                Entity::REFERENCE1 => $this->getReferenceNumber($row),
            ]
        ];
    }
}
