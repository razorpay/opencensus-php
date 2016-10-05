<?php

namespace RZP\Models\Emi\Banks\Indusind;

use Carbon\Carbon;

use RZP\Services\TokenEx;
use RZP\Models\Card;
use RZP\Models\Emi\Service;
use RZP\Models\Emi\Banks\Base;
use RZP\Gateway\Base\Action;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'IndusInd_Emi_File';

    protected $emailIdsToSendTo = ['indusind.emi@razorpay.com'];

    protected $bankName  = 'IndusInd';

    protected static $headers = [
            'EMI ID',
            'Card Pan',
            'Issuer',
            'RRN',
            'Auth Code',
            'Tx Amount',
            'EMI_Offer',
            'Manufacturer',
            'Merchant Name',
            'Address1',
            'Store City',
            'Store State',
            'Acquirer',
            'MID',
            'TID',
            'Tx Time',
            'Settlement Time',
            'Customer Processing Fee',
            'Customer Processing Amt',
            'Subvention payable to Issuer',
            'Subvention Amount (Rs.)',
            'Interest Rate',
            'Tx Status',
            'Product Category',
            'Product Sub-Category 1',
            'Product Sub-Category 2',
            'Model Name',
            'Card Hash',
            'EMI Amount',
            'Loan Amount',
            'Discount / Cashback %',
            'Discount / Cashback Amount',
            'Is New Model',
            'Additional Cashback',
            'Reward Point',
            'Txn Type',
        ];

    public function generate($input)
    {
        $txt = $this->getEmiData($input);

        $urlExcel = $this->writeToExcelFile($txt, $this->getFileToWriteNameWithoutExt());

        $fullPath = $this->getExcelFullFilePath();

        $this->sendEmiFile($fullPath);

        return $urlExcel;
    }

    protected function getEmiData($input)
    {
        $emiPayments = [];

        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan['duration'];

            $emiPercent = $emiPlan['rate']/100;

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Card Pan'                     => $this->getCardNumber($emiPayment->card),
                'Issuer'                       => 'INDUSIND',
                'RRN'                          => '',
                'Auth Code'                    => $this->getAuthCode($emiPayment),
                'Tx Amount'                    => $emiPayment->getAmount()/ 100,
                'EMI_Offer'                    => $emiTenure.' Months',
                'Manufacturer'                 => '',
                'Merchant Name'                => 'Razorpay Payments',
                'Address1'                     => '',
                'Store City'                   => '',
                'Store State'                  => '',
                'Acquirer'                     => '',
                'MID'                          => '',
                'TID'                          => '',
                'Tx Time'                      => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
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

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('j/n/Y');
    }
}
