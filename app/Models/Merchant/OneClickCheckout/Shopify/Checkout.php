<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;

class Checkout extends Base\Core
{
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

    public function getNotesForCheckout(array $checkout): array
    {
        $notes = ['storefront_id'  => $checkout['id']];

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
    public function getCheckoutFromAdminApi(string $checkoutId)
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

    protected function getCartTokenFromCheckoutId(string $checkoutId): string
    {
        $tokenGid = explode('?', base64_decode($checkoutId))[0];

        return explode('gid://shopify/Checkout/', $tokenGid)[1];
    }

    protected function getShopifyClientByMerchant()
    {
        $creds = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());

        return new Client($creds);
    }
}
