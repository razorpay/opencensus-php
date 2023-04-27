<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Trace\TraceCode;
use Str;
use Mail;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;


class Kotak extends Base
{
    const BANK_CODE   = IFSC::KKBK;
    const FILE_TYPE   = FileStore\Type::KOTAK_EMI_FILE;
    const FILE_NAME   = 'Kotak_Emi_File';
    const DATE_FORMAT = 'Y-m-d';

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
                'Card Pan'                   => str_repeat('X', 12) . $emiPayment->card->getLast4(),
                'Issuer'                     => 'Kotak',
                'Auth Code'                  => $authCode,
                'Tx Amount'                  => $emiPayment->getAmount() / 100,
                'Tenure'                     => $emiPlan['duration'],
                'Manufacturer'               => '', // Non Mandatory
                'Merchant Name'              => $emiPayment->merchant->getDbaName() ?: 'Razorpay Payments',
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
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        return $formattedData;
    }
}
