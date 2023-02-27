<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use RZP\Models\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Models\PaymentLink;
use RZP\Models\Item;
use RZP\Models\Store;
use RZP\Models\Merchant;
use RZP\Trace\Tracer;

class Repository extends Base\Repository
{
    protected $entity = 'payment_page_item';

    protected $expands = [
        Entity::ITEM,
    ];

    public function findByIdAndPaymentLinkEntityOrFail(
        string $id,
        PaymentLink\Entity $paymentLink): Entity
    {
        return Tracer::inSpan(['name' =>  'payment_page.find_by_id_and_payment_link'], function() use($id, $paymentLink)
        {
            return $this->newQuery()
                    ->where(Entity::PAYMENT_LINK_ID, $paymentLink->getId())
                    ->findOrFailPublic($id);
        });
    }

    public function fetchAllItemsOfPaymentLink(
        Store\Entity $paymentLink,
        Merchant\Entity $merchant,
        bool $avoidTrashed = true)
    {
        $query = $this->newQuery()
                      ->where(Entity::PAYMENT_LINK_ID, $paymentLink->getId())
                      ->where(Entity::MERCHANT_ID, $merchant->getId());

        if($avoidTrashed === false)
        {
            $query->withTrashed();
        }

        return $query->get();
    }

    public function findByIdAndPaymentLinkEntityAndMerchantOrFail(
        string $id,
        Store\Entity $paymentLink,
        Merchant\Entity $merchant): Entity
    {
        return $this->newQuery()
            ->where(Entity::PAYMENT_LINK_ID, $paymentLink->getId())
            ->where(Entity::MERCHANT_ID, $merchant->getId())
            ->withTrashed()
            ->findOrFailPublic($id);
    }

    protected function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        $serialized[Item\Entity::NAME] = $entity->item->getName();

        $serialized[Item\Entity::DESCRIPTION] = $entity->item->getDescription();

        $serialized[Entity::ITEM_DELETED_AT] = $entity->item->getDeletedAt();

        return $serialized;
    }


    public function fetchByPaymentLinkIdAndMerchant(
        string $paymentLinkId,
        string $merchantId)
    {
        $query = $this->newQuery()
            ->where(Entity::PAYMENT_LINK_ID, $paymentLinkId)
            ->where(Entity::MERCHANT_ID, $merchantId);

        return $query->get();
    }
}
