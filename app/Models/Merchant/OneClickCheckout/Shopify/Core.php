<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Models\Order\OrderMeta\Type as OrderMetaType;

class Core extends Base\Core
{

    const POLLING_INTERVAL_MILLIS = 100; // 400ms counting API call

    const MAX_TIME_DELAY_SEC = 10; // 10sec

    const CACHE_VALIDITY_TTL = 5 * 1440; // 5 days

    const SHA_256 = 'sha256';

    const ORDER_CACHE_KEY = 'shopify_1cc_order';

    const ORDER_CACHE_KEY_TTL = 1 * 1440; // 1 day

    const MUTEX_KEY = 'shopify_1cc_place_order_mutex';

    public function placeShopifyCheckout(array $input): array
    {
        $start = millitime();

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
            ['body' => $body, 'response' => $response, 'time' => millitime() - $start]
        );

        if (empty($response['errors']) === false)
        {
            $this->trace->info(
                 TraceCode::SHOPIFY_1CC_API_ERROR,
                 [
                     'type'     => 'error_creating_checkout',
                     'response' => $response,
                 ]
            );

            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR
            );
        }

        $checkoutCreate = $response['data']['checkoutCreate'];

        if (empty($checkoutCreate['checkoutUserErrors']) === false)
        {
            $this->trace->info(
                 TraceCode::SHOPIFY_1CC_API_ERROR,
                 [
                     'type'     => 'error_creating_checkout',
                     'response' => $response,
                 ]
            );

            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR
            );
        }

        return $checkoutCreate['checkout'];
    }

    // Fire and forget API so we silently catch the Throwable
    public function addMagicCheckoutUrlToShopifyCheckout(array $input): void
    {
        try
        {
          $input['shop_id'] = (new Utils)->stripAndReturnShopId($input['shop_id']);

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

    public function applyCoupon($input, $checkoutId)
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->applyCouponMutation();

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'discountCode' => $input['code'],
                'checkoutId' => $checkoutId,
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
                'checkoutId' => $checkoutId,
            ],
        ];

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    // TODO: check update condition to handle concurrency issues when checkoutId changes
    public function updateCheckoutEmail(string $checkoutId, string $email)
    {
        $start = millitime();

        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getcheckoutEmailUpdateMutation();

        $graphqlQuery = [
            'query' => $mutation,
            'variables' => [
                'checkoutId' => $checkoutId,
                'email'      => $email,
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_BODY,
            ['checkoutId' => $checkoutId, 'time' => millitime() - $start]);

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
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

        // name and address1 are compulsory fields but we don't collect it from
        // user at this time so we put default value
        // province field can take state code or full state name depending on what is passed
        $shippingAddress = [
            'firstName' => $address['first_name'] ?? 'name',
            'lastName'  => $address['last_name']  ?? 'not entered',
            'address1'  => $address['line1']      ?? 'address not entered',
            'address2'  => $address['line2']      ?? '',
            'country'   => $address['country'],
            'province'  => $address['state_code'] ?? $address['state'],
            'zip'       => $address['zipcode'],
            'city'      => $address['city'],
            'phone'     => $address['contact'] ?? '',
        ];

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'checkoutId'      => $checkoutId,
                'shippingAddress' => $shippingAddress
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_SHIPPING_BODY,
            ['checkoutId' => $checkoutId, 'shippingAddress' => $shippingAddress]);

        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    // processing is async so we need to sleep and poll
    public function sleepAndPollForShippingInfo(string $checkoutId, int $maxTries = 5)
    {
        $start = millitime();

        $currentTries = 0;

        do
        {
            // TODO: optimize by checking logs
            usleep(self::POLLING_INTERVAL_MILLIS * 1000);

            $currentTries++;

            $response = $this->getAvailableShippingRates($checkoutId);

            $body = json_decode($response, true);

            if (empty($body['errors']) === false || $body['data'] === null)
            {
                $this->trace->info(
                     TraceCode::SHOPIFY_1CC_API_ERROR,
                     [
                         'type'       => 'invalid_response_fetching_rates',
                         'response'   => $body,
                         'checkoutId' => $checkoutId,
                         'retries'    => $currentTries,
                         'time'       => millitime() - $start,
                     ]
                );

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
                $rates = (new Core)->parseShippingRates($shippingRates);

                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_SHIPPING_RESPONSE,
                    [
                        'checkout'     => $checkout,
                        'currentTries' => $currentTries,
                        'time'         => millitime() - $start,
                        'rates'        => $rates,
                    ]
                );

                return $rates;
            }

        } while ($currentTries < $maxTries);

        $this->trace->info(
             TraceCode::SHOPIFY_1CC_API_ERROR,
             [
                 'type'       => 'rety_limit_exceeded_fetching_rates',
                 'checkoutId' => $checkoutId,
                 'retries'    => $currentTries,
                 'time'       => millitime() - $start,
             ]
        );

        return [
            'serviceable'  => false,
            'cod'          => false,
            'shipping_fee' => 0,
            'cod_fee'      => null,
        ];
    }

    /**
     * in shopify shipping rate includes cod fee so we try and split it intelligently
     * this is a hack which needs to be monitored
     * other option is we use cod slabs and ask merchant to not set diff fees in shopify
     * if multiple cod options are available the lowest is chosen
     */
    public function parseShippingRates($rates): array
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
            if ($codFee < 0) {
                $codFee = 0;
            }
        }

        $shippingResponse = [
            'serviceable'  => true,
            'shipping_fee' => intval($bestRate)*100,
            'cod'          => $hasCod,
            'cod_fee'      => $codFee === null ? $codFee : intval($codFee)*100,
        ];

        (new ShippingRates)->forceEnableCODIfApplicable($shippingResponse);

        return $shippingResponse;
    }

    protected function isMaybeCod(array $rate): bool
    {
        return (
            strpos(strtolower($rate['handle']), 'cash on delivery ') !== false
            or strpos(strtolower($rate['title']), 'cash on delivery ') !== false
        );
    }

    public function validateCheckoutOptionsRequest(array $input)
    {
        if (isset($input['order_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException('order_id is a compulsory field');
        }

        $config = (new Core)->getShopifyAuthByMerchant();

        if ((new Utils)->stripAndReturnShopId($input['shop']) !== $config[OneClickCheckout\Constants::SHOP_ID])
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
    }

    public function verifyHmacSignature(array $input, bool $useKeyId = true)
    {
        $config = (new Core)->getShopifyAuthByMerchant();

        $secret = $config[OneClickCheckout\Constants::API_SECRET];

        $query = ''
            . ($useKeyId === true ? 'key_id=' . $input['key'] : '')
            .'path_prefix=' . $input['path_prefix']
            .'shop=' . $input['shop']
            .'timestamp=' . $input['timestamp']
            ;

        $hmac = hash_hmac(self::SHA_256, $query, $secret);

        if ($hmac !== $input['signature'])
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_HMAC_VALIDATION_FAILED,
                [
                    'query'     => $query,
                    'shop_id'   => $config[OneClickCheckout\Constants::SHOP_ID],
                    'hmac'      => $hmac,
                    'signature' => $input['signature']
                ]
            );
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
    }

    public function canShopifyOrderBePlaced($order, $fromShopifyApi)
    {
        $receipt = $order->getReceipt();

        $orderId = $order->getId();

        $key = $this->getCacheKeyForPlacedOrders($orderId);

        $this->cache = $this->app['cache'];

        if (empty($this->cache->get($key)) === false or $receipt !== (new OneClickCheckout\Constants)::SHOPIFY_TEMP_RECEIPT)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                    'type'             => 'duplicate_order_received',
                    'error'            => 'Order has already been placed for this payment',
                    'order_id'         => $orderId,
                    'from_shopify_api' => $fromShopifyApi,
                ]
            );
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
    }

    public function saveShopifyOrderAsPlaced(string $orderId)
    {
        $key = $this->getCacheKeyForPlacedOrders($orderId);

        $this->cache->put($key, 1, self::CACHE_VALIDITY_TTL);
    }

    public function placeShopifyOrder(array $rzpOrder, array $rzpPayment): array
    {
        $start = millitime();

        $client = $this->getShopifyClientByMerchant();

        $body = $this->getCreateOrderPayload($rzpOrder, $rzpPayment['method']);

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

        $this->updateShopifyTransaction($order['order']['id'], $rzpPayment['method']);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_PLACE_ORDER_RES,
            ['shopify_order_id' => $order['order']['id'], 'time' => millitime() - $start]
        );

        return $order;
    }

    protected function getCreateOrderPayload($rzpOrder, string $paymentMethod): array
    {
        $checkoutId = $rzpOrder['notes']['storefront_id'];

        $checkout = (new Checkout)->getCheckoutbyStorefrontId($checkoutId);

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

        $body['shipping_address'] = [
            'first_name' => $splitNames[0],
            'last_name'  => $splitNames[1],
            'address1'   => $shippingAddress['line1'],
            'address2'   => $shippingAddress['line2'],
            'phone'      => $shippingAddress['contact'],
            'city'       => $shippingAddress['city'],
            'province'   => $shippingAddress['state'],
            'country'    => $shippingAddress['country'],
            'zip'        => $shippingAddress['zipcode']
        ];

        $splitNames = $this->splitName($billingAddress['name']);

        $body['billing_address'] = [
            'first_name' => $splitNames[0],
            'last_name'  => $splitNames[1],
            'address1'   => $billingAddress['line1'],
            'address2'   => $billingAddress['line2'],
            'phone'      => $billingAddress['contact'],
            'city'       => $billingAddress['city'],
            'province'   => $billingAddress['state'],
            'country'    => $billingAddress['country'],
            'zip'        => $billingAddress['zipcode']
        ];

        $body['email'] = $customerDetails['email'];

        $body['phone'] = $customerDetails['contact'];

        $body['customer'] = [
            'phone' => $customerDetails['contact'],
        ];

        if (empty($rzpOrder['promotions']) === false)
        {
            $promotions = $rzpOrder['promotions'];

            $body['discount_codes'][] = [
                'code'   => $promotions[0]['code'],
                'amount' => $promotions[0]['value']/100
            ];

            $body['current_total_discounts'] = $promotions[0]['value'];
        }

        $body['financial_status'] = 'paid';

        if (strtolower($paymentMethod) === 'cod')
        {
           $body['financial_status'] = 'pending';
           $shippingFee = $shippingFee + $codFee;
        }

        $body['shipping_lines'] = [
            [
                'price' => $shippingFee,
                'title' => 'Standard Shipping'
            ]
        ];

        return $body;
    }

    public function splitName(string $name): array
    {
        $name = preg_replace('/\s+/', ' ', trim($name));

        $words = explode(' ', $name);

        if (count($words) === 1)
        {
            $lastName = '.';

            $firstName = $name;

        }
        else
        {
            $lastName = array_pop($words);

            $firstName = implode(' ', $words);
        }

        return [$firstName, $lastName];
    }

    protected function updateShopifyTransaction(string $merchantOrderId, string $paymentMethod): array
    {
        $start = millitime();

        $body = $this->getTransactionBody($merchantOrderId, $paymentMethod);

        try
        {
          $client = $this->getShopifyClientByMerchant();

          $order = $client->sendRestApiRequest(
              json_encode($body),
              'POST',
              '/orders/' . strval($merchantOrderId) . '/transactions.json'
          );

          $this->trace->info(
              TraceCode::SHOPIFY_1CC_UPDATE_TRANSACTION_BODY,
              ['body' => $body, 'time' => millitime() - $start]
          );

          return json_decode($order, true);
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                ['error' => $e->getMessage(), 'time' => millitime() - $start]
            );
        }
    }

    protected function getTransactionBody(string $merchantOrderId, string $paymentMethod): array
    {
        $txn = [
            'kind'              => 'sale',
            'order_id'          => $merchantOrderId,
            'source'            => 'external',
            'processing_method' => 'manual',
        ];

        // TODO: evaluate default cod gateway used
        if (strtolower($paymentMethod) === 'cod')
        {
            $txn = array_merge($txn, [
                'message' => 'Pending Cash on Delivery (COD) payment from the buyer',
                'gateway' => 'Cash on Delivery (COD)',
                'status'  => 'pending',
            ]);
        }
        else
        {
            $txn = array_merge($txn, [
                'message' => 'Paid via Razorpay Magic Checkout',
                'gateway' => 'Razorpay',
                'status'  => 'success',
            ]);
        }

        return ['transaction' => $txn];
    }

    protected function getOrderFromCheckout($checkout)
    {
        $order = [
            'currency'               => $checkout['currencyCode'],
            'current_subtotal_price' => $checkout['subtotalPrice'],
            'taxes_included'         => $checkout['taxesIncluded'],
            'total_tax'              => $checkout['totalTax'],
            'inventory_behaviour'    => 'decrement_obeying_policy',
            'send_receipt'           => false,
            'note_attributes'        => [
                [
                  'name'  => 'Paid via',
                  'value' => 'Razorpay Magic Checkout'
                ]
            ]
        ];

        if (empty($checkout['lineItems']['edges']) === false)
        {
            $lineItems = [];

            foreach ($checkout['lineItems']['edges'] as $value)
            {
                $lineItems[] = [
                  'variant_id' => str_replace('gid://shopify/ProductVariant/', '', base64_decode($value['node']['variant']['id'])),
                  'quantity'   => $value['node']['quantity']
                ];
            }
            $order['line_items'] = $lineItems;
        }

        return $order;
    }

    public function getOrderFromCache(string $cartToken, string $browserUuid)
    {
        return $this->cache->get($this->getCacheKeyForReusingOrders($cartToken, $browserUuid));
    }

    public function setOrderFromCache(string $cartToken, string $browserUuid, string $orderId)
    {
        return $this->cache->set(
            $this->getCacheKeyForReusingOrders($cartToken, $browserUuid),
            $orderId,
            self::ORDER_CACHE_KEY_TTL
        );
    }

    protected function getCacheKeyForReusingOrder(string $cartToken, string $browserUuid): string
    {
        return self::ORDER_CACHE_KEY . ':' . $cartToken . ':' . $browserUuid;
    }

    public function getMutexKeyForOrder(string $paymentId): string
    {
        return self::MUTEX_KEY . ':' . $paymentId;
    }

    protected function getCacheKeyForPlacedOrders(string $orderId): string
    {
        return 'MAGIC_CHECKOUT:' . $orderId;
    }

    public function isPaymentAndOrderValid($order, $payment)
    {
        $method = $payment->getMethod();

        $status = $payment->getStatus();

        if ($payment->toArrayPublic()['order_id'] !== $order->getPublicId())
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        if (
            ($method === PaymentMethod::COD and $payment->getStatus() === PaymentStatus::PENDING) or
            ($method !== PaymentMethod::COD and in_array($status, [PaymentStatus::CAPTURED, PaymentStatus::AUTHORIZED]))
        )
        {
            return true;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
    }

    public function updateCheckout(array $input)
    {
        $orderId = $input['order_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $customerDetails = $this->getCustomerDetailsFromOrderMeta($order);

        if (empty($customerDetails) === true)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_UPDATE_CHECKOUT,
                ['input' => $input, 'status' => 'not_updated']);

            return;
        }

        $checkoutId = $this->getCheckoutIdFromOrder($order);

        // overwrite contact as we want to link the abandoned Shopify checkout contact
        // with the contact used to initiate Rzp checkout
        // This is imp for WhatsApp and SMS retargeting
        $customerDetails['shipping_address']['contact'] = $customerDetails['contact'];

        (new Checkout)->updateCheckoutFromAdmin([
            'order_id'         => $orderId,
            'checkout_id'      => $checkoutId,
            'phone'            => $customerDetails['contact'],
            'email'            => $customerDetails['email'],
            'shipping_address' => $customerDetails['shipping_address'],
        ]);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_CHECKOUT,
            ['input' => $input, 'status' => 'updated']);
    }

    protected function formatShippingAddressForCheckout($shippingAddress): array
    {
        $splitNames = $this->splitName($shippingAddress['name']);

        unset($shippingAddress['name']);

        return array_merge(
            $shippingAddress,
            [
                'first_name' => $splitNames[0],
                'last_name'  => $splitNames[1],
                'line2'      => $shippingAddress['line2'] ?? ''
            ]);
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
        $checkout = (new Checkout)->getCheckoutbyStorefrontId($checkoutId);

        return $checkout['data']['node'] ?? [];
    }

}
