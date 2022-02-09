<?php

namespace RZP\Models\Order;

use App;
use RZP\Constants\Mode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use ApiResponse;
use RZP\Models\Base;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants;
use RZP\Models\BankAccount;
use RZP\Base\ConnectionType;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\Netbanking;

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

        if ((bool) ConfigKey::get(ConfigKey::PG_ROUTER_SERVICE_ENABLED, false) === false)
        {
            return false;
        }

        if (isset($input[Entity::CONVENIENCE_FEE_CONFIG]) === true)
        {
            return false;
        }

        if ($merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === true)
        {
            return false;
        }

        $result = $this->app->razorx->getTreatment($merchant->getId(), RazorxTreatment::ROUTE_ORDER_TO_PG_ROUTER, $this->mode);

        return ($result === 'on');
    }

    public function createOrder(array $input)
    {
        $this->checkRouteIsAccessible($input);

        $routeToPGRouter = $this->canRouteOrderCreationToPGRouter($input, $this->merchant);

        if ($routeToPGRouter === true)
        {
            $this->trace->info(TraceCode::ORDER_ROUTING_TO_PG_ROUTER);

            $this->modifyBankAccountRequestFromOldFormat($input);

            $input['public_key'] = App::getFacadeRoot()['basicauth']->getPublicKey();

            $input['merchant_id'] = $this->merchant->getId();

            return $this->app['pg_router']->createOrder($input, true);
        }

        $this->beforeCreate($input);

        $orderInput = (new Core())->getInputWithoutExtraParams($input);

        $order = $this->processCreate($orderInput);

        $order = $this->afterCreate($input, $order);

        return $order;
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

        $this->trace->info(TraceCode::ORDER_CREATION_INITIATED, [
            "merchant_id" => $this->merchant->getId(),
        ]);

        $result = $order->toArrayPublic();

        if(isset($input[Entity::CONVENIENCE_FEE_CONFIG]) === true and
            empty($input[Entity::CONVENIENCE_FEE_CONFIG]) === false)
        {
            $result[Entity::CONVENIENCE_FEE_CONFIG] = $input[Entity::CONVENIENCE_FEE_CONFIG];
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

    public function fetchMultiple($input)
    {
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

    public function fetchPaymentsFor(string $id, array $input): array
    {
        $input[Payment\Entity::ORDER_ID] = $id;

        $payments = $this->repo->payment->fetch($input, $this->merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $isPrivateAuth = $this->app['basicauth']->isPrivateAuth();

        if ($isPrivateAuth === true)
        {
            $tidbPaymentIds = $payments->pluck(Payment\Entity::ID);

            $apiPayments = $this->repo->payment->fetchPaymentsGivenIds($tidbPaymentIds->toArray(), $tidbPaymentIds->count());

            $apiPaymentIds = $apiPayments->pluck(Payment\Entity::ID);

            $diffPaymentIds = array_diff($tidbPaymentIds->toArray(), $apiPaymentIds->toArray());

            foreach ($diffPaymentIds as $paymentId)
            {
                $payment = $this->app['pg_router']->fetch(Constants\Entity::PAYMENT, $paymentId, '', array());

                if ($payment !== null)
                {
                    $apiPayments->push($payment);
                }
            }

            return $apiPayments->toArrayPublic();

        }
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
}
