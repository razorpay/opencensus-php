<?php

namespace RZP\Models\Order;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Diag\EventCode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants;
use RZP\Models\BankAccount;
use RZP\Base\ConnectionType;
use RZP\Models\Bank\BankCodes;
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

    public function createOrder(array $input)
    {

        $this->beforeCreate($input);

        $orderInput = (new Core())->getInputWithoutExtraParams($input);

        $order = $this->processCreate($orderInput);

        $order = $this->afterCreate($input, $order);

        return $order;
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
            $order = $this->repo->order->find($orderId);
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
}
