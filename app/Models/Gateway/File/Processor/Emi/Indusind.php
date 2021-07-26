<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;

class Indusind extends Base
{
    const BANK_CODE   = IFSC::INDB;
    const FILE_TYPE   = FileStore\Type::INDUSIND_EMI_FILE;
    const FILE_NAME   = 'IndusInd_Emi_File';
    const DATE_FORMAT = 'j/n/Y';

    public function generateData(PublicCollection $emiPayments): array
    {
        $data['items'] = $emiPayments->all();

        if($this->mode === "test")
        {
            $monthYear = Carbon::now(Timezone::IST)->format('mY');

            $data['password'] = "razorpay" . $monthYear;
        }
        else {
            $data['password'] = $this->generateEmiFilePassword();
        }

        return $data;
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan['duration'];

            $emiPercent = $emiPlan['rate'] / 100;

            $cardNumber = $emiPayment->card->getMaskedCardNumber();

            $formattedData[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Card Pan'                     => $cardNumber,
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
