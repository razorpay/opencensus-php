<?php

namespace RZP\Models\Emi\Banks\Rbl;

use Carbon\Carbon;

use RZP\Services\TokenEx;
use RZP\Models\Card;
use RZP\Models\Emi\Entity;
use RZP\Models\Emi\Service;
use RZP\Gateway\Base\Action;
use RZP\Models\Emi\Banks\Base;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Rbl_Emi_File';

    protected $emailIdsToSendTo = ['Rblcards.emi@razorpay.com'];

    protected $bankName  = 'Rbl';

    protected static $headers = [
        'EMI ID',
        'RBL Card no',
        'Issuer',
        'Acquirer',
        'Aggregator Merchant Name',
        'Manufacturer',
        'Auth Code',
        'Tx Amount',
        'EMI Offer',
        'EMI Plan ID',
        'Customer Name',
        'Mobile No',
        'Store Name',
        'Address1',
        'Store City',
        'Store State',
        'MID',
        'TID',
        'Tx Time',
        'Subvention payable to Issuer',
        'Subvention Amount (Rs.)',
        'Interest Rate',
        'Customer Processing Fee',
        'Customer Processing Amount (Rs.)',
        'Tx Status',
        'Status',
        'Description',
        'Product Category',
        'Product Sub-Category 1',
        'Product Sub-Category 2',
        'Model Name',
        'Merchant Name',
        'EMI Amount',
        'Loan Amount',
        'Discount / Cashback %',
        'Discount / Cashback Amount',
        'Additional Cashback',
        'Bonus Reward Points',
        'EMI Model',
    ];

    public function generate($input)
    {
        $txt = $this->getEmiData($input);

        $urlExcel = $this->writeToExcelFile($txt, $this->getFileToWriteNameWithoutExt());

        $fullPath = $this->getExcelFullFilePath();

        $this->sendEmiFile($fullPath);

        return $urlExcel;
    }

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
                'Tx Time'                          => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
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

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d-M-y');
    }
}
