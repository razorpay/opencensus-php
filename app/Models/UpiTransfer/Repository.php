<?php

namespace RZP\Models\UpiTransfer;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::UPI_TRANSFER;

    public function findByProviderReferenceId(string $providerReferenceId)
    {
        return $this->newQuery()
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->first();
    }

    public function findByPaymentId(string $paymentId)
    {
        $upiTransfer = $this->newQuery()
                            ->where(Entity::PAYMENT_ID, '=', $paymentId)
                            ->firstOrFail();

        return $upiTransfer;
    }

    public function findByNpciReferenceIdAndGateway(string $npciReferenceId, string $gateway)
    {
        return $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();
    }
}
