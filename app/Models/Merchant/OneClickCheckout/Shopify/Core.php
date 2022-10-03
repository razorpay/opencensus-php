<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Payment\Method as PaymentMethod;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Models\Order\OrderMeta\Type as OrderMetaType;
use RZP\Models\Merchant\Metric;

class Core extends Base\Core
{

    const POLLING_INTERVAL_MILLIS = 100; // 400ms counting API call

    const MAX_TIME_DELAY_SEC = 10; // 10sec

    const CACHE_VALIDITY_TTL = 5 * 1440; // 5 days

    const SHA_256 = 'sha256';

    const ORDER_CACHE_KEY = 'shopify_1cc_order';

    const ORDER_CACHE_KEY_TTL = 1 * 1440; // 1 day

    const MUTEX_KEY = 'shopify_1cc_place_order_mutex';

    protected $monitoring;

    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();
    }

    public function placeShopifyCheckout(array $input): array
    {
        $start = millitime();

        $cart = $input['cart'];

        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getCreateCheckoutMutation();

        $lineItems = (new Utils)->getLineItemsFromCart($cart);

        $graphqlLineItems = (new Utils)->convertToGraphqlId($lineItems);

        $body = ['query' => $mutation, 'variables' => ['input' => $graphqlLineItems]];

        $this->monitoring->addTraceCount(Metric::CREATE_SHOPIFY_CHECKOUT_REQUEST_COUNT, []);

        $requestStart = millitime();

        $response = null;

        try {
            $response = json_decode($client->sendStorefrontRequest(json_encode($body)), true);
        }
        catch(\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::CREATE_SHOPIFY_CHECKOUT_ERROR_COUNT,['error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR]);

            throw new Exception\ServerErrorException(
                'Error while calling Shopify URL',
                ErrorCode::SERVER_ERROR
            );
        }

        $this->monitoring->traceResponseTime(Metric::CREATE_SHOPIFY_CHECKOUT_CALL_TIME, $requestStart, []);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_CREATE_CHECKOUT_RES,
            [
                'type' => 'place_shopify_checkout',
                'body' => $body,
                'response' => $response,
                'time' => millitime() - $start
            ]
        );

        if (empty($response['errors']) === false)
        {
            $this->trace->info(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'     => 'error_creating_checkout',
                     'response' => $response,
                 ]
            );

            $this->monitoring->addTraceCount(Metric::CREATE_API_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR]);

            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR
            );
        }

        $checkoutCreate = $response['data']['checkoutCreate'];

        if (empty($checkoutCreate['checkoutUserErrors']) === false)
        {
            $checkoutError = $checkoutCreate['checkoutUserErrors'][0];

            if ($checkoutError['message'] === "Variant is invalid")
            {
                $this->trace->error(TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                    [
                        'type' => 'shopify_invalid_variant_error',
                        'response' => $checkoutError
                    ]);
                $this->monitoring->addTraceCount(Metric::CREATE_API_CHECKOUT_ERROR_COUNT,['error_type' => TraceCode::SHOPIFY_INVALID_VARIANT_ERROR]);
            }
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'     => 'error_creating_checkout',
                     'response' => $response,
                 ]
            );

            $this->monitoring->addTraceCount(Metric::CREATE_API_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR]);

            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR
            );
        }

        return $checkoutCreate['checkout'];
    }

    public function setMetaFieldValue(string $key, string $value)
    {
        $client = $this->getShopifyClientByMerchant();
        $body[OneClickCheckout\Constants::METAFIELD] = [
          OneClickCheckout\Constants::NAMESPACE => OneClickCheckout\Constants::MAGIC_CHECKOUT,
          OneClickCheckout\Constants::KEY => $key,
          OneClickCheckout\Constants::VALUE => $value,
          OneClickCheckout\Constants::TYPE => OneClickCheckout\Constants::BOOLEAN
        ];
        $method = OneClickCheckout\Constants::POST;
        $resource = OneClickCheckout\Constants::METAFIELD_ENDPOINT;
        $res = array();
        try {
            $res = $client->sendRestApiRequest(json_encode($body), $method, $resource);
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_UPDATE_METAFIELD_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_METAFIELD_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                    'merchant_id'=>$this->merchant->getId(),
                    'error' => $e->getMessage()
                ]
            );
            return [];
        }

        $res = json_decode($res,true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_UPDATE_METAFIELD_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_METAFIELD_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                    'merchant_id'=>$this->merchant->getId(),
                    'error' => 'Invalid json response'
                ]
            );
            throw new Exception\RuntimeException('Invalid json response');
        }

        if($key ==  OneClickCheckout\Constants::ONE_CLICK_CHECKOUT_ENABLED)
        {
            $action = $value == 'true' ? 'activate' : 'deactivate';

            $dimensions = [
                'action' => $action
            ];

            $this->monitoring->addTraceCount(Metric::SHOPIFY_UPDATE_METAFIELD_SUCCESS_COUNT, $dimensions);
        }

        return $res;
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
        $this->monitoring->addTraceCount(Metric::GET_AVAILABLE_SHIPPING_RATES_REQUEST_COUNT, []);

        $start = millitime();

        $res = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        $this->monitoring->traceResponseTime(Metric::GET_AVAILABLE_SHIPPING_RATES_CALL_TIME, $start, []);

        return $res;
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

        $this->monitoring->addTraceCount(Metric::SHOPIFY_APPLY_COUPONS_REQUEST_COUNT, []);

        $start = millitime();

        $res = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_APPLY_COUPONS_CALL_TIME, $start, []);

        return $res;
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

        $this->monitoring->addTraceCount(Metric::SHOPIFY_REMOVE_COUPONS_REQUEST_COUNT, []);

        $start = millitime();

        $res = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_REMOVE_COUPONS_CALL_TIME, $start, []);

        return $res;
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
            [
                'type' => 'update_checkout_email',
                'checkoutId' => $checkoutId,
                'time' => millitime() - $start
            ]
        );
        //TODO: Metrics creation for this Shopify API
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

        $stateCode = (new StateMap)->getShopifyStateCode($address['state_code']);

        $stateCodeFromName = (new StateMap)->getShopifyStateCodeFromName($address['state']);

        // name and address1 are compulsory fields but we don't collect it from
        // user at this time so we put default value
        // province field can take state code or full state name depending on what is passed
        $shippingAddress = [
            'firstName' => $address['first_name'] ?? 'name',
            'lastName'  => $address['last_name']  ?? 'not entered',
            'address1'  => $address['line1']      ?? 'address not entered',
            'address2'  => $address['line2']      ?? '',
            'country'   => $address['country'],
            'province'  => $stateCode ?? $stateCodeFromName,
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
            [
                'type' => 'update_shipping_address',
                'checkoutId' => $checkoutId,
                'shippingAddress' => $shippingAddress
            ]
        );

        $this->monitoring->addTraceCount(Metric::UPDATE_SHIPPING_ADDRESS_REQUEST_COUNT, []);

        $start = millitime();

        $res = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        $this->monitoring->traceResponseTime(Metric::UPDATE_SHIPPING_ADDRESS_CALL_TIME, $start, []);

        return $res;
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

            if (
              empty($body['errors']) === false
              or $body['data'] === null
              or empty($body['checkoutUserErrors']) === false)
            {
                $this->trace->info(
                     TraceCode::SHOPIFY_1CC_API_SHIPPING_ERROR,
                     [
                         'type'       => 'invalid_response_fetching_rates',
                         'response'   => $body,
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

            $checkout = $body['data']['node'];

            $availableShippingRates = $checkout['availableShippingRates'];
            $shippingRates = $availableShippingRates['shippingRates'];
            $isShippingReady = $availableShippingRates['ready'];

            if ($isShippingReady === true and empty($shippingRates) === false)
            {
                $rates = (new Core)->parseShippingRates($shippingRates);

                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_API_SHIPPING_RESPONSE,
                    [
                        'type' => 'shipping_api_response',
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
             TraceCode::SHOPIFY_1CC_API_SHIPPING_ERROR,
             [
                 'type'       => 'retry_limit_exceeded_fetching_rates',
                 'checkoutId' => $checkoutId,
                 'retries'    => $currentTries,
                 'time'       => millitime() - $start,
             ]
        );

        $this->monitoring->addTraceCount(Metric::RETRY_LIMIT_EXCEEDED_RATES_ERROR_COUNT,['error_type'=>'retry_limit_exceeded_fetching_rates'] );

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
    }

    public function exceptionPlaceShopifyOrderAPI($e, array $rzpOrder, array $rzpPayment, array $body): array
    {
        $start = millitime();

        $orderId = $rzpOrder['id'];

        $client = $this->getShopifyClientByMerchant();

        $message = strtolower($e->getMessage());

        $errorInventory = "unable to reserve inventory";

        $errorPhone = "phone has already been taken";

        $errorCustomer = "has already been taken";

        $errorBadGateway = "502 bad gateway";

        $errorService = "503 service unavailable";

        $retry = false;

        if(strpos($message, $errorPhone) !== false || strpos($message, $errorCustomer) !== false)
        {
            $body['customer']['phone'] = null;

            $retry = true;
        }
        else if (strpos($message, $errorBadGateway) !== false || strpos($message, $errorService) !== false)
        {
            $retry = true;
        }

        // Inventory case we are delegating it to SQS job.
        if(strpos($message, $errorInventory) !== false)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_DELEGATED_SQS,
                [
                    'type'          => 'order_place_api_delegated_sqs',
                    'order_id'      => $orderId,
                    'strategy'      => 'inventory not available, delegated to SQS job',
                    'error_message' => $message
                ]
            );

            $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_PLACE_ORDER_DELEGATED_SQS]);

            throw new Exception\BadRequestException(
              ErrorCode::BAD_REQUEST_ERROR,
              null,
              null,
              'INSUFFICIENT_INVENTORY'
            );
        }

        // Retry work: retry only once only for User click journey not for SQS job order creation
        // We are retrying once and if it fails again then we bank on SQS worker flow to try and place the order again
        if ($retry === true)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_RETRY,
                [
                    'type'          => 'order_place_api_retry_initiated',
                    'order_id'      => $orderId,
                    'strategy'      => 'retry',
                    'error_message' => $message
                ]
            );

            try
            {
                $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_REQUEST_COUNT, []);

                $placeOrderStart = millitime();

                $order = $client->sendRestApiRequest(
                    json_encode(['order' => $body]),
                    'POST',
                    '/orders.json'
                );

                $this->monitoring->traceResponseTime(Metric::PLACE_SHOPIFY_ORDER_CALL_TIME, $placeOrderStart, []);

            }
            catch (\Exception $e)
            {
                $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_API_ORDER_RETRY_ERROR]);

                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_API_ORDER_RETRY_ERROR,
                    [
                        'type'     => 'order_place_api_retry_failed',
                        'order_id' => $orderId,
                        'error'    => $e->getMessage()
                    ]
                );

                $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_API_ORDER_RETRY_ERROR]);

                throw new Exception\BadRequestException(
                  ErrorCode::BAD_REQUEST_ERROR,
                  null,
                  null,
                  'RETRY_FAILED'
                );
            }

            $order = json_decode($order, true);

            $this->updateShopifyTransaction($order['order']['id'], $rzpPayment);

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_RETRY_RES,
                [
                    'type'             => 'order_place_api_retry_success',
                    'order_id'         => $orderId,
                    'shopify_order_id' => $order['order']['id'],
                    'time'             => millitime() - $start
                ]
            );

            return $order;
        }

        return [];
    }

    public function exceptionPlaceShopifyOrderSQS($e, array $rzpOrder, array $rzpPayment): array
    {
        $orderId = $rzpOrder['id'];

        $notes = $rzpOrder['notes'];

        $message = strtolower($e->getMessage());

        $errorInventory = "unable to reserve inventory";

        // For SQS, we will refund the money back to the customer when we have an error
        // We need to refund in SQS backend job if not, the customers money would be held with no order with Merchant
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_SQS_PLACE_ORDER_REFUND,
            [
                'type'           => 'sqs_order_place_refund_initiated',
                'order_id'       => $orderId,
                'strategy'       => 'refund',
                'error_message'  => $message,
                'payment_method' => $rzpPayment['method']
            ]
        );

        // Refund if applicable, please double check
        if (strtolower($rzpPayment['method']) !== 'cod')
        {
            $paymentId = $rzpPayment['id'];

            $refundData = [
                'amount' => $rzpPayment['amount'],
            ];

            try
            {
                (new Payment\Service)->refund($paymentId, $refundData);
            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::SHOPIFY_1CC_SQS_PAYMENT_REFUND_FAILURE,
                    [
                        'type'     => 'sqs_order_place_refund_failed',
                        'order_id' => $orderId,
                        'payment_id' => $paymentId,
                        'error'    => $e->getMessage()
                    ]
                );
            }

            //Update Razorpay Order with the error message
            if (strpos($message, $errorInventory) !== false)
            {
                $notes['error'] = "Refund initiated to customer. Order could not be placed, lack of inventory.";
            }
            else
            {
                $notes['error'] = "Refund initiated to customer. Order could not be placed, due to error on shopify.";
            }

            (new Order\Service)->update($orderId, array('notes'=> $notes));

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_SQS_PLACE_ORDER_ERROR,
                [
                    'type'     => 'sqs_order_place_error',
                    'order_id' => $orderId,
                    'error'    => $e->getMessage()
                ]
            );

            $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_SQS_PLACE_ORDER_ERROR]);

            throw new Exception\BadRequestException(
              ErrorCode::BAD_REQUEST_ERROR,
              null,
              null,
              'SQS_ORDER_PLACE_ERROR'
            );
        }

        return [];
    }

    public function placeShopifyOrder(array $rzpOrder, array $rzpPayment, $fromShopifyApi): array
    {
        $start = millitime();

        $finalErrorCode = "";

        $orderId = $rzpOrder['id'];

        $notes = $rzpOrder['notes'];

        $client = $this->getShopifyClientByMerchant();

        $body = $this->getCreateOrderPayload($rzpOrder, $rzpPayment['method']);

        try
        {
            $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_REQUEST_COUNT, []);

            $placeOrderStart = millitime();

            $order = $client->sendRestApiRequest(
                json_encode(['order' => $body]),
                'POST',
                '/orders.json'
            );

            $this->monitoring->traceResponseTime(Metric::PLACE_SHOPIFY_ORDER_CALL_TIME, $placeOrderStart, []);
        }
        catch (\Exception $e)
        {
            $exceptionHandlerResponse = null;

            if ($fromShopifyApi === true)
            {
                $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_ERROR_COUNT, ['error_type' => 'DELEGATED_TO_SQS']);

                $exceptionHandlerResponse = $this->exceptionPlaceShopifyOrderAPI($e, $rzpOrder, $rzpPayment, $body);

                $finalErrorCode = "DELEGATED_TO_SQS";
            }
            else
            {
                $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_ERROR_COUNT, ['error_type' => 'SQS_TOO_FAILED']);

                $exceptionHandlerResponse = $this->exceptionPlaceShopifyOrderSQS($e, $rzpOrder, $rzpPayment);

                $finalErrorCode = "SQS_TOO_FAILED";
            }

            if (!empty($exceptionHandlerResponse))
            {
                return $exceptionHandlerResponse;
            }

            // Ensure we have this trace and exception at the end of this overall catch block
            // Why: We are trying to handle the order errors with different strategies like retry and/or refund.
            //      if we couldnt do any of these resolutions, these are the errors we need to handle in the future
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ORDER_ERROR,
                [
                    'type'     => 'order_place_api_error',
                    'order_id' => $orderId,
                    'errorcode'=> $finalErrorCode,
                    'error'    => $e->getMessage()
                ]
            );

            $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, [ 'error_type' => 'SQS_TOO_FAILED'] );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                $finalErrorCode
              );
        }

        $order = json_decode($order, true);

        $this->updateShopifyTransaction($order['order']['id'], $rzpPayment);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_PLACE_ORDER_RES,
            [
                'type'             => 'order_place_api_response',
                'order_id'         => $orderId,
                'shopify_order_id' => $order['order']['id'],
                'time'             => millitime() - $start
            ]
        );

        return $order;
    }

    protected function getCreateOrderPayload($rzpOrder, string $paymentMethod): array
    {
        $checkoutId = $rzpOrder['notes']['storefront_id'];

        $checkout = (new Checkout)->getCheckoutbyStorefrontId($checkoutId);

        if (empty($checkout['data']['node']) === true)
        {
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'        => 'error_fetching_checkout',
                     'response'    => $checkout,
                     'checkout_id' => $checkoutId,
                     'error'       => 'checkout not found',
                 ]);

            $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, [ 'error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR ]);

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
            'address2'   => $shippingAddress['line2'] ?? '',
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
            'address2'   => $billingAddress['line2'] ?? '',
            'phone'      => $billingAddress['contact'],
            'city'       => $billingAddress['city'],
            'province'   => $billingAddress['state'],
            'country'    => $billingAddress['country'],
            'zip'        => $billingAddress['zipcode']
        ];

        $body['email'] = $customerDetails['email'];

        $body['phone'] = $customerDetails['contact'];

        // NOTE: updating the contact causes conflicts in creating customer accounts
        // as shopify allows only single email per phone number
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

    protected function updateShopifyTransaction(string $merchantOrderId, array $payment): array
    {
        if ($payment['amount'] <= 100)
        {
            $this->trace->info(
              TraceCode::SHOPIFY_1CC_UPDATE_TRANSACTION_BODY,
                [
                    'type'    => 'update_transaction_skipped',
                    'payment' => $payment,
                ]
            );
            return [];
        }

        $start = millitime();

        $body = $this->getTransactionBody($merchantOrderId, $payment);

        try
        {
          $client = $this->getShopifyClientByMerchant();

          $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_REQUEST_COUNT, []);

          $updateRequestStart = millitime();

          $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_TRANSACTION_BODY,
            [
              'type' => 'update_transaction_initiated',
              'body' => $body,
            ]
          );

          $order = $client->sendRestApiRequest(
              json_encode($body),
              'POST',
              '/orders/' . strval($merchantOrderId) . '/transactions.json'
          );

          $this->monitoring->traceResponseTime(Metric::UPDATE_SHOPIFY_TRANSACTION_CALL_TIME, $updateRequestStart, []);


          return json_decode($order, true);
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_ERROR_COUNT, [ 'error_type' => TraceCode::SHOPIFY_1CC_API_TRANSACTION_ERROR] );

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_TRANSACTION_ERROR,
                [
                    'type' => 'update_transaction_failed',
                    'error' => $e->getMessage(),
                    'time' => millitime() - $start
                ]
            );

            $message = strtolower($e->getMessage());

            $errorBadGateway = "502 bad gateway";


            if(strpos($message, $errorBadGateway) !== false)
            {
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_API_ERROR,
                    [
                        'type' => 'update_transaction_retry_initiated',
                        'strategy' => 'retry',
                        'error_message' => $message
                    ]
                );

                try
                {
                    $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_REQUEST_COUNT, []);

                    $updateRequestStart = millitime();

                    $order = $client->sendRestApiRequest(
                        json_encode($body),
                        'POST',
                        '/orders/' . strval($merchantOrderId) . '/transactions.json'
                    );

                    $this->monitoring->traceResponseTime(Metric::UPDATE_SHOPIFY_TRANSACTION_CALL_TIME,$updateRequestStart, []);

                }
                catch (\Exception $e)
                {
                    $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_ERROR_COUNT, [ 'error_type' => TraceCode::SHOPIFY_1CC_API_TRANSACTION_ERROR] );

                    $this->trace->info(
                        TraceCode::SHOPIFY_1CC_API_TRANSACTION_ERROR,
                        [
                            'type' => 'update_transaction_retry_failed',
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }
        }
    }

    protected function getTransactionBody(string $merchantOrderId, array $payment): array
    {
        $txn = [
            'kind'              => 'sale',
            'order_id'          => $merchantOrderId,
            'source'            => 'external',
            'processing_method' => 'manual',
            'currency'          => 'INR',
        ];

        $paymentMethod = $payment['method'];

        // TODO: evaluate default cod gateway used
        if (strtolower($paymentMethod) === 'cod')
        {
            $txn = array_merge($txn, [
                'message' => 'Pending Cash on Delivery (COD) payment from the buyer',
                'gateway' => 'Cash on Delivery (COD)',
                'status'  => 'pending',
                'authorization' => $payment['order_id'].'|'.$payment['id'],
            ]);
        }
        else
        {
            $txn = array_merge($txn, [
                'message' => 'Paid via Razorpay Magic Checkout',
                'gateway' => 'Razorpay',
                'status'  => 'success',
                'authorization' => $payment['order_id'].'|'.$payment['id'],
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

        $order['note'] =  $checkout['note'];

        if (empty($checkout['lineItems']['edges']) === false)
        {
            $lineItems = [];

            $items = $checkout['lineItems']['edges'];

            foreach ($items as $item)
            {

                $attributes = $item['node']['customAttributes'];

                $properties = [];

                if($attributes !== null)
                {
                    foreach ($attributes as $attribute)
                    {
                        $properties[] = [
                            'name'  => $attribute['key'],
                            'value' => $attribute['value']
                        ];
                    }
                }

                $lineItems[] = [
                    'variant_id' => str_replace('gid://shopify/ProductVariant/', '', base64_decode($item['node']['variant']['id'])),
                    'quantity'   => $item['node']['quantity'],
                    'properties' => $properties
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

    public function isPaymentAndOrderValid($order, $payment)
    {
        $method = $payment->getMethod();

        $status = $payment->getStatus();

        if ($payment->toArrayPublic()['order_id'] !== $order->getPublicId())
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, [ 'error_type' => ErrorCode::BAD_REQUEST_ERROR]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        if (
            ($method === PaymentMethod::COD and $payment->getStatus() === PaymentStatus::PENDING) or
            ($method !== PaymentMethod::COD and in_array($status, [PaymentStatus::CAPTURED, PaymentStatus::AUTHORIZED]))
        )
        {
            return true;
        }
        $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, [ 'error_type' => ErrorCode::BAD_REQUEST_ERROR]);

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
                [
                    'type' => 'update_checkout',
                    'input' => $input,
                    'status' => 'not_updated'
                ]
            );

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
            [
                'type' => 'update_checkout',
                'input' => $input,
                'status' => 'updated'
            ]
        );
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

    public function addTagToOrder($shopifyOrderId, string $merchantId, array $tags)
    {
        $client = $this->getShopifyClientByMerchantId($merchantId);

        $id = Constants::GID_ORDER.$shopifyOrderId;

        $mutation = (new Mutations)->getAddTagMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'id'        => $id,
                'tags'      => $tags
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_ADD_TAG,
            [
                'id'    => $id,
                'tags'  => $tags
            ]
        );

        $start = millitime();

        try
        {
            $response = $client->sendGraphqlRequest(json_encode($graphqlQuery));
        }
        catch (\Exception $exception)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_ADD_TAG_ERROR_COUNT,['error_code' => TraceCode::SHOPIFY_1CC_ORDER_ADD_TAG_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_ORDER_ADD_TAG_API_ERROR,
                [
                    'merchant_id'=>$merchantId,
                    'query' => $graphqlQuery,
                    'error' => $exception->getMessage()
                ]
            );
            throw new Exception\ServerErrorException(
                $exception->getMessage(),
                $exception->getCode()
            );
        }

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_ADD_TAG_CALL_TIME, $start, []);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_ORDER_ADD_TAG_RES,
            [
                'type' => 'add_tag',
                'response' => $response,
                'time' => millitime() - $start,
                'shopify_order_id' => $shopifyOrderId
            ]
        );

        $this->monitoring->addTraceCount(Metric::SHOPIFY_ADD_TAG_SUCCESS_COUNT, []);

    }

    public function removeTagToOrder($shopifyOrderId, string $merchantId, array $tags)
    {
        $client = $this->getShopifyClientByMerchantId($merchantId);

        $id = Constants::GID_ORDER.$shopifyOrderId;

        $mutation = (new Mutations)->getRemoveTagMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'id'        => $id,
                'tags'      => $tags
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_REMOVE_TAG,
            [
                'id'    => $id,
                'tags'  => $tags
            ]
        );

        $start = millitime();

        try
        {
            $response = $client->sendGraphqlRequest(json_encode($graphqlQuery));
        }
        catch (\Exception $exception)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_ADD_TAG_ERROR_COUNT,['error_code' => TraceCode::SHOPIFY_1CC_ORDER_REMOVE_TAG_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_ORDER_REMOVE_TAG_API_ERROR,
                [
                    'merchant_id'=>$merchantId,
                    'query' => $graphqlQuery,
                    'error' => $exception->getMessage()
                ]
            );
            throw new Exception\ServerErrorException(
                $exception->getMessage(),
                $exception->getCode()
            );
        }

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_ADD_TAG_CALL_TIME, $start, []);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_ORDER_REMOVE_TAG_RES,
            [
                'type' => 'remove_tag',
                'response' => $response,
                'time' => millitime() - $start,
                'shopify_order_id' => $shopifyOrderId
            ]
        );

        $this->monitoring->addTraceCount(Metric::SHOPIFY_ADD_TAG_SUCCESS_COUNT, []);

    }

    public function cancelShopifyOrder(string $shopifyOrderId, string $merchantId){

        $client = $this->getShopifyClientByMerchantId($merchantId);

        $method = OneClickCheckout\Constants::POST;

        $resource = '/orders/'.$shopifyOrderId.OneClickCheckout\Constants::CANCEL_ORDER_ENDPOINT;

        $requestStart = millitime();

        try
        {
            $response = $client->sendRestApiRequest(null, $method, $resource);
        }
        catch (\Exception $exception)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_CANCEL_STATUS_ERROR_COUNT,['error_code' => TraceCode::SHOPIFY_1CC_ORDER_CANCEL_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_ORDER_CANCEL_API_ERROR,
                [
                    'merchant_id'=>$merchantId,
                    'error' => $exception->getMessage()
                ]
            );
            throw new Exception\ServerErrorException(
                'Error while calling Shopify URL',
                ErrorCode::SERVER_ERROR
            );
        }

        $this->monitoring->traceResponseTime(Metric::CANCEL_SHOPIFY_ORDER_STATUS_CALL_TIME, $requestStart, []);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_ORDER_CANCEL_API_RES,
            [
                'type' => 'cancel_order',
                'response' => $response,
                'time' => millitime() - $requestStart,
                'shopify_order_id' => $shopifyOrderId
            ]
        );

        $this->monitoring->addTraceCount(Metric::SHOPIFY_CANCEL_ORDER_SUCCESS_COUNT, []);

    }

    private function getShopifyClientByMerchantId(string $merchantId)
    {
        $credentials = $this->getShopifyAuthByMerchantId($merchantId);

        return new Client($credentials);
    }

    private function getShopifyAuthByMerchantId(string $merchantId)
    {
        return (new AuthConfig\Core)->ge1ccAuthConfigsByMerchantIdAndPlatform($merchantId,
            \RZP\Models\Merchant\OneClickCheckout\Constants::SHOPIFY
        );
    }
}
