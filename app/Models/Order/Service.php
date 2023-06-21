<?php

namespace RZP\Models\Order;

use App;
use RZP\Constants\Mode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use ApiResponse;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Models\Base;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Order\OrderMeta\Order1cc;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Services\RazorXClient;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;
use RZP\Constants;
use RZP\Models\BankAccount;
use RZP\Base\ConnectionType;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Offer;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;

class Service extends Base\Service
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    private function beforeCreate(array $input)
    {
        $preCreateHooks = new PreCreateHook($input);

        $preCreateHooks->process();

        return;
    }

    private function afterCreate(array $input, Entity $order): Entity
    {
        $postCreateHooks = new PostCreateHook($input, $order);

        $postCreateHooks->process();

        return $order;
    }

    private function processCreate(array $input): Entity
    {
        $properties = $input;

        $properties['user_agent']  = $this->app['request']->header('User-Agent');

        $properties['merchant_id'] = $this->merchant->getId();

        $this->app['diag']->trackOrderEvent(EventCode::ORDER_CREATION_INITIATED, null, null, $properties);

        try
        {
            $merchant = $this->merchant;

            $this->modifyOfferRequestFromOldFormat($input);

            $this->modifyBankAccountRequestFromOldFormat($input);

            $order = (new Core)->create($input, $merchant);

            $this->app['diag']->trackOrderEvent(EventCode::ORDER_CREATION_PROCESSED, $order, null, $properties);
        }
        catch (\Throwable $ex)
        {
            $properties['merchant'] = $this->merchant->getMerchantProperties();

            $this->app['diag']->trackOrderEvent(EventCode::ORDER_CREATION_PROCESSED, null, $ex, $properties);

            throw $ex;
        }

        return $order;
    }

    public function canRouteOrderCreationToPGRouter($input, $merchant)
    {
        if ((app()->isEnvironmentProduction() === true) and
            ($this->mode === Mode::TEST))
        {
            return false;
        }

        if ($this->isRearchBVTRequest() === true)
        {
            return true;
        }

        if ($this->app->runningUnitTests() === true)
        {
            return false;
        }

        $result = $this->app->razorx->getTreatment($merchant->getId(), RazorxTreatment::ROUTE_ORDER_TO_PG_ROUTER_REVERSE, $this->mode);

        if ($result === 'on')
        {
            return false;
        }

        return true;
    }

    protected function isRearchBVTRequest(): bool
    {
        $rzpTestCaseID = $this->app['request']->header(RequestHeader::X_RZP_REARCH_ORDER_TESTCASE_ID);
        if(empty($rzpTestCaseID) === true)
        {
            return false;
        }

        return (app()->isEnvironmentQA() === true && str_ends_with(strtolower($rzpTestCaseID),'rearch_order'));
    }

    public function createOrder(array $input)
    {
        $this->checkRouteIsAccessible($input);

        $routeToPGRouter = $this->canRouteOrderCreationToPGRouter($input, $this->merchant);

        if ($routeToPGRouter === true)
        {
            $this->trace->info(TraceCode::ORDER_ROUTING_TO_PG_ROUTER);

            $this->modifyBankAccountRequestFromOldFormat($input);

            $this->checkForDefaultOffers($input);

            $input['public_key'] = (new Core())->getOrderPublicKey($this->merchant);

            $input['merchant_id'] = $this->merchant->getId();

            $order = $this->app['pg_router']->createOrder($input, true);

            $dimensions = [
                'source' => 'rearch'
            ];
            $this->trace->count(TraceCode::ORDERS_CREATED_COUNT, $dimensions);

            return $order;
        }

        $this->beforeCreate($input);

        $orderInput = (new Core())->getInputWithoutExtraParams($input);

        $order = $this->processCreate($orderInput);

        $order = $this->afterCreate($input, $order);

        $dimensions = [
            'source' => 'api'
        ];
        $this->trace->count(TraceCode::ORDERS_CREATED_COUNT, $dimensions);

        return $order;
    }

    public function checkForDefaultOffers(array & $input)
    {
        $defaultOffers = (new Offer\Core())->fetchDefaultOffersForMerchant($this->merchant->getId());

        $offerCore = (new Offer\Core);

        $offers = array();

        $order = (new Entity())->forceFill($input);

        $order->merchant()->associate($this->merchant);

        foreach($defaultOffers as $offer)
        {
            $offer = $offerCore->validateDefaultOfferForOrder($order, $offer);

            if($offer !== null)
            {
                array_push($offers, $offer);
            }
        }

        if (count($offers) > 0)
        {
            $input['default_offers'] = true;
        }
    }

    public function checkRouteIsAccessible( $input){

        if(isset($input['transfers']))
        {
            $ret = $this->validateOrgMerchantFeatureAccess();

            if($ret != null)
            {
                throw new BadRequestException(ErrorCode::BAD_FEATURE_PERMISSION_NOT_FOUND);
            }
        }

    }

    public function checkRouteIsAccessibleWithExpand($input){

        $expands = $input['expand'] ?? [];

        if( isset($expands[0]) && $expands[0] === 'transfers')
        {
            $ret = $this->validateOrgMerchantFeatureAccess();

            if($ret != null)
            {
                throw new BadRequestException(ErrorCode::BAD_FEATURE_PERMISSION_NOT_FOUND);
            }
        }
    }

    public function validateOrgMerchantFeatureAccess(){

        $orgId = $this->merchant->getOrgId();

        $org = $this->repo->org->findOrFailPublic($orgId);

        $orgEnableFeatures = $org->getEnabledFeatures();

        $orgRouteFeatures = array_intersect([Feature\Constants::WHITE_LABELLED_ROUTE], $orgEnableFeatures);
        if (empty($orgRouteFeatures) === true)
        {
            return null;
        }

        $merchantFeatures = $this->merchant->getEnabledFeatures();

        $routeFeatures = array_intersect($orgRouteFeatures, $merchantFeatures);

        // if org has any enabled feature for route
        if (empty($routeFeatures) === false)
        {
            return null;
        }

        $this->trace->info(TraceCode::ORG_LEVEL_WHITELISTING_FEATURE_ACCESS_VALIDATION_FAILURE, [
            \RZP\Models\Merchant\Entity::ORG_ID      => $this->merchant->getOrgId(),
            Entity::MERCHANT_ID => $this->merchant->getId(),
        ]);

        return ApiResponse::featurePermissionNotFound();
    }

    public function create(array $input)
    {
        $order = $this->createOrder($input);

        $result = $order->toArrayPublic();

        if(isset($input[Entity::CONVENIENCE_FEE_CONFIG]) === true and
            empty($input[Entity::CONVENIENCE_FEE_CONFIG]) === false)
        {
            $result[Entity::CONVENIENCE_FEE_CONFIG] = $input[Entity::CONVENIENCE_FEE_CONFIG];
        }

        if(isset($input[Entity::CUSTOMER_ADDITIONAL_INFO]) === true and
            empty($input[Entity::CUSTOMER_ADDITIONAL_INFO]) === false)
        {
            $result[Entity::CUSTOMER_ADDITIONAL_INFO] = $input[Entity::CUSTOMER_ADDITIONAL_INFO];
        }

        $pgRouterPublicResponse = $order->getAttribute("public_response");

        if(isset($pgRouterPublicResponse))
        {
            if (isset($pgRouterPublicResponse['offers']) === true)
            {
                sort($pgRouterPublicResponse['offers']);
            }

            if (isset($result['offers']) === true)
            {
                sort($result['offers']);
            }

            $responseParity = $result == $pgRouterPublicResponse;
            $responseParityWithTripleCheck = $result === $pgRouterPublicResponse;

            $this->trace->info(TraceCode::ORDER_RESPONSE_PARITY, [
                "ARRAY_DIFF_API_PGROUTER" => array_diff($result, $pgRouterPublicResponse),
                "ARRAY_DIFF_PGROUTER_API" => array_diff($pgRouterPublicResponse, $result),
                "SAME_VALUE" => $responseParity,
                "SAME_VALUE_AND_TYPE" => $responseParityWithTripleCheck,
                "ORDER_RESPONSE" => $responseParityWithTripleCheck ? "SUCCESS" : $result
            ]);
        }
        else
        {
            $this->trace->info(TraceCode::ORDER_RESPONSE_PARITY, [
                "Error" => "Empty Public Response"
            ]);
        }

        return $result;
    }

    /**
     * Old format:
     * {
     *   "payer_name": "string"
     *   "bank_code": "SBIN"
     *   "account_number": "string"
     * }
     *
     * New format:
     * {
     *   "bank_account": {
     *     "account_number": "string",
     *     "ifsc_code" : "ifsc_code",
     *     "beneficiary_name" : "string"
     *   }
     * }
     *
     * Both formats are to be concurrently supported.
     * Here, we create the new format from the old one,
     * Old format will continue to work the way it did
     * until gateway side changes are made.
     *
     * @param  array $input
     */
    protected function modifyBankAccountRequestFromOldFormat(array & $input)
    {
        if ($this->isOldFormatBankAccountRequest($input) === false)
        {
            $this->addBankCodeFromBankAccount($input);

            return;
        }

        (new Validator())->validateBank($input);

        if (isset($input[Entity::BANK_ACCOUNT]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payer Name, Account Number and Bank is not required if you are sending Bank Account Entity.', null, [
                Entity::BANK_ACCOUNT    => $input[Entity::BANK_ACCOUNT],
            ]);
        }

        $ifsc = BankCodes::getIfscForBankCode($input[Entity::BANK]);

        if (empty($ifsc) === true)
        {
            throw new Exception\LogicException(
                'Should not have reached here.', null, [
                Entity::BANK    => $input[Entity::BANK],
            ]);
        }

        $additionalInput = [
            Entity::BANK_ACCOUNT    => [
                BankAccount\Entity::ACCOUNT_NUMBER          =>  $input[Entity::ACCOUNT_NUMBER],
                BankAccount\Entity::IFSC                    =>  $ifsc,
                BankAccount\Entity::NAME                    =>  $input[Entity::PAYER_NAME] ?? '',
            ],
        ];

        $oldBankFormat = [
                Entity::ACCOUNT_NUMBER => (isset($input[Entity::ACCOUNT_NUMBER])?$input[Entity::ACCOUNT_NUMBER]:""),
                Entity::PAYER_NAME => (isset($input[Entity::PAYER_NAME])?$input[Entity::PAYER_NAME]:""),
        ];

        $input[Entity::OLD_BANK_FORMAT] = $oldBankFormat;

        unset($input[Entity::ACCOUNT_NUMBER]);

        unset($input[Entity::PAYER_NAME]);

        $input = array_merge($input, $additionalInput);

    }

    /**
     * Old format:
     * {
     *   "payer_name": "string"
     *   "bank_code": "SBIN"
     *   "account_number": "string"
     * }
     *
     * New format:
     * {
     *   "bank_account": {
     *     "account_number": "string",
     *     "ifsc_code" : "ifsc_code",
     *     "beneficiary_name" : "string"
     *   }
     * }
     *
     * Both formats are to be concurrently supported.
     * Here, we create the old format from the new one,
     * Old format will continue to work the way it did
     * until gateway side changes are made.
     *
     * @param  array $input
     */
    protected function addBankCodeFromBankAccount(array & $input)
    {
        if (isset($input[Entity::BANK_ACCOUNT]) === false)
        {
            return;
        }

        if (isset($input[Entity::BANK_ACCOUNT][BankAccount\Entity::NAME]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The bank account.name field is required when bank account is present.',
                Entity::BANK_ACCOUNT . '.' . BankAccount\Entity::NAME
            );
        }

        $this->updateIfscMappingIfApplicable($input);

        if ($this->merchant->isFeatureEnabled(Feature\Constants::ENABLE_IFSC_VALIDATION) === true)
        {
            (new BankAccount\Validator())->validateIfscCode($input[Entity::BANK_ACCOUNT], $this->mode);
        }

        // Get Bank Code from IFSC here.
        $bankCode   = strtoupper(substr($input[Entity::BANK_ACCOUNT][BankAccount\Entity::IFSC], 0, 4));

        if (array_key_exists($bankCode, Netbanking::$defaultInconsistentBankCodesMapping) === true)
        {
            $bankCode = Netbanking::$defaultInconsistentBankCodesMapping[$bankCode];
        }

        $input[Entity::BANK] =  $bankCode;
    }

    /**
     * Old format:
     * {
     *   "offer_id": "offer_AJDTUWZjgei84L"
     * }
     *
     * New format:
     * {
     *   "offers": [
     *     "offer_AJDTUWZjgei84L"
     *   ]
     * }
     *
     * Both formats are to be concurrently supported.
     * Here, we convert the old format to the new one, and force_offer explicitly,
     * so that the old format continues to work the way it did.
     *
     * @param  array  $input
     */
    protected function modifyOfferRequestFromOldFormat(array &$input)
    {
        if ($this->isOldFormatOfferRequest($input) === false)
        {
            return;
        }

        if (isset($input[Entity::OFFERS]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Request should send either offer_id or offers', null, [
                    Entity::OFFER_ID => $input[Entity::OFFER_ID],
                    Entity::OFFERS   => $input[Entity::OFFERS],
                ]);
        }

        $additionalInput = [
            Entity::FORCE_OFFER => true,
            Entity::OFFERS      => [
                $input[Entity::OFFER_ID],
            ],
        ];

        $input = array_merge($input, $additionalInput);

        unset($input[Entity::OFFER_ID]);
    }

    protected function isOldFormatOfferRequest(array $input): bool
    {
        return isset($input[Entity::OFFER_ID]) ? true : false;
    }

    protected function isOldFormatBankAccountRequest(array $input): bool
    {
        return ((isset($input[Entity::PAYER_NAME]) === true) or
               (isset($input[Entity::ACCOUNT_NUMBER]) === true));
    }

    public function fetch($id, array $input = [])
    {
        $this->checkRouteIsAccessibleWithExpand($input);

        $order = $this->repo->order->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $order->toArrayPublic();
    }

    public function fetchById($id)
    {
        $order = $this->repo->order->findByPublicId($id);

        return $order->toArrayPublic();
    }

    public function fetchByIdInternal($id)
    {
        $order = $this->repo->order->findByPublicId($id);

        return $order->toArrayInternal();
    }

    public function fetchByIdForAdmin($id, $input)
    {
        $orderId = Entity::verifyIdAndSilentlyStripSign($id);

        if (isset($input['merchant_id']) === true)
        {
            $order = $this->repo->order->findByIdAndMerchantId($orderId, $input['merchant_id']);

        }
        else
        {
            $order = $this->repo->order->findOrFail($orderId);
        }

        $checkoutConfigId = $order->getAttribute(Entity::CHECKOUT_CONFIG_ID);

        $orderAdminArray = $order->toArrayAdmin();

        $orderAdminArray['checkout_config_id'] = $checkoutConfigId;

        return $orderAdminArray;
    }

    public function fetchOrderDetailsForCheckout($input): array
    {
        $validator = new Validator();
        $validator->validateInput('fetch_order_details_for_checkout', $input);

        /** @var Entity $order */
        $order = null;

        if (empty($input['order'])) {
            if (!empty($input['order_id'])) {
                 $order = $this->repo->order->findByPublicId($input['order_id']);

                 $input['order'] = $order->toArrayPublic();
            } elseif (!empty($input['subscription_id'])) {
                $subscriptionId = $input['subscription_id'];

                if (($pos = strpos($subscriptionId, "_")) !== false) {
                    $subscriptionId = substr($subscriptionId, $pos + 1);
                }

                /** @var InvoiceEntity $invoice */
                $invoice = $this->repo->invoice->fetchIssuedInvoicesOfSubscriptionId($subscriptionId);

                if ($invoice === null || empty($invoice->getOrderId())) {
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_ORDER_DOES_NOT_EXIST, 'subscription_id');
                }

                $orderId = 'order_' . $invoice->getOrderId();

                $order = $this->repo->order->findByPublicId($orderId);

                $input['order'] = $order->toArrayPublic();
            }
        }

        // create order entity using forcefill
        $order = $order ?? $this->app['pg_router']->getOrderEntityFromOrderAttributes($input['order']);

        if ($order === null || empty($order->getId())) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ORDER_DOES_NOT_EXIST);
        }

        $validator->validateNachStatusForCheckout($order);

        $core = new Core();

        $response = $core->getFormattedDataForCheckout($order, $this->merchant);

        $expand = $input['expand'] ?? [];

         if (in_array('order', $expand, true)) {
             $orderAttributes = array_merge($order->toArray(), $order->toArrayInternal());
             Entity::stripSignWithoutValidation($orderAttributes['id']);

             if (!empty($orderAttributes[Entity::ORDER_META_1CC])) {
                 // Keeping response contract same as PG Router's response
                 $orderAttributes['order_metas'] = $orderAttributes[Entity::ORDER_META_1CC];
                 unset($orderAttributes[Entity::ORDER_META_1CC]);
             }

             $accountNumber = $orderAttributes[Entity::ACCOUNT_NUMBER] ?? '';

             if ($accountNumber !== '') {
                 $orderAttributes[Entity::ACCOUNT_NUMBER] = $core->getMaskedAccountNumber($accountNumber);
             }

             $response['order'] = $orderAttributes;
         }

        return $response;
    }

    public function fetchMultiple($input)
    {
        $isMerchantDashboard = $this->app['basicauth']->isMerchantDashboardApp();
        if ($isMerchantDashboard) {
            // We are passing this filter to not show the internal orders in merchant dashboard
            $input[Entity::REFERENCE8] = 'null';
        }

        $orders = $this->repo->order->fetch($input, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $orders->toArrayPublic();
    }

    public function fetchWithOffer($id, $input)
    {
        // Magic Checkout (1CC) specific route for dashboard to fetch orders along with offers that were applied.
        $this->checkRouteIsAccessibleWithExpand($input);

        $order = $this->repo->order->findByPublicId($id);

        $orderArray = $order->toArrayPublic();

        $orderId = $order->getId();

        $payments = $this->repo->payment->fetchPaymentsForOrderId($orderId);

        $orderArray['offer'] = null;

        if (empty($payments) === false)
        {
            $successfulPayment = array_first($payments, function ($payment, $key)
            {
                return in_array($payment->getStatus(), [Payment\Status::CAPTURED, Payment\Status::AUTHORIZED, Payment\Status::PENDING, Payment\Status::REFUNDED]);
            });

            if ($successfulPayment !== null)
            {
                $offer = $successfulPayment->getOffer();
                if (empty($offer) === false)
                {
                    $discount = $offer->getDiscount($order['amount'], $offer->getPercentRate());
                    if ($offer->getMaxCashback() !== null)
                    {
                        $discount = min($discount, $offer->getMaxCashback());
                    }
                    $orderArray['offer'] = [
                        'id'       => $offer->getPublicId(),
                        'name'     => $offer->getName(),
                        'type'     => $offer->getOfferType(),
                        'discount' => $discount,
                    ];
                }
            }
        }


        return $orderArray;
    }

    // Fetches payments from api and pg-router for a given order_id
    public function fetchPaymentsFor(string $id, array $input): array
    {
        $input[Payment\Entity::ORDER_ID] = $id;

        $isPrivateAuth = $this->app['basicauth']->isPrivateAuth();

        if ($isPrivateAuth === true)
        {
            $orderId = Entity::verifyIdAndSilentlyStripSign($id);

            $apiPayments = $this->repo->payment->fetchPaymentsForOrderId($orderId);

            $rearchPayments = $this->app['pg_router']->fetchOrderPayments($orderId, $this->merchant->getId());

            $res = $apiPayments->merge($rearchPayments);

            return $res->toArrayPublic();

        }

        $payments = $this->repo->payment->fetch($input, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $payments->toArrayPublic();
    }

    public function fetchLineItemsFor(string $id): array
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($id, $this->merchant);

        return $order->lineItems->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $orderId = Entity::verifyIdAndStripSign($id);

        $order = $this->repo->order->findByIdAndMerchant($orderId, $this->merchant);

        if ($order->isExternal() === true)
        {
            $order = $this->app['pg_router']->updateOrder($input, $orderId, $this->merchant->getId(), true);

            return $order->toArrayPublic();
        }

        $order = $this->mutex->acquireAndRelease($orderId,
            function() use ($orderId, $input)
            {
                $order = $this->repo->order->findByIdAndMerchant($orderId, $this->merchant);

                $order->edit($input);

                $this->repo->saveOrFail($order);

                return $order;
            },
            20,
            ErrorCode::BAD_REQUEST_ORDER_ANOTHER_OPERATION_IN_PROGRESS);

        return $order->toArrayPublic();
    }

    // This function is being used by Create Payment Link flow with options containing an Order
    public function createOrderFromOptionsForPaymentLinks(array $input, bool $enablePartialPayment = false)
    {
        $routeToPGRouter = $this->canRouteOrderCreationToPGRouter($input, $this->merchant);

        if ($routeToPGRouter === true)
        {
            $this->modifyBankAccountRequestFromOldFormat($input);

            $input['merchant_id'] = $this->merchant->getId();

            $input['public_key'] = App::getFacadeRoot()['basicauth']->getPublicKey();

            $input['partial_payment'] = $enablePartialPayment;

            return $this->app['pg_router']->createOrder($input, true);
        }

        $this->beforeCreate($input);

        $orderInput = (new Core())->getInputWithoutExtraParams($input);

        $order = $this->processCreateFromOptionsForPaymentLinks($orderInput, $enablePartialPayment);

        $order = $this->afterCreate($input, $order);

        return $order;
    }

    private function processCreateFromOptionsForPaymentLinks(array $input, bool $enablePartialPayment): Entity
    {
        $properties = $input;

        $properties['user_agent'] = $this->app['request']->header('User-Agent');

        $this->app['diag']->trackOrderEvent(EventCode::ORDER_CREATION_INITIATED, null, null, $properties);

        try
        {
            $merchant = $this->merchant;

            $this->modifyOfferRequestFromOldFormat($input);

            $this->modifyBankAccountRequestFromOldFormat($input);

            $order = (new Core)->create($input, $merchant, $enablePartialPayment);

            $this->app['diag']->trackOrderEvent(EventCode::ORDER_CREATION_PROCESSED, $order);
        }
        catch (\Throwable $ex)
        {
            $properties = [];

            $properties['merchant'] = $this->merchant->getMerchantProperties();

            $this->app['diag']->trackOrderEvent(EventCode::ORDER_CREATION_PROCESSED, null, $ex, $properties);

            throw $ex;
        }

        return $order;
    }

    public function bulkSyncOrderToPgRouter(array $input)
    {
        return (new Core)->fetchOrdersAndSync($input);
    }

    public function fetchProductDetailsForOrder(string $orderId)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        return (new Core)->fetchProductDetailsForOrder($order, $this->merchant);
    }

    /**
     * Due to mergers, bank IFSC codes get updated. Merchants tend to send the old IFSC code in request
     * due to which payments fail. The long term and ideal solution is to educate merchant to send
     * correct IFSC code. As part of short term solution, we are keeping the mapping in the codebase.
     * @param  array $input
     */

    public function updateIfscMappingIfApplicable(array & $input)
    {
        $method = isset($input['method']) ? $input['method'] : null;

        //Currently enabling this change only for UPI.
        if (in_array(array_get($input, 'method'), ['upi'], true) === false )
        {
            return;
        }

        $this->updateIfscIfRequired($input);
    }

    public function updateIfscIfRequired(array & $input)
    {
        if (isset($input[Entity::BANK_ACCOUNT][BankAccount\Entity::IFSC]) === true)
        {
            $oldIfsc = $input[Entity::BANK_ACCOUNT][BankAccount\Entity::IFSC];

            if (array_key_exists($oldIfsc, BankAccount\OldNewIfscMapping::$oldToNewIfscMapping) === true)
            {
                $newIfsc =  BankAccount\OldNewIfscMapping::getNewIfsc($oldIfsc);

                $this->trace->info(TraceCode::BANK_ACCOUNT_OLD_TO_NEW_IFSC_BEING_USED, [
                    'old_ifsc' => $oldIfsc,
                    'new_ifsc' => $newIfsc,
                ]);

                $input[Entity::BANK_ACCOUNT][BankAccount\Entity::IFSC] = $newIfsc;
            }
        }
    }

    public function internalOrderUpdate(string $id, array $input): array
    {
        $orderId = Entity::verifyIdAndSilentlyStripSign($id);

        $order = $this->mutex->acquireAndRelease($orderId,
            function() use ($orderId, $input)
            {
                $order = $this->repo->order->findByIdAndMerchantId($orderId, $input['merchant_id']);

                $order->edit($input,"internal_edit");

                $this->repo->saveOrFail($order);

                return $order;
            },
            20,
            ErrorCode::BAD_REQUEST_ORDER_ANOTHER_OPERATION_IN_PROGRESS);

        return $order->toArrayPublic();
    }

    public function internalOrderValidateTokenParams($input)
    {
        $preCreateHooks = new PreCreateHook($input);

        if (isset($input['token']) === true)
        {
            $preCreateHooks->validateTokenParams($input['token']);
        }

        return true;
    }

    public function internalOrderValidateTransferParams($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        return (new Core)->internalOrderValidateTransferParams($input);
    }

    public function internalOrderValidateBank($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $validator = new Validator();

        $validator->merchant = $merchant;

        $validator->validateBank($input);

        return true;
    }

    public function internalOrderValidateAmount($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $validator = new Validator();

        $validator->merchant = $merchant;

        $validator->validateAmount($input);

        return true;
    }

    public function internalOrderValidateCurrency($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $validator = new Validator();

        $validator->merchant = $merchant;

        $validator->validateCurrency($input);

        return true;
    }

    public function internalOrderValidateCheckoutConfig($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        (new Core)->validateCheckoutConfigId($input[Entity::CHECKOUT_CONFIG_ID],$merchant);

        return true;
    }

    public function internalOrderValidateTPV($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        (new Core)->internalOrderValidateTPVChecks($input);

        return true;
    }

    public function internalCreateOrderRelations($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

       return (new Core)->internalCreateOrderRelations($input);
    }

    public function internalCreateOrderBankAccountRelations($input)
    {
        if (isset($input['merchant_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(PublicErrorDescription::BAD_REQUEST_MERCHANT_ID_IS_REQUIRED);
        }

        return (new Core)->internalCreateOrderBankAccountRelations($input);
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingOrderDetails($input)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = $this->getSelfServeActionForFetchingOrderDetail($input);

        if (isset($segmentProperties[SegmentConstants::SELF_SERVE_ACTION]) === true)
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $this->merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    public function sendSelfServeSuccessAnalyticsEventToSegmentForFetchingOrderDetailsFromOrderId()
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Order Details Searched';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $this->merchant, $segmentProperties, $segmentEventName
        );
    }

    private function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    private function getSelfServeActionForFetchingOrderDetail($input)
    {
        if ((isset($input[Entity::RECEIPT]) === true) or
            (isset($input[Entity::NOTES]) === true))
        {
            return 'Order Details Searched';
        }

        if (isset($input[Entity::STATUS]) === true)
        {
            return 'Order Details Filtered';
        }
    }

    public function getCODOrders($input){

        $params = $this->removeEmptyParams($input);

        $this->addDefaultParamCount($params);

        (new Order1cc\Validator)->validateInput('getCODOrder', $params);

        if ((isset($params[Entity::ID])))
        {
            $params[Entity::ID] = substr($input[Entity::ID],6);
        }

        $orders = $this->repo->order->getPaginatedCODOrders($params, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);


        $paginatedOrder = $this->toOneCCOrderArray($orders);

        return $paginatedOrder;

    }

    public function getPrepayOrders($input){

        $params = $this->removeEmptyParams($input);

        $this->addDefaultParamCount($params);

        (new Order1cc\Validator)->validateInput('getPrepayOrders', $params);

        if (isset($params[Entity::ID]))
        {
            $params[Entity::ID] = substr($input[Entity::ID],6);
        }

        $orders = $this->repo->order->getPaginatedPrepayOrders($params, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $paginatedOrder = $this->toPrepayOrderArray($orders);

        return $paginatedOrder;

    }

    public function getPrepayOrder(string $orderId){

        $orderId = substr($orderId,6);

        $order = $this->repo->order->getPrepayOrder($orderId, $this->merchant->getId(),ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $order?->toCodOrderArray();
    }

    private function addDefaultParamCount(array & $params)
    {
        if (isset($params[Entity::COUNT]) === true)
        {
            return;
        }

        $params[Entity::COUNT] = 20;
    }

    private function removeEmptyParams(array $params): array
    {

        foreach ($params as $key => $value)
        {
            if (!($params[$key] !== ''))
            {
                unset($params[$key]);
            }
        }

        return $params;
    }

    public function toOneCCOrderArray(Base\PublicCollection $publicCollection)
    {
        $array = [];

        $collectionClosure = function () {
            $array[Order1cc\Constants::ENTITY] = $this->entity;
            $array[Order1cc\Constants::COUNT] =  count($this->items);
            $array[Order1cc\Constants::HAS_MORE] = $this->getHasMore();
            $array[Order1cc\Constants::ITEMS] = $this->items;
            return $array;
        };

        $collectionArray = $collectionClosure->call($publicCollection);
        $array[Order1cc\Constants::ENTITY] = $collectionArray[Order1cc\Constants::ENTITY];
        $array[Order1cc\Constants::COUNT] = count($collectionArray[Order1cc\Constants::ITEMS]);
        $array[Order1cc\Constants::HAS_MORE] = $collectionArray[Order1cc\Constants::HAS_MORE];
        $array[Order1cc\Constants::ITEMS] = array_map(function($item)
        {
            return $item->toCodOrderArray();

        }, $collectionArray[Order1cc\Constants::ITEMS]);

        return $array;
    }

    public function toPrepayOrderArray(Base\PublicCollection $publicCollection)
    {
        $array = [];

        $collectionClosure = function () {
            $array[Order1cc\Constants::ENTITY] = $this->entity;
            $array[Order1cc\Constants::COUNT] =  count($this->items);
            $array[Order1cc\Constants::HAS_MORE] = $this->getHasMore();
            $array[Order1cc\Constants::ITEMS] = $this->items;
            return $array;
        };

        $collectionArray = $collectionClosure->call($publicCollection);
        $array[Order1cc\Constants::ENTITY] = $collectionArray[Order1cc\Constants::ENTITY];
        $array[Order1cc\Constants::COUNT] = count($collectionArray[Order1cc\Constants::ITEMS]);
        $array[Order1cc\Constants::HAS_MORE] = $collectionArray[Order1cc\Constants::HAS_MORE];
        $array[Order1cc\Constants::ITEMS] = array_map(function($item)
        {
            return $item->toPrepayOrderArray();

        }, $collectionArray[Order1cc\Constants::ITEMS]);

        return $array;
    }
}
