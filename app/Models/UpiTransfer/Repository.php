<?php

namespace RZP\Models\UpiTransfer;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::UPI_TRANSFER;

    public function findByProviderReferenceIdAndPayeeVpaAndAmount(string $providerReferenceId,string $payeeVpa,int $amount)
    {
        $mode = ($mode ?? $this->app['rzp.mode']) ?? Mode::LIVE;

        return $this->newQueryWithConnection($mode)
                    ->useWritePdo()
                    ->where(Entity::PROVIDER_REFERENCE_ID,'=',$providerReferenceId)
                    ->where(Entity::PAYEE_VPA,'=',$payeeVpa)
                    ->where(Entity::AMOUNT,'=',$amount)
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
