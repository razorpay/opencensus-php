<?php

namespace RZP\Models\Emi\Banks\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Emi;
use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;
use RZP\Trace\TraceCode;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Kotak_Emi_File';

    protected $emailIdsToSendTo = ['kotakcards.emi@razorpay.com'];

    protected $bankName  = 'Kotak';

    protected $type = FileStore\Type::KOTAK_EMI_FILE;

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $date = Carbon::createFromTimestamp($emiPayment->getCaptureTimestamp(), Timezone::IST)->format('M d,Y h:i:s A');

            $emiPlan = $emiPayment->emiPlan;

            $authCode = $this->getAuthCode($emiPayment);

            $data[] = [
                'EMI ID'                     => $emiPayment->getId(),
                'Card Pan'                   => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
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

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => $this->bankName,
                ]
            );
        }

        return $data;
    }
}
