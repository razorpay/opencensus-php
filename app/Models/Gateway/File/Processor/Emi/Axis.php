<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;

class Axis extends Base
{
    const BANK_CODE   = IFSC::UTIB;
    const EXTENSION   = FileStore\Format::CSV;
    const FILE_TYPE   = FileStore\Type::AXIS_EMI_FILE;
    const FILE_NAME   = 'Axis_Emi_File';
    const DATE_FORMAT = 'd-M-Y';

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data['items'] as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $txn = $emiPayment->transaction;

            $formattedData[] = [
                'Card Number'                  => $this->getCardNumber($emiPayment->card),
                'Transaction Amount'           => $emiPayment->getAmount() / 100,
                'Transaction Date'             => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Settlement Date'              => $this->getFormattedDate($txn->getSettledAt()),
                'Authorisation Id'             => $this->getAuthCode($emiPayment),
                'Merchant Name'                => 'Razorpay Payments',
                'MCC (Merchant Category Code)' => $merchant->getCategory(), // Non Mandatory,
                'Tenure'                       => $emiTenure,
                'Source'                       => 'Razorpay',
                'EMI ID'                       => $emiPayment->getId(), // Non Mandatory, filling with our payment id
            ];
        }

        return $formattedData;
    }
}
