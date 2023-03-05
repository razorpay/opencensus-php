<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Razorpay\Trace\Logger as Trace;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Constants;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Order\OrderMeta;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Constants as ShopifyConstants;

class Service extends Base\Service
{

    const MUTEX_LOCK_TTL_SEC = 60;

    const MAX_RETRY_COUNT = 4;

    const MAX_RETRY_DELAY_MILLIS = 1 * 30 * 1000;

    const skipListCouponMids = [
        'DzyQ9A6YiAcZpT',
        'Glcz7NhPAxVEOw',
        'GA7JN5LdX495NH',
        'F5JbTV6pBVIyud',
        'EGCzwErjjYe9nL',   //Adjavis Digital LLP
        'Hj1IOXYBFOQLRL',   //PSI EXCEL EXPORTS
        'ETejwsC2azC6tI',   //Nanda Electric
        'FopgLHiMahqW6K',   //BLISSCLUB FITNESS PRIVATE LIMITED
        'EbxFyGur6ER4eE',   //Talk To Crystals
        'GfX5XhS9sHvs7X',   //Re Thought
        'ChdCdGm7TvuVk6',   //boAt
        'FN2kulvZ47wf4g',   //Limese
        'IDTUUOoV4Ph06T',   //Khiangte Skincare
        'GkUeUmMJI0xrIN',   //Asa industries
        'FPAhixNh1FSnch',   //Rahul Trading and Lubricants
        '5IXXDp7kTi2BtJ',   //Cyahi
        'JCdhfzRcU0ymaX',   //LAMRIM LLP
    ];

    const farziEnabledMerchants = [
        'ChdCdGm7TvuVk6' => 'boat-api',//boAt
        'FBZftClq7omC5j' => 'minimalistfphapi', //beminimalist
        'Iip55js9TPnDd8' => 'wow-api' //wow science
    ];

    const MagicAnalyticsBESyncFlowEventsFlag = false;

    protected $mutex;

    protected $monitoring;

    protected $cache;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = App::getFacadeRoot()['api.mutex'];

