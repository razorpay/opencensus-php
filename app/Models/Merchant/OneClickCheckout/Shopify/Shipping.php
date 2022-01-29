<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout;

class Shipping
{
    public function updateShippingAddress(string $checkoutId, array $address, Shopify\Client $client)
    {
        $mutation = (new Mutations)->getUpdateShippingAddressMutation();

        // name and address1 are compulsory fields but we don't collect it from
        // user at this time so we put default value
        $shippingAddress = [
            'firstName' => 'name',
            'lastName'  => 'not entered',
            'address1'  => 'address not entered',
            'country'   => $address['country'],
            'province'  => $address['state_code'],
            'zip'       => $address['zipcode'],
            'city'      => $address['city'],
        ];

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'checkoutId'      => $checkoutId,
                'shippingAddress' => $shippingAddress
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_SHIPPING_BODY,
            ['graphqlQuery' => $graphqlQuery]
        );

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    // processing is async so we need to sleep and poll
    public function sleepAndPollForShippingInfo(string $checkoutId, int $maxTries = 5)
    {
        $currentTries = 0;
        do
        {
            usleep(self::POLLING_INTERVAL_MILLIS * 1000);

            $currentTries++;

            $response = $this->getAvailableShippingRates($checkoutId);

            $body = json_decode($response, true);

            if (empty($body['errors']) === false || $body['data'] === null)
            {
                throw new Exception\ServerErrorException(
                    'Fetching shipping rates from Shopify failed',
                    ErrorCode::SERVER_ERROR
                );
            }

            $checkout = $body['data']['node'];

            $availableShippingRates = $checkout['availableShippingRates'];
            $shippingRates = $availableShippingRates['shippingRates'];
            $isShippingReady = $availableShippingRates['ready'];

            if ($isShippingReady === true and empty($shippingRates) === false)
            {
              $this->trace->info(
                  TraceCode::SHOPIFY_1CC_SHIPPING_RESPONSE,
                  ['checkout' => $checkout, 'currentTries' => $currentTries]
              );
              return $this->parseShippingRates($shippingRates);
            }

        } while ($currentTries < $maxTries);

        // No response received from Shopify servers
        return [
            'serviceable'  => false,
            'cod'          => false,
            'shipping_fee' => 0,
            'cod_fee'      => null,
        ];
    }

    /**
     * discuss limitation for release v1
     * in shopify shipping rate includes cod fee so we try and split it intelligently
     * this is a hack which might not scale
     * other option is we use cod slabs and ask merchant to not set diff fees in shopify
     * NOTE: if multiple cod options are available the lowest is chosen, this may be incorrect
     */
    protected function parseShippingRates($rates): array
    {
        if (empty($rates) === true)
        {
            return [
                'serviceable'  => false,
                'cod'          => false,
                'shipping_fee' => 0,
                'cod_fee'      => null,
            ];
        }

        $bestRate;
        $codFee = null;
        $hasCod = false;

        foreach ($rates as $rate)
        {
            $handle = $rate['handle'];
            $title = $rate['title'];
            $priceV2 = $rate['priceV2'];
            $amount = $priceV2['amount'];
            $currencyCode = $priceV2['currencyCode'];

            if ($this->isMaybeCod($rate) === true)
            {
                $hasCod = true;
                $codRate = $amount;
            }
            elseif (isset($bestRate) === false or $amount < $bestRate)
            {
                $bestRate = $amount;
            }
        }

        if (isset($codRate) === true)
        {
            $codFee = (new Utils)->formatNumber($codRate - $bestRate);

            $codFee = $codFee < 0 ? 0: $codFee;
        }

        return [
            'serviceable'  => true,
            'shipping_fee' => intval($bestRate * 100),
            'cod'          => $hasCod,
            'cod_fee'      => $codFee === null ? $codFee : intval($codFee * 100),
        ];
    }

    protected function isMaybeCod(array $rate): bool
    {
        return (
            strpos(strtolower($rate['handle']), 'cash on delivery ') !== false
            or strpos(strtolower($rate['title']), 'cash on delivery ') !== false
        );
    }

    protected function getAvailableShippingRates($checkoutId)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getPollForShippingRatesMutation();

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'id'=> $checkoutId
            ]
        ];
        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }
}
