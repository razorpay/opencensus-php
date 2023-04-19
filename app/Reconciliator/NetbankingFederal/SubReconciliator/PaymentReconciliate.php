<?php

namespace RZP\Reconciliator\NetbankingFederal\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Federal;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_REF_NO  = 'agg_reference_id';
    const ATDR                   = 'atdr';
    const GST                    = 'gst';

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
            if (strpos($row[self::COLUMN_PAYMENT_REF_NO], '.') !== false)
            {
                return explode('.', $row[self::COLUMN_PAYMENT_REF_NO])[0];
            }

            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

//     protected function getReferenceNumber($row)
//     {
//         if (empty($row[self::COLUMN_BANK_PAYMENT_ID]) === false)
//         {
//             return $row[self::COLUMN_BANK_PAYMENT_ID];
//         }
//
//         return null;
//     }

    public function getGatewayPayment($paymentId)
    {
        $status = [Federal\Status::getAuthSuccessStatus()];

        return $this->repo->netbanking->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $status);
    }

    protected function getGatewayFee($row)
    {
        $gatewayFee = 0;

        if(isset($row[self::ATDR]) === false)
        {
            $this->reportMissingColumn($row,self::ATDR);

            return $gatewayFee;
        }

        $gatewayFee += Helper::getIntegerFormattedAmount($row[self::ATDR]);

        $serviceTax = $this->getGatewayServiceTax($row);

        $gatewayFee += $serviceTax;

        return $gatewayFee;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = 0;

        if(isset($row[self::GST])===false)
        {
            $this->reportMissingColumn($row,self::GST);

            return $serviceTax;
        }

        $gst = $row[self::GST];

        $serviceTax += Helper::getIntegerFormattedAmount($gst);

        return $serviceTax;
    }
}
