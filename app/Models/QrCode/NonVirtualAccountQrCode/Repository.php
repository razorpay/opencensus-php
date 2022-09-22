<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\Checkout\Order\Entity as CheckoutOrderEntity;
use RZP\Models\Order;
use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\QrPayment;
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

    public function fetchQrCodeForPaymentId(string $paymentId, string $merchantId)
    {
        $paymentMerchantId = $this->dbColumn(Entity::MERCHANT_ID);
        $qrData            = $this->dbColumn('*');
        $qrCodeId          = $this->dbColumn(Entity::ID);

        Payment\Entity::verifyIdAndStripSign($paymentId);

        return $this->newQuery()
                    ->select($qrData)
                    ->join(Table::QR_PAYMENT, function($join) use ($qrCodeId) {
                        $join->on($qrCodeId, '=', QrPayment\Entity::QR_CODE_ID);
                    })
                    ->where(QrPayment\Entity::PAYMENT_ID, '=', $paymentId)
                    ->where($paymentMerchantId, '=', $merchantId)
                    ->get();
    }

    public function findActiveQrCodeByCheckoutOrder(CheckoutOrderEntity $checkoutOrder)
    {
        return $this->newQuery()
            ->where(Entity::STATUS, '=', Status::ACTIVE)
            ->where(Entity::ENTITY_ID, '=', $checkoutOrder->getId())
            ->latest()
            ->first();
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
