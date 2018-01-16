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
}
