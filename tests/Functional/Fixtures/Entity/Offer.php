<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class Offer extends Base
{
    public function createCard(array $attributes = [])
    {
        $cardAttributes = [
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'iins'                => ['123456'],

        ];

        $attributes = array_merge($cardAttributes, $attributes);

        $offer = $this->fixtures->create('offer', $attributes);

        return $offer;
    }

    public function createWallet(array $attributes = [])
    {
        $walletAttributes = [
            'payment_method'      => 'wallet',
            'payment_network'     => 'olamoney',
        ];

        $attributes = array_merge($walletAttributes, $attributes);

        $offer = $this->fixtures->create('offer', $attributes);

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

    public function createLiveCard(array $attributes = [])
    {
        $startDate = ['starts_at' => Carbon::now('Asia/Kolkata')->subMonth()->timestamp];

        $attributes = array_merge($startDate, $attributes);

        return $this->fixtures->create('offer:card', $attributes);
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
