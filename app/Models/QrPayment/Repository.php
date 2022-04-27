<?php

namespace RZP\Models\QrPayment;

use RZP\Models\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;

class Repository extends Base\Repository
{
    protected $entity = 'qr_payment';

    public function isEsSyncNeeded(string $action, array $dirty = null, PublicEntity $qrPayment = null): bool
    {
        // Sync in ES only for payments on QRv2 created via API, DASHBOARD
        if ($qrPayment->qrCode->getRequestSource() === QrV2\RequestSource::CHECKOUT)
        {
            return false;
        }

        return parent::isEsSyncNeeded($action, $dirty, $qrPayment);
    }

    public function findByProviderReferenceIdAndGatewayAndAmount(string $providerReferenceId, string $gateway, int $amount)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->where(Entity::PROVIDER_REFERENCE_ID, '=', $providerReferenceId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(Entity::AMOUNT, '=', $amount)
                    ->first();
    }

    protected function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        if ($entity->payment !== null)
        {
            $serialized[PaymentEntity::STATUS] = $entity->payment->getStatus();

            $serialized[PaymentEntity::NOTES]  = $entity->payment->getNotes();
        }

        if ($entity->qrCode !== null)
        {
            $serialized[Entity::MERCHANT_ID] = $entity->qrCode->getMerchantId();

            if ($entity->qrCode->customer !== null)
            {
                $serialized[EsRepository::CUSTOMER_EMAIL] = $entity->qrCode->customer->getEmail();
            }
        }

        return $serialized;
    }

    public function getPaymentIdsForQrPaymentIds(array $qrPaymentIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $qrPaymentIds)
                    ->get()
                    ->pluck(Entity::PAYMENT_ID)
                    ->toArray();
    }

    public function getLatestExpectedPaymentIdForQrCodeId(string $qrCodeId)
    {
        QrV2\Entity::verifyIdAndStripSign($qrCodeId);

        return $this->newQuery()
                    ->where(Entity::QR_CODE_ID, '=', $qrCodeId)
                    ->where(Entity::EXPECTED, '=', 1)
                    ->latest()
                    ->get()
                    ->pluck(Entity::PAYMENT_ID)
                    ->first();
    }
}
