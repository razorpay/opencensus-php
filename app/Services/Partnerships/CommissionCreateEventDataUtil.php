<?php

namespace RZP\Services\Partnerships;

use RZP\Models\Base;
use RZP\Models\Order\ProductType;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\EntityOrigin\Core as EntityOriginCore;

/**
 * Class CommissionCreateEventDataUtil
 *
 * @package RZP\Services\Partnerships
 * This class will used as reference to understand the event structure what payments team has to emit during the payment capture phase
 */
class CommissionCreateEventDataUtil
{
    /**
     * @param array                 $commissions
     * @param PaymentEntity         $payment
     *
     * @return array
     */
    public static function getPayloadForCommissionCreate(array $commissions, array $components, PaymentEntity $payment): array
    {
        $commissionsPayload = [];
        foreach ($commissions as $index => $commission)
        {
            $payload = $commission->attributesToArray();
            $payload['commission_component'] = $components[$index]->attributesToArray();
            $payload['notes'] = (object) ($payload['notes']);
            $commissionsPayload[] = $payload;
        }

        $publicKey = self::getPaymentPublicKey($payment);

        $order = [
            'id'             => optional($payment->order)->getId(),
            'public_key'     => $publicKey,
            'application_id' => (new EntityOriginCore())->getOriginIDFromPublicKey($publicKey)
        ];

        return [
            // For now, we will be going with all the payment entity attributes. But only few of the attributes are needed for commission create
            'payment'          => [
                'id' => $payment->getId(),
                'merchant_id' => $payment->getMerchantId()
            ],
            'order'            => $order,
            // commissions data will be optional when we start reverse shadow phase. Current this data is used for preserving ids and for parity check
            'commissions'      => $commissionsPayload,
            'product_metadata' => self::getProductMetaData($payment),
            'auth'             => [
                'application_id' => app('basicauth')->getOAuthApplicationId()
            ]
        ];
    }

    private static function getPaymentPublicKey(PaymentEntity $payment): string
    {
        $publicKey = null;

        if ($payment->getPublicKey() != null)
        {
            $publicKey = $payment->getPublicKey();
        }
        else
        {
            if (optional($payment->order)->getPublicKey() !== null)
            {
                $publicKey = $payment->order->getPublicKey();
            }
        }

        return $publicKey ?? "";
    }

    /**
     * Gets the Product Metadata for a payment.
     *
     * @param PaymentEntity $payment The payment entity.
     *
     * @return  array   Associative array of format, ['product_type' => '', 'product_id' => ''].
     */
    private static function getProductMetaData(PaymentEntity $payment): array
    {
        $productType    = $productID = null;
        $subscriptionId = $payment->getSubscriptionId();
        $receiver       = $payment->receiver;

        if (in_array(optional($payment->order)->getProductType(), [ProductType::PAYMENT_LINK_V2, ProductType::INVOICE]))
        {
            $productType = $payment->order->getProductType();
            $productID   = $payment->order->getProductId();
        }
        else
        {
            if (empty($subscriptionId) === false)
            {
                $productType = 'subscription';
                $productID   = $subscriptionId;
            }
            else
            {
                if (empty($receiver) === false)
                {
                    $virtualAccount = $receiver->virtualAccount;

                    if (empty($virtualAccount) === false)
                    {
                        $productType = 'virtual_account';
                        $productID   = $virtualAccount->getId();
                    }
                    else
                    {
                        if ($receiver->getEntity() === 'qr_code')
                        {
                            $productType = 'qr_code';
                            $productID   = $receiver->getId();
                        }
                    }
                }
            }
        }

        return [
            'product_type' => $productType,
            'product_id'   => $productID
        ];
    }
}
