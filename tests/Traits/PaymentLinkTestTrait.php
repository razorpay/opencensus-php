<?php

namespace RZP\Tests\Traits;

use RZP\Models\Item;
use RZP\Models\Order;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as Fields;
use RZP\Models\Payment;
use RZP\Models\LineItem;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\PaymentLink as PaymentLinkModel;

trait PaymentLinkTestTrait
{
    protected function createNocodeCustomUrl(array $data, PaymentLinkModel\Entity $pl)
    {
        $ncu = new PaymentLinkModel\NocodeCustomUrl\Core();

        $repo = new PaymentLinkModel\Repository();

        $repo->transaction(function () use ($ncu, $data, $pl) {
            $ncu->upsert([
                PaymentLinkModel\NocodeCustomUrl\Entity::SLUG       => $data[PaymentLinkModel\NocodeCustomUrl\Entity::SLUG],
                PaymentLinkModel\NocodeCustomUrl\Entity::DOMAIN     => $data[PaymentLinkModel\NocodeCustomUrl\Entity::DOMAIN],
                PaymentLinkModel\NocodeCustomUrl\Entity::PRODUCT    => PaymentLinkModel\ViewType::PAGE,
                PaymentLinkModel\NocodeCustomUrl\Entity::META_DATA  => [],
            ], $pl->merchant, $pl);
        });
    }

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

    protected function createSubscriptionPaymentPageItem(
        string $id = self::TEST_PPI_ID,
        string $paymentLinkId = self::TEST_PL_ID,
        array $attributes = []
    ): PaymentLinkModel\PaymentPageItem\Entity
    {
        $attributes[PaymentLinkModel\PaymentPageItem\Entity::ID]              = $id;
        $attributes[PaymentLinkModel\PaymentPageItem\Entity::PAYMENT_LINK_ID] = $paymentLinkId;

        $defaultItem = [
            PaymentLinkModel\PaymentPageItem\Entity::PLAN_ID    => self::TEST_PLAN_ID
        ];

        $attributes = array_merge($defaultItem, $attributes);

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

    protected function createPaymentLinkAndOrderForThat(array $paymentLinkAttribute = [], array $orderAttribute = [])
    {
        $defaultPaymentLinkAttribute = [
            PaymentLinkModel\Entity::ID     => self::TEST_PL_ID,
            PaymentLinkModel\Entity::AMOUNT => null,
            PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS => [
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 5000,
                    ]
                ],
                [
                    PaymentLinkModel\PaymentPageItem\Entity::ID   => self::TEST_PPI_ID_2,
                    PaymentLinkModel\PaymentPageItem\Entity::ITEM => [
                        Item\Entity::AMOUNT => 10000,
                    ]
                ]
            ]
        ];

        $paymentLinkAttribute = array_merge($defaultPaymentLinkAttribute, $paymentLinkAttribute);

        $paymentPageItemsAttribute = array_pull($paymentLinkAttribute, PaymentLinkModel\Entity::PAYMENT_PAGE_ITEMS, []);

        $paymentLink = $this->createPaymentLink($paymentPageItemsAttribute[PaymentLinkModel\Entity::ID] ?? self::TEST_PL_ID, $paymentLinkAttribute);

        $paymentPageItems = $this->createPaymentPageItems(
            $paymentPageItemsAttribute[PaymentLinkModel\Entity::ID] ?? self::TEST_PL_ID,
            $paymentPageItemsAttribute);

        $data = [];

        $data['payment_link'] = $paymentLink;

        $data['payment_link_order'] = $this->createOrderForPaymentLink($paymentPageItems, $orderAttribute);

        return $data;
    }

    protected function makePaymentForPaymentLinkWithOrderAndAssert(
        PaymentLinkModel\Entity $paymentLink,
        Order\Entity $order,
        $status = Payment\Status::CAPTURED,
        array $paymentNotes = []
    )
    {
        $payment = $this->getDefaultPaymentArray();

        $payment[Payment\Entity::PAYMENT_LINK_ID] = $paymentLink->getPublicId();
        $payment[Payment\Entity::AMOUNT]          = $order->getAmount();
        $payment[Payment\Entity::ORDER_ID]        = $order->getPublicId();
        $payment[Payment\Entity::NOTES]           = $paymentNotes;

        $payment = $this->doAuthAndGetPayment($payment, [
            Payment\Entity::STATUS   => $status,
            Payment\Entity::ORDER_ID => $order->getPublicId(),
        ]);

        $this->assertEquals($order->getAmount(), $payment['amount']);
        $this->assertEquals($order->getPublicId(), $payment['order_id']);

        return $payment;
    }

    protected function createOrderForPaymentLink($paymentPageItems, array $orderAttribute = [])
    {
        $data = [];

        $totalAmount = 0;

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $item = $paymentPageItem[PaymentLinkModel\PaymentPageItem\Entity::ITEM];

            $amount = empty($item->getAmount()) === true ? 10000 : $item->getAmount();

            $totalAmount += 1 * $amount;
        }

        $orderAttribute = array_merge(
            [
                'amount' => $totalAmount,
                Order\Entity::PAYMENT_CAPTURE => true,
            ],
            $orderAttribute
        );

        $order = $this->getOrder($orderAttribute);

        $data['order'] = $order;

        foreach ($paymentPageItems as $paymentPageItem)
        {
            $item = $paymentPageItem[PaymentLinkModel\PaymentPageItem\Entity::ITEM];

            $itemForLineItemAttributes = [
                Item\Entity::ID     => UniqueIdEntity::generateUniqueId(),
                Item\Entity::AMOUNT => empty($item->getAmount()) === true ? 10000 : $item->getAmount()
            ];

            $itemForLineItem = $this->fixtures->create('item', $itemForLineItemAttributes);

            $lineItem = $this->fixtures->create('line_item', [
                LineItem\Entity::ID          => $paymentPageItem->getId(),
                LineItem\Entity::ITEM_ID     => $itemForLineItem->getId(),
                LineItem\Entity::REF_TYPE    => 'payment_page_item',
                LineItem\Entity::REF_ID      => $paymentPageItem->getId(),
                LineItem\Entity::ENTITY_ID   => $order->getId(),
                LineItem\Entity::ENTITY_TYPE => 'order',
                LineItem\Entity::AMOUNT      => $itemForLineItem->getAmount(),
            ]);

            $data['line_items'][] = $lineItem->toArrayPublic();
        }

        return $data;
    }

    /**
     * @param array $orderAttribute
     * @return array|mixed
     */
    protected function getOrder(array $orderAttribute)
    {
        if (array_get($orderAttribute, 'one_click_checkout', '0') === '1')
        {
            unset($orderAttribute['one_click_checkout']);

            return $this->fixtures->order->create1ccOrderWithLineItems($orderAttribute);
        }
        return $this->fixtures->create('order', $orderAttribute);
    }
}
