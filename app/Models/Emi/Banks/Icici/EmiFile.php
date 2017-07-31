<?php

namespace RZP\Models\Emi\Banks\Icici;

use Carbon\Carbon;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Emi;

class EmiFile extends Base\EmiFile
{
    protected $emailIdsToSendTo = [''];

    protected $bankName  = 'Icici';

    const TYPE = FileStore\Type::ICICI_EMI_FILE;

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount()/100;

            $merchantPayback = 'NA';

            $subventionAmount = 'NA';

            if ($emiPlan->getSubvention() === Emi\Subvention::MERCHANT)
            {
                $merchantPayback = $emiPlan->getMerchantPayback()/100;

                $subventionAmount = ($principalAmount * $merchantPayback)/100;
            }

            $rate = $emiPlan->getRate()/100;

            $tenure = $emiPlan->getDuration();

            $issuerPlanId = $emiPlan->getIssuerPlanId();

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $data[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'Transaction Date/Time'        => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                'Card No.'                     => $this->getCardNumber($emiPayment->card),
                'Amount'                       => $principalAmount,
                'Auth Code/ Approval Code'     => $this->getAuthCode($emiPayment),
                'Scheme Code'                  => $issuerPlanId,
                'Tenure'                       => $tenure,
                'Merchant Subvention'          => $merchantPayback,
                'Interest Rate'                => $rate,
                'Discount/ Cashback Amount'    => 'NA',
                'Discount/Cashback(%)'         => 'NA',
                'Cashback (Y/N)'               => 'N',
                'Manufacturer'                 => 'NA',
                'Merchant Name'                => $emiPayment->merchant->getName(),
                'Pinelabs Merchant Name'       => 'NA',
                'Issuer'                       => 'ICICI Bank',
                'Acquirer'                     => 'NA',
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
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d-M-y H-i-s');
    }

    protected function sendEmiFile(array $fileData)
    {
        return $fileData;
    }

    protected function generateEmiFile(array $emiData, $store = 's3')
    {
        $id = UniqueIdEntity::generateUniqueId();

        $count = count($emiData);

        $date = Carbon::now('Asia/Kolkata')->format('dmY');

        $fileName = 'Razorpay _ICICIEMI_' . $date . '_' . $count;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $creator->extension(static::EXTENSION)
                ->content($emiData)
                ->name($fileName)
                ->store($store)
                ->type(static::TYPE)
                ->id($id)
                ->metadata($metadata)
                ->save();

        $file = $creator->get();

        $signedFileUrl = $creator->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'signed_url' => $signedFileUrl,
            'file_name'  => basename($file['local_file_path']),
        ];

        return $fileData;
    }

    protected function fetchAndSendPassword()
    {
        return;
    }

    protected function getH2HMetadata()
    {
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }
}
