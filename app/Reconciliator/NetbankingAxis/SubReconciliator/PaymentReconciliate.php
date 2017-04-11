<?php

namespace RZP\Reconciliator\NetbankingAxis;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Axis;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    protected $netbankingRepo;

    const COLUMN_PAYMENT_REF_NO     = 'PRN No';
    const COLUMN_BANK_PAYMENT_ID    = 'BID';
    const COLUMN_BANK_CUSTOMER_ID   = 'User Id';
    const COLUMN_BANK_CUSTOMER_NAME = 'User Name';

    public function __construct()
    {
        parent::__construct();

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId($row)
    {
        if (isset($row[self::COLUMN_PAYMENT_REF_NO]) === true)
        {
            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[self::COLUMN_BANK_PAYMENT_ID]) === true)
        {
            return $row[self::COLUMN_BANK_PAYMENT_ID];
        }

        return null;
    }

    protected function getNbCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_ID   => $this->getNbCustomerId($row),
            Base\Reconciliate::CUSTOMER_NAME => $this->getNbCustomerName($row),
        ];
    }

    protected function getNbCustomerId($row)
    {
        if (isset($row[self::COLUMN_BANK_CUSTOMER_ID]) === true)
        {
            return $row[self::COLUMN_BANK_CUSTOMER_ID];
        }

        return null;
    }

    protected function getNbCustomerName($row)
    {
        if (isset($row[self::COLUMN_BANK_CUSTOMER_NAME]) === true)
        {
            return $row[self::COLUMN_BANK_CUSTOMER_NAME];
        }

        return null;
    }

    protected function getGatewayPayment($paymentId)
    {
        //
        // Successfully authorized payments are indicated by Y - Status::YES
        // Successfully verified payments are indicated by S - Status::SUCCESS
        // Currently even verified payments are marked as Y, but the older ones are
        // marked by S, so we are keeping both over here
        //
        $statuses = [Axis\Status::YES, Axis\Status::SUCCESS];

        return $this->netbankingRepo->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $statuses);
    }
}
