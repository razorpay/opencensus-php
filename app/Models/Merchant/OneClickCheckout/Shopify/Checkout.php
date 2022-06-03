<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Order\Service as OrderService;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Order\OrderMeta\Type as OrderMetaType;

class Checkout extends Base\Core
{
    const GID_CHECKOUT = 'gid://shopify/Checkout/';

    public function getCheckoutbyStorefrontId(string $checkoutId): array
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getCheckoutMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'id' => $checkoutId
            ]
        ];

        $res = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        return json_decode($res, true);
    }

    // returns notes for Rzp order using Shopify storefront id and line items
    public function getNotesForCheckout(array $checkout, string $cartId): array
    {
        $notes = [
            "storefront_id" => $checkout['id'],
            "cart_id" => $cartId
        ];

        $lineItems = $checkout['lineItems']['edges'];

        foreach ($lineItems as $lineItem)
        {
            $item = $lineItem['node'];

            $title = $this->getVariantName($item);

            $notes[$title] = 'Quantity: ' . strval($item['quantity']);
        }

        return $notes;
    }

    protected function getVariantName(array $item): string
    {
        $variantName = $item['variant']['title'] !== 'Default Title' ? ': ' . $item['variant']['title'] : '';

        return $item['title'] . $variantName;
    }

    // updates email for a storefront checkout
    public function updateCheckoutEmail(string $checkoutId, string $email)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getcheckoutEmailUpdateMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'checkoutId' => $checkoutId,
                'email'      => $email,
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_BODY,
            ['checkoutId' => $checkoutId]);

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    // we use the Admin REST API instead of Storefront as only the admin API
    // returns the value "phone". This key is not present in Storefront API and it
    // is a hard limitation from Shopify
    public function getCheckoutFromAdminApi(string $checkoutId): array
    {
        $token = $this->getCartTokenFromCheckoutId($checkoutId);

        $client = $this->getShopifyClientByMerchant();

        try
        {
            $checkoutRes = $client->sendRestApiRequest(
                null,
                'GET',
                '/checkouts/' . $token . '.json');

            $checkout = json_decode($checkoutRes, true);

            return $checkout['checkout'];
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_ERROR,
                 [
                     'type'       => 'get_admin_checkout',
                     'error'      => $e->getMessage(),
                     'checkoutId' => $checkoutId,
                 ]);
            return [];
        }
    }

    public function updateCheckoutFromAdmin(array $input): array
    {
        $orderId = $input['order_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $customerDetails = $this->getCustomerDetailsFromOrderMeta($order);

        if (empty($customerDetails) === true)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_UPDATE_CHECKOUT,
                [
                    'order_id' => $input['order_id'],
                    'status'   => 'not_updated',
                    'reason'   => 'empty_customer_details'
                ]);

            return [];
        }

        $checkoutId = $this->getCheckoutIdFromOrder($order);

        $payload = $this->getPayloadForUpdateCheckoutAdmin($checkoutId, $customerDetails);

        $newCheckoutId = $this->updateCheckoutAdminApi($payload);

        $this->trace->info(
          TraceCode::SHOPIFY_1CC_UPDATE_CHECKOUT,
          [
              'order_id'        => $input['order_id'],
              'status'          => 'updated',
              'checkout_id'     => $checkoutId,
              'new_checkout_id' => $newCheckoutId,
          ]);

        if ($newCheckoutId !== $checkoutId and $newCheckoutId !== '')
        {
            $newNotes = array_merge($order->getNotes()->toArray(), ['storefront_id' => $newCheckoutId]);

            (new OrderService)->update(
              $orderId,
              ['notes' => $newNotes]);
        }

        return [];
    }

    public function updateCheckoutUrl(array $input)
    {
        $start = millitime();

        $orderId = $input['order_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        if (empty($order->getNotes()['storefront_id']) === true)
        {
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_ERROR,
                 [
                     'type'     => 'update_checkout_url',
                     'order_id' => $orderId,
                     'reason'   => 'missing_storefront_id'
                 ]);
            return;
        }

        $checkoutId = $order->getNotes()['storefront_id'];

        $client = $this->getShopifyClientByMerchant();

        $this->addMagicCheckoutUrlToShopifyCheckout(array_merge($input, ['checkout_id' => $checkoutId]));

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_RETARGETING_URL,
            ['input' => $input, 'time' => millitime() - $start]);
    }

    protected function getPayloadForUpdateCheckoutAdmin(string $checkoutId, array $customerDetails): array
    {
        $payload = ['token' => $this->getCartTokenFromCheckoutId($checkoutId)];

        if (empty($customerDetails['shipping_address']) === false)
        {
            $payload['shipping_address'] = (new Address)->transformRzpToShopifyAdminAddress($customerDetails['shipping_address']);
        }

        if (empty($customerDetails['contact']) === false)
        {
            $payload['phone'] = $customerDetails['contact'];
        }

        if (empty($customerDetails['email']) === false)
        {
            $payload['email'] = $customerDetails['email'];
        }

        return $payload;
    }

    // fire and forget API so we don't reveal the error to the FE
    protected function updateCheckoutAdminApi(array $input): string
    {
        $checkout = null;
        $token = $input['token'];

        unset($input['token']);

        $body = ['checkout' => $input];

        $client = $this->getShopifyClientByMerchant();

        try
        {
            $checkoutRes = $client->sendRestApiRequest(
                json_encode($body),
                'PUT',
                '/checkouts/' . $token . '.json');

            $checkout = json_decode($checkoutRes, true);

            return $this->getStorefrontIdFromWebUrl($checkout['checkout']['web_url']);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                    'type'        => 'update_checkout',
                    'error'       => $e->getMessage(),
                    'checkout_id' => $token,
                ]);

            return '';
        }
    }

    protected function getCustomerDetailsFromOrderMeta($order): array
    {
        $meta1cc = [];

        foreach ($order->orderMetas as $orderMeta)
        {
            if ($orderMeta->getType() === OrderMetaType::ONE_CLICK_CHECKOUT)
            {
                $meta1cc = $orderMeta->getValue();

                break;
            }
        }

        return $meta1cc['customer_details'] ?? [];
    }

    protected function getCheckoutIdFromOrder($order): string
    {
        return $order->toArrayPublic()['notes']['storefront_id'];
    }

    protected function getShopifyCheckout(string $checkoutId): array
    {
        $checkout = $this->getCheckoutbyStorefrontId($checkoutId);

        return $checkout['data']['node'] ?? [];
    }

    protected function getStorefrontIdFromWebUrl(string $webUrl): string
    {
        return base64_encode(self::GID_CHECKOUT . explode('checkouts/', $webUrl)[1]);
    }

    protected function getCartTokenFromCheckoutId(string $checkoutId): string
    {
        $gid = explode('?', base64_decode($checkoutId))[0];

        return str_replace(self::GID_CHECKOUT, '', $gid);
    }

    protected function getShopifyClientByMerchant()
    {
        $creds = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());

        return new Client($creds);
    }

    /**
     * Fire and forget API so we silently catch the throwable
     * @param array
     * @return void
     */
    protected function addMagicCheckoutUrlToShopifyCheckout(array $input): void
    {
        try
        {
            $input['shop_id'] = (new Utils)->stripAndReturnShopId($input['shop']);

            $checkoutUrl = $this->getMagicCheckoutUrl($input);

            $this->updateCheckoutWithUrl($checkoutUrl, $input['checkout_id']);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                    'type'  => 'update_attributes_failed',
                    'input' => $input,
                    'error' => $e->getMessage(),
                ]);
        }
    }

    protected function getMagicCheckoutUrl(array $input): string
    {
        return 'https://' . $input['shop_id'] . '.myshopify.com/cart?magic_order_id=' . $input['order_id'];
    }

    protected function updateCheckoutWithUrl(string $checkoutUrl, string $checkoutId)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->checkoutAttributesUpdateMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'checkoutId' => $checkoutId,
                'input'      => [
                    'customAttributes' => [
                        [
                          'key'   => 'magic_checkout_url',
                          'value' => $checkoutUrl
                        ]
                    ]
                ]
            ]
        ];

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

}
