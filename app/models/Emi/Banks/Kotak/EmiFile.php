<?php

namespace Models\Emi\Banks\Kotak;

class EmiFile extends \Models\Emi\Banks\Base\EmiFile
{
    protected static $fileToWriteName = 'Kotak_Emi_File';

    protected static $headers = array(
        'EMI ID',
        'Card Pan',
        'Issuer',
        'Auth Code',
        'Tx Amount',
        'Tenure',
        'Manufacturer',
        'Merchant Name',
        'Address1',
        'Acquirer',
        'MID',
        'TID',
        'Tx Time',
        'Settlement Time',
        'Interest Rate',
        'Discount / Cashback %',
        'Discount / Cashback Amount',
    );

    public function generate($input)
    {
        $txt = $this->getEmiData($input);
    }

    protected function getEmiData($input)
    {

        $emiPayments = [];

        $data = [];
        foreach ($emiPayments as $emiPayment)
        {
            $date = Carbon::createFromTimestamp($emiPayment->getCaptureTimestamp(), 'Asia/Kolkata')->format('M d,Y h:i:s A');

            $emiPlan = (new Service)->fetch($emiPayment->getEmiPlanId());

            $emiPercent =

            $data[] = array(
                $emiPayment->getId(),
                $this->getCardNumber($emiPayment->card()),
                'Kotak',
                '0XXXx',
                $emiPayment->getAmount()/ 100,
                $emiPlan->getDuration(),
                'Samsung',
                $emiPayment->merchant()->getName(),
                'NA',
                '',// Acquiring bank
                'Acq Mid',
                'Acq Tid',
                $date,
                $date,
                $emiPlan->getRate().'%',
                '0.00%',
                '0');
        }
    }
}
