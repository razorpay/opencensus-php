<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'bharat_qr';

    public function findByProviderReferenceId(string $providerReferenceId)
    {
        return $this->newQuery()
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->first();
    }

    public function findByPaymentId(string $paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->first();
    }

    // This is currently being used just for test cases
    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_REFERENCE, '=', $merchantReference)
                    ->first();
    }
}
