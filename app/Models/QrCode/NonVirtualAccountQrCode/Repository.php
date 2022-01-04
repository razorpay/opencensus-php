<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\Order;
use RZP\Models\QrCode;
use RZP\Models\Base\PublicEntity;

class Repository extends QrCode\Repository
{
    public function isEsSyncNeeded(string $action, array $dirty = null, PublicEntity $qrCode = null): bool
    {
        // Sync in ES only for QRv2 created via API, DASHBOARD
        if (($qrCode->source !== null) or
            ($qrCode->getRequestSource() === RequestSource::CHECKOUT))
        {
            return false;
        }

        return parent::isEsSyncNeeded($action, $dirty, $qrCode);
    }

    public function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        if ($entity->customer !== null)
        {
            $serialized[EsRepository::CUSTOMER_CONTACT] = preg_replace('/[^A-Za-z0-9]/', '',
                                                                       $entity->customer->getContact());

            $serialized[EsRepository::CUSTOMER_NAME] = $entity->customer->getName();

            $serialized[EsRepository::CUSTOMER_EMAIL] = $entity->customer->getEmail();
        }

        return $serialized;
    }

    public function addQueryParamEntityType($query, $params)
    {
        $query->whereNull(Entity::ENTITY_TYPE);
    }

    public function findActiveQrCodeByOrder(Order\Entity $order)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::ENTITY_ID, '=', $order->getId())
                    ->latest()
                    ->first();
    }
}
