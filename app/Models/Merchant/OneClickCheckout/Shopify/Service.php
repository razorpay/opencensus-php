<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
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

    const farziEnabledMids = [
        'ChdCdGm7TvuVk6',   //boAt
    ];

    protected $mutex;

    protected $monitoring;

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

        foreach ($lineItems as $item)
        {
            $item=$item['node'];
            $cartLineItems[] = [
                'variant_id'        => mb_substr(strval($item['variant']['id']), 0, 128, "UTF-8"),
                'tax_amount'        => 0,
                'sku'               => mb_substr(strval($item['variant']['sku']), 0, 128, "UTF-8"),
                'price'             => (int)(floatval($item['variant']['price']) * 100),
                'quantity'          => (int)floatval($item['quantity']),
                'name'              => mb_substr(strval($item['title']), 0, 128, "UTF-8"),
                'description'       => mb_substr($item['variant']['product']['description'], 0, 256, "UTF-8"),
                'weight'            => (int)floatval($item['variant']['weight']),
                'image_url'         => $item['variant']['image']['url'] ?? ""
            ];
        }

        return $cartLineItems;
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
    public function shopifyCreateCheckout(array $input): array
    {
        $start = millitime();
        $cart = $input['cart'];
        $cartId = $cart['token'];

        $checkout = (new Core)->placeShopifyCheckout(['cart' => $cart]);

        $amount = (int)(floatval($checkout['totalPriceV2']['amount']) * 100);

        $order = (new Order\Service)->createOrder([
            'receipt'          => (new OneClickCheckout\Constants)::SHOPIFY_TEMP_RECEIPT,
            'amount'           => $amount,
            'currency'         => 'INR',
            'payment_capture'  => 1,
            'line_items_total' => $amount,
            'notes'            => (new Checkout)->getNotesForCheckout($checkout, $cartId),
            'line_items'       => $this->shopifyCartLineItems($checkout),
        ]);

        $checkoutParams = [
            'order_id'           => $order->getPublicId(),
            'currency'           => 'INR',
            'name'               => $this->merchant->getBillingLabel(),
            'one_click_checkout' => true,
            'customer_cart'      => (new Pixels)->getDataForFbPixels($checkout),
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CREATE_RZP_ORDER_RES,
            ['order_id' => $order->getPublicId(), 'time' => millitime() - $start]);

        // form url encoded sends bool as string!
        if (isset($input['send_preferences']) === true and $input['send_preferences'] == 'true')
        {
            $params = $this->getParamsForPreferences($input, $order);
            $preferences = (new MerchantService)->getCheckoutPreferences($params);
            $checkoutParams = array_merge($checkoutParams, ['preferences' => $preferences]);
        }

        return $checkoutParams;
    }

    /**
     * Ensures preferences function receives same parametres as in normal API call
     * @param array input - Post body and URL params received
     * @param Order\Entity order - Razorpay order
     */
    protected function getParamsForPreferences(array $input, Order\Entity $order): array
    {
        unset($input['cart']);
        unset($input['key']);
        return array_merge(
            $input,
            [
                'order_id' => $order->getPublicId(),
            ]);
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

    public function shopifyGetCheckoutOptions(array $input): array
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_RETARGETING_URL_HIT,
            ['input' => $input]);

        (new Core)->validateCheckoutOptionsRequest($input);

        $order = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);

        $checkoutId = $order->getNotes()['storefront_id'];

        $checkout = (new Checkout)->getCheckoutFromAdminApi($checkoutId);

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
        ];

        return $checkoutParams;
    }

    public function updateCheckout(array $input): array
    {
        try
        {
            $response =  (new Checkout)->updateCheckoutFromAdmin($input);
            return $response;
        }
        catch (\Throwable $e)
        {
            $this->monitoring->addTraceCount(Metric::ABANDON_CHECKOUT_ERROR_COUNT,['error_type'=>'update_checkout_failed']);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                [
                    'type'  => 'update_checkout_failed',
                    'input' => $input,
                    'error' => $e->getMessage()
                ]);
        }

        return [];
    }

    public function completeCheckoutWithLock(array $input, bool $fromShopifyApi = true): array
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_MUTEX_INITIATED,
            [
                'type'           => 'mutex_initiated',
                'input'          => $input,
                'fromShopifyApi' => $fromShopifyApi,
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

        $orderId = $input['razorpay_order_id'];

        $paymentId = $input['razorpay_payment_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

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

        $shopifyOrder = $this->placeShopifyOrder($order, $payment, $fromShopifyApi);

        $this->updateRzpOrder($order, $shopifyOrder);

        $orderArray = $order->toArrayPublic();

        $countryCode = $orderArray['customer_details']['shipping_address']['country'];

        // NOTE: promotions is not set if the 1ccResetAPI call fails, until CX team fixes it
        // keep the null check here
        $response = [
            'total_amount'     => $orderArray['amount'],
            'promotions'       => $orderArray['promotions'] ?? [],
            'shipping_fee'     => $orderArray['shipping_fee'],
            'order_id'         => $shopifyOrder['order']['name'],
            'total_tax'        => $shopifyOrder['order']['total_tax'],
            'payment_method'   => $payment['method'],
            'payment_currency' => $payment['currency'],
            'payment_id'       => $paymentId,
            'shipping_country' => Constants\Country::getCountryNameByCode($countryCode) ?? $countryCode
        ];

        // NOTE: Logging the response to debug an issue where the FE is not receiving data
        // for analytics
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_COMPLETE_ORDER_RESPONSE,
            [
                'type'     => 'order_complete_response',
                'order_id' => $orderId,
                'response' => $response,
            ]);

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

        (new Order\Service)->update($rzpOrderId, array('notes'=> $notes));

        $shopifyOrderName = strval($shopifyOrder['order']['name']);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_RECEIPT_UPDATE,
            [
                'step'     => 'update_receipt',
                'order_id' => $rzpOrder->getId(),
                'receipt'  => $shopifyOrderName,
                'external' => $rzpOrder->isExternal()
        ]);

        (new Order\Core)->updateReceipt($rzpOrder, $shopifyOrderName);
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
                     'checkoutId' => $checkoutId,
                     'checkout'   => $checkout,
                 ]);

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
                    ['checkoutId' => $checkoutId, 'reason' => $e.getMessage()]);
            }
        }

        if (isset($merchantId) === true and in_array($merchantId, self::skipListCouponMids) === true)
        {
            return ['promotions' => []];
        }


        $input['amount'] = $checkoutNode['subtotalPrice'];

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

    public function applyShopifyCoupon(array $input, string $merchantId = ''):array
    {
        // existing coupon will be removed by checkout when new coupon is applied
        $checkoutId = $input['order_id'];

        // remove existing coupon
        $response = (new Core)->removeCoupon($checkoutId);

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
                    ['checkoutId' => $checkoutId, 'reason' => $e.getMessage()]);
            }
        }

        $cartId = $input['cart_id'];

        $code = $input['code'];

        //adding the coupon to Shopify if generated by Farzi

        if (isset($merchantId) === true and in_array($merchantId, self::farziEnabledMids) === true)
        {
           (new Farzi)->addFarziCoupon($code, $cartId);
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
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_API_SHIPPING_ERROR,
              [
                  'type'       => 'update_address_failed',
                  'response'   => $response,
                  'checkoutId' => $checkoutId,
                  'address'    => $address
              ]
          );

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
            'id'			   => $address['id'],
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

        return new Client($creds);
    }

    protected function getShopifyAuthByMerchant()
    {
        $config = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());

        return $config;
    }

    public function cancelShopifyOrder($input){

        $rzpOrderId = $input[OneClickCheckout\Constants::ID];

        $order = (new Order\Service())->fetchById($rzpOrderId);

        $shopifyOrderId = $order[Order\Entity::NOTES][\RZP\Models\Merchant\OneClickCheckout\Shopify\Constants::SHOPIFY_ORDER_ID];

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

        $shopifyOrderId = $order[Order\Entity::NOTES][Constants::SHOPIFY_ORDER_ID];

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

        $shopifyOrderId = $order[Order\Entity::NOTES][\RZP\Models\Merchant\OneClickCheckout\Shopify\Constants::SHOPIFY_ORDER_ID];

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
}
