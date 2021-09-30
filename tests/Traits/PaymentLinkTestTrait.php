<?php

namespace RZP\Tests\Traits;

use RZP\Models\Item;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\PaymentLink as PaymentLinkModel;

trait PaymentLinkTestTrait
{
    protected function createPaymentLink(string $id = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\Entity
    {
        $attributes[PaymentLinkModel\Entity::ID]      = $id;
        $attributes[PaymentLinkModel\Entity::USER_ID] = User::MERCHANT_USER_ID;

        return $this->fixtures->create('payment_link', $attributes);
    }

    protected function createPaymentLinkWithMultipleItem(string $id = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\Entity
    {
        $defaultPaymentLinkAttribute = [
            PaymentLinkModel\Entity::ID     => self::TEST_PL_ID,
            PaymentLinkModel\Entity::AMOUNT => null,
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => PublicEntity::generateUniqueId(),
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => PublicEntity::generateUniqueId(),
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ];

        $paymentLinkAttribute = array_merge($defaultPaymentLinkAttribute, $attributes);

        $paymentPageItemsAttribute = array_pull($paymentLinkAttribute, PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS, []);

        $paymentLink = $this->createPaymentLink($id, $paymentLinkAttribute);

        $this->createPaymentPageItems(
            $paymentPageItemsAttribute[PaymentLinkModel\Entity::ID] ?? self::TEST_PL_ID,
            $paymentPageItemsAttribute
        );

        return $paymentLink;
    }

    protected function createPaymentPageItem(string $id = self::TEST_PPI_ID, string $paymentLinkId = self::TEST_PL_ID, array $attributes = []): PaymentLinkModel\PaymentPageItem\Entity
    {
        $attributes[PaymentLinkModel\PaymentPageItem\Entity::ID]              = $id;
        $attributes[PaymentLinkModel\PaymentPageItem\Entity::PAYMENT_LINK_ID] = $paymentLinkId;

        $defaultItem = [
            Item\Entity::ID     => $id,
            Item\Entity::TYPE   => Item\Type::PAYMENT_PAGE,
            Item\Entity::NAME   => 'amount',
            Item\Entity::AMOUNT => null
        ];

        $defaultItem = array_merge($defaultItem, array_pull($attributes, PaymentLinkModel\PaymentPageItem\Entity::ITEM, []));

        $item = $this->fixtures->create('item', $defaultItem);

        $attributes[PaymentLinkModel\PaymentPageItem\Entity::ITEM_ID] = $item->getId();

        return $this->fixtures->create('payment_page_item', $attributes);
    }

    protected function createPaymentPageItems(string $paymentLinkId = self::TEST_PL_ID, array $paymentPageItems = [])
    {
        $data = [];

        foreach ($paymentPageItems as $paymentPageItem) {
            $data[] = $this->createPaymentPageItem(
                $paymentPageItem['id'] ?? UniqueIdEntity::generateUniqueId(),
                $paymentLinkId,
                $paymentPageItem
            );
        }

        return $data;
    }
}
