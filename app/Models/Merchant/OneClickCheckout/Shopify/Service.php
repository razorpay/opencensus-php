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

    const MAX_RETRY_COUNT = 3;

    const MAX_RETRY_DELAY_MILLIS = 2 * 60 * 1000;

    const MIN_RETRY_DELAY_MILLIS = 2 * 60 * 1000;

    const skipListCouponMids = [
        'DzyQ9A6YiAcZpT',
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
        (new Core)->verifyHmacSignature($input);

        $checkout = (new Core)->placeShopifyCheckout($input);

        $amount = (int)(floatval($checkout['totalPriceV2']['amount']) * 100);

        $rzporder = (new Order\Service)->createOrder([
            'receipt'          => 'TEMP_' . strval(time()),
            'amount'           => $amount,
            'currency'         => 'INR',
            'payment_capture'  => 1,
            'line_items_total' => $amount,
            'notes'            => [
                'storefront_id'  => $checkout['id'],
            ],
        ])->toArrayPublic();

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CREATE_RZP_ORDER_RES,
            ['order_id' => $rzporder['id']]
        );

        return [
            'order_id'    => $rzporder['id'],
            'currency'    => 'INR',
            'name'        => $this->merchant->getBillingLabel(),
            'description' => '',
            'prefill'     => [
                'name'    => '',
                'email'   => '',
                'contact' => '',
            ],
            'one_click_checkout' => true,
        ];
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
        if ($fromShopifyApi === true)
        {
            (new Core)->verifyHmacSignature($input);
        }

        $orderId = $input['razorpay_order_id'];

        $paymentId = $input['razorpay_payment_id'];

        // $rzpSignature = $input['razorpay_signature'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $orderData = $order->toArrayPublic();

        $paymentData = $payment->toArrayPublic();

        if ($paymentData['status'] === 'failed' || $paymentData['status'] === 'refunded')
        {
            // TODO: fail the order here
        }

        if ($paymentData['order_id'] !== $orderData['id'])
        {
            // code...
        }

        $shopifyOrder = $this->placeShopifyOrder($order, $payment);

        $this->updateRzpOrder($order, $shopifyOrder['order']['id']);

        return [
            'order_status_url' => $shopifyOrder['order']['order_status_url'],
        ];
    }

    // places final order and gateway transaction to Shopify
    public function placeShopifyOrder($order, $payment): array
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_COMPLETE_ORDER_REQUEST,
            ['order_id' => $order->getId(), 'payment_id' => $payment->getId()]
        );

        // TODO: or we do getPublicId()
        (new Core)->canShopifyOrderBePlaced($order->getId());

        $shopifyOrder = (new Core)->placeShopifyOrder($order->toArrayPublic(), $payment->toArrayPublic());

        (new Core)->saveShopifyOrderAsPlaced($order->getId());

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
        if (isset($merchantId) === true and in_array($merchantId, self::skipListCouponMids) === true)
        {
            return ['promotions' => []];
        }

        $checkoutId = $input['order_id'];

        $checkout = (new Core)->getOrderDetailsFromCheckout($checkoutId);

        $checkout = json_decode($checkout, true);

        // TODO: discuss proper error for this
        if (empty($checkout['data']['node']) === true)
        {
          return (new Errors)->getInvalidCouponApplicationResponse();
        }

        $checkoutNode = $checkout['data']['node'];

        $amount = $checkoutNode['subtotalPrice'];

        $countryCode = $checkoutNode['currencyCode'];

        $orderQuantity = 0;

        foreach ($checkoutNode['lineItems']['edges'] as $item)
        {
            $item = $item['node'];
            $orderQuantity += $item['quantity'];
        }

        // get all discount codes
        $response = (new Core)->getCoupons($input);
        $discounts = json_decode(json_encode(json_decode($response)), true);
        $discountData = array();

        foreach($discounts['data']['priceRules']['edges'] as $value)
        {
            $value = $value['node'];
            $discountMinAmount = floatval($value['prerequisiteSubtotalRange']['greaterThanOrEqualTo']);
            $minQuantityRange = floatval($value['prerequisiteQuantityRange']['greaterThanOrEqualTo']);
            $discountStartDate = $value['startsAt'];
            $dicountEndDate = $value['endsAt'];

            if (($value['customerSelection']['forAllCustomers'] !== null && $value['customerSelection']['forAllCustomers'] === false)
            || ($value['itemEntitlements']['targetAllLineItems'] !== null && $value['itemEntitlements']['targetAllLineItems'] === false)){
                continue;
            }

            // skip free shipping in v1
            if ($value['target'] == 'SHIPPING_LINE')
            {
                continue;
                // if(($value['prerequisiteShippingPriceRange'] !== null && $value['prerequisiteShippingPriceRange']['lessThanOrEqualTo'] > $amount)
                // || (empty($value['shippingEntitlements']['countryCodes']) === false && in_array($countryCode,$value['shippingEntitlements']['countryCodes']) === false)
                // ){
                //     continue;
                // }
            }

            // validation for min amount and expire date
            $dateTimeNow = date('Y-m-d H:i:s');
            if (($amount < $discountMinAmount)
                || ($minQuantityRange !== null && $minQuantityRange > $orderQuantity )
                || ($discountStartDate !== null && $discountStartDate > time())
                || ($dicountEndDate !== null && $dicountEndDate < $dateTimeNow)
                ) {
                continue;
            }

            $count = $value['usageCount'];

            $limit = $value['usageLimit'];

            if (!empty($count) && !empty($limit))
            {
                $remaining = $limit - $count;
                if ($remaining <=  0)
                {
                    continue;
                }
            }

            foreach($value['discountCodes']['edges'] as $node)
            {
                $discountCodeNode = $node['node'];
                if (empty($discountCodeNode) === false)
                {
                    array_push(
                      $discountData,
                      [
                          'code' => $discountCodeNode['code'],
                          'summary' => $value['summary'],
                          'tnc'=> []
                      ]
                    );
                }
            }
        }
        return ['promotions' => $discountData];
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
                $emailRes = (new Core)->updateCheckoutEmail($checkoutId, $input['email']);
                $emailRes = json_decode($emailRes, true);
            }
            catch (\Exception $e)
            {
              $this->trace->info(
                  TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_FAILED,
                  ['reason' => $e.getMessage()]
              );
            }
        }

        $response = (new Core)->applyCoupon($input, $checkoutId);

        $response = json_decode($response, true);

        if (empty($response['errors']) === false)
        {
            return (new Errors)->getInvalidCouponApplicationResponse();
        }

        $data = $response['data']['checkoutDiscountCodeApplyV2'];

        if (empty($data['checkoutUserErrors']) === false)
        {
            return (new Errors)->getInvalidCouponApplicationResponse();
        }

        $checkout = $data['checkout'];

        $discountData = $checkout['discountApplications']['edges'][0]['node'];

        if ($discountData['applicable'] === true)
        {
            $value = (new Utils)->formatNumber($checkout['lineItemsSubtotalPrice']['amount'] - $checkout['subtotalPrice']) * 100;
            return [
                'response' => [
                    'promotion' => [
                        'code'          => $discountData['code'],
                        'reference_id'  => $discountData['code'],
                        'value'         => (int)$value,
                    ],
                ],
                'status_code' => 200,
            ];
        }
        else
        {
            return (new Errors)->getInvalidCouponApplicationResponse();
        }
    }

    // update checkout email
    public function updateCheckoutEmail(string $checkoutId, string $email)
    {
        $response = (new Core)->updateCheckoutEmail($checkoutId, $email);
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
        $addresses = $input['addresses'];

        foreach ($addresses as $address)
        {
            $finalVal[] = $this->getShippingForOneAddress($checkoutId, $address);
        }
        return ['addresses' => $finalVal];
    }

    // get serviceability and fee for single address
    public function getShippingForOneAddress(string $checkoutId, array $address): array
    {
        $response = (new Core)->updateShippingAddress($checkoutId, $address);

        $response = json_decode($response, true);

        if (empty($response['errors']) === false)
        {
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
