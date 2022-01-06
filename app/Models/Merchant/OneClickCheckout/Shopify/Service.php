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

class Service extends Base\Service
{

    const skipListCouponMids = [
        'DzyQ9A6YiAcZpT',
    ];

    /**
     * starts the 1cc flow for shopify
     * amount from checkout and order should be the same
     * it may misbehave when auto coupon apply and free items work
     * explore building the order with the line_items from $checkout
     */
    public function shopifyCreateCheckout(array $input): array
    {
        $signature = (new Core)->verifyHmacSignature($input);

        if ($signature === false)
        {
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_HMAC_VALIDATION_FAILED,
              ['input' => $input]
          );
          throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $checkout = (new Core)->placeShopifyCheckout($input);

        $amount = floatval($checkout['totalPriceV2']['amount']) * 100;

        $rzporder = (new Order\Service)->createOrder([
            'receipt'          => strval(time()),
            'amount'           => $amount,
            'currency'         => 'INR',
            'payment_capture'  => 1,
            'line_items_total' => $amount,
            'notes'            => array(
                'storefront_id'  => $checkout['id'],
            ),
        ])->toArrayPublic();

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CREATE_RZP_ORDER_RES,
            ['rzporder' => $rzporder]
        );

        return [
            'order_id' => $rzporder['id'],
            'currency' => 'INR',
            'name' => $this->merchant->getBillingLabel(),
            'description' => '',
            'prefill' => [
                'name'  => '',
                'email' => '',
                'contact' => '',
            ],
            'one_click_checkout' => true,
        ];
    }

    // updates shopify order post payment and redirects the user
    public function shopifyCompleteCheckout(array $input): array
    {
        $signature = (new Core)->verifyHmacSignature($input);

        if ($signature === false)
        {
          $this->trace->info(
              TraceCode::SHOPIFY_1CC_HMAC_VALIDATION_FAILED,
              ['input' => $input]
          );
          throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $orderId = $input['razorpay_order_id'];

        $paymentId = $input['razorpay_payment_id'];

        $rzpSignature = $input['razorpay_signature'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $this->trace->info(
          TraceCode::SHOPIFY_1CC_COMPLETE_ORDER_REQUEST,
          ['order' => $order->toArrayPublic(), 'payment' => $payment->toArrayPublic()]
        );

        $shopifyOrder = (new Core)->completeShopifyOrder($order->toArrayPublic(), $payment->toArrayPublic());

        $this->trace->info(
          TraceCode::SHOPIFY_1CC_PLACE_ORDER_RES,
          ['shopifyOrder' => $shopifyOrder]
        );

        $order->setReceipt($shopifyOrder['order']['id']);

        $this->repo->saveOrFail($order);

        $orderArr = $order->toArrayPublic();

        return [
            'order_status_url' => $shopifyOrder['order']['order_status_url'],
        ];
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

        foreach ($checkoutNode['lineItems']['edges'] as $item) {
            $item = $item['node'];
            $orderQuantity += $item['quantity'];
        }

        // get all discount codes
        $response = (new Core)->getCoupons($input);
        $discounts = json_decode(json_encode(json_decode($response)), true);
        $discountData = array();

        foreach($discounts['data']['priceRules']['edges'] as $value){
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

            if (!empty($count) && !empty($limit)) {
                $remaining = $limit - $count;
                if ($remaining <=  0) {
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
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_BODY,
                    ['input' => $input, 'emailRes' => $emailRes]
                );
            }
            catch (\Exception $e)
            {
              $this->trace->info(
                  TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_FAILED,
                  ['input' => $input, 'reason' => $e.getMessage()]
              );
            }
        }

        $response = (new Core)->applyCoupon($input, $checkoutId);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_APPLY_COUPON,
            ['response' => json_decode($response, true)]
        );

        $response = json_decode($response, true);

        // TODO: discuss this error handling
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
            $value = ($checkout['lineItemsSubtotalPrice']['amount'] - $checkout['subtotalPrice']) * 100;
            return [
                'response' => [
                    'promotion' => [
                        'code' => $discountData['code'],
                        'reference_id' => $discountData['code'],
                        'value' => $value,
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

        $response = array(
            'id'			       => $address['id'],
            'zipcode'        => $address['zipcode'],
            'state_code'     => $address['state_code'],
            'country'        => $address['country'],
        );
        return array_merge($response, $rates);
    }
}
