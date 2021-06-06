<?php

namespace RZP\Models\QrPayment;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'qr_payment';

    public function findByProviderReferenceIdAndGateway(string $providerReferenceId, string $gateway)
    {
        return $this->newQuery()
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();
    }
}
