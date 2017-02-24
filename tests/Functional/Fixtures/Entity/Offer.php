<?php

namespace RZP\Tests\Functional\Fixtures\Entity;
use Carbon\Carbon;

class Offer extends Base
{
    public function createCard()
    {
        $cardAttributes = [
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'iins'                => [123456],
        ];

        $offer = $this->fixtures->create('offer', $cardAttributes);

        return $offer;
    }

    public function createWallet()
    {
        $cardAttributes = [
            'payment_method'      => 'wallet',
            'payment_network'     => 'olamoney',
        ];

        $offer = $this->fixtures->create('offer', $cardAttributes);

        return $offer;
    }

    public function createExpired()
    {
        $offer = $this->fixtures->create('offer:card');

        $offer['starts_at'] = Carbon::createFromTimestamp(1424762670, 'Asia/Kolkata')->timestamp;

        $offer['ends_at'] = Carbon::createFromTimestamp(1456298670, 'Asia/Kolkata')->timestamp;

        $offer->saveOrFail();

        return $offer;
    }

    public function create(array $attributes = [])
    {
        $defaultValues = [
            'name'            => 'Test Offer',
            'merchant_id'     => '10000000000000',
            'percent_rate'    => 1000,
            'min_amount'      => 1000,
            'payment_count'   => 2,
            'processing_time' => 86400,
            'starts_at'       => Carbon::createFromTimestamp(1519457070, 'Asia/Kolkata')->timestamp,
            'ends_at'         => Carbon::createFromTimestamp(1550993070, 'Asia/Kolkata')->timestamp,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $offer = parent::create($attributes);

        return $offer;
    }
}
