<?php

namespace RZP\Models\Emi\Banks\Kotak;

use Carbon\Carbon;
use RZP\Models\Emi;
use RZP\Models\Emi\Banks\Base;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Kotak_Emi_File';

    protected $emailIdsToSendTo = ['kotakcards.emi@razorpay.com'];

    protected $bankName  = 'Kotak';

    protected static $headers = [
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
    ];

    protected function writeEmiFile($emiData)
    {
        $url = $this->writeToExcelFile($emiData, $this->getFileToWriteNameWithoutExt());

        $path = $this->getExcelFullFilePath();

        return compact('url', 'path');
    }

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $date = Carbon::createFromTimestamp($emiPayment->getCaptureTimestamp(), 'Asia/Kolkata')->format('M d,Y h:i:s A');

            $emiPlan = $emiPayment->emiPlan;

            $authCode = $this->getAuthCode($emiPayment);

            $data[] = [
                'EMI ID'                     => $emiPayment->getId(),
                'Card Pan'                   => $this->getCardNumber($emiPayment->card),
                'Issuer'                     => 'Kotak',
                'Auth Code'                  => $authCode,
                'Tx Amount'                  => $emiPayment->getAmount()/ 100,
                'Tenure'                     => $emiPlan['duration'],
                'Manufacturer'               => '', // Non Mandatory
                'Merchant Name'              => 'Razorpay Payments',
                'Address1'                   => '', // Non Mandatory
                'Acquirer'                   => '', // Non Mandatory
                'MID'                        => '', // Non Mandatory
                'TID'                        => '', // Non Mandatory
                'Tx Time'                    => $date,
                'Settlement Time'            => '', // Non Mandatory
                'Interest Rate'              => '', // Non Mandatory
                'Discount / Cashback %'      => '0.00%',
                'Discount / Cashback Amount' => '0'
            ];
        }

        return $data;
    }
}
