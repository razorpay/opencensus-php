<?php

namespace RZP\Tests\Functional\Fixtures\Entity;
use Carbon\Carbon;

class Offer extends Base
{
    public function createCardOffer()
    {
        $offer = $this->fixtures->create('offer', [
            'name'                => 'Test Offer',
            'merchant_id'         => '10000000000000',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'percent_rate'        => 1000,
            'min_amount'          => 1000,
            'payment_count'       => 2,
            'processing_time'     => 86400,
            'starts_at'           => Carbon::today('Asia/Kolkata')->timestamp,
            'ends_at'             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp
        ]);

        return $offer;
    }

    public function createExpiredOffer()
    {
        $offer = $this->createCardOffer();

        $offer['starts_at'] = Carbon::now('Asia/Kolkata')->subMonth()->timestamp;

        $offer['ends_at'] = Carbon::yesterday('Asia/Kolkata')->timestamp;

        $offer->saveOrFail();

        return $offer;
    }
}
