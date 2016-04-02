<?php

namespace Models\Emi\Banks\Axis;

use Carbon\Carbon;
use Models\Emi\Service;

class EmiFile extends \Models\Emi\Banks\Base\EmiFile
{
    protected static $fileToWriteName = 'Axis_Emi_File';

    protected static $headers = array(
        'Card Number',
        'Transaction Amount',
        'Transaction Date',
        'Settlement Date',
        'Authorisation Id',
        'Merchant Name',
        'MCC (Merchant Category Code)',
        'Tenure',
        'Source',
        'EMI ID',
    );

    public function generate($input)
    {
        $xt = $this->getEmiData($input);
    }

    protected function getEmiData($input)
    {
        $data = [];
        sd($input);
        foreach ($input as $emiPayment)
        {
            $date = Carbon::createFromTimestamp($emiPayment->getCaptureTimestamp(), 'Asia/Kolkata')->format('d-M-Y');

            $emiTenure = (new Service)->fetch($emiPayment->getEmiPlanId())->getDuration();

            $data[] = array(
            $this->getCardNumber(),
            $emiPayment->getAmount()/100,
            $date,
            $date,
            '',// Auth code -
            $emiPayment->merchant()->getName(),
            $emiPayment->merchant()->getCategory(),
            $emiTenure,
            'Ezetap',
            $emiPayment->getId(),
            );
        }
    }
}
