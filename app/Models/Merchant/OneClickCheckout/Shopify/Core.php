<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use Throwable;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use RZP\Models\Customer\CustomerConsent1cc;
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

    const ORDER_CACHE_KEY_TTL = 24 * 60 * 60; // 1 day

    const MUTEX_KEY = 'shopify_1cc_place_order_mutex';

    const gupShupEnabledMids = [
        '7E6oragoxHFlvV',  //Go Noise
    ];

    const MAX_LENGTH = 8;

    const SHOPIFY_ORDER_PLACED_CACHE_KEY = '1cc:shopify_order_placed';

    const MAGIC_CHECKOUT_SERVICE_SHOPIFY_PATH        = 'v1/integrations/shopify';

    const CUSTOMER_ACCOUNTS                          = 'customer_accounts';

    protected $monitoring;

    public function __construct()
    {
        parent::__construct();

        $this->monitoring = new Monitoring();

        $this->cache = $this->app['cache'];
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
                'checkout_id' => $checkoutId,
                'time' => millitime() - $start
            ]
        );
        //TODO: Metrics creation for this Shopify API
        return $client->sendStorefrontRequest(json_encode($graphqlQuery));
    }

    public function getShopifyClientByMerchant()
    {
        $creds = $this->getShopifyAuthByMerchant();
        if (empty($creds) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED);
        }
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

        $stateCode = $stateCodeFromName = (new StateMap)->getPincodeMappedStateCode($address['zipcode']);

        if($stateCode === null)
        {
            $stateCode = (new StateMap)->getShopifyStateCode($address);

            $stateCodeFromName = (new StateMap)->getShopifyStateCodeFromName($address);
        }

        // name and address1 are compulsory fields but we don't collect it from
        // user at this time so we put default value
        // province field can take state code or full state name depending on what is passed
        $shippingAddress = [
            'firstName' => $address['first_name'] ?? 'User',
            'lastName'  => $address['last_name']  ?? '.',
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
                'checkout_id' => $checkoutId,
                'shipping_address' => $shippingAddress
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
                $errorType = (new Shipping)->getValueForErrorTypeDimension($body, 'fetch_shipping_rates_failed');
                $this->trace->info(
                     TraceCode::SHOPIFY_1CC_API_SHIPPING_ERROR,
                     [
                         'type'       => $errorType,
                         'response'   => $body,
                         'checkout_id' => $checkoutId,
                         'retries'    => $currentTries,
                         'time'       => millitime() - $start,
                     ]
                );
                $this->monitoring->addTraceCount(Metric::FETCH_SHIPPING_INFO_ERROR_COUNT, ['error_type' => $errorType]);

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
                $rates = (new Core)->parseShippingRates($shippingRates, $checkoutId);

                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_API_SHIPPING_RESPONSE,
                    [
                        'type' => 'shipping_api_response',
                        'checkout'     => $checkout,
                        'current_tries' => $currentTries,
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
                 'checkout_id' => $checkoutId,
                 'retries'    => $currentTries,
                 'time'       => millitime() - $start,
             ]
        );

        $this->monitoring->addTraceCount(Metric::FETCH_SHIPPING_INFO_ERROR_COUNT, ['error_type '=> 'retry_limit_exceeded_fetching_rates']);

        return [
            'serviceable'  => false,
            'cod'          => false,
            'shipping_fee' => 0,
            'cod_fee'      => null,
            'use_fallback' => true,
        ];
    }

    /**
     * in shopify shipping rate includes cod fee so we try and split it intelligently
     * this is a hack which needs to be monitored
     * other option is we use cod slabs and ask merchant to not set diff fees in shopify
     * if multiple cod options are available the lowest is chosen
     * Sample rates -
     * "shippingRates": [
     *       {
     *           "handle": "shopify-Standard%201-60.00",
     *           "title": "Standard 1",
     *           "priceV2": {
     *               "amount": "60.0",
     *               "currencyCode": "INR"
     *           }
     *       },
     *       {
     *           "handle": "Cash on Delivery app-advanced_cash_on_delivery_350083-90.00",
     *           "title": "Standard 1 ACOD",
     *           "priceV2": {
     *               "amount": "90.0",
     *               "currencyCode": "INR"
     *           }
     *       }
     *   ]
     */
    public function parseShippingRates($rates, $checkoutId): array
    {
        $shippingMethods = [];
        $shippingDetails = [];
        $shippingOption = null;
        $shippingDetail = null;
        $shippingId = 0;

        if (empty($rates) === true)
        {
            return [
                'serviceable'  => false,
                'cod'          => false,
                'shipping_fee' => 0,
                'cod_fee'      => null,
                'shipping_methods' => $shippingMethods
            ];
        }

        $bestRate;
        $codRate;
        $codFee = null;
        $hasCod = false;

        foreach ($rates as $rate)
        {
            $handle = $rate['handle'];
            $title = $rate['title'];
            $price = $rate['price'];
            $amount = $price['amount'];
            $currencyCode = $price['currencyCode'];

            if ($this->isMaybeCod($rate) === true)
            {
                $hasCod = true;

                if (isset($codRate) === false or $amount < $codRate)
                {
                    $codRate = $amount;
                }

                continue;
            }

            if (isset($bestRate) === false or $amount < $bestRate)
            {
                $bestRate = $amount;
            }

            //Multiple shipping options
            $shippingOption['id'] = 'id'.strval($shippingId);
            $shippingOption['name'] = $rate['title'];
            $shippingOption['shipping_fee'] = (int) $price['amount'] * 100;

            array_push($shippingDetails, $shippingOption);
            $shippingId++;
        }

        if (isset($codRate) === true)
        {
            $codFee = (new Utils)->formatNumber($codRate - $bestRate);
            if ($codFee < 0) {
                $codFee = 0;
            }

            if(empty($shippingDetails) === false)
            {
                //COD details for multiple shipping options
                foreach ($shippingDetails as $shippingDetail)
                {
                    $multiCodRate = $this->fetchCodRate($rates, $shippingDetail['name']);

                    if(empty($multiCodRate) === false)
                    {
                        $codFeeMulti = (new Utils)->formatNumber($multiCodRate - $shippingDetail['shipping_fee']/100);
                        if ($codFeeMulti < 0) {
                            $codFeeMulti = 0;
                        }
                        $shippingDetail['cod'] = $hasCod;
                        $shippingDetail['cod_fee'] = $codFeeMulti === null ? $codFeeMulti : intval($codFeeMulti)*100;
                        array_push($shippingMethods, $shippingDetail);
                    }
                    else
                    {
                        $shippingDetail['cod'] = false;
                        $shippingDetail['cod_fee'] = null;
                        array_push($shippingMethods, $shippingDetail);
                    }
                }
            }
        }
        else
        {
            //COD implementation for product tags - choosing the highest rate
            $checkout = (new Checkout)->getCheckoutbyStorefrontId($checkoutId);

            $products = $checkout['data']['node']['lineItems']['edges'];

            $codTags = [];

            $maxCod = 0;

            $validTag = false;

            if(!empty($products))
            {
                foreach ($products as $product)
                {
                    $tags = $product['node']['variant']['product']['tags'];

                    if(!empty($tags))
                    {
                        foreach ($tags as $tag)
                        {
                            if(strpos($tag, ' ') === false)
                            {
                                if(substr($tag, 0, 3) === "COD")
                                {
                                    $taglength = strlen($tag);

                                    if($taglength < self::MAX_LENGTH)
                                    {
                                        $cod = substr($tag, 3, $taglength);

                                        if(is_numeric($cod))
                                        {
                                            if ($cod >= 0)
                                            {
                                                if($maxCod < $cod)
                                                {
                                                    $maxCod = $cod;
                                                }
                                                $validTag = true;
                                            }
                                        }
                                        elseif(empty($cod))
                                        {
                                            $cod = 0;

                                            if($maxCod < $cod)
                                            {
                                                $maxCod = $cod;
                                            }
                                            $validTag = true;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if($validTag === true)
                    {
                        array_push($codTags, $maxCod);

                        $validTag = false;
                    }
                    else
                    {
                        //If COD tags are not available in any of the products, COD should not be enabled.
                        $codTags = [];

                        break;
                    }
                }

                if(!empty($codTags))
                {
                    $bestCod = max($codTags);
                }

                if(isset($bestCod))
                {
                    $hasCod = true;
                    $codFee = $bestCod;

                    //COD details for multiple shipping options - Product tags
                    foreach ($shippingDetails as $shippingDetail)
                    {
                        $shippingDetail['cod'] = $hasCod;
                        $shippingDetail['cod_fee'] = $codFee === null ? $codFee : intval($codFee)*100;
                        array_push($shippingMethods, $shippingDetail);
                    }
                }
            }
        }

        if(isset($codRate) === false && isset($bestCod) === false)
        {
            //COD details for multiple shipping options - No ACOD and Product tags
            foreach ($shippingDetails as $shippingDetail)
            {
                $shippingDetail['cod'] = $hasCod;
                $shippingDetail['cod_fee'] = $codFee === null ? $codFee : intval($codFee)*100;
                array_push($shippingMethods, $shippingDetail);
            }
        }

        $shippingResponse = [
            'serviceable'  => true,
            'shipping_fee' => intval($bestRate)*100,
            'cod'          => $hasCod,
            'cod_fee'      => $codFee === null ? $codFee : intval($codFee)*100,
        ];

        (new ShippingRates)->forceEnableCODIfApplicable($shippingResponse);

        if($this->merchant->isFeatureEnabled(Feature\Constants::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING))
        {
            $shippingResponse['shipping_methods'] = $shippingMethods;
        }

        return $shippingResponse;
    }

    protected function isMaybeCod(array $rate): bool
    {
        return (
            strpos(strtolower($rate['handle']), 'cash on delivery ') !== false
            or strpos(strtolower($rate['title']), 'cash on delivery ') !== false
        );
    }

    protected function fetchCodRate(array $rates, $title )
    {
        foreach ($rates as $rate) {
            if ( strpos(strtolower($rate['handle']), 'cash on delivery ') !== false
            or strpos(strtolower($rate['title']), 'cash on delivery ') !== false )
            {
                if ( strpos(strtolower($rate['title']), strtolower($title)) !== false )
                {
                    $codAmount = $rate['price']['amount'];
                    return $codAmount;
                }
            }
        }
        return 0;
    }

    public function validateCheckoutOptionsRequest(array $input)
    {
        if (isset($input['order_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException('order_id is a compulsory field');
        }
    }

    public function exceptionPlaceShopifyOrderAPI($client, $e, array $rzpOrder, array $rzpPayment, array $body): array
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
            if (empty($body['email']) === true)
            {
                //find customer id with phone
                $customerId = $this->findCustomerIdByPhone($body['customer']['phone']);

                if(empty($customerId) === false)
                {
                    $body['customer']['id'] = $customerId;
                    $body['customer']['phone'] = null;
                }
            }
            else
            {
                $body['customer']['phone'] = null;
            }

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
                    'type'          => 'order_place_api_retry_initiated_from_api',
                    'order_id'      => $orderId,
                    'strategy'      => 'retry',
                    'error_message' => $message
                ]
            );

            $order = $this->retryPlaceShopifyOrder($client, $rzpOrder, $body, $rzpPayment, true);

            return $order;
        }

        return [];
    }

    public function exceptionPlaceShopifyOrderSQS($client, $e, array $rzpOrder, array $rzpPayment, array $body): array
    {
        $orderId = $rzpOrder['id'];

        $notes = $rzpOrder['notes'];

        $message = strtolower($e->getMessage());

        $errorInventory = "unable to reserve inventory";

        $errorPhone = "phone has already been taken";

        $errorCustomer = "has already been taken";

        $retry = false;

        if(strpos($message, $errorPhone) !== false || strpos($message, $errorCustomer) !== false)
        {
            if (empty($body['email']) === true)
            {
                //find customer id with phone
                $customerId = $this->findCustomerIdByPhone($body['customer']['phone']);

                if(empty($customerId) === false)
                {
                    $body['customer']['id'] = $customerId;
                    $body['customer']['phone'] = null;
                }
            }
            else
            {
                $body['customer']['phone'] = null;
            }
            //New check for retry is made in case we want to add additional retry logic in the future
            $retry = true;
        }

        if ($retry === true)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_RETRY,
                [
                    'type'          => 'order_place_api_retry_initiated_from_SQS',
                    'order_id'      => $orderId,
                    'strategy'      => 'retry',
                    'error_message' => $message
                ]
            );

            $retryOrderResponse = $this->retryPlaceShopifyOrder($client, $rzpOrder, $body, $rzpPayment, false);

            if (is_array($retryOrderResponse) === true)
            {
                return $retryOrderResponse;
            }
            $message = $retryOrderResponse;
        }

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

        $promotions = $rzpOrder['promotions'];

        foreach($promotions as $promotion)
        {
            if(isset($promotion['type']) && $promotion['type'] === 'gift_card')
            {
                (new GiftCards)->refundGiftCard($promotion, $rzpOrder, $rzpPayment, $this->merchant->getId());
            }
        }

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

    protected function retryPlaceShopifyOrder($client, array $rzpOrder, array $body, $rzpPayment, bool $fromShopifyApi)
    {
        try
        {
            $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_REQUEST_COUNT, []);

            $placeOrderStart = millitime();

            $order = $client->sendRestApiRequest(
                json_encode(['order' => $body]),
                Client::POST,
                '/orders.json'
            );

            $this->monitoring->traceResponseTime(Metric::PLACE_SHOPIFY_ORDER_CALL_TIME, $placeOrderStart, []);

            $order = json_decode($order, true);

            $this->updateShopifyTransaction($order['order']['id'], $rzpPayment);

            $promotions = $rzpOrder['promotions'];

            foreach($promotions as $promotion)
            {
                if(isset($promotion['type']) && $promotion['type'] === 'gift_card')
                {
                    $this->updateShopifyGCTransaction($order['order']['id'], $promotion);
                }
                else
                {
                    $couponCode = $promotion['code'];
                }
            }

            $this->updateShopifyCustomer($client, $order);

            $isSEwithCouponApplied = $body['script_with_coupon_applied'];

            // This action is need to handle the orders which is placed with both SE discount and customer specific coupons
            // if($isSEwithCouponApplied === true)
            // {
            //     $this->disableUsedCoupon($client, $couponCode, $rzpOrder['id']);
            // }

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_PLACE_ORDER_RETRY_RES,
                [
                    'type'             => $fromShopifyApi === true ? 'order_place_api_retry_success' : 'order_place_sqs_retry_success',
                    'order_id'         => $rzpOrder['id'],
                    'shopify_order_id' => $order['order']['id'],
                    'time'             => millitime() - $placeOrderStart
                ]
            );

            return $order;
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_API_ORDER_RETRY_ERROR]);

            $message = $e->getMessage();

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ORDER_RETRY_ERROR,
                [
                    'type'     => $fromShopifyApi === true ? 'order_place_api_retry_failed' : 'order_place_sqs_retry_failed',
                    'order_id' => $rzpOrder['id'],
                    'error'    => $message
                ]
            );

            $this->monitoring->addTraceCount(Metric::SHOPIFY_COMPLETE_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_API_ORDER_RETRY_ERROR]);

            if ($fromShopifyApi === false)
            {
                return $message;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'RETRY_FAILED'
            );
        }

    }

    public function placeShopifyOrder(array $rzpOrder, array $rzpPayment, $fromShopifyApi,array $utmParameters=[]): array
    {
        $start = millitime();

        $finalErrorCode = "";

        $orderId = $rzpOrder['id'];

        $notes = $rzpOrder['notes'];

        $client = $this->getShopifyClientByMerchant();

        $body = $this->getCreateOrderPayload($rzpOrder, $rzpPayment, $utmParameters);

        $isSEwithCouponApplied = $body['script_with_coupon_applied'];

        try
        {
            $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_REQUEST_COUNT, []);

            $placeOrderStart = millitime();

            $order = $client->sendRestApiRequest(
                json_encode(['order' => $body]),
                Client::POST,
                '/orders.json'
            );

            $this->monitoring->traceResponseTime(Metric::PLACE_SHOPIFY_ORDER_CALL_TIME, $placeOrderStart, []);
        }
        catch (\Exception $e)
        {
            $exceptionHandlerResponse = null;
            // If rate limit is hit then do nothing
            if ($fromShopifyApi === true)
            {
                $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_ERROR_COUNT, ['error_type' => 'DELEGATED_TO_SQS']);

                $exceptionHandlerResponse = $this->exceptionPlaceShopifyOrderAPI($client, $e, $rzpOrder, $rzpPayment, $body);

                $finalErrorCode = "DELEGATED_TO_SQS";
            }
            else
            {
                $this->monitoring->addTraceCount(Metric::PLACE_SHOPIFY_ORDER_ERROR_COUNT, ['error_type' => 'SQS_TOO_FAILED']);

                $exceptionHandlerResponse = $this->exceptionPlaceShopifyOrderSQS($client, $e, $rzpOrder, $rzpPayment, $body);

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

        // Mark order as placed in redis
        $this->markShopifyOrderPlaced($orderId);

        $order = json_decode($order, true);

        $this->updateShopifyTransaction($order['order']['id'], $rzpPayment);

        $promotions = $rzpOrder['promotions'];

        $couponCode = null;

        foreach($promotions as $promotion)
        {
            if(isset($promotion['type']) && $promotion['type'] === 'gift_card')
            {
                $this->updateShopifyGCTransaction($order['order']['id'], $promotion);
            }
            else
            {
                $couponCode = $promotion['code'];
            }
        }

        $this->updateShopifyCustomer($client, $order);

        // This action is need to handle the orders which is placed with both SE discount and customer specific coupons
        // if($isSEwithCouponApplied === true)
        // {
        //     $this->disableUsedCoupon($client, $couponCode, $orderId);
        // }

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

    protected function disableUsedCoupon($client, $couponCode, $orderId)
    {
        $couponDetail = $this->fetchCouponCodeDetail($client, $couponCode, $orderId);

        if (isset($couponDetail['discount_code']) === true)
        {
            $couponPriceRuleId = $couponDetail['discount_code']['price_rule_id'];

            $couponCodeId = Constants::GID_DISCOUNT.$couponPriceRuleId;

            $this->disableCoupon($client, $couponCodeId, $orderId);
        }

        return;
    }

    protected function fetchCouponCodeDetail($client, $couponCode, $orderId)
    {
        $start = millitime();

        try
        {
          $this->trace->info(
            TraceCode::SHOPIFY_1CC_COUPON_CODE_FETCH,
            [
              'type' => 'coupon_fetch_details',
              'body' => $couponCode,
              'order_id' => $orderId,
            ]
          );

          $coupon = $client->sendRestApiRequest(
              null,
              Client::GET,
              '/discount_codes/lookup.json?code=' . strval($couponCode)
          );

          return json_decode($coupon, true);
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_FETCH_COUPON_ERROR,
                [
                    'type'     => 'coupon_fetch_details_failed',
                    'error'    => $e->getMessage(),
                    'order_id' => $orderId,
                    'time'     => millitime() - $start,
                ]
            );

            return;
        }
    }

    protected function disableCoupon($client, $couponCodeId, $orderId)
    {
        $mutation = (new Mutations)->disableCouponMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'id' => $couponCodeId
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_DISABLE_COUPON,
            [
                'id'       => $couponCodeId,
                'order_id' => $orderId,
            ]
        );

        $start = millitime();

        try
        {
            $response = $client->sendGraphqlRequest(json_encode($graphqlQuery));
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_DISABLE_COUPON_ERROR_COUNT,['error_code' => TraceCode::SHOPIFY_1CC_DISABLE_COUPON_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_DISABLE_COUPON_API_ERROR,
                [
                    'query'       => $graphqlQuery,
                    'order_id'    => $orderId,
                    'error'       => $e->getMessage()
                ]
            );

            return;
        }

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_DISABLE_COUPON_CALL_TIME, $start, []);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_DISABLE_COUPON_RES,
            [
                'type'     => 'disable_coupon',
                'response' => $response,
                'order_id' => $orderId,
                'time'     => millitime() - $start
            ]
        );

        $this->monitoring->addTraceCount(Metric::SHOPIFY_DISABLE_COUPON_SUCCESS_COUNT, []);

        return;
    }

    protected function updateShopifyCustomer($client, $order)
    {
        $customerId = $order['order']['customer']['id'];

        $rzpCustomerPhone = $order['order']['phone'];

        $shopifyCustomerPhone = $order['order']['customer']['phone'];

        $shopifyCustomerEmail = $order['order']['customer']['email'];

        $emailMarketing = isset($order['order']['customer']['email_marketing_consent']['state']) ? $order['order']['customer']['email_marketing_consent']['state'] : null;

        $smsMarketing = isset($order['order']['customer']['sms_marketing_consent']['state']) ? $order['order']['customer']['sms_marketing_consent']['state'] : null;

        $start = millitime();

        $customerConsent = $this->fetchCustomerConsentFor1CC($rzpCustomerPhone, $this->merchant->getId());

        if ($customerConsent == 0 || $customerConsent == null || $customerConsent == false)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_UPDATE_CUSTOMER,
                [
                  'type' => 'no_customer_consent',
                  'customerConsent' => $customerConsent,
                ]
              );
            return;
        }

        // Udate customer consent state, incase either email or sms is not subscribed.
        if ($emailMarketing != 'subscribed' || $smsMarketing != 'subscribed')
        {
            $body = $this->getCustomerUpdateBody($customerId, $shopifyCustomerPhone, $shopifyCustomerEmail);

            try
            {
              $customer = $client->sendRestApiRequest(
                  json_encode($body),
                  Client::PUT,
                  '/customers/' . strval($customerId) . '.json'
              );
            }
            catch (\Exception $e)
            {
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_API_CUSTOMER_ERROR,
                    [
                        'type' => 'update_customer_failed',
                        'error' => $e->getMessage()
                    ]
                );
            }
        }

        $merchantId = $this->merchant->getId();

        // Call GupShup consent API for enabled MID's

        $isMerchantGupshupEnabled = false;

        $gupshupCredentialsValue = null;

        $gupshupConfigs = $this->repo->merchant_1cc_configs->findByMerchantAndConfigArray(
            $this->merchant->getId(),
            [Type::ONE_CC_GUPSHUP_CREDENTIALS, Type::ONE_CC_ENABLE_GUPSHUP]
        );

        foreach($gupshupConfigs as $config)
        {
            if($config!=null && $config->getConfig() === Type::ONE_CC_ENABLE_GUPSHUP)
            {
                $isMerchantGupshupEnabled = $config->getValue() === "1";
            }
            else if($config!=null && $config->getConfig() === Type::ONE_CC_GUPSHUP_CREDENTIALS)
            {
                $gupshupCredentialsValue = $config->getValueJson();
            }
        }


        if ($isMerchantGupshupEnabled && $gupshupCredentialsValue != null && $shopifyCustomerPhone != null)
        {
            (new GupShup)->callGupShupConsent($shopifyCustomerPhone, $gupshupCredentialsValue);
        }
    }

    protected function getCustomerUpdateBody($customerId, $shopifyCustomerPhone, $shopifyCustomerEmail)
    {
        $customer = [
            'id'                     => $customerId,
            'accepts_marketing'      => true,
            'marketing_opt_in_level' => 'single_opt_in'
        ];

        if ($shopifyCustomerEmail != null || empty($shopifyCustomerEmail) === false)
        {
            $customer = array_merge($customer, [
                'email_marketing_consent' => [
                    'state'        => 'subscribed',
                    'opt_in_level' => 'single_opt_in'
                ]
            ]);
        }

        if ($shopifyCustomerPhone != null)
        {
            $customer = array_merge($customer, [
                'sms_marketing_consent' => [
                    'state'        => 'subscribed',
                    'opt_in_level' => 'single_opt_in'
                ]
            ]);
        }

        return ['customer' => $customer];
    }

    protected function getCreateOrderPayload($rzpOrder, $rzpPayment, array $utmParameters): array
    {
        $checkoutId = $rzpOrder['notes']['storefront_id'];

        $checkout = (new Checkout)->getCheckoutbyStorefrontId($checkoutId);

        $order = (new RzpOrders())->findOrderByIdAndMerchant($rzpOrder['id']);

        $orderMeta = array_first($order->orderMetas ?? [], function ($orderMeta)
        {
            return $orderMeta->getType() === Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
        });

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

        $noteAttributes = $body['note_attributes'];

        if (empty($rzpOrder['notes']['gstin']) === false)
        {
            array_push($noteAttributes,
            [
                'name'  => 'GSTIN',
                'value' => $rzpOrder['notes']['gstin']
            ]);
        }

        if (empty($rzpOrder['notes']['order_instructions']) === false)
        {
            array_push($noteAttributes,
            [
                'name'  => 'Additional Notes',
                'value' => $rzpOrder['notes']['order_instructions']
            ]);
        }

        if (empty($rzpOrder['notes']['cart_id']) === false)
        {
            array_push($noteAttributes,
            [
                'name'  => 'cart_token',
                'value' => $rzpOrder['notes']['cart_id']
            ]);
        }
        // add utm parameters as notes in shopify order
        foreach($utmParameters as $key=>$value)
        {
            array_push($noteAttributes,
                [
                    'name'  => $key,
                    'value' => $value
                ]);
        }

        $body['note_attributes'] = $noteAttributes;

        // We override the subtotal price to account for the Re 1 payment in case of 100% discount coupons
        $body['current_subtotal_price'] = strval($rzpOrder['amount']/100);

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

        if (empty($customerDetails['email']) === false)
        {
            $body['email'] = $customerDetails['email'];
        }

        $body['phone'] = $customerDetails['contact'];

        // NOTE: updating the contact causes conflicts in creating customer accounts
        // as shopify allows only single email per phone number
        $body['customer'] = [
            'phone' => $customerDetails['contact'],
        ];

        $discountAmountPaise = 0;
        $couponAmount = 0;
        $giftCardAmount = 0;
        $promotionCouponAmount = 0;
        $couponCode = null;

        if (empty($rzpOrder['promotions']) === false)
        {
            $promotions = $rzpOrder['promotions'];

            // We do not support Rs 0 orders so if a 100% discount coupon is applied
            // we hardcode the order amount to Re 1. To maintain consistency, we subtract
            // Re 1 from the discount applied so Shopify reflects the Re 1 payment even for 100% discount
            // We chose amount as amount_paid for cod orders is Re 0.

            foreach ($promotions as $key=>$value)
            {
                if (isset($value['type']) && $value['type'] === 'gift_card')
                {
                    $giftCardAmount = $giftCardAmount + $value['value'];
                }
                else
                {
                    $couponCode = $value['code'];

                    $couponAmount = $value['value'];

                    $promotionCouponAmount = $value['value'];
                }
            }
        }

        $scriptDiscountTitle = null;
        $isSEwithCouponApplied = false;

        // Add script discount as coupon
        if (isset($rzpOrder['notes']['Script_Discount_Amount']) && $rzpOrder['notes']['Script_Discount_Amount'] > 0)
        {
            if ($rzpOrder['notes']['Script_Discount_Title'] != null)
            {
                $scriptDiscountTitle = $rzpOrder['notes']['Script_Discount_Title'];
            }
            else
            {
                $scriptDiscountTitle = "Discount";
            }

            if($couponCode != null && $couponAmount>0)
            {
                $couponCode   = $couponCode. '+' .$scriptDiscountTitle;
                $couponAmount = (floatval($rzpOrder['notes']['Script_Discount_Amount'])*100) + $couponAmount;
                $isSEwithCouponApplied = true;
            }
            else
            {
                $couponCode   = $scriptDiscountTitle;
                $couponAmount = floatval($rzpOrder['notes']['Script_Discount_Amount'])*100;
            }
        }

        $body['script_with_coupon_applied'] = $isSEwithCouponApplied;

        $codFeeApplied = 0;

        if (strtolower($rzpPayment['method']) === 'cod' && isset($rzpOrder['cod_fee']))
        {
            $codFeeApplied = $rzpOrder['cod_fee'];
        }

        $discountAmountPaise = $rzpOrder['line_items_total'] + $rzpOrder['shipping_fee'] + $codFeeApplied - $rzpPayment['amount'] - $giftCardAmount;

        if(isset($scriptDiscountTitle))
        {
            if($isSEwithCouponApplied == true)
            {
                $rzpOffers = $discountAmountPaise - $promotionCouponAmount;
                $discountAmountPaise = $couponAmount + $rzpOffers;
            }
            else
            {
                $rzpOffers = $discountAmountPaise;
                $discountAmountPaise = $couponAmount + $rzpOffers;
            }
        }
        else
        {
            $rzpOffers = $discountAmountPaise - $couponAmount;
        }

        $rzpOffersRupee = round($rzpOffers/100,2);

        $discountAmountRupee = round($discountAmountPaise/100,2);

        if(isset($couponCode))
        {
            if($rzpOffersRupee > 0)
            {
                $body['discount_codes'][] = [
                    'code'   => $couponCode.' + Razorpay offers(₹'.$rzpOffersRupee.')',
                    'amount' => $discountAmountRupee,
                ];
            }
            else
            {
                $body['discount_codes'][] = [
                    'code'   => $couponCode,
                    'amount' => $discountAmountRupee,
                ];
            }
        }
        else
        {
            if($rzpOffersRupee > 0)
            {
                $body['discount_codes'][] = [
                    'code'   => 'Razorpay offers(₹'.$rzpOffersRupee.')',
                    'amount' => $discountAmountRupee,
                ];
            }
        }

        $body['current_total_discounts'] = $discountAmountRupee;

        $defaultPendingStatus = false;

        try {
            $defaultPendingStatus = $this->canSetOrderStatusPending($rzpOrder['id'], $this->merchant);
        } catch (\Throwable $e) {

            $this->trace->info(
                TraceCode::SHOPIFY_1CC_API_ERROR,
                [
                    'type' => 'canSetOrderStatusPending',
                    'errorMessage' => $e->getMessage()
                ]
            );
            $defaultPendingStatus = false;
        }

        // Based on the exp value this status will be set.
        if ($defaultPendingStatus === true)
        {
            $body['financial_status'] = 'pending';
        }
        else
        {
            $body['financial_status'] = 'paid';
        }


        if (strtolower($rzpPayment['method']) === 'cod')
        {
           $body['financial_status'] = 'pending';
           $shippingFee = $shippingFee + $codFee;
        }

        if(empty($orderMeta) === false)
        {
            $value = $orderMeta->getValue();

            if (empty($value['shipping_method']) === false)
            {
                $shippingTitle = $value['shipping_method']['name'];
            }
        }

        $body['shipping_lines'] = [
            [
                'price' => $shippingFee,
                'title' =>  $shippingTitle ?? 'Standard Shipping'
            ]
        ];

        $body['tags'] = 'Magic, '.$rzpPayment['method'];

        if (empty($rzpOrder['notes']['gstin']) === false)
        {
            $body['tags'] = $body['tags'].', GSTIN';
        }

        if (empty($rzpOrder['notes']['order_instructions']) === false)
        {
            $body['tags'] = $body['tags'].', Additional Notes';
        }

        if(empty($orderMeta) === false && strtolower($rzpPayment['method']) === 'cod')
        {
            $value = $orderMeta->getValue();

            $rtoReasons = [];

            if (empty($value['cod_intelligence']['risk_tier']) === false &&
                empty($value['cod_intelligence']['manual_control_cod_order']) === false &&
                $value['cod_intelligence']['manual_control_cod_order'] === true)
            {
                $body['tags'] = $body['tags'].', RTO Risk - '.$value['cod_intelligence']['risk_tier'];
                $rtoReasons = $value['cod_intelligence']['rto_reasons'] ?? [];
            }

            if (empty($rtoReasons) === false)
            {
                $noteAttributes = $body['note_attributes'];

                $rtoLabels = [];

                foreach ($rtoReasons as $rtoReason)
                {
                    $rtoLabel = (new RtoReasons)->getRtoReasons($rtoReason);

                    if(!empty($rtoLabel))
                    {
                        array_push($rtoLabels, $rtoLabel);
                    }
                }

                $rtoString = implode(', ', $rtoLabels);

                array_push($noteAttributes,
                [
                    'name'  => 'Risk Reasons',
                    'value' => $rtoString
                ]);

                $body['note_attributes'] = $noteAttributes;
            }
        }
        return $body;
    }

    protected function fetchCustomerConsentFor1CC($contact, $merchantId)
    {
        $customerConsent = (new CustomerConsent1cc\Core())->fetchCustomerConsent1cc($contact, $merchantId);

        if (empty($customerConsent) == false) {
            return $customerConsent['status'];
        }
       return 0;
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

    protected function updateShopifyGCTransaction(string $merchantOrderId, $promotion): array
    {
        $start = millitime();

        $txn = [
            'kind'              => 'sale',
            'amount'            => $promotion['value']/100,
            'source'            => 'external',
            'processing_method' => 'manual',
            'currency'          => 'INR',
            'authorization'     => 'GIFT CARD - '.strtoupper($promotion['code']),
            'gateway' => 'Gift Card',
            'status'  => 'success',
        ];

        $body = ['transaction' => $txn];

        $client = $this->getShopifyClientByMerchant();

        try
        {
          $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_REQUEST_COUNT, []);

          $updateRequestStart = millitime();

          $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_TRANSACTION_BODY,
            [
              'type' => 'update_gc_transaction_initiated',
              'body' => $body,
            ]
          );

          $order = $client->sendRestApiRequest(
              json_encode($body),
              Client::POST,
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
                    'type' => 'update_gc_transaction_failed',
                    'error' => $e->getMessage(),
                    'time' => millitime() - $start
                ]
            );
        }
    }

    protected function updateShopifyTransaction(string $merchantOrderId, array $payment): array
    {
        $start = millitime();

        $body = $this->getTransactionBody($merchantOrderId, $payment);

        $client = $this->getShopifyClientByMerchant();

        $errorMessage = null;

        try
        {
          $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_REQUEST_COUNT, []);

          $updateRequestStart = millitime();

          $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_TRANSACTION_BODY,
            [
              'type' => 'update_transaction_initiated',
              'body' => $body,
              'order_id' => $payment['order_id']
            ]
          );

          $order = $client->sendRestApiRequest(
              json_encode($body),
              Client::POST,
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
                    'time' => millitime() - $start,
                    'order_id' => $payment['order_id']
                ]
            );

            $errorMessage = strtolower($e->getMessage());

            $errorBadGateway = "502 bad gateway";

            if(strpos($errorMessage, $errorBadGateway) !== false)
            {
                $this->trace->info(
                    TraceCode::SHOPIFY_1CC_API_ERROR,
                    [
                        'type' => 'update_transaction_retry_initiated',
                        'strategy' => 'retry',
                        'error_message' => $errorMessage,
                        'order_id' => $payment['order_id']
                    ]
                );

                try
                {
                    $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_REQUEST_COUNT, []);

                    $updateRequestStart = millitime();

                    $order = $client->sendRestApiRequest(
                        json_encode($body),
                        Client::POST,
                        '/orders/' . strval($merchantOrderId) . '/transactions.json'
                    );

                    $this->monitoring->traceResponseTime(Metric::UPDATE_SHOPIFY_TRANSACTION_CALL_TIME,$updateRequestStart, []);

                    $errorMessage = null;
                }
                catch (\Exception $e)
                {
                    $this->monitoring->addTraceCount(Metric::UPDATE_SHOPIFY_TRANSACTION_ERROR_COUNT, [ 'error_type' => TraceCode::SHOPIFY_1CC_API_TRANSACTION_ERROR] );

                    $this->trace->info(
                        TraceCode::SHOPIFY_1CC_API_TRANSACTION_ERROR,
                        [
                            'type' => 'update_transaction_retry_failed',
                            'error' => $e->getMessage(),
                            'order_id' => $payment['order_id']
                        ]
                    );

                    $errorMessage = strtolower($e->getMessage());
                }
            }

            if ($errorMessage != null)
            {
                $order = (new RzpOrders)->findOrderByIdAndMerchant($payment['order_id']);

                $newNotes = array_merge($order->getNotes()->toArray(), ['Transaction Error' => $errorMessage]);
                (new RzpOrders)->updateOrderNotes($payment['order_id'], $newNotes);
            }
            return [];
        }
    }

    protected function getTransactionBody(string $merchantOrderId, array $payment): array
    {
        $txn = [
            'kind'              => 'sale',
            'amount'            => $payment['amount']/100,
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
            'current_subtotal_price' => $checkout['subtotalPrice']['amount'],
            'taxes_included'         => $checkout['taxesIncluded'],
            'total_tax'              => $checkout['totalTax']['amount'],
            'inventory_behaviour'    => 'decrement_obeying_policy',
            'send_receipt'           => true,
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
                    'variant_id' => str_replace(Constants::GID_PRODUCT_VARIANT, '', $item['node']['variant']['id']),
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
            $this->monitoring->addTraceCount(Metric::SHOPIFY_REMOVE_TAG_ERROR_COUNT,['error_code' => TraceCode::SHOPIFY_1CC_ORDER_REMOVE_TAG_API_ERROR]);

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

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_REMOVE_TAG_CALL_TIME, $start, []);

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_ORDER_REMOVE_TAG_RES,
            [
                'type' => 'remove_tag',
                'response' => $response,
                'time' => millitime() - $start,
                'shopify_order_id' => $shopifyOrderId
            ]
        );

        $this->monitoring->addTraceCount(Metric::SHOPIFY_REMOVE_TAG_SUCCESS_COUNT, []);

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
            $this->monitoring->addTraceCount(Metric::SHOPIFY_CANCEL_ORDER_ERROR_COUNT,['error_code' => TraceCode::SHOPIFY_1CC_ORDER_CANCEL_API_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_ORDER_CANCEL_API_ERROR,
                [
                    'type'  => 'error_while_calling_shopify_url',
                    'error' => $exception->getMessage()
                ]
            );
            throw new Exception\ServerErrorException(
                'Error while calling Shopify URL',
                ErrorCode::SERVER_ERROR
            );
        }

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_CANCEL_ORDER_CALL_TIME, $requestStart, []);

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

    private function findCustomerIdByPhone($phone)
    {

        $client = $this->getShopifyClientByMerchantId($this->merchant->getId());

        $method = Client::GET;

        if(substr($phone,0,1) === '+')
        {
            $phone = substr($phone,1,mb_strlen($phone) - 1);
        }

        $queryString = '?query=phone:'.$phone;

        $resource = OneClickCheckout\Constants::CUSTOMER_SEARCH_ENDPOINT.$queryString;

        $requestStart = millitime();

        $customerId = null;

        $response = null;

        $this->monitoring->addTraceCount(Metric::SHOPIFY_CUSTOMER_SEARCH_REQUEST_COUNT, []);

        try
        {
            $response = $client->sendRestApiRequest(null, $method, $resource);

            $customerDetails = json_decode($response, true);

            if (empty($customerDetails['customers']) === false)
            {
                $customerId = $customerDetails['customers'][0]['id'];
            }
        }
        catch (\Exception $exception)
        {
            $this->monitoring->addTraceCount(Metric::SHOPIFY_CUSTOMER_SEARCH_ERROR_COUNT,['error_type' => TraceCode::SHOPIFY_FETCH_CUSTOMER_FAILED]);

            $this->trace->error(
                TraceCode::SHOPIFY_CUSTOMER_SEARCH_API_ERROR,
                [
                    'type'  => 'error_fetching_customer',
                    'error' => $exception->getMessage()
                ]
            );
            return $customerId;
        }

        $this->monitoring->traceResponseTime(Metric::SHOPIFY_CUSTOMER_SEARCH_CALL_TIME, $requestStart, []);

        $this->trace->info(
            TraceCode::SHOPIFY_CUSTOMER_SEARCH_API_RES,
            [
                'type' => 'customer_search_with_phone',
                'time' => millitime() - $requestStart,
                'customer_id' => $customerId
            ]
        );
        return $customerId;
    }

    /**
     * Will return false if platform in apart from shopify or test mode keys,
     * and return true in case of variant is equal to magic_order
     */
    public function canSetOrderStatusPending($orderId, $merchant): bool
    {
        $platformConfig = $merchant->getMerchantPlatformConfig();
        if ((app()->isEnvironmentProduction() === true && $this->mode === Mode::TEST) ||
            ($platformConfig != null && $platformConfig->getValue() !== Constants::SHOPIFY))
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_ORDER_STATUS_PENDING_EXP,
                [
                    'type' => 'canSetOrderStatusPending',
                    'mode' => $this->mode,
                    'env' => app()->isEnvironmentProduction(),
                    'platform' => $platformConfig->getValue()
                ]
            );
            return false;
        }

        $expResult = (new SplitzExperimentEvaluator())->evaluateExperiment(
            [
                'id'            => $orderId,
                'experiment_id' => $this->app['config']->get('app.1cc_order_default_pending_splitz_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchant->getId(),
                    ]),
            ]
        );

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_ORDER_STATUS_PENDING_EXP,
            [
                'type' => 'canSetOrderStatusPending',
                'mode' => $this->mode,
                'env' => app()->isEnvironmentProduction(),
                'platform' => $platformConfig->getValue(),
                'variant' => $expResult['variant']
            ]
        );
        return $expResult['variant'] === 'magic_order';
    }

    public function markShopifyOrderPlaced(string $orderId): void
    {
        $key = $this->getCacheKeyForPlacedOrders($orderId);
        $this->cache->put($key, 1, self::ORDER_CACHE_KEY_TTL);
    }

    protected function getCacheKeyForPlacedOrders(string $orderId): string
    {
        return self::SHOPIFY_ORDER_PLACED_CACHE_KEY . ':' . $orderId;
    }

    // canShopifyOrderBePlaced checks if the Rzp order receipt is updated or local storage (Redis).
    public function canShopifyOrderBePlaced(string $orderId, string $receipt): bool
    {
        $key = $this->getCacheKeyForPlacedOrders($orderId);
        return empty($this->cache->get($key)) === true and $receipt === (new OneClickCheckout\Constants)::SHOPIFY_TEMP_RECEIPT;
    }

    public function createCustomerAccount($customer) : array
    {
        if($customer['email'] != null && $customer['email'] != "")
        {
            $input['email'] = $customer['email'];
        }
        else
        {
            return [];
        }

        try {

            $input['contact'] = $customer['contact'];

            $client = $this->getShopifyClientByMerchant();

            $input['store_front_access_token'] = $client->getStoreFrontAccessToken();
            $input['shop_id'] = $client->getShopId();

            $input['merchant_id'] = $this->merchant->getId();

            $path = self::MAGIC_CHECKOUT_SERVICE_SHOPIFY_PATH . '/' . self::CUSTOMER_ACCOUNTS;

            $this->trace->info(TraceCode::SHOPIFY_AUTOMATIC_ACCOUNT_CREATION_STARTED,[
                'merchant_id'=>$input['merchant_id']
            ]);

            $this->app['magic_checkout_service_client']->sendRequest($path, $input, Requests::POST);

            $this->trace->info(TraceCode::SHOPIFY_AUTOMATIC_ACCOUNT_CREATION_MAIL_TRIGGERED,[
                'merchant_id'=>$input['merchant_id']
            ]);

            return [];

        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SHOPIFY_AUTOMATIC_ACCOUNT_CREATION_ERROR,[
                'error'=> $e->getMessage()
            ]);
            return [];
        }
    }
}
