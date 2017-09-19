<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;

class Indusind extends Base
{
    const BANK_CODE   = IFSC::INDB;
    const FILE_TYPE   = FileStore\Type::INDUSIND_EMI_FILE;
    const FILE_NAME   = 'IndusInd_Emi_File';
    const DATE_FORMAT = 'j/n/Y';

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan['duration'];

            $emiPercent = $emiPlan['rate'] / 100;

            $formattedData[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Card Pan'                     => $this->getCardNumber($emiPayment->card),
                'Issuer'                       => 'INDUSIND',
                'RRN'                          => '',
                'Auth Code'                    => $this->getAuthCode($emiPayment),
                'Tx Amount'                    => $emiPayment->getAmount() / 100,
                'EMI_Offer'                    => $emiTenure.' Months',
                'Manufacturer'                 => '',
                'Merchant Name'                => 'Razorpay Payments',
                'Address1'                     => '',
                'Store City'                   => '',
                'Store State'                  => '',
                'Acquirer'                     => '',
                'MID'                          => '',
                'TID'                          => '',
                'Tx Time'                      => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Settlement Time'              => '',
                'Customer Processing Fee'      => '',
                'Customer Processing Amt'      => '',
                'Subvention payable to Issuer' => '',
                'Subvention Amount (Rs.)'      => '',
                'Interest Rate'                => $emiPercent.'%',
                'Tx Status'                    => '',
                'Product Category'             => '',
                'Product Sub-Category 1'       => '',
                'Product Sub-Category 2'       => '',
                'Model Name'                   => '',
                'Card Hash'                    => '',
                'EMI Amount'                   => '',
                'Loan Amount'                  => '',
                'Discount / Cashback %'        => '',
                'Discount / Cashback Amount'   => '',
                'Is New Model'                 => '',
                'Additional Cashback'          => '',
                'Reward Point'                 => '',
                'Txn Type'                     => '',
            ];
        }

        return $formattedData;
    }
}