        $this->monitoring = new Monitoring();
    }

    public function shopifyCartLineItems(array $checkout) : array
    {
        //Cart line items for the modal
        $lineItems = $checkout['lineItems']['edges'];

        $cartLineItems = [];

        $isCartDiscountApplied = false;

        foreach ($lineItems as $key => $item)
        {
            $item=$item['node'];

            $offerPrice = round(floatval($item['variant']['price']['amount']) * 100);

            if (empty($item['discountAllocations']) === false)
            {
                $offerPrice = round(floatval($item['variant']['price']['amount']) * 100) - round(floatval($item['discountAllocations'][0]['allocatedAmount']['amount']) * 100);

                if ($offerPrice < 0)
                {
                    $offerPrice = 0;
                }

                $isCartDiscountApplied = true;
            }

            $cartLineItems[] = [
                'variant_id'        => mb_substr(strval($item['variant']['id']), 0, 128, 'UTF-8'),
                'tax_amount'        => 0,
                'sku'               => mb_substr(strval($item['variant']['sku']), 0, 128, 'UTF-8'),
                'price'             => round(floatval($item['variant']['price']['amount']) * 100),
                'offer_price'       => $offerPrice,
                'quantity'          => (int)floatval($item['quantity']),
                'name'              => mb_substr(strval($item['title']), 0, 128, 'UTF-8'),
                'description'       => mb_substr($item['variant']['product']['description'], 0, 256, 'UTF-8'),
                'weight'            => (int)floatval($item['variant']['weight']),
                'image_url'         => $item['variant']['image']['url'] ?? ""
            ];
        }

        return [
            'cart_line_items' => $cartLineItems,
            'is_cart_discount_applied' => $isCartDiscountApplied
        ];
    }

    public function shopifyScriptCartLineItems(array $checkout, $cartFromCache) : array
    {
        if (empty($cartFromCache) === true)
        {
            return $this->shopifyCartLineItems($checkout);
        }

        //Cart line items for the modal
        $cacheCartLineItems = $cartFromCache['line_items'];

        $checkoutLineItems = $checkout['lineItems']['edges'];

        foreach ($cacheCartLineItems as $key => $item)
        {
            $cartLineItems[] = [
                'variant_id'        => mb_substr(strval($item['variant_id']), 0, 128, 'UTF-8'),
                'tax_amount'        => 0,
                'sku'               => mb_substr(strval($item['sku']), 0, 128, 'UTF-8'),
                'price'             => round(floatval($item['original_price']) * 100),
                'offer_price'       => round(floatval($item['discounted_price']) * 100),
                'quantity'          => (int)floatval($item['quantity']),
                'name'              => mb_substr(strval($item['title']), 0, 128, 'UTF-8'),
                'description'       => mb_substr($item['title'], 0, 256, 'UTF-8'),
                'weight'            => (int)floatval($item['grams'] / 1000),
                'image_url'         => "",
            ];

            foreach ($checkoutLineItems as $lineItem) {

                $lineItem = $lineItem['node'];

                // To support backward compatibility of Shopify API version update from 2022-01 to 2022-10
                if(substr($lineItem['variant']['id'], 0, 3) != "gid")
                {
                    $variantIdFromCheckout = str_replace('gid://shopify/ProductVariant/', '', base64_decode($lineItem['variant']['id']));
                }
                else
                {
                    $variantIdFromCheckout = $lineItem['variant']['id'];
                }

                if ($item['variant_id'] == $variantIdFromCheckout)
                {
                    $cartLineItems[$key]['image_url'] = $lineItem['variant']['image']['url'] ?? "";
                }
            }
        }

        return [
            'cart_line_items' => $cartLineItems
        ];
    }

    /**
     * starts the 1cc flow for shopify
     * amount from checkout and order should be the same
     * it may misbehave when auto coupon apply and free items work
     * explore building the order with the line_items from $checkout
     * @param array cart
     * @param token string
     * @param string additional params part of preferences API
     * @return array checkoutParams - Checkout and preferences object
     */
    public function shopifyCreateCheckout(array $input, array $customerInfo): array
    {
        unset($input['ga_id']);
        $start = millitime();
        (new Checkout)->validateCreateCheckout($input);
        $isAutoDiscountApplied = false;
        $cart = $input['cart'];

        $checkout = (new Checkout)->placeShopifyCheckout(['cart' => $cart]);

        $preferenceParams = [];
        if (isset($input['send_preferences']) === true and $input['send_preferences'] == 'true')
        {
            $preferenceParams = $this->getParamsForPreferences($input);
            $preferenceParams['send_preferences'] = true;
        }

        $response = $this->createOrderAndGetCheckoutPreferences($checkout, $cart, $preferenceParams, $customerInfo);
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CREATE_RZP_ORDER_RES,
            [
                'order_id' => $response['order_id'],
                'time'     => millitime() - $start,
            ]);

        // todo: remove this if condition when we enable flag for sync events.
        if (self::MagicAnalyticsBESyncFlowEventsFlag === true)
        {
            try
            {
                (new Analytics)->sendCheckoutEvent($cart, $customerInfo);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::MAGIC_CHECKOUT_CHECKOUT_EVENT_FAILED,
                    []
                );

                $this->trace->count(TraceCode::MAGIC_CHECKOUT_CHECKOUT_EVENT_FAILED);
            }
        }

        return $response;
    }


    /**
     * Creates a razorpay order for a given shopify checkout and returns order_id, preferences
     *
     * @param array $input
     * @return array
     */
    public function createOrderAndGetPreferences(array $input, array $customerInfo): array
    {
        unset($input['ga_id'], $input['fb_analytics']);

        // To support backward compatibility of Shopify API version update from 2022-01 to 2022-10
        $input = $this->versionBasedInput($input);

        (new Validator)->validateInput('createShopifyOrderAndPreferences', $input);

        // Set Merchant basic auth
        $merchantId = $input['merchant_id'];
        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setMerchant($this->merchant);

        $checkout = $input['checkout'];
        $cart = $input['cart'];
        $preferenceParams = $input['preference_params'];

        $response = $this->createOrderAndGetCheckoutPreferences(
            $checkout,
            $cart,
            $preferenceParams,
            $customerInfo
        );

        return [
            'order_id'   => $response['order_id'],
            'preferences' => $response['preferences'],
        ];
    }

    protected function versionBasedInput(array $input)
    {
        if(isset($input['checkout']['totalPriceV2']) === true)
        {
            $input['checkout']['totalPrice'] = $input['checkout']['totalPriceV2'];
        }

        $lineItems = $input['checkout']['lineItems']['edges'];

        foreach ($lineItems as $key => $item)
        {
            $item=$item['node'];

            if(is_array($item['variant']['price']) === false)
            {
                $price['amount'] = $item['variant']['price'];
                $input['checkout']['lineItems']['edges'][$key]['node']['variant']['price'] = $price;
            }
        }

        return $input;
    }

    protected function createOrderAndGetCheckoutPreferences(
        array $checkout,
        array $cart,
        array  $preferenceParams,
        array $customerInfo
    ): array
    {
        $cartId = $cart['token'];

        $checkoutAmount = round(floatval($checkout['totalPrice']['amount']) * 100);

        $isAutoDiscountApplied = $this->isScriptDiscountApplied($cart);

        if ($isAutoDiscountApplied)
        {
            $cartPrice = (int)(floatval($cart['total_price']));

            $scriptData = $this->getScriptData($cartId, $cartPrice, $checkout);

            $amount = $scriptData['amount'];

            $lineItemsData = $scriptData['lineItemsData'];

            $orderNotes = $scriptData['orderNotes'];
        }
        else
        {
            $cartLineItemsData = $this->shopifyCartLineItems($checkout);

            $isAutoDiscountApplied = $cartLineItemsData['is_cart_discount_applied'];

            $lineItemsData = $cartLineItemsData['cart_line_items'];

            $amount = $checkoutAmount;

            $orderNotes = (new Checkout)->getNotesForCheckout($checkout, $cartId, $cart);
        }

        $order = (new RzpOrders)->createOrder(
            [
                'receipt'          => (new OneClickCheckout\Constants)::SHOPIFY_TEMP_RECEIPT,
                'amount'           => $amount,
                'currency'         => 'INR',
                'payment_capture'  => 1,
                'line_items_total' => $amount,
                'notes'            => $orderNotes,
                'line_items'       => $lineItemsData,
            ]
        );

        $checkoutParams = [
            'order_id'              => $order->getPublicId(),
            'currency'              => 'INR',
            'name'                  => $this->merchant->getBillingLabel(),
            'one_click_checkout'    => true,
            'customer_cart'         => (new Pixels)->getDataForFbPixels($checkout),
            'script_coupon_applied' => $isAutoDiscountApplied,
        ];

        if (isset($preferenceParams['send_preferences']) === true and $preferenceParams['send_preferences'] === true)
        {
            unset($preferenceParams['send_preferences']);
            $preferenceParams['order_id'] = $order->getPublicId();
            $preferences = (new MerchantService)->getCheckoutPreferences($preferenceParams);
            $checkoutParams = array_merge($checkoutParams, ['preferences' => $preferences]);
        }

        (new Analytics)->storeAnalyticsCustomerInfoInCache($checkoutParams['order_id'], json_encode($customerInfo));

        return $checkoutParams;
    }

    public function isScriptDiscountApplied(array $cart)
    {
        $isScriptApplied = false;

        foreach ($cart['items'] as $key => $item)
        {
            if (empty($item['line_level_discount_allocations']) === false)
            {
                foreach ($item['line_level_discount_allocations'] as $lineLevelDiscount)
                {
                    if ($lineLevelDiscount['discount_application']['type'] === 'script')
                    {
                        $isScriptApplied = true;

                        break;
                    }
                }
            }
        }

        return $isScriptApplied;
    }

    /**
     * Ensures preferences function receives same parametres as in normal API call
     * @param array input - Post body and URL params received
     * @param Order\Entity order - Razorpay order
     */
    protected function getParamsForPreferences(array $input): array
    {
        unset($input['cart']);
        unset($input['key']);
        return $input;
    }

    /**
     * Get the final checkout
     */
    protected function getScriptData($cartId, $cartPrice, $checkout)
    {
        $checkoutAmount = round(floatval($checkout['totalPrice']['amount']) * 100);

        $cart = (new Cart)->getCartData($cartId);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_GET_SCRIPT_DISCOUNT,
            [
                'type'            => 'create_checkout_amount',
                'cart'            => $cart,
                'checkout_amount' => $checkoutAmount,
                'cart_price'      => $cartPrice
            ]);

        if (empty($cart) === true || isset($cart['error']) === true)
        {
            $amount = $checkoutAmount;

            $cartLineItemsData = $this->shopifyCartLineItems($checkout);

            $lineItemsData = $cartLineItemsData['cart_line_items'];

            $orderNotes = (new Checkout)->getNotesForCheckout($checkout, $cartId);

            $this->monitoring->addTraceCount(Metric::SCRIPT_DISCOUNT_FETCH_FAIL_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_SCRIPT_DISCOUNT_FETCH_FAIL]);
        }
        else
        {
            $amount = 0;

            foreach($cart['line_items'] as $item)
            {
                $amount += round(floatval($item['line_price']) * 100);
            }

            // TODO: Reconsider this check, is it required or not
            if (strval($amount) != strval($cartPrice))
            {
                $amount = $checkoutAmount;
            }

            $this->monitoring->addTraceCount(Metric::SCRIPT_DISCOUNT_FETCH_SUCCESS_COUNT, []);

            $cartLineItemsData = $this->shopifyScriptCartLineItems($checkout, $cart);

            $lineItemsData = $cartLineItemsData['cart_line_items'];

            $orderNotes = (new Checkout)->getNotesForCheckout($checkout, $cartId, $cart);
        }

        return [
            'amount'        => $amount,
            'lineItemsData' => $lineItemsData,
            'orderNotes'    => $orderNotes
        ];
    }

    public function controlMagicCheckout(string $key, string $value)
    {
        $start = millitime();
        $data = $this->core()->setMetaFieldValue($key,$value);
        $this->trace->info(
            TraceCode::METAFIELD_KEY_VALUE_SET,
            [
                'merchant_id'=>$this->merchant->getId(),
                'key'=>$key,
                'value'=> $value,
                'time'=> millitime() - $start
            ]);
        return $data;
    }

    public function getCheckoutOptions(array $input): array
    {
        $isAutoDiscountApplied = false;

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_RETARGETING_URL_HIT,
            ['input' => $input]);

        (new Core)->validateCheckoutOptionsRequest($input);

        $order = (new RzpOrders)->findOrderByIdAndMerchant($input['order_id']);

        $checkoutId = $order->getNotes()['storefront_id'];

        $scriptDiscountAmount = $order->getNotes()['Script_Discount_Amount'] ?? 0;

        $checkout = (new Checkout)->getCheckoutFromAdminApi($checkoutId);

        if ($scriptDiscountAmount > 0)
        {
            $isAutoDiscountApplied = true;
        }

        $checkoutParams = [
            'order_id'           => $input['order_id'],
            'currency'           => 'INR',
            'name'               => $this->merchant->getBillingLabel(),
            'one_click_checkout' => true,
            'customer_cart'      => (new Pixels)->getDataForFbPixels($checkout),
            'prefill'            => [
                'email'   => $checkout['email'] ?? '',
                'contact' => $checkout['phone'] ?? '',
            ],
            'script_coupon_applied' => $isAutoDiscountApplied,
        ];

        return $checkoutParams;
    }

    public function updateCheckout(array $input): array
    {
        (new Validator())->setStrictFalse()->validateInput(Validator::UPDATE_CHECKOUT, $input);
        (new Checkout)->updateCheckoutFromAdmin($input);
        // TODO: handle 400, 503 to frontend
        return [];
    }

    public function completeCheckoutWithLock(array $input, bool $fromShopifyApi = true): array
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_MUTEX_INITIATED,
            [
                'type'           => 'mutex_initiated',
                'input'          => $input,
                'from_shopify_api' => $fromShopifyApi,
            ]
        );

        $key = (new Core)->getMutexKeyForOrder($input['razorpay_order_id']);

        $res = $this->mutex->acquireAndRelease(
            $key,
            function () use ($input, $fromShopifyApi)
            {
                return $this->shopifyCompleteCheckout($input, $fromShopifyApi);
            },
            self::MUTEX_LOCK_TTL_SEC,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::MAX_RETRY_COUNT,
            self::MAX_RETRY_DELAY_MILLIS - 500,
            self::MAX_RETRY_DELAY_MILLIS
        );

        return $res;
    }

    // updates shopify order post payment and redirects the user
    protected function shopifyCompleteCheckout(array $input, bool $fromShopifyApi): array
    {
        // set the merchant, mode for SQS job
        if ($fromShopifyApi === false)
        {
          $this->app['basicauth']->setMode($input['mode']);

          $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

          $this->app['basicauth']->setMerchant($this->merchant);
        }
        else
        {
            (new Validator)->setStrictFalse()->validateInput(Validator::COMPLETE_CHECKOUT, $input);
        }

        $orderId = $input['razorpay_order_id'];

        $paymentId = $input['razorpay_payment_id'];

        $order = (new RzpOrders())->findOrderByIdAndMerchant($orderId);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        (new Core)->isPaymentAndOrderValid($order, $payment);

        $receipt = $order->getReceipt();

        if ($receipt !== OneClickCheckout\Constants::SHOPIFY_TEMP_RECEIPT)
        {
            if ($fromShopifyApi === true)
            {
                $this->trace->error(
                    TraceCode::SHOPIFY_1CC_API_ERROR,
                    [
                        'type'             => 'duplicate_order_received',
                        'order_id'         => $order->getPublicId(),
                        'from_shopify_api' => $fromShopifyApi,
                    ]);
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
            }
            return [];
        }

        $this->checkForGiftCardPayment($order, $payment, $this->merchant, $fromShopifyApi);

        $orderArray = $order->toArrayPublic();

        $shopifyOrder = $this->placeShopifyOrder($order, $payment, $fromShopifyApi);

        $this->updateRzpOrder($order, $shopifyOrder);

        $analytics = new Analytics();

        $analytics->setShopifyOrderInCache($shopifyOrder, $orderArray, $payment->getMethod());

        // send GA purchase event only on async flow
        // todo: remove this if condition when we enable events for sync flow too
        if ($fromShopifyApi === false)
        {
            $providerTypeList = [ShopifyConstants::GOOGLE_UNIVERSAL_ANALYTICS, ShopifyConstants::FB_ANALYTICS];
        }
        else
        {
            $providerTypeList = [ShopifyConstants::FB_ANALYTICS];
        }

        try
        {
            $customerInfo = $analytics->getAnalyticsCustomerInfoFromCache($order->getPublicId());
            if (!empty($customerInfo))
            {
                $analytics->sendPurchaseEvent($shopifyOrder, $orderArray, $customerInfo, $providerTypeList);
            }
            else
            {
                $this->trace->count(TraceCode::MAGIC_CHECKOUT_PURCHASE_EVENT_FAILED);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAGIC_CHECKOUT_PURCHASE_EVENT_FAILED,
                []
            );

            $this->trace->count(TraceCode::MAGIC_CHECKOUT_PURCHASE_EVENT_FAILED);
        }

        $countryCode = $orderArray['customer_details']['shipping_address']['country'];

        // NOTE: promotions is not set if the 1ccResetAPI call fails, until CX team fixes it
        // keep the null check here
        $response = [
            'total_amount'     => $orderArray['amount'],
            'promotions'       => $orderArray['promotions'] ?? [],
            'shipping_fee'     => $orderArray['shipping_fee'],
            'order_id'         => $shopifyOrder['order']['name'],
            'id'               => $shopifyOrder['order']['id'],
            'total_tax'        => $shopifyOrder['order']['total_tax'],
            'payment_method'   => $payment['method'],
            'payment_currency' => $payment['currency'],
            'payment_id'       => $paymentId,
            'shipping_country' => Constants\Country::getCountryNameByCode($countryCode) ?? $countryCode
        ];

        // Do not log PII.
        $response['customer_details'] = $orderArray['customer_details'];
        $response['order_status_url'] = $shopifyOrder['order']['order_status_url'];

        return $response;
    }

    // places final order and gateway transaction to Shopify
    public function placeShopifyOrder($order, $payment, $fromShopifyApi): array
    {
        $start = millitime();

        $shopifyOrder = (new Core)->placeShopifyOrder($order->toArrayPublic(), $payment->toArrayPublic(), $fromShopifyApi);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_COMPLETE_ORDER_REQUEST,
            [
                'type'             => 'order_place_request',
                'order_id'         => $order->getId(),
                'payment_id'       => $payment->getId(),
                'time'             => millitime() - $start,
                'from_shopify_api' => $fromShopifyApi,
            ]
        );

        return $shopifyOrder;
    }

    // Updating the Razorpay order with the necessary details
    protected function updateRzpOrder($rzpOrder, $shopifyOrder)
    {
        $rzpOrderArray = $rzpOrder->toArrayPublic();

        $rzpOrderId = $rzpOrderArray['id'];

        $notes = $rzpOrderArray['notes'];

        $notes['shopify_order_id'] = strval($shopifyOrder['order']['id']);

        (new RzpOrders())->updateOrderNotes($rzpOrderId, $notes);

        $shopifyOrderName = strval($shopifyOrder['order']['name']);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_RECEIPT_UPDATE,
            [
                'step'     => 'update_receipt',
                'order_id' => $rzpOrder->getId(),
                'receipt'  => $shopifyOrderName,
                'external' => $rzpOrder->isExternal()
        ]);
        (new RzpOrders())->updateReceipt($rzpOrder, $shopifyOrderName);
    }

    // returns list of coupons, filter out personal and shipping coupons
    public function getShopifyCoupons(array $input, string $merchantId = ''): array
    {
        $checkoutId = $input['order_id'];

        $checkout = (new Checkout)->getCheckoutbyStorefrontId($checkoutId);

        // TODO: discuss proper error for this
        if (empty($checkout['data']['node']) === true)
        {
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_COUPONS_ERROR,
                 [
                     'type'       => 'invalid_checkout_id',
                     'checkout_id' => $checkoutId,
                     'checkout'   => $checkout,
                 ]);

            $this->monitoring->addTraceCount(Metric::SHOPIFY_COUPON_FETCH_ERROR_COUNT, ['error_type' => 'invalid_checkout_id']);

            return ['promotions' => []];
        }

        $checkoutNode = $checkout['data']['node'];

        // NOTE: For now we do not update existing emails until storefront_id fix is completed
        // update emails for logged in users
        if (empty($input['email']) === false and empty($checkoutNode['email']) === true)
        {
            try
            {
                (new Checkout)->updateCheckoutEmail($checkoutId, $input['email']);
            }
            catch (\Exception $e)
            {
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_FAILED,
                    ['checkout_id' => $checkoutId, 'reason' => $e.getMessage()]);

            $this->monitoring->addTraceCount(Metric::SHOPIFY_UPDATE_EMAIL_ERROR_COUNT, ['error_type' => 'email_update_failed']);
            }
        }

        if (isset($merchantId) === true and in_array($merchantId, self::skipListCouponMids) === true)
        {
            return ['promotions' => []];
        }


        $input['amount'] = $checkoutNode['subtotalPrice']['amount'];

        $countryCode = $checkoutNode['currencyCode'];

        $orderQuantity = 0;

        foreach ($checkoutNode['lineItems']['edges'] as $item)
        {
            $item = $item['node'];
            $orderQuantity += $item['quantity'];
        }

        $input['order_quantity'] = $orderQuantity;

        return (new Coupons)->getCoupons($input);
    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     */
    public function applyShopifyCoupon(array $input, string $merchantId = ''):array
    {
        if (empty($input['order_id']) === true)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_APPLY_COUPON_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_COUPONS_BAD_REQUEST_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_APPLY_COUPON_ERROR,
                [
                    'input' => $input,
                    'error' => 'Shopify checkout_id is required'
                ]);

            throw new Exception\BadRequestValidationFailureException("Shopify checkout_id is required.");
        }

        // existing coupon will be removed by checkout when new coupon is applied
        $checkoutId = $input['order_id'];

        // remove existing coupon
        (new Core)->removeCoupon($checkoutId);

        // TODO: fix when email updated multiple times
        if (empty($input['email']) === false)
        {
            try
            {
                (new Checkout)->updateCheckoutEmail($checkoutId, $input['email']);
            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_FAILED,
                    ['checkout_id' => $checkoutId, 'reason' => $e.getMessage()]);

                $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_UPDATE_EMAIL_FAILURE_COUNT, ['error_type' => 'shopify_1cc_apply_coupon_error']);

            }
        }

        $cartId = $input['cart_id'];

        $code = $input['code'];

        //adding the coupon to Shopify if generated by Farzi

        if (isset($merchantId) === true  and in_array($merchantId, array_keys(self::farziEnabledMerchants)) === true)
        {
            if (empty($input['cart_id']) === true)
            {
                $this->monitoring->addTraceCount(Metric::SHOPIFY_1CC_APPLY_COUPON_ERROR_COUNT, ['error_type' => 'shopify_1cc_apply_coupon_error']);

                $this->trace->error(
                    TraceCode::SHOPIFY_1CC_APPLY_COUPON_ERROR,
                    [
                        'input' => $input,
                        'error' => 'Shopify cart_id is required'
                    ]);

                throw new Exception\BadRequestValidationFailureException("Shopify cart_id is required.");
            }

            (new Farzi)->addFarziCoupon($code, $cartId, self::farziEnabledMerchants[$merchantId]);
        }

        return (new Coupons)->applyCoupon($input, $checkoutId);
    }

    /**
     * updates the notes of shopify checkout with magic checkout url
     * TODO: see metrics for this
     */
    public function updateCheckoutUrl(array $input): array
    {
        (new Checkout)->updateCheckoutUrl($input);

        return [];
    }

    /**
     * shipping calculations are async from Shopify
     * sleep and poll till rates are ready
     * if address is changed, we may get the stale rates
     * so we start with one sleep and poll
     */
    public function getShippingInfo(array $input)
    {
        $checkoutId = $input['order_id'];
        $address = $input['address'];

        $address['city'] = empty($address['city']) === false ? $address['city'] : 'NA';

        $address['zipcode'] = empty($address['zipcode']) === false ? $address['zipcode'] : $address['state_code']; //handles null check

        return $this->getShippingForOneAddress($checkoutId, $address);
    }

    // get serviceability and fee for single address
    public function getShippingForOneAddress(string $checkoutId, array $address): array
    {
        $response = (new Core)->updateShippingAddress($checkoutId, $address);

        $response = json_decode($response, true);

        if (empty($response['errors']) === false
        or empty($response['data']['checkoutShippingAddressUpdateV2']['checkoutUserErrors']) === false)
        {
          // address has pincode and state so we can log it (no PII)
          $errorType = (new Shipping)->getValueForErrorTypeDimension($response, 'update_address_failed');
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_API_SHIPPING_ERROR,
              [
                  'type'        => $errorType,
                  'response'    => $response,
                  'checkout_id' => $checkoutId,
                  'address'     => $address
              ]
          );
          $this->monitoring->addTraceCount(Metric::FETCH_SHIPPING_INFO_ERROR_COUNT, ['error_type' => $errorType]);

          return [
              'id'			     => $address['id'],
              'zipcode'      => $address['zipcode'],
              'state_code'   => $address['state_code'],
              'country'      => $address['country'],
              'serviceable'  => false,
              'cod'          => false,
              'shipping_fee' => 0,
              'cod_fee'      => null,
          ];
        }

        $rates = (new Core)->sleepAndPollForShippingInfo($checkoutId);

        $response = [
            'id'	       => $address['id'],
            'zipcode'    => $address['zipcode'],
            'state_code' => $address['state_code'],
            'country'    => $address['country'],
        ];

        return array_merge($response, $rates);
    }

    // TODO: mock this class
    protected function getShopifyClientByMerchant()
    {
        $creds = $this->getShopifyAuthByMerchant();
        if (empty($creds) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED);
        }
        return new Client($creds);
    }

    protected function getShopifyAuthByMerchant()
    {
        $config = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());

        return $config;
    }

    public function storeCartInCache(string $merchantId, $cartInput)
    {
        if (empty($cartInput['token']) === true)
        {
            return ['success' => false];
        }

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CART_WEBHOOK,
            [
                'type'        => 'store_webhook_cart',
                'cart_object' => $cartInput,
            ]);

        (new Cart)->setCartToCache($merchantId, $cartInput);

        return ['success' => true];
    }

    public function cancelShopifyOrder($input){

        $rzpOrderId = $input[OneClickCheckout\Constants::ID];

        $order = (new Order\Service())->fetchById($rzpOrderId);

        $shopifyOrderId = $order[Order\Entity::NOTES][OneClickCheckout\Shopify\Constants::SHOPIFY_ORDER_ID];

        $start = millitime();

        (new Core())->cancelShopifyOrder($shopifyOrderId, $input[OneClickCheckout\Constants::MERCHANT_ID]);

        $this->trace->info(
            TraceCode::SHOPIFY_ORDER_CANCEL,
            [
                'id'        =>$rzpOrderId,
                'time'      => millitime() - $start
            ]);

        $param = [
            Order1cc\Fields::REVIEW_STATUS  => Order1cc\Constants::CANCELED,
            Order\Entity::ID                => $rzpOrderId
        ];

        (new OrderMeta\Service())->updateReviewStatusFor1ccOrder($param,$input[OneClickCheckout\Constants::MERCHANT_ID]);

    }

    public function addTag($input){

        $rzpOrderId = $input[OneClickCheckout\Constants::ID];

        $order = (new Order\Service())->fetchById($rzpOrderId);

        $shopifyOrderId = $order[Order\Entity::NOTES][OneClickCheckout\Shopify\Constants::SHOPIFY_ORDER_ID];

        $tags = [\RZP\Models\Merchant\OneClickCheckout\Shopify\Constants::TAG_HOLD];

        $start = millitime();

        (new Core())->addTagToOrder($shopifyOrderId, $input[OneClickCheckout\Constants::MERCHANT_ID],$tags);

        $this->trace->info(
            TraceCode::SHOPIFY_ADD_TAG,
            [
                'id'        =>$rzpOrderId,
                'time'      => millitime() - $start
            ]);

        $param = [
            Order1cc\Fields::REVIEW_STATUS  => OneClickCheckout\Constants::HOLD,
            Order\Entity::ID                => $rzpOrderId
        ];

        (new OrderMeta\Service())->updateReviewStatusFor1ccOrder($param,$input[OneClickCheckout\Constants::MERCHANT_ID]);

    }

    public function removeTag($input){

        $rzpOrderId = $input[OneClickCheckout\Constants::ID];

        $order = (new Order\Service())->fetchById($rzpOrderId);

        $shopifyOrderId = $order[Order\Entity::NOTES][OneClickCheckout\Shopify\Constants::SHOPIFY_ORDER_ID];

        $tags = [\RZP\Models\Merchant\OneClickCheckout\Shopify\Constants::TAG_HOLD];

        $start = millitime();

        (new Core())->removeTagToOrder($shopifyOrderId, $input[OneClickCheckout\Constants::MERCHANT_ID],$tags);

        $this->trace->info(
            TraceCode::SHOPIFY_REMOVE_TAG,
            [
                'id'        =>$rzpOrderId,
                'time'      => millitime() - $start
            ]);

        $param = [
            Order1cc\Fields::REVIEW_STATUS  => Order1cc\Constants::APPROVED,
            Order\Entity::ID                => $rzpOrderId
        ];

        (new OrderMeta\Service())->updateReviewStatusFor1ccOrder($param,$input[OneClickCheckout\Constants::MERCHANT_ID]);
    }

    public function validateGiftCard(array $input, string $merchantId = ''):array
    {
        return (new GiftCards)->validateGiftCard($input, $merchantId);
    }

    public function checkForGiftCardPayment($order, $payment, $merchant, $fromShopifyApi)
    {
        if($fromShopifyApi === true)
        {
            $orderMeta = array_first($order->orderMetas ?? [], function ($orderMeta)
            {
                return $orderMeta->getType() === Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
            });

            $value = $orderMeta->getValue();

            $promotions = $orderMeta->getValue()['promotions'] ?? [];

            $promotionsGC = [];
            $promotionsAll = [];
            $promotionsRefund = [];

            if(!empty($promotions))
            {
                foreach ($promotions as $promotion)
                {
                    if(isset($promotion['type']) && $promotion['type'] === 'gift_card')
                    {
                        $response = (new GiftCards)->applyGiftCard($promotion, $order, $payment, $this->merchant->getId());

                         array_push($promotionsGC, $response);

                         array_push($promotionsAll, $response);
                    }
                    else
                    {
                        array_push($promotionsAll, $promotion);
                    }
                }

                if(!empty($promotionsGC))
                {
                    $value['promotions'] = $promotionsAll;

                    $orderMeta->setValue($value);

                    $this->repo->order_meta->saveOrFail($orderMeta);

                    foreach ($promotionsGC as $promotionGC)
                    {
                        if($promotionGC['description'] === 'invalid'){

                            foreach ($promotionsAll as $promotionAll)
                            {
                                $gcResponse = (new GiftCards)->refundGiftCard($promotionAll, $order, $payment, $this->merchant->getId());

                                array_push($promotionsRefund, $gcResponse);
                            }

                            $value['promotions'] = $promotionsRefund;

                            $orderMeta->setValue($value);

                            $this->repo->order_meta->saveOrFail($orderMeta);

                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_ERROR,
                                null,
                                null,
                                'APPLY_GIFTCARD_FAILED'
                            );
                        }
                    }
                }
            }
        }
    }
    // getOrderAnalytics checks if the Shopify order is stored in cache and returns it. This is used by the frontend
    // for pushing events to Google Analytics.
    public function getOrderAnalytics(array $input): array
    {
        if (empty($input['order_status_url']))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "order_status_url must be sent.");
        }
        return (new Analytics())->getShopifyOrderFromCache($input['order_status_url']);
    }
}
