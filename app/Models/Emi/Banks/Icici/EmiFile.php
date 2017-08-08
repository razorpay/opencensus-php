<?php

namespace RZP\Models\Emi\Banks\Icici;

use Carbon\Carbon;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Emi;
use RZP\Models\Payment;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Icici_Emi_File';

    protected $emailIdsToSendTo = [];

    protected $bankName  = 'Icici';

    const TYPE = FileStore\Type::ICICI_EMI_FILE;

    public function __construct()
    {
        parent::__construct();

        $this->shouldCompress = false;
    }

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount()/100;

            $merchantPayback = 'NA';

            $subventionAmount = 'NA';

            $acquirer = 'NA';

            if ($emiPlan->getSubvention() === Emi\Subvention::MERCHANT)
            {
                $merchantPayback = $emiPlan->getMerchantPayback()/100;

                $amount = ($principalAmount * $merchantPayback)/100;

                $subventionAmount = number_format((float)$amount, 2, '.', '');
            }

            if (empty($emiPayment->terminal->getGatewayAcquirer()) === false)
            {
                $acquirer = Payment\Gateway::getAcquirerName($emiPayment->terminal->getGatewayAcquirer());
            }

            $rate = $emiPlan->getRate()/100;

            $tenure = $emiPlan->getDuration();

            $issuerPlanId = $emiPlan->getIssuerPlanId();

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Transaction Date/Time'        => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                'Card No.'                     => $this->getCardNumber($emiPayment->card),
                'Amount'                       => $principalAmount,
                'Auth Code/ Approval Code'     => $this->getAuthCode($emiPayment),
                'Scheme Code'                  => $issuerPlanId,
                'Tenure'                       => $tenure,
                'Interest Rate'                => $rate,
                'Merchant Subvention'          => $merchantPayback,
                'Customer Subvention'          => 'NA',
                'Discount/ Cashback Amount'    => 'NA',
                'Discount/Cashback(%)'         => 'NA',
                'Cashback (Y/N)'               => 'N',
                'Manufacturer'                 => 'NA',
                'Merchant Name'                => $emiPayment->merchant->getName(),
                'Pinelabs Merchant Name'       => 'NA',
                'Issuer'                       => 'ICICI Bank',
                'Acquirer'                     => $acquirer,
                'Settlement Time'              => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Subvention Payable to Issuer' => 'NA',
                'Subvention Amount (Rs.)'      => $subventionAmount,
                'Addition Cashback'            => 'NA',
            ];
        }

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d/m/Y');
    }

    protected function generateEmiFile(array $emiData, array $metadata = [])
    {
        if (empty($this->emailIdsToSendTo) === true)
        {
            $metadata = $this->getH2HMetadata();
        }

        $fileData = parent::generateEmiFile($emiData, $metadata);

        return $fileData;
    }

    protected function getFileToWriteName(array $data)
    {
        $count = count($data);

        $date = Carbon::now('Asia/Kolkata')->format('dmY');

        $fileToWriteName = 'icici/outgoing/Razorpay_ICICIEMI_' . $date . '_' . $count;

        if (empty($this->emailIdsToSendTo) === false)
        {
            $fileToWriteName = 'icici/temp/Razorpay_ICICIEMI_' . $date . '_' . $count;
        }

        return $fileToWriteName;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }
}
