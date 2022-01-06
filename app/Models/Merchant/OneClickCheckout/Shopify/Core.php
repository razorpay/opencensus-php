<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Services\CredcaseSigner;
use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;

class Core extends Base\Core
{

    const POLLING_INTERVAL_MILLIS = 10; // 300ms
    const MAX_TIME_DELAY_SEC = 10; // 10sec
    const CACHE_VALIDITY_TTL = 5 * 1440; // 5 days
    const SHA_256 = 'sha256';

    public function placeShopifyCheckout(array $input): array
    {
        $cart = $input['cart'];

        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getCreateCheckoutMutation();

        $lineItems = (new Utils)->getLineItemsFromCart($cart);

        $graphqlLineItems = (new Utils)->convertToGraphqlId($lineItems);

        $body = ['query' => $mutation, 'variables' => ['input' => $graphqlLineItems]];

        $response = json_decode(
            $client->sendStorefrontRequest(json_encode($body)),
            true
        );

        $this->trace->info(
          TraceCode::SHOPIFY_1CC_CREATE_CHECKOUT_RES,
          ['body' => $body, 'response' => $response]
        );

        if (empty($response['errors']) === false)
        {
            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR
              );
        }

        $checkoutCreate = $response['data']['checkoutCreate'];

        if (empty($checkoutCreate['checkoutUserErrors']) === false)
        {
            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR
              );
        }

        return $checkoutCreate['checkout'];
    }

    public function getOrderDetailsFromCheckout($checkoutId)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getCheckoutMutation();

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'id'=> $checkoutId
            ]
        ];

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    public function getAvailableShippingRates($checkoutId)
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

    public function getCoupons($input)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getCouponListMutation();

        $graphqlQuery = array('query' => $mutation);

        return $client->sendGraphqlRequest(json_encode($graphqlQuery));
    }

    public function applyCoupon($input, $checkoutId)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->applyCouponMutation();

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'discountCode'=> $input['code'],
                'checkoutId'=> $checkoutId,
            ],
        ];

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    public function removeCoupon($checkoutId)
    {
      $client = $this->getShopifyClientByMerchant();

      $mutation = (new Mutations)->removeCouponMutation();

      $graphqlQuery = array('query' => $mutation);

      $graphqlQuery = [
          'query' => $mutation,
          'variables' => [
              'checkoutId'=> $checkoutId,
          ],
      ];

      return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    public function updateCheckoutEmail(string $checkoutId, string $email)
    {
      $client = $this->getShopifyClientByMerchant();

      $mutation = (new Mutations)->getcheckoutEmailUpdateMutation();

      $graphqlQuery = [
          'query' => $mutation,
          'variables' => [
              'checkoutId' => $checkoutId,
              'email' => $email,
          ],
      ];
      $this->trace->info(
          TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_BODY,
          ['graphqlQuery' => $graphqlQuery]
      );
      return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    public function placeShopifyOrder($orderDetails)
    {
        $client = $this->getShopifyClientByMerchant();
        try {
            $order = $client->sendRestApiRequest(
              json_encode($orderDetails),
              'POST',
              '/orders.json'
            );
          } catch (\Exception $e)
          {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                  'error' => $e->getMessage()
                ]
            );

            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR,
                null,
                $e
            );
          }
          return json_decode($order, true);
    }

    public function getShopifyClientByMerchant()
    {
        $creds = $this->getShopifyAuthByMerchant();

        return new Client($creds);
    }

    public function getShopifyAuthByMerchant()
    {
        $config = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());

        return $config;
    }

    public function updateShippingAddress($checkoutId, $address)
    {
        $client = $this->getShopifyClientByMerchant();
        $mutation = (new Mutations)->getUpdateShippingAddressMutation();

        $shippingAddress['country'] = $address['country'];
        $shippingAddress['province'] = $address['state_code'];
        $shippingAddress['zip'] = $address['zipcode'];
        $shippingAddress['city'] = $address['city'];

        // mandatory fields for shopify so we mock them
        $shippingAddress['firstName'] = 'john';
        $shippingAddress['lastName'] = 'doe';
        $shippingAddress['address1'] = '11a john doe residency';

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'checkoutId'=> $checkoutId,
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
              return (new Core)->parseShippingRates($shippingRates);
            }

        } while ($currentTries < $maxTries);

        return [
            'serviceable' => false,
            'cod' => false,
            'shipping_fee' => 0,
            'cod_fee' => null,
        ];
    }

    /**
     * discuss limitation for release v1
     * in shopify shipping rate includes cod fee so we try and split it intelligently
     * this is a hack which might not scale
     * other option is we use cod slabs and ask merchant to not set diff fees in shopify
     */
    public function parseShippingRates($rates): array
    {
        if (empty($rates) === true)
        {
            return [
                'serviceable' => false,
                'cod' => false,
                'shipping_fee' => 0,
                'cod_fee' => null,
            ];
        }

        $bestRate;
        $codFee = null;
        $hasCod = false;

        foreach ($rates as $rate) {
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
            $codFee = $codRate - $bestRate;
            if ($codFee < 0) {
                $codFee = 0;
            }
        }

        return [
            'serviceable' => true,
            'shipping_fee' => intval($bestRate)*100,
            'cod' => $hasCod,
            'cod_fee' => $codFee === null ? $codFee : intval($codFee)*100,
        ];
    }

    protected function isMaybeCod(array $rate): bool
    {
        return (
            strpos(strtolower($rate['handle']), 'cash on delivery ') !== false
            or strpos(strtolower($rate['title']), 'cash on delivery ') !== false
        );
    }

    public function verifyHmacSignature(array $input)
    {

        $config = (new Core)->getShopifyAuthByMerchant();
        $secret = $config[OneClickCheckout\Constants::API_SECRET];

        // replay attacks; trace this!
        // if (time() - $input['timestamp'] > self::MAX_TIME_DELAY_SEC) {
        //     return false;
        // }

        $query = ''
            .'key_id=' . $input['key']
            .'path_prefix=' . $input['path_prefix']
            .'shop=' . $input['shop']
            .'timestamp=' . $input['timestamp']
            ;
        $hmac = hash_hmac(self::SHA_256, $query, $secret);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_HMAC_SIGNATURE,
            [
                'query'     => $query,
                'shop_id'   => $config[OneClickCheckout\Constants::SHOP_ID],
                'hmac'      => $hmac,
                'signature' => $input['signature']
            ]
        );
        return $input['signature'] === $hmac;
    }

    public function completeShopifyOrder(array $rzpOrder, array $rzpPayment): array
    {
        $client = $this->getShopifyClientByMerchant();

        $key = 'MAGIC_CHECKOUT:' . $rzpOrder['id'];

        $this->cache = $this->app['cache'];

        if (empty($this->cache->get($key)) === false)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                  'error' => 'Order has already been placed for this payment',
                  'order' => $rzpOrder
                ]
            );
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $body = $this->getCreateOrderPayload($rzpOrder, $rzpPayment['method']);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_PLACE_ORDER_BODY,
            ['body' => $body]
        );

        try
        {
          $order = $client->sendRestApiRequest(
            json_encode(['order' => $body]),
            'POST',
            '/orders.json'
          );
        }
        catch (\Exception $e)
        {
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_API_ERROR,
              [
                'error' => $e->getMessage()
              ]
          );
          throw new Exception\ServerErrorException(
              'Error while calling URL',
              ErrorCode::SERVER_ERROR,
              null,
              $e
          );
        }

        $order = json_decode($order, true);

        $this->cache->put($key, 1, self::CACHE_VALIDITY_TTL);

        $this->updateShopifyTransaction($order['order']['id'], $rzpPayment['method']);

        return $order;
    }

    protected function getCreateOrderPayload($rzpOrder, string $paymentMethod): array
    {
        $checkoutId = $rzpOrder['notes']['storefront_id'];
        $checkout = json_decode($this->getOrderDetailsFromCheckout($checkoutId), true);
        if (empty($checkout['data']['node']) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
        $body = $this->getOrderFromCheckout($checkout['data']['node']);

        $codFee = $rzpOrder['cod_fee']/100;
        $shippingFee = $rzpOrder['shipping_fee']/100;
        $customerDetails = $rzpOrder['customer_details'];
        $shippingAddress = $customerDetails['shipping_address'];
        $billingAddress = $customerDetails['billing_address'];

        $splitNames = $this->splitName($shippingAddress['name']);

        $body['shipping_address'] = array(
            'first_name' => $splitNames[0],
            'last_name' => $splitNames[1],
            'address1' => $shippingAddress['line1'],
            'address2' => $shippingAddress['line2'],
            'phone' => $shippingAddress['contact'],
            'city' => $shippingAddress['city'],
            'province' => $shippingAddress['state'],
            'country' => $shippingAddress['country'],
            'zip' => $shippingAddress['zipcode']
        );

        $splitNames = $this->splitName($billingAddress['name']);

        $body['billing_address'] = array(
            'first_name' => $splitNames[0],
            'last_name' => $splitNames[1],
            'address1' => $billingAddress['line1'],
            'address2' => $billingAddress['line2'],
            'phone' => $billingAddress['contact'],
            'city' => $billingAddress['city'],
            'province' => $billingAddress['state'],
            'country' => $billingAddress['country'],
            'zip' => $billingAddress['zipcode']
        );

        $body['email'] = $customerDetails['email'];

        $body['phone'] = $customerDetails['contact'];

        if (empty($rzpOrder['promotions']) === false)
        {
            $promotions = $rzpOrder['promotions'];

            $body['discount_codes'][] = array(
                'code' => $promotions[0]['code'],
                'amount' => $promotions[0]['value']/100
            );

            $body['current_total_discounts'] = $promotions[0]['value'];
        }

        $body['financial_status'] = 'paid';

        if (strtolower($paymentMethod) === 'cod')
        {
           $body['financial_status'] = 'pending';
           $shippingFee = $shippingFee + $codFee;
        }

        $body['shipping_lines'] = array(array('price' => $shippingFee,'title' => 'Standard Shipping'));

        return $body;
    }

    protected function splitName(string $name)
    {
      $words = explode(' ', $name);
      if (count($words) === 1)
      {
          $lastName = '.';
          $firstName = $name;
      } else
      {
          $lastName = array_pop($words);
          $firstName = implode(' ', $words);
      }
      return [$firstName, $lastName];
    }

    protected function updateShopifyTransaction(string $merchantOrderId, string $paymentMethod)
    {
        $body = [];
        if (strtolower($paymentMethod) === 'cod')
        {
            $body['transaction'] = [
                'message' => 'Pending Cash on Delivery (COD) payment from the buyer',
                'gateway' => 'Cash on Delivery (COD)',
                'status'  => 'pending',
            ];
        }
        else
        {
            $body['transaction'] = [
                'message' => 'Paid via Razorpay Magic Checkout',
                'gateway' => 'Razorpay',
                'status'  => 'success',
            ];
        }
        $body['transaction']['kind'] = 'sale';
        $body['transaction']['order_id'] = $merchantOrderId;
        $body['transaction']['source'] = 'external';
        $body['transaction']['processing_method'] = 'manual';
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_TRANSACTION_BODY,
            ['body' => $body]
        );
        try
        {
          $client = $this->getShopifyClientByMerchant();
          $order = $client->sendRestApiRequest(
            json_encode($body),
            'POST',
            '/orders/' . strval($merchantOrderId) . '/transactions.json'
          );
          return json_decode($order, true);
        }
        catch (\Exception $e)
        {
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_API_ERROR,
              [
                'error' => $e->getMessage()
              ]
          );
        }
    }

    function getOrderFromCheckout($checkout){
        $order = array();
        $order['currency'] = $checkout['currencyCode'];
        $order['current_subtotal_price'] = $checkout['subtotalPrice'];

        $order['taxes_included'] = $checkout['taxesIncluded'];
        if(!empty($checkout['lineItems']['edges'])){
            $line_items = [];
            foreach($checkout['lineItems']['edges'] as $value){
                $line_items[] = array(
                  'variant_id'=>str_replace('gid://shopify/ProductVariant/', '',base64_decode($value['node']['variant']['id'])),
                  'quantity'=>$value['node']['quantity']
                );
            }
            $order['line_items'] = $line_items;
        }

        $order['total_tax'] = $checkout['totalTax'];
        $order['inventory_behaviour'] = 'decrement_obeying_policy';
        $order['send_receipt'] = false;
        $order['note_attributes'] = [
          [
            "name" => "Paid via",
            "value" => "Razorpay Magic Checkout"
          ]
        ];
        return $order;
    }
}
