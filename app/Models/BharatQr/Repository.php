<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    protected $entity = 'bharat_qr';

    public function findByProviderReferenceIdAndAmount(string $providerReferenceId, int $amount)
    {
        $mode = ($mode ?? $this->app['rzp.mode']) ?? Mode::LIVE;

        return $this->newQueryWithConnection($mode)
                    ->useWritePdo()
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->where(Entity::AMOUNT, '=', $amount)
                    ->first();
    }

    public function findByPaymentId(string $paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->first();
    }

    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_REFERENCE, '=', $merchantReference)
                    ->first();
    }
}
