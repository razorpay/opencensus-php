<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use App;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Metric;
use RZP\Models\Order\Service as OrderService;
use RZP\Models\Merchant\OneClickCheckout;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Models\Order\OrderMeta\Type as OrderMetaType;

class Checkout extends Base\Core
{
    const GID_CHECKOUT = 'gid://shopify/Checkout/';

    protected $errors;
    protected $monitoring;

    public function __construct()
    {
        parent::__construct();
        $this->monitoring = new Monitoring();
        $this->errors = new Errors();
    }

    public function validateCreateCheckout(array $input): void
    {
        if (empty($input['cart']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The cart field is required.'
            );
        }
        else if (empty($input['cart']['items']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The cart cannot be empty.'
            );
        }
        else if (empty($input['cart']['token']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The cart token is required.'
            );
        }
    }

    // Creates a Shopify checkout using Storefront API.
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
            $this->monitoring->addTraceCount(
                Metric::CREATE_SHOPIFY_CHECKOUT_ERROR_COUNT,
                ['error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR]
            );
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'     => 'error_while_calling_shopify_url',
                     'response' => $e->getMessage(),
                 ]
            );
            throw $e;
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

            $this->monitoring->addTraceCount(
                Metric::CREATE_API_CHECKOUT_ERROR_COUNT,
                ['error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR]
            );

            throw new Exception\ServerErrorException(
                'error_creating_checkout',
                ErrorCode::SERVER_ERROR
            );
        }

        $checkoutCreate = $response['data']['checkoutCreate'];
        if (empty($checkoutCreate['checkoutUserErrors']) === false)
        {
            $checkoutError = $checkoutCreate['checkoutUserErrors'][0];
            $this->throwIfVariantIsInvalid($checkoutError);
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'     => 'error_creating_checkout',
                     'response' => $response,
                 ]
            );

            $this->monitoring->addTraceCount(
                Metric::CREATE_API_CHECKOUT_ERROR_COUNT,
                ['error_type' => TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR]);

            throw new Exception\ServerErrorException(
                'error_creating_checkout',
                ErrorCode::SERVER_ERROR
            );
        }

        return $checkoutCreate['checkout'];
    }

    public function getCheckoutbyStorefrontId(string $checkoutId): array
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getCheckoutMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'id' => $checkoutId
            ]
        ];
        //TODO:Adding Error Metrics for this Shopify Request
        $this->monitoring->addTraceCount(Metric::GET_CHECKOUT_BY_STOREFRONT_ID_REQUEST_COUNT,[]);

        $start = millitime();

        $res = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        $this->monitoring->traceResponseTime(Metric::GET_CHECKOUT_BY_STOREFRONT_ID_CALL_TIME,$start,[]);

        return json_decode($res, true);
    }


    // returns notes for Rzp order using Shopify storefront id and line items
    public function getNotesForCheckout(array $checkout, string $cartId, array $cartObj = []): array
    {
        $notes = [
            'storefront_id' => $checkout['id'],
            'cart_id'       => $cartId
        ];

        // Store the script discount details in RZP notes
        if (empty($cartObj) === false)
        {
            $discountFromScript = 0;
            $discountTitle = null;

            //For script editor application the cart obj will be fetched through admin api and other cases the cart obj will be fetch from cart.js so the structure will be different here.
            if (array_key_exists("line_items", $cartObj) === true)
            {
                $cartLineItem = $cartObj['line_items'];
                //By default amount will be in Rupees.
                $denominator = 1;
            }
            else
            {
                $cartLineItem = $cartObj['items'];
                //By default amount will be in Paisa. So need to convert it to Rupees.
                $denominator = 100;
            }

            foreach ($cartLineItem as $lineItem)
            {
                $discountFromScript += $lineItem['total_discount'] / $denominator;

                if (empty($lineItem['discounts']) === false)
                {
                    $discountTitle = $lineItem['discounts'][0]['title'];
                }
            }

            if ($discountFromScript > 0)
            {
                $notes['Script_Discount_Amount'] = $discountFromScript;
                $notes['Script_Discount_Title']  = $discountTitle;
            }
        }

        return $notes;
    }

    // updates email for a storefront checkout
    public function updateCheckoutEmail(string $checkoutId, string $email, string $orderId = '')
    {
        $client = $this->getShopifyClientByMerchant();

        $mutation = (new Mutations)->getcheckoutEmailUpdateMutation();

        $graphqlQuery = [
            'query'     => $mutation,
            'variables' => [
                'checkoutId' => $checkoutId,
                'email'      => $email,
            ],
        ];

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_EMAIL_BODY,
            ['checkoutId' => $checkoutId]);

        $checkoutRes = $client->sendStorefrontRequest(json_encode($graphqlQuery));

        $checkout = json_decode($checkoutRes, true);

        $newCheckoutId = $checkout['data']['checkoutEmailUpdateV2']['checkout']['id']?? null;

        if (($newCheckoutId !== $checkoutId) and
            ($newCheckoutId !== null) and
            ($orderId !== ''))
        {
            $order = (new RzpOrders)->findOrderByIdAndMerchant($orderId);

            $newNotes = array_merge($order->getNotes()->toArray(), [Constants::STOREFRONT_ID => $newCheckoutId]);

            $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_NEW_CHECKOUT_ID,
            [
                'checkoutId' => $checkoutId,
                'newCheckoutId' => $newCheckoutId
            ]);

            (new RzpOrders)->updateOrderNotes($orderId, $newNotes);
        }

        return $newCheckoutId;
    }

    // we use the Admin REST API instead of Storefront as only the admin API
    // returns the value "phone". This key is not present in Storefront API and it
    // is a hard limitation from Shopify
    public function getCheckoutFromAdminApi(string $checkoutId): array
    {
        $token = $this->getCartTokenFromCheckoutId($checkoutId);

        $client = $this->getShopifyClientByMerchant();

        $this->monitoring->addTraceCount(Metric::GET_SHOPIFY_CHECKOUT_DETAILS_REQUEST_COUNT, []);

        $start = millitime();
        try
        {
            $checkoutRes = $client->sendRestApiRequest(
                null,
                Client::GET,
                '/checkouts/' . $token . '.json');

            $checkout = json_decode($checkoutRes, true);

            $this->monitoring->traceResponseTime(Metric::GET_SHOPIFY_CHECKOUT_DETAILS_CALL_TIME,$start,[]);

            return $checkout['checkout'];
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(Metric::GET_SHOPIFY_CHECKOUT_DETAILS_ERROR_COUNT,['error_type'=>TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR] );
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'       => 'get_admin_checkout',
                     'error'      => $e->getMessage(),
                     'checkoutId' => $checkoutId,
                 ]);
            return [];
        }
    }

    public function updateCheckoutFromAdmin(array $input): array
    {
        $orderId = $input['order_id'];
        $order = (new RzpOrders)->findOrderByIdAndMerchant($orderId);
        $customerDetails = $this->getCustomerDetailsFromOrderMeta($order);

        if (empty($customerDetails) === true)
        {
            $this->trace->info(
                TraceCode::SHOPIFY_1CC_UPDATE_CHECKOUT,
                [
                    'order_id' => $input['order_id'],
                    'status'   => 'not_updated',
                    'reason'   => 'empty_customer_details'
                ]);

            return [];
        }

        $checkoutId = $this->getCheckoutIdFromOrder($order);

        $payload = $this->getPayloadForUpdateCheckoutAdmin($checkoutId, $customerDetails);

        $newCheckoutId = $this->updateCheckoutAdminApi($payload);

        $this->trace->info(
          TraceCode::SHOPIFY_1CC_UPDATE_CHECKOUT,
          [
              'order_id'        => $input['order_id'],
              'status'          => 'updated',
              'checkout_id'     => $checkoutId,
              'new_checkout_id' => $newCheckoutId,
          ]);

        if ($newCheckoutId !== $checkoutId and $newCheckoutId !== '')
        {
            $newNotes = array_merge($order->getNotes()->toArray(), ['storefront_id' => $newCheckoutId]);
            (new RzpOrders)->updateOrderNotes($orderId, $newNotes);
        }

        return [];
    }

    public function updateCheckoutUrl(array $input)
    {
        $start = millitime();

        $orderId = $input['order_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        if (empty($order->getNotes()['storefront_id']) === true)
        {
            $this->trace->error(
                 TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                 [
                     'type'     => 'update_checkout_url',
                     'order_id' => $orderId,
                     'reason'   => 'missing_storefront_id'
                 ]);
            $this->monitoring->addTraceCount(Metric::SHOPIFY_ADD_CHECKOUT_URL_ERROR_COUNT,['error_type'=>TraceCode::SHOPIFY_1CC_MISSING_STOREFRONT_ID]);
            return;
        }

        $checkoutId = $order->getNotes()['storefront_id'];

        $cartId = $order->getNotes()['cart_id'];

        $client = $this->getShopifyClientByMerchant();

        $this->addMagicCheckoutUrlToShopifyCheckout(array_merge($input, ['checkout_id' => $checkoutId], ['cart_id' => $cartId]));

        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_RETARGETING_URL,
            ['input' => $input, 'time' => millitime() - $start]);
    }

    protected function getPayloadForUpdateCheckoutAdmin(string $checkoutId, array $customerDetails): array
    {
        $payload = ['token' => $this->getCartTokenFromCheckoutId($checkoutId)];

        if (empty($customerDetails['shipping_address']) === false)
        {
            $payload['shipping_address'] = (new Address)->transformRzpToShopifyAdminAddress($customerDetails['shipping_address']);
        }

        if (empty($customerDetails['contact']) === false)
        {
            $payload['phone'] = $customerDetails['contact'];
        }

        if (empty($customerDetails['email']) === false)
        {
            $payload['email'] = $customerDetails['email'];
        }

        return $payload;
    }

    // fire and forget API so we don't reveal the error to the FE
    protected function updateCheckoutAdminApi(array $input): string
    {
        $checkout = null;
        $token = $input['token'];

        unset($input['token']);

        $body = ['checkout' => $input];

        $client = $this->getShopifyClientByMerchant();

        $this->monitoring->addTraceCount(Metric::UPDATE_CHECKOUT_DETAILS_REQUEST_COUNT, []);
        $start = millitime();

        try
        {
            $checkoutRes = $client->sendRestApiRequest(
                json_encode($body),
                Client::PUT,
                '/checkouts/' . $token . '.json');

            $checkout = json_decode($checkoutRes, true);
            $response =  $this->getStorefrontIdFromWebUrl($checkout['checkout']['web_url']);

            $this->monitoring->traceResponseTime(Metric::UPDATE_CHECKOUT_DETAILS_CALL_TIME, $start, []);

            return $response;
        }
        catch (\Exception $e)
        {
            $this->monitoring->addTraceCount(
                Metric::UPDATE_CHECKOUT_DETAILS_ERROR_COUNT,
                ['error_type' => 'update_checkout']
            );

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                [
                    'type'        => 'update_checkout_failed',
                    'error'       => $e->getMessage(),
                    'checkout_id' => $token,
                ]);

            return '';
        }
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
        $notes = $order->getNotes();
        if (empty($notes['storefront_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Invalid Magic Order'
            );
        }
        return $order->toArrayPublic()['notes']['storefront_id'];
    }

    protected function getShopifyCheckout(string $checkoutId): array
    {
        $checkout = $this->getCheckoutbyStorefrontId($checkoutId);

        return $checkout['data']['node'] ?? [];
    }

    protected function getStorefrontIdFromWebUrl(string $webUrl): string
    {
        return (self::GID_CHECKOUT . explode('checkouts/', $webUrl)[1]);
    }

    protected function getCartTokenFromCheckoutId(string $checkoutId): string
    {
        // To support backward compatibility of Shopify API version update from 2022-01 to 2022-10
        if(substr($checkoutId, 0, 3) != "gid")
        {
            $checkoutId = base64_decode($checkoutId);
        }

        $gid = explode('?', $checkoutId)[0];

        return str_replace(self::GID_CHECKOUT, '', $gid);
    }

    protected function getShopifyClientByMerchant()
    {
        $creds = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());
        if (empty($creds) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_ACCOUNT_NOT_CONFIGURED);
        }
        return new Client($creds);
    }

    /**
     * Fire and forget API so we silently catch the throwable
     * @param array
     * @return void
     */
    protected function addMagicCheckoutUrlToShopifyCheckout(array $input): void
    {
        try
        {
            $creds = (new AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());
            $input['shop_id'] = (new Utils)->stripAndReturnShopId($creds['shop_id']);

            $checkoutUrl = $this->getMagicCheckoutUrl($input);

            $this->updateCheckoutWithUrl($checkoutUrl, $input);
        }
        catch (\Throwable $e)
        {
            $this->monitoring->addTraceCount(Metric::ADD_MAGIC_URL_IN_CHECKOUT_ERROR_COUNT, ['error_type' => TraceCode::SHOPIFY_1CC_MAGIC_URL_UPDATE_ERROR]);

            $this->trace->error(
                TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                [
                    'type'  => 'update_attributes_failed',
                    'input' => $input,
                    'error' => $e->getMessage(),
                ]);
        }
    }

    protected function getMagicCheckoutUrl(array $input): string
    {
        return 'https://' . $input['shop_id'] . '.myshopify.com/cart?magic_order_id=' . $input['order_id'];
    }

    protected function updateCheckoutWithUrl(string $checkoutUrl, array $input)
    {
        $client = $this->getShopifyClientByMerchant();

        $checkoutId = $input['checkout_id'];

        $cartId = $input['cart_id'];

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
                        ],
                        [
                            'key'   => 'cart_token',
                            'value' => $cartId
                        ]
                    ],
                    'note' => $input['cart_note']
                ]
            ]
        ];
        $this->monitoring->addTraceCount(Metric::ADD_MAGIC_URL_IN_CHECKOUT_REQUEST_COUNT, []);
        $start = millitime();
        $response = $client->sendStorefrontRequest(json_encode($graphqlQuery));
        $this->monitoring->traceResponseTime(Metric::ADD_MAGIC_URL_IN_CHECKOUT_CALL_TIME, $start, []);
        return $response;
    }

    // Throws an error if the variant is invalid.
    protected function throwIfVariantIsInvalid(array $checkoutError): void
    {
        if ($checkoutError['message'] === 'Variant is invalid')
        {
            $this->trace->error(TraceCode::SHOPIFY_1CC_API_CHECKOUT_ERROR,
                [
                    'type'     => 'shopify_invalid_variant_error',
                    'response' => $checkoutError
                ]);
            $this->monitoring->addTraceCount(
                Metric::CREATE_API_CHECKOUT_ERROR_COUNT,
                ['error_type' => TraceCode::SHOPIFY_INVALID_VARIANT_ERROR]
            );

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR_MERCHANT_SHOPIFY_INVALID_VARIANTS_RECEIVED);
        }
    }
}
