<?php

namespace RZP\Models\Emi\Banks\Icici;

use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Models\Emi\Banks\Base;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Icici_Emi_File';

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

            $rate = $emiPlan[Entity::RATE]/100;

            $tenure = $emiPlan[Entity::DURATION];

            $issuerPlanId = $emiPlan[Entity::ISSUER_PLAN_ID];

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $data[] = [
                Headers::EMI_ID               => $emiPayment->getId(),
                Headers::TRANSACTION_TIME     => $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                Headers::CARD_NUMBER          => $this->getCardNumber($emiPayment->card),
                Headers::AMOUNT               => $principalAmount,
                Headers::AUTH_CODE            => 'Email/SFTP',
                Headers::SCHEME_CODE          => '',
                Headers::TENURE               => $tenure,
                Headers::MERCHANT_SUBVENTION  => '',
                Headers::CUSTOMER_SUBVENTION  => '',
                Headers::DISCOUNT_AMOUNT      => '',
                Headers::DISCOUNT_PERCENTAGE  => '',
                Headers::CASHBACK             => 'N',
                Headers::MANUFACTURER         => 'Card Emi',
                Headers::MERCHANT_NAME        => 'Razorpay',
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
}
