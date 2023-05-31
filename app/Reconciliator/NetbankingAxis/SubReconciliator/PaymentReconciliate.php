<?php

namespace RZP\Reconciliator\NetbankingAxis\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Gateway\Netbanking\Axis;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_REF_NO     = 'PRN';
    const COLUMN_BANK_PAYMENT_ID    = 'TXN ID';
    const COLUMN_BANK_CUSTOMER_ID   = 'User ID';
    const COLUMN_BANK_CUSTOMER_NAME = 'User Name';
    const COLUMN_PAYMENT_AMOUNT     = 'Amount';

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_BANK_CUSTOMER_NAME,
    ];

    protected function handleAlreadyReconciled(string $entityId, int $reconciledAt = null)
    {
        // For axis we get can same payment multiple times with different BID, hence we need to prevent
        // incrementing success count for such entries
        $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::ALREADY_RECONCILED, $reconciledAt);

        $this->setSummaryCount(self::FAILURES_SUMMARY, $entityId);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $bankPaymentId = $this->getReferenceNumber($row);

        $bankPaymentIdInDB = null;

        if(($this->payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE) or
            ($this->payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            $bankPaymentIdInDB = $this->payment->getReference1();
        }
        else
        {
            $bankPaymentIdInDB = $this->gatewayPayment->getBankPaymentId();
        }

        // Duplicate entry, fail the row
        if($bankPaymentIdInDB !== null && $bankPaymentIdInDB !== $bankPaymentId)
        {
            return Status::FAILED;
        }

        // case of late auth when gateway_payment_id is not present in DB, the move ahead and
        // try authorizing failed payment on api
        // or if bank id in db matches with on the on in row.. consider this row as success
    }

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (empty($row[self::COLUMN_BANK_PAYMENT_ID]) === false)
        {
            return $row[self::COLUMN_BANK_PAYMENT_ID];
        }

        return null;
    }

    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_ID   => $this->getNbCustomerId($row),
            Base\Reconciliate::CUSTOMER_NAME => $this->getNbCustomerName($row),
        ];
    }

    protected function getNbCustomerId($row)
    {
        if (empty($row[self::COLUMN_BANK_CUSTOMER_ID]) === false)
        {
            return $row[self::COLUMN_BANK_CUSTOMER_ID];
        }

        return null;
    }

    protected function getNbCustomerName($row)
    {
        if (empty($row[self::COLUMN_BANK_CUSTOMER_NAME]) === false)
        {
            return $row[self::COLUMN_BANK_CUSTOMER_NAME];
        }

        return null;
    }

    public function getGatewayPayment($paymentId)
    {
        //
        // Successfully authorized payments are indicated by Y - Status::YES
        // Successfully verified payments are indicated by S - Status::SUCCESS
        // Currently even verified payments are marked as Y, but the older ones are
        // marked by S, so we are keeping both over here
        //
        $statuses = [Axis\Status::YES, Axis\Status::SUCCESS];

        return $this->repo->netbanking->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $statuses);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id'    => $this->getReferenceNumber($row),
            'acquirer'              =>       [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
