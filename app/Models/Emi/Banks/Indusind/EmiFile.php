<?php

namespace RZP\Models\Emi\Banks\Indusind;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Card;
use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;
use RZP\Trace\TraceCode;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'IndusInd_Emi_File';

    protected $emailIdsToSendTo = ['indusind.emi@razorpay.com'];

    protected $bankName  = 'IndusInd';

    protected $type = FileStore\Type::INDUSIND_EMI_FILE;

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan['duration'];

            $emiPercent = $emiPlan['rate']/100;

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Card Pan'                     => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
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

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => $this->bankName,
                ]
            );
        }

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('j/n/Y');
    }
}
