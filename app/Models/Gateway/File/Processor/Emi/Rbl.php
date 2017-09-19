<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity;

class Rbl extends Base
{
    const BANK_CODE   = IFSC::RATN;
    const FILE_TYPE   = FileStore\Type::RBL_EMI_FILE;
    const FILE_NAME   = 'Rbl_Emi_File';
    const DATE_FORMAT = 'd-M-y';

    protected function formatDataForFile()
    {
        $formattedData = [];

        foreach ($this->data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount() / 100;

            $rate = $emiPlan[Entity::RATE] / 100;

            $tenure = $emiPlan[Entity::DURATION];

            $issuerPlanId = $emiPlan[Entity::ISSUER_PLAN_ID];

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $formattedData[] = [
                'EMI ID'                           => $emiPayment->getId(),
                'RBL Card no'                      => $this->getCardNumber($emiPayment->card),
                'Issuer'                           => 'RBL Bank',
                'Acquirer'                         => '',
                'Aggregator Merchant Name'         => 'RAZORPAY',
                'Manufacturer'                     => '',
                'Auth Code'                        => $this->getAuthCode($emiPayment),
                'Tx Amount'                        => $principalAmount,
                'EMI Offer'                        => $tenure,
                'EMI Plan ID'                      => $issuerPlanId,
                'Customer Name'                    => '',
                'Mobile No'                        => '',
                'Store Name'                       => '',
                'Address1'                         => '',
                'Store City'                       => '',
                'Store State'                      => '',
                'MID'                              => '',
                'TID'                              => '',
                'Tx Time'                          => $this->getFormattedDate($emiPayment->getAuthorizeTimestamp()),
                'Subvention payable to Issuer'     => '',
                'Subvention Amount (Rs.)'          => '',
                'Interest Rate'                    => $rate,
                'Customer Processing Fee'          => '',
                'Customer Processing Amount (Rs.)' => '',
                'Tx Status'                        => '',
                'Status'                           => 'Success',
                'Description'                      => 'Online',
                'Product Category'                 => '',
                'Product Sub-Category 1'           => '',
                'Product Sub-Category 2'           => '',
                'Model Name'                       => '',
                'Merchant Name'                    => '',
                'EMI Amount'                       => $emiAmount,
                'Loan Amount'                      => $principalAmount,
                'Discount / Cashback %'            => '',
                'Discount / Cashback Amount'       => '',
                'Additional Cashback'              => '',
                'Bonus Reward Points'              => '',
                'EMI Model'                        => 'Y',
            ];
        }

        return $formattedData;
    }

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is a
        // $monthlyRate is a/12 i.e should be treated as 13/1200
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = $annualRate / 1200;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return floor($num / $den);
    }
}
