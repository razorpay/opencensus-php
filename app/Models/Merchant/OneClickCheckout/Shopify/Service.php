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
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;

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
    ];

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = App::getFacadeRoot()['api.mutex'];
    }

    /**
     * starts the 1cc flow for shopify
     * amount from checkout and order should be the same
     * it may misbehave when auto coupon apply and free items work
     * explore building the order with the line_items from $checkout
     */
    public function shopifyCreateCheckout(array $input): array
    {
        $start = millitime();

        (new Core)->verifyHmacSignature($input);

        $checkout = (new Core)->placeShopifyCheckout($input);

        $amount = (int)(floatval($checkout['totalPriceV2']['amount']) * 100);

        $rzporder = (new Order\Service)->createOrder([
            'receipt'          => (new OneClickCheckout\Constants)::SHOPIFY_TEMP_RECEIPT,
            'amount'           => $amount,
            'currency'         => 'INR',
            'payment_capture'  => 1,
            'line_items_total' => $amount,
            'notes'            => (new Checkout)->getNotesForCheckout($checkout),
        ])->toArrayPublic();

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CREATE_RZP_ORDER_RES,
            ['order_id' => $rzporder['id'], 'time' => millitime() - $start]
        );

        $checkoutParams = [
            'order_id'           => $rzporder['id'],
            'currency'           => 'INR',
            'name'               => $this->merchant->getBillingLabel(),
            'checkout_id'        => $checkout['id'],
            'shop_id'            => $input['shop'],
            'one_click_checkout' => true,
            'customer_cart'      => (new Pixels)->getDataForFbPixels($checkout),
        ];

        (new Core)->addMagicCheckoutUrlToShopifyCheckout($checkoutParams);

        unset($checkoutParams['checkout_id']);

        unset($checkoutParams['shop_id']);

        return $checkoutParams;
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
            (new Validator)->setStrictFalse()->validateInput(Validator::UPDATE_CHECKOUT, $input);

            (new Core)->verifyHmacSignature($input, false);

            (new Core)->updateCheckout($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_ERROR,
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
        // if it from public API, verify the signature
        if ($fromShopifyApi === true)
        {
            (new Core)->verifyHmacSignature($input);
        }
        else
        {
            // set the merchant as this is called through SQS
            $this->app['basicauth']->setMode($input['mode']);

            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $this->app['basicauth']->setMerchant($this->merchant);
        }

        $orderId = $input['razorpay_order_id'];

        $paymentId = $input['razorpay_payment_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        (new Core)->isPaymentAndOrderValid($order, $payment);

        $shopifyOrder = $this->placeShopifyOrder($order, $payment, $fromShopifyApi);

        $this->updateRzpOrder($order, $shopifyOrder['order']['id']);

        return [
            'order_status_url' => $shopifyOrder['order']['order_status_url'],
        ];
    }

    // places final order and gateway transaction to Shopify
    public function placeShopifyOrder($order, $payment, $fromShopifyApi): array
    {
        $start = millitime();

        (new Core)->canShopifyOrderBePlaced($order, $fromShopifyApi);

        $shopifyOrder = (new Core)->placeShopifyOrder($order->toArrayPublic(), $payment->toArrayPublic());

        (new Core)->saveShopifyOrderAsPlaced($order->getId());

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_COMPLETE_ORDER_REQUEST,
            [
                'order_id'   => $order->getId(),
                'payment_id' => $payment->getId(),
                'time'       => millitime() - $start,
                'from_shopify_api' => $fromShopifyApi,
            ]
        );

        return $shopifyOrder;
    }

    // TODO: consider 1cc order meta
    protected function updateRzpOrder($order, string $id)
    {
        $order->setReceipt($id);

        $this->repo->saveOrFail($order);
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
                 TraceCode::SHOPIFY_1CC_API_ERROR,
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

    public function applyShopifyCoupon(array $input):array
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

        return (new Coupons)->applyCoupon($input, $checkoutId);
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

        if (empty($response['errors']) === false)
        {
          // address has no PII so we can log it
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_API_ERROR,
              [
                  'type'       => 'update_address_failed',
                  'response'   => $response,
                  'checkoutId' => $checkoutId,
                  'address'    => $address
              ]
          );
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
}
