<?php

namespace RZP\Models\Emi\Banks\Icici;

use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity;
use RZP\Models\Base\UniqueIdEntity;

class EmiFile extends Base\EmiFile
{
    protected $emailIdsToSendTo = [''];

    protected $bankName  = 'Icici';

    const TYPE = FileStore\Type::ICICI_EMI_FILE;

    protected static $headers = [
        Headers::EMI_ID,
        Headers::TRANSACTION_TIME,
        Headers::AMOUNT,
        Headers::AUTH_CODE,
        Headers::SCHEME_CODE,
        Headers::TENURE,
        Headers::MERCHANT_SUBVENTION,
        Headers::CUSTOMER_SUBVENTION,
        Headers::DISCOUNT_AMOUNT,
        Headers::DISCOUNT_PERCENTAGE,
        Headers::CASHBACK,
        Headers::MANUFACTURER,
        Headers::MERCHANT_NAME,
        Headers::PINELAB_NAME,
        Headers::ISSUER,
        Headers::ACQUIRER,
        Headers::SETTLEMENT_TIME,
        Headers::SUBVENTION_PAYABLE,
        Headers::SUBVENTION_AMOUNT,
        Headers::ADDITIONAL_CASHBACK,
    ];

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount()/100;

            $rate = $emiPlan->getRate()/100;

            $tenure = $emiPlan->getDuration();

            $issuerPlanId = $emiPlan->getIssuerPlanId();

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $data[] = [
                Headers::EMI_ID               => $emiPayment->getId(),
                Headers::TRANSACTION_TIME     => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                Headers::CARD_NUMBER          => $this->getCardNumber($emiPayment->card),
                Headers::AMOUNT               => $principalAmount,
                Headers::AUTH_CODE            => 'Email/SFTP',
                Headers::SCHEME_CODE          => $issuerPlanId,
                Headers::TENURE               => $tenure,
                Headers::MERCHANT_SUBVENTION  => '',
                Headers::CUSTOMER_SUBVENTION  => '',
                Headers::DISCOUNT_AMOUNT      => '',
                Headers::DISCOUNT_PERCENTAGE  => '',
                Headers::CASHBACK             => 'N',
                Headers::MANUFACTURER         => '',
                Headers::MERCHANT_NAME        => 'Razorpay Payments',
                Headers::PINELAB_NAME         => '',
                Headers::ISSUER               => 'ICICI Bank',
                Headers::ACQUIRER             => '',
                Headers::SETTLEMENT_TIME      => '',
                Headers::SUBVENTION_PAYABLE   => '',
                Headers::SUBVENTION_AMOUNT    => '',
                Headers::ADDITIONAL_CASHBACK  => '',
            ];
        }

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d-M-y');
    }

    protected function sendEmiFile(array $fileData)
    {
        return $fileData;
    }

    protected function generateEmiFile(array $emiData, $store = 's3')
    {
        $id = UniqueIdEntity::generateUniqueId();

        $fileName = 'icici/outgoing/NRPSS_NRPSSUPLDNEW_' . $id;

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
