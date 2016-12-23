<?php

namespace RZP\Tests\Functional\Fixtures\Entity;
use Carbon\Carbon;

class Offer extends Base
{
    public function createCardOffer()
    {
        $offer = $this->fixtures->create('offer', [
            'name'                => 'Test Offer',
            'payment_method'      => 'card',
            'payment_method_type' => 'credit',
            'payment_network'     => 'VISA',
            'issuer'              => 'HDFC',
            'percent_rate'        => 1000,
            'payment_count'       => 2,
            'processing_time'     => "1",
            'starts_at'           => Carbon::today('Asia/Kolkata')->timestamp,
            'ends_at'             => Carbon::today('Asia/Kolkata')->addMonth()->timestamp
        ]);

        return $offer;
    }
}
