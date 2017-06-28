<?php

namespace RZP\Models\Emi\Banks\Axis;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Axis_Emi_File';

    protected $emailIdsToSendTo = ['axiscards.emi@razorpay.com'];

    protected $bankName  = 'Axis';

    const EXTENSION = FileStore\Format::CSV;

    const TYPE = FileStore\Type::AXIS_EMI_FILE;

    protected static $headers = [
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
    ];

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $this->repo->merchant->fetchMerchantFromEntity($emiPayment);

            $txn = $this->repo->transaction->fetchForPayment($emiPayment);

            $data[] = [
                'Card Number'                  => $this->getCardNumber($emiPayment->card),
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
        }

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d-M-Y');
    }
}
