<?php

namespace RZP\Models\Emi\Banks\Axis;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;
use RZP\Trace\TraceCode;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Axis_Emi_File';

    protected $emailIdsToSendTo = ['axiscards.emi@razorpay.com'];

    protected $bankName  = 'Axis';

    protected $type = FileStore\Type::AXIS_EMI_FILE;

    const EXTENSION = FileStore\Format::CSV;

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $txn = $emiPayment->transaction;

            $data[] = [
                'Card Number'                  => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
                'Transaction Amount'           => $emiPayment->getAmount()/100,
                'Transaction Date'             => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Settlement Date'              => $this->formattedDateFromTimestamp($txn->getSettledAt()),
                'Authorisation Id'             => $this->getAuthCode($emiPayment),
                'Merchant Name'                => 'Razorpay Payments',
                'MCC (Merchant Category Code)' => $merchant->getCategory(), // Non Mandatory,
                'Tenure'                       => $emiTenure,
                'Source'                       => 'Razorpay',
                'EMI ID'                       => $emiPayment->getId(), // Non Mandatory, filling with our payment id
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
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d-M-Y');
    }
}
