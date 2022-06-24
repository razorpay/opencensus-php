<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity as EmiPlanEntity;

class Axis extends Base
{
    const BANK_CODE   = IFSC::UTIB;
    const EXTENSION   = FileStore\Format::CSV;
    const FILE_TYPE   = FileStore\Type::AXIS_EMI_FILE;
    const FILE_NAME   = 'Axis_Emi_File';
    const DATE_FORMAT = 'd-M-Y';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $rateofinterest = $emiPayment->emiPlan[EmiPlanEntity::RATE] / 100;

            $txn = $emiPayment->transaction;

            $formattedData[] = [
                'Card Number'                  => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
                'Transaction Amount'           => $emiPayment->getAmount() / 100,
                'Transaction Date'             => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Settlement Date'              => $this->getFormattedDate($txn->getSettledAt()),
                'Authorisation Id'             => $this->getAuthCode($emiPayment),
                'Merchant Name'                => 'Razorpay Payments',
                'MCC (Merchant Category Code)' => $merchant->getCategory(), // Non Mandatory,
                'Tenure'                       => $emiTenure,
                'Source'                       => 'Razorpay',
                'EMI ID'                       => $emiPayment->getId(), // Non Mandatory, filling with our payment id
                'Rate of Interest'             => number_format($rateofinterest, 2, '.', ''),
            ];
        }

        return $formattedData;
    }
}
