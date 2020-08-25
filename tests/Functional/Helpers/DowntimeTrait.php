<?php

namespace RZP\Tests\Functional\Helpers;

trait DowntimeTrait
{
    protected function enablePaymentDowntimes()
    {
        $this->ba->adminAuth();

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_card' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_card_issuer' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_card_network' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_netbanking' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_upi' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_wallet' => '1',
            ],
        ]);

        $this->fixtures->merchant->addFeatures('expose_downtimes');
    }
}
