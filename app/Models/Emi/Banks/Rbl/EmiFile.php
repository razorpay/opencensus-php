<?php

namespace RZP\Models\Emi\Banks\Rbl;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Card;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity;
use RZP\Trace\TraceCode;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Rbl_Emi_File';

    protected $emailIdsToSendTo = ['Rblcards.emi@razorpay.com'];

    protected $bankName  = 'Rbl';

    protected $type = FileStore\Type::RBL_EMI_FILE;

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount()/100;

            $rate = $emiPlan[Entity::RATE]/100;

            $tenure = $emiPlan[Entity::DURATION];

            $issuerPlanId = $emiPlan[Entity::ISSUER_PLAN_ID];

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $data[] = [
                'EMI ID'                           => $emiPayment->getId(),
                'RBL Card no'                      => $this->getCardNumber($emiPayment->card,$emiPayment->getGateway()),
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
                'Tx Time'                          => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
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
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d-M-y');
    }
}
