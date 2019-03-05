<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

use RZP\Constants\Timezone;

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
            'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.'
        ];

        $attributes = array_merge($cardAttributes, $attributes);

        $offer = $this->fixtures->create('offer', $attributes);

        return $offer;
    }

    public function createWallet(array $attributes = [])
    {
        $walletAttributes = [
            'payment_method' => 'wallet',
            'issuer'         => 'olamoney',
        ];

        $attributes = array_merge($walletAttributes, $attributes);

        $offer = $this->fixtures->create('offer', $attributes);

        return $offer;
    }

    public function createEmiSubvention(array $attributes = [])
    {
        $emiSubventionAttributes = [
            'payment_method'  => 'emi',
            'emi_subvention'  => true,
            'emi_durations'   => [9],
            'payment_network' => 'AMEX',
            'issuer'          => null,
            'min_amount'      => 319149,
            'percent_rate'    => null,
        ];

        $attributes = array_merge($emiSubventionAttributes, $attributes);

        $offer = $this->fixtures->create('offer', $attributes);

        return $offer;
    }

    public function createExpired()
    {
        $offer = $this->fixtures->create('offer:card');

        $offer['starts_at'] = 1424762670;

        $offer['ends_at'] = 1456298670;

        $offer->saveOrFail();

        return $offer;
    }

    public function createLiveCard(array $attributes = [])
    {
        $startDate = ['starts_at' => Carbon::now(Timezone::IST)->subMonth()->timestamp];

        $attributes = array_merge($startDate, $attributes);

        return $this->fixtures->create('offer:card', $attributes);
    }

    public function create(array $attributes = [])
    {
        $start = Carbon::now()->getTimestamp();
        $end = Carbon::now()->addYear(1)->getTimestamp();

        $defaultValues = [
            'name'              => 'Test Offer',
            'merchant_id'       => '10000000000000',
            'percent_rate'      => 1000,
            'min_amount'        => 1000,
            'max_payment_count' => 2,
            'processing_time'   => 86400,
            'starts_at'         => $start,
            'ends_at'           => $end,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $offer = parent::create($attributes);

        return $offer;
    }
}
