<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;

class Kotak extends Base
{
    const BANK_CODE   = IFSC::KKBK;
    const FILE_TYPE   = FileStore\Type::KOTAK_EMI_FILE;
    const FILE_NAME   = 'Kotak_Emi_File';
    const DATE_FORMAT = 'M d,Y h:i:s A';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $date = $this->getFormattedDate($emiPayment->getCaptureTimestamp());

            $emiPlan = $emiPayment->emiPlan;

            $authCode = $this->getAuthCode($emiPayment);

            $formattedData[] = [
                'EMI ID'                     => $emiPayment->getId(),
                'Card Pan'                   => $this->getCardNumber($emiPayment->card),
                'Issuer'                     => 'Kotak',
                'Auth Code'                  => $authCode,
                'Tx Amount'                  => $emiPayment->getAmount() / 100,
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

        return $formattedData;
    }
}
