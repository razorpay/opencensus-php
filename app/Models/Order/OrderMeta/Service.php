<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
use RZP\Jobs\OneCCReviewCODOrder;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\OneClickCheckout\Shopify\RzpOrders;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils as OneCcUtils;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Merchant\ShippingInfo;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Services\LocationService;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Models\Order;
use Throwable;
use RZP\Models\Merchant\OneClickCheckout\Core as OneClickCheckoutCore;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Payment;
use RZP\Models\Customer;

class Service extends \RZP\Models\Base\Service
{
    /**
     * @var Mutex
     */
    protected $mutex;

    const MUTEX_PREFIX_1CC = "1cc_order_action:";

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }


    /**
     * Function to update customer details for 1CC Orders.
     * @param string $orderId
     * @param array $input
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */

    public function updateCustomerDetailsFor1CCOrder(string $orderId, array $input): array
    {
        $startTime = millitime();
        $dimensions = [
            "mode" => $this->mode
        ];

        $ex = [];
        $result = [];

        try {
            $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_COUNT, $dimensions);

            try {
                if (!empty($input['customer_details']['shipping_address']['id']) && !empty($input['customer_details']['billing_address'])) {
                    $addressId = $input['customer_details']['shipping_address']['id'];
                    $customer = (new Customer\Service)->getMagicCustomer();
                    if ($customer != null) {
                        $customerId = $customer->getId();
                        (new MagicCheckoutService\Service)->updateAddressUsageToMagicCheckoutService($addressId, $customerId);
                    }
                }
            } catch (\Throwable $e) {
                $this->trace->error(TraceCode::UPDATE_ADDRESS_USAGE_ERROR, [
                    'error_message' => $e->getMessage(),
                    'exception'=> $e->getTrace()
                ]);
                $this->trace->count(Metric::UPDATE_SHIPPING_ADDRESS_USAGE_REQUEST_ERROR_COUNT, $dimensions);
            }

            try {
                (new Order1cc\Validator())->validateInput('editCustomerDetails', $input);
                (new Core)->validateActive1CCOrderId($orderId);
            } catch (\Throwable $e) {
                $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_ERROR_COUNT, $dimensions);
                $ex = $e;
                throw $e;
            }

            $orderMetaInput = [];
            $shippingMethod = null;
            if (empty($input[Order1cc\Fields::SHIPPING_METHOD]) === false)
            {
                $shippingMethod = $input[Order1cc\Fields::SHIPPING_METHOD];
            }
            $customerInfo = $input[Order1cc\Fields::CUSTOMER_DETAILS];
            if (isset($customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]) === true &&
                isset($customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]['country']) === true) {
                $country = $customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]['country'];
                $state = $customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]['state'];
                $address[0] = [
                    "zipcode" => $customerInfo[Order1cc\Fields::CUSTOMER_DETAILS_SHIPPING_ADDRESS]['zipcode'],
                    "country" => $country,
                    "state"   => $state,
                    "state_code" => $this->getStateCodeFromCountryAndState($country, $state)
                ];
                $shippingInfoReq = [
                    'order_id' => $orderId,
                    'addresses' => $address,
                ];

                $taxesApplied = [];

                try {
                    $useMCS = (new SplitzExperimentEvaluator())->isShippingInfoDecompEnabled();
                    if ($useMCS === true) {
                        $merchant_id = $this->merchant->getId();
                        $shippingInfoReq['merchant_id'] = $merchant_id;
                        $shippingInfoReq['addresses'][0]['country_code'] = $country;
                        $shippingInfoResponse  = (new MagicCheckoutService\Service())->getTaxDetailsAndShippingOptions($shippingInfoReq);
                    }
                    else {
                        $shippingInfoResponse = (new ShippingInfo\Service())->getShippingInfo($shippingInfoReq);
                    }

                    $addresses = $shippingInfoResponse['addresses'];
                    $shippingInfo = $addresses[0];
                    $taxesApplied = $shippingInfoResponse['tax_details'] ?? [];

                } catch (\Throwable $e) {
                    $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_FAULT_COUNT, $dimensions);
                    $ex = new BadRequestException(ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND);
                    throw $ex;
                }
                if ($shippingInfo === null or
                    $shippingInfo['serviceable'] === false) {
                    $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_FAULT_COUNT, $dimensions);
                    $ex = new BadRequestException(ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND);
                    throw $ex;
                }

                $shippingFee = $shippingInfo[Order1cc\Fields::SHIPPING_FEE] ?? 0;
                $codFee = $shippingInfo[Order1cc\Fields::COD_FEE] ?? 0;

                if (empty($shippingMethod) === false && empty($shippingInfo['shipping_methods']) === false)
                {
                    $selectedMethod = $this->isValidShippingMethod($shippingInfo['shipping_methods'], $shippingMethod, $dimensions);
                    $shippingMethod = [
                        Order1cc\Fields::COD_FEE      => $selectedMethod[Order1cc\Fields::COD_FEE],
                        Order1cc\Fields::SHIPPING_FEE => $selectedMethod[Order1cc\Fields::SHIPPING_FEE],
                        Order1cc\Fields::NAME         => $selectedMethod[Order1cc\Fields::NAME] ?? Order1cc\Fields::STANDARD_SHIPPING,
                        Order1cc\Fields::DESCRIPTION  => $selectedMethod[Order1cc\Fields::DESCRIPTION] ?? Order1cc\Fields::STANDARD_SHIPPING,
                    ];
                    $shippingFee = $selectedMethod[Order1cc\Fields::SHIPPING_FEE];
                    $codFee = $selectedMethod[Order1cc\Fields::COD_FEE];
                }
                else
                {
                    $shippingMethod = [
                        Order1cc\Fields::COD_FEE      => $codFee,
                        Order1cc\Fields::SHIPPING_FEE => $shippingFee,
                        Order1cc\Fields::NAME         => Order1cc\Fields::STANDARD_SHIPPING,
                        Order1cc\Fields::DESCRIPTION  => Order1cc\Fields::STANDARD_SHIPPING,
                    ];
                }

            $orderMetaInput = [
                Order1cc\Fields::COD_FEE      => $codFee,
                Order1cc\Fields::SHIPPING_FEE => $shippingFee,
                Order1cc\Fields::TAX_DETAILS  => $taxesApplied,
            ];
        }

            $orderMetaInput = array_merge($orderMetaInput, [
                Order1cc\Fields::CUSTOMER_DETAILS => $customerInfo,
            ]);

            if (empty($shippingMethod) === false)
            {
                $orderMetaInput = array_merge($orderMetaInput, [
                    Order1cc\Fields::SHIPPING_METHOD => $shippingMethod,
                ]);
                $platformConfig = $this->merchant->getMerchantPlatformConfig();
                $platform = "";
                if ($platformConfig !== null) {
                    $platform = $platformConfig->getValue();
                }
                if ($platform ===  Merchant1ccConfig\Type::WOOCOMMERCE)
                {
                    $this->updateOrderNotesWithShippingMethodName($orderId, $shippingMethod[Order1cc\Fields::NAME]);
                }
            }
            $result = (new OneClickCheckoutCore)->update1CcOrder($orderId, $orderMetaInput);

            $duration = millitime() - $startTime;
            $this->trace->histogram(Metric::UPDATE_CUSTOMERS_DETAILS_TIME_MILLIS, $duration, $dimensions);

            return $result;
        }
        catch(\Throwable $e)
        {
            $ex = $e;
            throw $e;
        }
        finally {
            $this->traceUpdateCustomerDetailsLogs($orderId, $input, $result, $ex);
        }
    }

    private function updateOrderNotesWithShippingMethodName(string $orderId, string $shippingMethodName) {
        $order = (new RzpOrders)->findOrderByIdAndMerchant($orderId);
        $newNotes = array_merge($order->getNotes()->toArray(), ['shipping_method_name' => $shippingMethodName]);
        (new RzpOrders)->updateOrderNotes($orderId, $newNotes);
    }

    /**
     * Resets fee details and applied promotions for the order in case the orderId is re-used.
     * Applies only for 1CC Orders.
     * @param string $orderId
     * @return array
     * @throws \RZP\Exception\BadRequestException
     */
    public function reset1CCOrder(string $orderId)
    {
        $start = millitime();

        $this->trace->count(Metric::RESET_ORDER_REQUEST_COUNT,[]);

        $core = (new Core);

        $input = ['order_id' => $orderId];

        try {
            $core->validateActive1CCOrderId($orderId);

            [$notes, $taxDetails] = $this->getOrderNotes($orderId);

            if (empty($taxDetails) === false && empty($taxDetails['total_tax']) === false) {
                $taxDetails['total_tax'] = 0;
            }

            (new OneClickCheckoutCore)->update1CcOrder(
                $orderId,
                [
                    Order1cc\Fields::COD_FEE => 0,
                    Order1cc\Fields::SHIPPING_FEE => 0,
                    Order1cc\Fields::PROMOTIONS => [],
                    Order1cc\Fields::TAX_DETAILS => $taxDetails,
                ]);

            /*
             * reset gstin & order_instructions
             */
            unset($notes[Order1cc\Fields::GSTIN]);
            unset($notes[Order1cc\Fields::ORDER_INSTRUCTIONS]);
            (new Order\Service)->update($orderId, ['notes' => $notes]);

        } catch (\Throwable $e) {
            $internalErrorCode = '';
            if (($e instanceof BaseException) === true)
            {
                $internalErrorCode = $e->getError()->getInternalErrorCode();
            }

            $this->trace->count(Metric::RESET_ORDER_ERROR_COUNT, [
                'mode' => $this->mode,
                'internal_error_code' => $internalErrorCode,
            ]);

            $this->trace->error(TraceCode::RESET_ORDER_REQUEST_ERROR,
                [
                    'request' =>  $input,
                    'exception'=> $e->getTrace()
                ]
            );

            throw $e;
        }
        $this->trace->histogram(Metric::RESET_ORDER_TIME_MILLIS, millitime() - $start, []);

        $this->trace->info(TraceCode::RESET_ORDER_REQUEST,
            [
                'request' =>  $input
            ]
        );

    }

    protected function getShippingInfoCacheKey($orderId, $address): string
    {
        $merchantId = $this->merchant !== null ? $this->merchant->getId() : "";
        $zipcode = $address['zipcode'] ?? "";
        return "SHIPPING_INFO_"
            . $merchantId
            . "_"
            . $orderId
            . "_"
            . $zipcode
            . "_"
            . $address['country'];
    }

    protected function traceUpdateCustomerDetailsLogs($orderId, $input, $response, $ex) {
        $input = array_merge(
            $input,
            [
                'order_id' => $orderId
            ]
        );

        if (empty($ex) === true){
            $this->trace->info(TraceCode::UPDATE_CUSTOMERS_DETAILS_REQUEST,
                [
                    'request' =>  $this->maskCustomerDetails($input),
                    'response'=>  $this->maskCustomerDetails($response),
                    'exception'=> $ex
                ]
            );
        }else {
            $internalErrorCode = '';
            if (($ex instanceof BaseException) === true)
            {
                $internalErrorCode = $ex->getError()->getInternalErrorCode();
            }
            $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_ERROR_COUNT, array_merge(
                [
                    'internal_error_code' => $internalErrorCode,
                    'mode'                => $this->mode,
                ]
            ));
            $this->trace->error(TraceCode::UPDATE_CUSTOMERS_DETAILS_REQUEST_ERROR,
                [
                    'request' =>  $this->maskCustomerDetails($input),
                    'response'=>  $this->maskCustomerDetails($response),
                    'exception'=> $ex->getTrace()
                ]
            );
        }
    }

    protected function maskCustomerDetails($input) {
        $maskedRequest =[];

        if (empty($input['order_id']) === false) {
            $maskedRequest = array_merge($maskedRequest,
                [
                    "order_id" => $input['order_id']
                ]);
        }


        if (empty($input['customer_details']) === false) {

            $customerInfo = $input['customer_details'];
            if (empty($customerInfo['shipping_address']) === false && empty($customerInfo['shipping_address']['line1']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'address_line_1' => mask_by_percentage($customerInfo['shipping_address']['line1'])
                    ]);
            }
            if (empty($customerInfo['shipping_address']) === false && empty($customerInfo['shipping_address']['line2']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'address_line_2' => mask_by_percentage($customerInfo['shipping_address']['line2'])
                    ]);
            }


            if (empty($customerInfo['contact']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'contact' => mask_phone($customerInfo['contact'])
                    ]);
            }
            if (empty($customerInfo['email']) === false) {
                $maskedRequest = array_merge($maskedRequest,
                    [
                        'email' => mask_email($customerInfo['email'])]
                );
            }
        }

        return $maskedRequest;
    }

    /**
     * Update GSTIN and orderInstruction for the order.
     * Applies only for 1CC Orders.
     * @param string $orderId
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function update1CCOrderNotes(string $orderId, $input)
    {

        try {
            $core = (new Core);

            $core->validateActive1CCOrderId($orderId);

            (new Order1cc\Validator())->validateInput('editOrderNotes', $input);

            [$notes, $taxDetails] = $this->getOrderNotes($orderId);

            if ($this->merchant->get1ccConfigFlagStatus('one_cc_capture_gstin') === true &&
                isset($input[Order1cc\Fields::GSTIN]) === true) {
                $notes[Order1cc\Fields::GSTIN] = $input[Order1cc\Fields::GSTIN];
            } else {
                unset($notes[Order1cc\Fields::GSTIN]);
            }

            if ($this->merchant->get1ccConfigFlagStatus('one_cc_capture_order_instructions') === true &&
                isset($input[Order1cc\Fields::ORDER_INSTRUCTIONS]) === true) {
                $notes[Order1cc\Fields::ORDER_INSTRUCTIONS] = $input[Order1cc\Fields::ORDER_INSTRUCTIONS];
            } else {
                unset($notes[Order1cc\Fields::ORDER_INSTRUCTIONS]);
            }

            (new Order\Service)->update($orderId, ['notes' => $notes]);


            $this->trace->info(TraceCode::UPDATE_1CC_ORDER_NOTES_REQUEST,
                [
                   'request' =>  $this->maskOrderNotesRequest($orderId, $input)
                ]
            );

        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::UPDATE_1CC_ORDER_NOTES_REQUEST_ERROR,
                [
                    'request' =>  $this->maskOrderNotesRequest($orderId, $input),
                    'exception'=> $e->getTrace()
                ]
            );

            throw $e;
        }

    }

    /**
     * @param string $orderId
     * @throws \Throwable
     */
    protected function getOrderNotes(string $orderId) {
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);
        $orderArray = $order->toArrayPublic();
        $taxDetails = $orderArray['tax_details'] ?? [];
        return [$orderArray['notes'], $taxDetails];
    }

    protected function maskOrderNotesRequest($orderId, $input) {
        $data = [
            'order_id' => $orderId
        ];

        if (isset($input['gstin']) === true) {
            $data['gstin'] = mask_by_percentage($input['gstin']);
        }

        if (isset($input['order_instructions']) === true) {
            $data['order_instructions'] = mask_by_percentage($input['order_instructions']);
        }

        return $data;
    }

    public function updateActionFor1ccOrders($input,$merchant, string $userEmail)
    {
        (new Order1cc\Validator())->validateInput('action', $input);

        $action = $input[Order1cc\Constants::ACTION];

        $orderIds = $input[Order1cc\Fields::ID];

        $responses = [];

        foreach ($orderIds as $orderId) {

            $param = [
                Order1cc\Constants::ACTION => $action,
                Order\Entity::ID => $orderId
            ];

            $response = $this-> updateActionFor1ccOrder($param,$merchant,$userEmail);

            array_push($responses,$response);
        }

        return $responses;
    }

    public function review1ccOrder($input): array
    {

        $merchantId = $input['merchant_id'];

        unset($input['merchant_id']);

        try
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Exception $ex)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
        }

        if ($this->merchant->get1ccConfigFlagStatus('manual_control_cod_order'))
        {
            $this->app['basicauth']->setMerchant($this->merchant);

            return $this->updateActionFor1ccOrders($input, $this->merchant, 'automation_intelligence@razorpay.com');
        }

        $response = [];
        foreach ($input[Order\Entity::ID] as $orderId)
        {
            $orderResponse = [
                Order\Entity::ID => $orderId,
                Order1cc\Constants::ACTION_STATUS => Order1cc\Constants::FAILURE,
                Order1cc\Constants::ACTION_ERROR => [
                    Order1cc\Constants::ACTION_ERROR_CODE => Order1cc\Constants::BAD_REQUEST_MERCHANT_DISABLED_MANUAL_REVIEW,
                ]
            ];
            $response[] = $orderResponse;
        }
        return $response;
    }


    public function updateActionFor1ccOrder($input,$merchant, string $userEmail)
    {
        $response[Order\Entity::ID] =$input[Order\Entity::ID];

        $reviewedAt = time();

        $input[Order1cc\Constants::PLATFORM] = $merchant->getMerchantPlatformConfig()->getValue();

        $merchantId = $merchant->getId();

        try
        {
            $order = (new Order\Repository())->findByPublicId($input[Order\Entity::ID]);
        }
        catch (Throwable $err)
        {
            $response[Order1cc\Constants::ACTION_STATUS] = Order1cc\Constants::FAILURE;
            $response[Order1cc\Constants::ACTION_ERROR] = [
                Order1cc\Constants::ACTION_ERROR_CODE => Order1cc\Constants::BAD_REQUEST_ORDER_NOT_FOUND_CODE,
            ];
            return $response;
        }

        $orderMeta = array_first($order->orderMetas, function ($orderMeta)
        {
            return $orderMeta->getType() === Type::ONE_CLICK_CHECKOUT;
        });

        $value = $orderMeta->getValue();

        if ((isset($value[Order1cc\Fields::REVIEW_STATUS]))&& ($value[Order1cc\Fields::REVIEW_STATUS] !== Order1cc\Constants::HOLD))
        {
            $response[Order1cc\Constants::ACTION_STATUS] = Order1cc\Constants::FAILURE;

            $response[Order1cc\Constants::ACTION_ERROR] = [
                Order1cc\Constants::ACTION_ERROR_CODE => Order1cc\Constants::BAD_REQUEST_ACTION_TAKEN_BY_SOMEONE_CODE,
                Order1cc\Constants::ACTION_ERROR_DATA => [
                    Order1cc\Fields::REVIEW_STATUS => $value[Order1cc\Fields::REVIEW_STATUS],
                    Order1cc\Fields::REVIEWED_AT => $value[Order1cc\Fields::REVIEWED_AT],
                    Order1cc\Fields::REVIEWED_BY => $value[Order1cc\Fields::REVIEWED_BY]
                ]
            ];

            return $response;
        }

        $reviewStatus = Order1cc\Constants::ACTION_INTERMEDIATE_REVIEW_STATUS_MAPPING[$input[Order1cc\Constants::ACTION]];

        $mutexKey = $this->get1ccOrderMutex($input[Order1cc\Fields::ID]);

        try
        {
            $this->mutex->acquireAndRelease($mutexKey,
                function() use ($reviewedAt, $userEmail, $reviewStatus, $merchantId, $input) {
                    $param = [
                        Order1cc\Fields::REVIEW_STATUS  => $reviewStatus,
                        Order1cc\Fields::REVIEWED_AT    => $reviewedAt,
                        Order1cc\Fields::REVIEWED_BY    => $userEmail
                    ];
                    (new Core())->update1CCOrderByMerchantId($input[Order\Entity::ID],$param,$merchantId);
                },
                Order1cc\Constants::ORDER_ACTION_MUTEX_LOCK_TIMEOUT,
                null,
                Order1cc\Constants::ORDER_ACTION_MUTEX_RETRY_COUNT
            );

            $input[Order\Entity::MERCHANT_ID] = $merchantId;

            $this->publishReviewCodOrder($input);

            $response[Order1cc\Constants::ACTION_STATUS] = Order1cc\Constants::SUCCESS;

            return $response;
        }
        catch (Throwable $err)
        {
            $response[Order1cc\Constants::ACTION_STATUS] = Order1cc\Constants::FAILURE;
            $response[Order1cc\Constants::ACTION_ERROR] = [
                Order1cc\Constants::ACTION_ERROR_CODE => Order1cc\Constants::BAD_REQUEST_ACTION_ON_ORDER_IN_PROGRESS_CODE,
            ];

            return $response;
        }
    }

    private function publishReviewCodOrder($input)
    {
        try
        {
            $initialMode = $this->app->environment(Environment::PRODUCTION) === true ? Mode::LIVE : Mode::TEST;

            OneCCReviewCODOrder::dispatch(array_merge($input,
                [
                    'mode' => $initialMode,
                ])
            );

        }
        catch (Throwable $err) {
            $this->trace->error(TraceCode::PUBLISH_ORDER_REVIEW_ACTION_EVENT, [
                'error' => $err->getMessage(),
            ]);
        }
    }

    public function updateReviewStatusFor1ccOrder($input,$merchantId)
    {
        (new Order1cc\Validator())->validateInput('reviewStatus', $input);

        $param = [
            Order1cc\Fields::REVIEW_STATUS  => $input[Order1cc\Fields::REVIEW_STATUS],
        ];

        (new Core())->update1CCOrderByMerchantId($input[Order\Entity::ID],$param,$merchantId);
    }

    public function updatePostCheckoutDetailsFor1ccOrder($input,$merchantId)
    {
        try
        {
            $this->trace->info(TraceCode::ONECC_UPDATE_POST_CHECKOUT_DETAILS,
                [
                    'order_id' =>  $input['id']
                ]
            );

            (new Order1cc\Validator())->validateInput('postCheckoutDetails', $input[Order1cc\Fields::POST_CHECKOUT_DETAILS]);

            $this->trace->count(
                Metric::SHOPIFY_POST_CHECKOUT_STATUS_COUNT,
                [
                    'status' => $input[Order1cc\Fields::POST_CHECKOUT_DETAILS][Order1cc\Fields::STATUS],
                ]);

            $param = [
                Order1cc\Fields::POST_CHECKOUT_DETAILS => $input[Order1cc\Fields::POST_CHECKOUT_DETAILS],
            ];

            (new Core())->update1CCOrderByMerchantId($input[Order\Entity::ID],$param,$merchantId);
        }
        catch (\Throwable $ex)
        {
            $this->trace->error(TraceCode::ONECC_UPDATE_POST_CHECKOUT_DETAILS_ERROR,
                [
                    'order_id' =>  $input['id'],
                    'error' => $ex->getMessage(),
                ]
            );
        }

    }

    protected function get1ccOrderMutex(string $orderId) : string
    {
        return self::MUTEX_PREFIX_1CC . $orderId;
    }

    protected function getStateCodeFromCountryAndState($country, $stateName)
    {
        $states = (new LocationService($this->app))->getStatesByCountry($country);

        foreach ($states as $state)
        {
            if (strtolower($state['name']) === strtolower($stateName))
            {
                return $state['state_code'];
            }
        }

        return 'NA';
    }

    /**
     * @param $shipping_methods
     * @param mixed $shippingMethod
     * @param array $dimensions
     * @return array
     * @throws BadRequestException
     */
    protected function isValidShippingMethod($shipping_methods, mixed $shippingMethod, array $dimensions): array
    {
        $selectedMethod = array_first($shipping_methods, function ($methods) use ($shippingMethod) {
            return $methods[Order1cc\Fields::NAME] === $shippingMethod[Order1cc\Fields::NAME];
        });
        if (empty($selectedMethod) === true) {
            $this->trace->count(Metric::UPDATE_CUSTOMERS_DETAILS_REQUEST_FAULT_COUNT, $dimensions);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SHIPPING_INFO_NOT_FOUND, null, null, "shipping method not found");
        }
        return $selectedMethod;
    }

    /**
     * since order amount is mutable in magic checkout due to application of coupons and shipping charges.
     * Percent based offers were returning discount wrt original amount in preferences call,
     * hence this API will fetch offers associated with order based final order amount on payment screen.
     * @param string $orderId
     * @param array $input
     * @return array
     * @throws \Throwable
     */
    public function getOffersForOrder(string $orderId, array $input): array
    {
        try {

            $action = function () use ($orderId, $input) {

                $data = [];

                $merchant = $this->merchant;

                $input['order_id'] = $orderId;

                $this->validateRequestForFetchOffersForOrder($input);

                (new Merchant\Checkout())->fillPaymentMethodsForOrder($input, $data, $merchant);

                (new Merchant\Checkout())->checkAndFillOfferDetails($merchant, $input, $data, $this->mode);

                $offers = empty($data['offers']) === false ? $data['offers'] : [];

                return ['offers' => $offers];
            };

            $mutexKey = (new CommonUtils())->get1ccUpdateOrderMutex($orderId);

            return $this->mutex->acquireAndRelease(
                $mutexKey,
                $action,
                Order1cc\Constants::ORDER_ACTION_MUTEX_LOCK_TIMEOUT,
                ErrorCode::SERVER_ERROR_MUTEX_RESOURCE_NOT_ACQUIRED,
                Order1cc\Constants::ORDER_ACTION_MUTEX_RETRY_COUNT
            );

        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::FETCH_OFFER_FOR_1CC_ORDER_FAILED,
                [
                    'error' => $e->getMessage(),
                ]
            );
            throw $e;
        }
    }

    /**
     * @param array $input
     * @throws \Throwable
     */
    protected function validateRequestForFetchOffersForOrder(array $input): void
    {

        (new Core)->validateActive1CCOrderId($input['order_id']);

        $order = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);

        if (empty($input['amount']) == true || $order->getAmount() != $input['amount']) {

            $this->trace->error(TraceCode::MAGIC_ORDER_FETCH_OFFER_ERROR,
                [
                    'code'    => ErrorCode::BAD_REQUEST_ERROR,
                    'message' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
                    'order_amount' => $order->getAmount(),
                    'amount' => $input['amount']
                ]
            );

            $this->trace->count(Metric::MAGIC_ORDER_FETCH_OFFER_ERROR_COUNT, [
                'type'    => 'payment_order_amount_mismatch',
            ]);

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH);
        }

    }


    /**
     * @param string $orderId
     * @param array $utmParameters
     * @return void
     * @throws Throwable
     */
    public function updateUtmParametersFor1CCOrder(string $orderId, array $utmParameters=[])
    {
        if (count($utmParameters) === 0)
        {
            return;
        }
        try
        {
            $orderMetaInput = [];

            $orderMetaInput = array_merge($orderMetaInput, [
                Order1cc\Fields::UTM_PARAMETERS => $utmParameters,
            ]);

            (new OneClickCheckoutCore)->update1CcOrder($orderId, $orderMetaInput);
        }
        catch (\Exception $ex){

            $this->trace->count(TraceCode::UPDATE_1CC_ORDER_UTM_PARAMETERS_ERROR_COUNT, [
                'order_id' =>  $orderId
            ]);

            $this->trace->error(TraceCode::UPDATE_1CC_ORDER_UTM_PARAMETERS_REQUEST_ERROR,
                [
                    'order_id' =>  $orderId,
                    'utm_parameters' => $utmParameters,
                    'exception'=> $ex->getTrace()
                ]
            );
            throw $ex;
        }
    }

    public function updateShopifyStatusFor1ccOrder(string $orderId, string $shopifyStatus)
    {
        if (strlen($shopifyStatus) == 0 || $shopifyStatus === null)
        {
            return;
        }
        try {
            $orderMetaInput = [];

            $orderMetaInput = array_merge($orderMetaInput, [
                Order1cc\Fields::SHOPIFY_ORDER_STATUS => $shopifyStatus,
            ]);

            (new OneClickCheckoutCore)->update1CcOrder($orderId, $orderMetaInput);
        }
        catch (\Exception $ex) {
            $this->trace->count(TraceCode::UPDATE_1CC_ORDER_SHOPIFY_STATUS_ERROR_COUNT, [
                'order_id' =>  $orderId
            ]);

            $this->trace->error(TraceCode::UPDATE_1CC_ORDER_SHOPIFY_STATUS_REQUEST_ERROR,
                [
                    'order_id' =>  $orderId,
                    'shopify_order_status' => $shopifyStatus,
                    'exception'=> $ex->getTrace()
                ]
            );
            throw $ex;
        }
    }

    public function getUtmParametersFor1CCOrder(string $orderId):array
    {
        $utmParameters = [];
        $order = (new Order\Repository())->findByPublicId($orderId);

        $orderMeta = array_first($order->orderMetas, function ($orderMeta)
        {
            return $orderMeta->getType() === Type::ONE_CLICK_CHECKOUT;
        });

        $value = $orderMeta->getValue();

        if (isset($value[Order1cc\Fields::UTM_PARAMETERS]))
        {
            $utmParameters = $value[Order1cc\Fields::UTM_PARAMETERS];
        }

        return $utmParameters;
    }

}
