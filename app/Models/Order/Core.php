<?php

namespace RZP\Models\Order;

use App;
use Illuminate\Support\Arr;
use RZP\Base\ConnectionType;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature\Constants;
use RZP\Models\Offer;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Services\PGRouter;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Payment\Config;
use RZP\Jobs\SyncOrderPgRouter;
use RZP\Models\Merchant\Methods;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Jobs\UpdateSyncedOrderPgRouter;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\OrderOutbox\Constants as OrderOutboxConstants;
use RZP\Models\SubscriptionRegistration;

class Core extends Base\Core
{
    const RECEIPT_MUTEX_TIMEOUT  = 10; // 10 seconds timeout

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param boolean         $partialPayment
     *
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant, bool $partialPayment = false, $dummyProcessing=false)
    {
        $orderService = new Service();

        $routeToPGRouter = $orderService->canRouteOrderCreationToPGRouter($input, $merchant);

        if ($routeToPGRouter === true)
        {
            $this->trace->info(TraceCode::ORDER_ROUTING_TO_PG_ROUTER);

            $input['public_key'] = App::getFacadeRoot()['basicauth']->getPublicKey();

            $orderService->checkForDefaultOffers($input);

            $input['merchant_id'] = $merchant->getId();

            $input['partial_payment'] = $partialPayment;

            return $this->app['pg_router']->createOrder($input, true);
        }

        unset($input[Entity::OLD_BANK_FORMAT]);

        $inputTrace = $input;

        unset($inputTrace['bank_account']['account_number'], $inputTrace['bank_account']['name'],
                $inputTrace['notes'], $inputTrace['receipt'], $inputTrace['cardnumber'], $inputTrace['products']);

        $this->trace->info(
            TraceCode::ORDER_CREATE_REQUEST,
            $inputTrace
        );

        if (isset($input[Entity::CHECKOUT_CONFIG_ID]) === true)
        {
            $this->validateCheckoutConfigId($input[Entity::CHECKOUT_CONFIG_ID]);
        }

        //Unsetting CUSTOMER_ADDITIONAL_INFO from input
        if (isset($input[Entity::CUSTOMER_ADDITIONAL_INFO]) === true)
        {
            $orderOfflineInput = $input[Entity::CUSTOMER_ADDITIONAL_INFO];
            unset($input[Entity::CUSTOMER_ADDITIONAL_INFO]);
        }

        $order = new Entity;

        // Needs to be associated first cause merchant entity is required
        // in orders create validators.
        $order->merchant()->associate($merchant);

        // Extracting 1cc specific fields to not interfere with Order creation.
        list($orderMeta1ccInput, $input) = (new OrderMeta\Core())->extract1ccFields($input);

        $order->build($input);

        // Re-merging 1CC specific fields.
        $input = array_merge($orderMeta1ccInput, $input);

        // Re-merging CUSTOMER_ADDITIONAL_INFO specific fields.
        if (empty($orderOfflineInput) === false) {

            $input[Entity::CUSTOMER_ADDITIONAL_INFO] = $orderOfflineInput;
        }

        $order->setPublicKey($this->getOrderPublicKey($merchant));

        $order->generateId();

        $this->validateReceiptUniqueness($order);

        if ($partialPayment === true)
        {
            $order->allowPartialPayment();
        }

        if (isset($input['payment_capture']) === false)
        {
            $order->setAttribute(Entity::PAYMENT_CAPTURE, null);
        }

        $this->createLateAuthConfigIfApplicable($input, $order);

        $this->createConvenienceFeeConfigIfApplicable($input, $order);


        list($order, $ba) = $this->repo->transaction(function() use ($order, $input, $dummyProcessing)
        {
            //The variable pushToQueue is added since we want to delay razorx call and queue push till
            //transaction completion.
            $ba = $this->createAndAssociateBankAccount($order, $input, false);

            $order->getValidator()->validateMerchantSpecificData();

            if (empty($ba) === false)
            {
                $order->setAccountNumber($ba->getAccountNumber());

                $order->setPayerName($ba->getBeneficiaryName());
            }

            $this->associateOffers($order, $input);

            $this->associateProducts($order, $input);

            $this->associateOrderMeta($order, $input);

            if ($dummyProcessing === false)
            {
                $this->repo->saveOrFail($order);
            }

            return [$order, $ba];
        });

        if (isset($input[Entity::BANK_ACCOUNT]) === true)
        {
            (new BankAccount\Beneficiary)->enqueueForBeneficiaryRegistration($ba);
        }

        $this->trace->info(
            TraceCode::ORDER_CREATED,
            ['order_id' => $order->getId()]
        );

        return $order;
    }

    /**
     * Function to format ordermeta array as key-value format
     * where key is type and value is the value array
     *
     * @param Entity $order
     *
     * @return array
     */
    public function getFormattedOrderMeta(Entity $order) : array
    {
        $orderMetas = $order->orderMetas;

        $result = [];

        if (($orderMetas !== null) and
            (count($orderMetas) > 0))
        {
            foreach ($orderMetas as $orderMeta)
            {
                $result[$orderMeta->getType()] = $orderMeta->getValue();
            }
        }

        return $result;
    }

    private function createLateAuthConfigIfApplicable(&  $input, $order)
    {
        if (isset($input['payment']) === true)
        {
            $config['config'] = $input['payment'];

            $config['type'] = 'late_auth';

            $config['name'] = $order->getAttribute(Entity::MERCHANT_ID).'_late_auth';

            $config['is_default'] = false;

            $configCore = new Config\Core();

            if ($configCore->merchant === null)
            {
                $configCore->merchant = $this->merchant;
            }

            $configEntity = $configCore->create($config);

            $order->setLateAuthConfigId($configEntity->getId());

            return $configEntity->getId();
        }

        return null;
    }

    /*
     * This function is used to create method wise convenience fee config
     * based on rules sent in order create input. This is only applicable
     * for merchant who are on Dynamic Fee Bearer configuration
     */
    private function createConvenienceFeeConfigIfApplicable(& $input, $order)
    {
        if(isset($input[Entity::CONVENIENCE_FEE_CONFIG]) === false)
        {
            return;
        }

        if(isset($input[Entity::CONVENIENCE_FEE_CONFIG]) === true and
            empty( $input[Entity::CONVENIENCE_FEE_CONFIG]) === true)
        {
            return;
        }

        $inputConfig = $input[Entity::CONVENIENCE_FEE_CONFIG];

        if($order->merchant->getFeeBearer() !== 'dynamic')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG,
                'convenience_fee_config',
                null,
                'Convenience fee configurable for dynamic fee bearer users only'
            );
        }

        return $this->createConvenienceFeeConfig($inputConfig, $order);
    }

    private function createConvenienceFeeConfig($convenienceFeeConfig, $order)
    {
        $config['config'] = $convenienceFeeConfig;

        $config['type'] = 'convenience_fee';

        $config['name'] = $order->getAttribute(Entity::MERCHANT_ID).'_fee_config';

        $config['is_default'] = false;

        $configCore = new Config\Core();

        $configEntity = $configCore->create($config);

        $order->setFeeConfigId($configEntity->getId());

        return $configEntity->getId();

    }

    public function getInputWithoutExtraParams(array $input)
    {
        $newInput = $input;

        foreach (ExtraParams::allExtraParams as $extraParam)
        {
            if (array_key_exists($extraParam, $newInput) === true)
            {
                unset($newInput[$extraParam]);
            }
        }

        return $newInput;
    }

    protected function createAndAssociateBankAccount(Entity $order, array $input, bool $pushToQueue = true)
    {
        if (isset($input[Entity::BANK_ACCOUNT]) === false)
        {
            return;
        }

        return (new BankAccount\Core)->createBankAccountForSource(
            $input[Entity::BANK_ACCOUNT],
            $order->merchant,
            $order,
            'addTpvBankAccount',
            $pushToQueue);
    }

    protected function associateOffers(Entity $order, array $input)
    {
        if(($order->isOfferForced()) === null or ($order->isOfferForced() === false))
        {
            $this->associateDefaultOffers( $order);
        }

        if (isset($input[Entity::OFFERS]) === false)
        {
            return;
        }

        foreach (array_unique($input[Entity::OFFERS]) as $offerId)
        {
            $this->validateAndAssociateOffer($order, $offerId);
        }
    }

    protected function associateProducts(Entity $order, array $input)
    {
        if (isset($input[Entity::PRODUCTS]) === false)
        {
            return;
        }

        (new Product\Core)->createMany($order, $input[Entity::PRODUCTS]);
    }

    /**
     * @param Entity $order
     * @param array  $input
     *
     * @return OrderMeta\Entity|null
     */
    protected function associateOrderMeta(Entity $order, array $input)
    {
       return (new OrderMeta\Core)->createAndSaveOrderMeta($order, $input);
    }

    protected function validateAndAssociateOffer(Entity $order, string $offerId)
    {
        $offer = (new Offer\Core)->fetchAndValidateOfferForOrder($offerId, $order);

        if(($offer->isDefaultOffer() === false) or ($order->isOfferForced() === true))
        {
            $this->associateOffer($order, $offer);
        }
    }

    protected function associateDefaultOffers(Entity $order)
    {
        $defaultOffers = (new Offer\Core)->fetchDefaultOffersForMerchant($order->getMerchantId());

        foreach($defaultOffers as $offer)
        {
            $offer = (new Offer\Core)->validateDefaultOfferForOrder($order, $offer);

            if($offer !== null)
            {
                $this->associateOffer($order, $offer);
            }
        }
    }

    protected function associateOffer(Entity $order,  offer\Entity $offer)
    {
        // Creates row in entity_offers table
        $order->associateOffer($offer);

        $this->trace->info(
            TraceCode::OFFER_APPLIED_ON_ORDER,
            [
                'offer_id' => $offer->getId(),
                'order_id' => $order->getId()
            ]);
    }

    /**
     * Returns formatted data of order to be used by checkout.
     * Includes:
     * - Amount fields
     * - TPV data
     *
     * @param Entity          $order
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function getFormattedDataForCheckout(
        Entity $order,
        Merchant\Entity $merchant): array
    {
        if ($order->getStatus() === Status::PAID)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID,
                null,
                [
                    'order_id' => $order->getId(),
                ]);
        }

        $data = [
            Entity::PARTIAL_PAYMENT          => $order->isPartialPaymentAllowed(),
            Entity::AMOUNT                   => $order->getAmount(),
            Entity::CURRENCY                 => $order->getCurrency(),
            Entity::AMOUNT_PAID              => $order->getAmountPaid(),
            Entity::AMOUNT_DUE               => $order->getAmountDue(),
            Entity::FIRST_PAYMENT_MIN_AMOUNT => $order->getFirstPaymentMinAmount(),
        ];

        $isForNocodeApps = ProductType::IsForNocodeApps($order->getProductType());

        if($merchant->isFeatureEnabled(Constants::ONE_CLICK_CHECKOUT) === true || $isForNocodeApps)
        {
            foreach ($order->orderMetas as $orderMeta)
            {
                if($orderMeta->getType() !== OrderMeta\Type::ONE_CLICK_CHECKOUT){
                    continue;
                }
                $data[OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL] = $orderMeta->getValue()[OrderMeta\Order1cc\Fields::LINE_ITEMS_TOTAL];
                $data[OrderMeta\Order1cc\Fields::LINE_ITEMS]       = $orderMeta->getValue()[OrderMeta\Order1cc\Fields::LINE_ITEMS] ?? [];
                break;
            }

            if ($isForNocodeApps)
            {
                $data[Entity::PRODUCT_TYPE] = $order->getProductType();
            }
        }

        $orderMethod = $order->getMethod();

        $feeConfigId = $order->getFeeConfigId();

        if(isset($feeConfigId) === true)
        {
            $configCore = new Config\Core();

            $convenienceFeeConfig = $configCore->getConvenienceFeeConfigForCheckout($order);

            if(empty($convenienceFeeConfig) === false )
            {
              $data['convenience_fee_config'] = $convenienceFeeConfig;
            }
        }


        if ($merchant->isTPVRequired() === true)
        {
            // TODO: Change this after creating bank account entities for all the previous TPV orders
            $accountNumber = empty($order->bankAccount) === true ? $order->getAccountNumber() : $order->bankAccount->getAccountNumber();

            $data += [
                Entity::BANK           => $order->getBank(),
                Entity::ACCOUNT_NUMBER => $this->getMaskedAccountNumber($accountNumber),
            ];
        }
        else if ($order->getBank() !== null)
        {
            $data += [
                Entity::BANK           => $order->getBank(),
            ];
        }

        $tokenRegistration = $order->getTokenRegistration();

        if ($tokenRegistration !== null)
        {
            if ( ($tokenRegistration->getEntityType() === Entity::BANK_ACCOUNT) === true )
            {
                $bankAccount = $tokenRegistration->bankAccount;

                $bankCode = $bankAccount->getBankCode();

                $data[Entity::BANK] = $bankCode;

                $bankAccountData = $bankAccount->getDataForCheckout();

                if ($order->getMethod() === Methods\Entity::EMANDATE)
                {
                    $bankAccountData[BankAccount\Entity::ACCOUNT_NUMBER] = mask_except_last4($bankAccountData[BankAccount\Entity::ACCOUNT_NUMBER]);
                }

                $data[Entity::BANK_ACCOUNT] = $bankAccountData;
            }

            if ($tokenRegistration->getMethod() === Methods\Entity::CARD){
                $data[Entity::TOKEN]['frequency'] = $tokenRegistration->getFrequency() ?? $tokenRegistration::AS_PRESENTED;
                $data[Entity::TOKEN]['max_amount'] = $tokenRegistration->getMaxAmount() ?? null;
                $data[Entity::TOKEN]['end_time'] = $tokenRegistration->getExpireAt() ?? null;
            }

            $data[Entity::AUTH_TYPE] = $tokenRegistration->getAuthType();
            $data[Entity::MAX_AMOUNT] = $tokenRegistration->getMaxAmount();
        }

        if ($order->upiMandate !== null)
        {
            $data['token']['start_time'] = $order->upiMandate['start_time'];
            $data['token']['end_time'] = $order->upiMandate['end_time'];
            $data['token']['recurring_type'] = $order->upiMandate['recurring_type'];
            $data['token']['frequency'] = $order->upiMandate['frequency'];
            $data['token']['max_amount']= $order->upiMandate['max_amount'];
        }

        if ($orderMethod !== null)
        {
            $data += [Entity::METHOD => $orderMethod];
        }

        return $data;
    }

    public function getMaskedAccountNumber($accountNumber)
    {
        $accountNumberLength = strlen($accountNumber);

        $last2Digits = substr($accountNumber, -2);

        $formattedNumber = str_repeat('X', $accountNumberLength - 2) . $last2Digits;

        return $formattedNumber;
    }

    public function getAccountForRefund(Entity $order)
    {
        $payerAccount = $order->bankAccount;

        // TODO: Change this after creating bank account entities for all the previous TPV orders
        if (empty($payerAccount) === true)
        {
            $ifscCode = BankCodes::getIfscForBankCode($order->getBank());

            $beneficiaryName = $order->getPayerName();

            $input[BankAccount\Entity::IFSC_CODE] = $ifscCode;
            $input[BankAccount\Entity::ACCOUNT_NUMBER] = $order->getAccountNumber();
            $input[BankAccount\Entity::BENEFICIARY_NAME] = ($beneficiaryName === null) ? '' : $beneficiaryName;
        }
        else
        {
            $beneficiaryName = $payerAccount->getBeneficiaryName();

            $input = [
                BankAccount\Entity::IFSC_CODE        => $payerAccount->getIfscCode(),
                BankAccount\Entity::ACCOUNT_NUMBER   => $payerAccount->getAccountNumber(),
                BankAccount\Entity::BENEFICIARY_NAME => ($beneficiaryName === null) ? '' : $beneficiaryName,
            ];
        }

        return $input;
    }

    /**
     * Validates the uniqueness of the receipt for featured merchants. The uniqueness here, is within the orders of that
     * particular merchant and not across all the merchants.
     *
     * @param Entity $order
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateReceiptUniqueness(Entity $order)
    {
        $merchant = $order->merchant;

        if ($merchant->isFeatureEnabled(FeatureConstants::ORDER_RECEIPT_UNIQUE) === false)
        {
            return;
        }

        $receipt = $order->getReceipt();

        if ($receipt === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_REQUIRED,
                Entity::RECEIPT);
        }

        $mutex =  App::getFacadeRoot()['api.mutex'];

        $mutexAcquired = $mutex->acquire($merchant->getId()."-".$receipt, self::RECEIPT_MUTEX_TIMEOUT);

        if ($mutexAcquired === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_RECEIPT_ANOTHER_OPERATION_IN_PROGRESS,
                null,
                ['resource' => $merchant->getId()."-".$receipt]
            );
        }

        $params = [Entity::RECEIPT => $receipt];

        //TODO: Check if we can move this to data warehouse
        $duplicateOrders = $this->repo->order->fetch($params, $merchant->getId(), ConnectionType::SLAVE);

        if (count($duplicateOrders) > 0)
        {
            // Ideally the duplicate order_ids sent in the exception should be a public id,
            // But we have been sending the raw id to the merchant.
            // Since a merchant has raised an issue regarding this, and so that other merchants don't get impacted,
            // we are fixing this functionality with a feature flag for merchants.
            if ($merchant->isFeatureEnabled(FeatureConstants::ORDER_RECEIPT_UNIQUE_ERR) === false)
            {
                $duplicateOrderIds = $duplicateOrders->getPublicIds();
            }
            else
            {
                $duplicateOrderIds = $duplicateOrders->pluck(Entity::ID)->all();
            }

            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_NOT_UNIQUE,
                ['order_ids' => $duplicateOrderIds]);
        }
    }

    /**
     * Method validates whether configid is valid or not for the merchant
     *
     * Throws BAD_REQUEST_ERROR error with description "The id provided does not exist"
     */

    public function validateCheckoutConfigId($configId, $merchant = null)
    {
        if ($merchant !== null)
        {
            $this->merchant = $merchant;
        }

        $this->repo->config->findByPublicIdAndMerchant($configId, $this->merchant);
    }

    public function mergeOrderOutbox(Entity $order)
    {
        $orderOutbox = $this->repo->order_outbox->fetchByOrderId($order->getId());

        $this->trace->info(TraceCode::ORDER_OUTBOX_FETCH,
            [
                OrderOutboxConstants::ORDER_OUTBOX         => $orderOutbox
            ]
        );

        if (empty($orderOutbox) === false)
        {
            $orderOutboxPayload = $payload = json_decode($orderOutbox->getPayload(), true);

            switch ($orderOutbox->getEventName())
            {
                case OrderOutboxConstants::ORDER_AMOUNT_PAID_EVENT:
                    $orderAmountPaid = $orderOutboxPayload[Entity::AMOUNT_PAID];

                    $order->setAmountPaid($orderAmountPaid);
                    break;

                case OrderOutboxConstants::ORDER_STATUS_PAID_EVENT:
                    $orderAmountPaid = $orderOutboxPayload[Entity::AMOUNT_PAID];
                    $orderStatus = $orderOutboxPayload[Entity::STATUS];

                    $order->setAmountPaid($orderAmountPaid);
                    $order->setStatus($orderStatus);
                    break;

            }

            $this->trace->info(TraceCode::ORDER_UPDATE_BY_OUTBOX_ENTITY,
                [
                    'order'     => $order
                ]
            );
        }

        return $order;
    }

    public function dispatchOrderToPGRouter($data)
    {
//        $traceData = $data;
//
//        unset($traceData['account_number'], $traceData['payer_name']);


        if ((isset($data['notes']) === false) or
            (count($data['notes']) === 0))
        {
            $data['notes'] = null;
        }

//        $this->trace->info(
//            TraceCode::ORDER_QUEUE_PG_ROUTER_DISPATCH,
//            $traceData
//        );

        SyncOrderPgRouter::dispatch($data);
    }

    public function dispatchUpdatedOrderToPGRouter($data)
    {
        $traceData = $data;

        unset($traceData['order_sync_request']['account_number'], $traceData['order_sync_request']['payer_name']);

        $this->trace->info(
            TraceCode::ORDER_QUEUE_PG_ROUTER_DISPATCH,
            $traceData
        );

        UpdateSyncedOrderPgRouter::dispatch($data);
    }

    public function findByPublicIdAndMerchant(string $id ,Merchant\Entity $merchant)
    {
        return $this->repo->order->findByPublicIdAndMerchant($id ,$merchant);
    }

    public function fetchOrdersAndSync(array $input)
    {
        $mode = App::getFacadeRoot()['rzp.mode'];

        if (isset($mode) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Mode is required");
        }

        if ($mode === 'test')
        {
            throw new Exception\BadRequestException( ErrorCode::BAD_REQUEST_PG_ROUTER_ONLY_LIVE_MODE_SUPPORTED);
        }

        $orders = $this->repo->order->fetchMultipleOrdersBasedOnIds($input['order_ids']);

        $orderArray = $orders->toArray();

        if ((isset($orderArray) === true) and
            (count($orderArray) > 0))
        {
            foreach ($orderArray as &$key)
            {
                if ((isset($key['notes']) === true) and
                    (Arr::isAssoc($key['notes']) === false))
                {
                    $key['notes'] = array_combine($key['notes'], $key['notes']);
                }

                if ((isset($key['notes']) === false) or
                    (count($key['notes']) === 0))
                {
                    $key['notes'] = null;
                }

                unset($key['merchant'], $key['bank_account'], $key['offers']);

                $key['id'] = Entity::verifyIdAndSilentlyStripSign($key['id']);
            }
        }

        $data = ['orderBulkRequest' => $orderArray];

        $response = App::getFacadeRoot()['pg_router']->syncBulkOrderToPgRouter($data, false);

        if ($response['code'] === 200)
        {
            $this->repo->order->bulkUpdatePgRouterSynced($response['body']['sync_success_ids']);

            return $response['body'];
        }
        else
        {
            return ['sync_failure_ids' => $input['order_ids']];
        }
    }

    public function fetchProductDetailsForOrder(Entity $order, Merchant\Entity $merchant)
    {
        $productType = $order->getProductType();

        switch ($productType)
        {
            case ProductType::PAYMENT_PAGE:
            case ProductType::PAYMENT_BUTTON:

                $productId = $order->getProductId();

                $paymentPage = $this->repo->payment_link->findByIdAndMerchant($productId, $merchant);

                $serializedData = $order->toArrayPublic();

                $serializedData[Entity::PRODUCT_TYPE] = $productType;

                $serializedData[$productType] = $paymentPage->toArrayPublic();

                return $serializedData;

            default:

                throw new Exception\BadRequestValidationFailureException(
                    'Invalid product type / Product type not implemented'
                );
        }
    }

    public function internalOrderValidateTPVChecks($input)
    {
        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $tpvRequired = $merchant->isTPVRequired();

        if ($tpvRequired === false)
        {
            return;
        }

        $method = $input['method'] ?? null;

        if (($method !== null) and
            ($method !== Payment\Method::NETBANKING) and
            ($method !== Payment\Method::UPI))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order method needs to be netbanking or upi for the merchant');
        }

        $orderBank = $input['bank'] ?? null;

        $tpvBanks = Netbanking::getSupportedBanksForTPV();

        if (($method !== null and
                $method === Payment\Method::NETBANKING) and
            (in_array($orderBank, $tpvBanks, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order bank does not support TPV');
        }

        if (isset($input['bank_account']['account_number']) === true)
        {
            $accountNumber = $input['bank_account']['account_number'];
        }
        elseif (isset($input['account_number']) === true)
        {
            $accountNumber = $input['account_number'];
        }

        if (empty($accountNumber) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT);
        }
    }

    public function internalCreateOrderRelations($input)
    {
        $data = null;

        $order = (new Entity())->forceFill($input);

        $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $this->merchant = $merchant;

        $lateAuthConfigId = $this->createLateAuthConfigIfApplicable($input, $order);

        if ($lateAuthConfigId !== null)
        {
            $data['late_auth_config_id'] = $lateAuthConfigId;
        }

        $bankAccount = $this->createBankAccountForTpv($input, $order);

        if (empty($bankAccount) === false)
        {
            $data['bank_account_number'] = $bankAccount->getAccountNumber();

            $data['bank_account_beneficiary'] = $bankAccount->getBeneficiaryName();
        }

        $offers = $this->validateAndCreateEntityOffer($order,$input, $merchant);

        if(empty($offers) === false)
        {
            $offerIds = array_column($offers, Entity::ID);
            $data['offers'] = $offerIds;
        }

        $this->associateProducts($order, $input);

        if (($order->products !== null) and
            (count($order->products) > 0))
        {
            $data['products'] = $order->products;
        }

        $orderPostCreateHook = new PostCreateHook($input, $order);

        $this->app['basicauth']->setMerchant($merchant);

        $orderPostCreateHook->process();

        $token = $order->getTokenRegistration();

        $transfers = $order->transfers;

        if (isset($transfers) === true)
        {
            $data['transfers'] = $transfers;
        }

        if ($token !== null)
        {
            $invoice = $order->getMethod() === Payment\Method::NACH ? $order->invoice : null;

            $tokenVar = $token->toArrayTokenFields($invoice);

            // Doing this as per the requirement for the orders api response for CAW Card methods.
            if (($order->getMethod() === null) or
                ($order->getMethod() === Payment\Method::CARD))
            {
                unset($tokenVar[SubscriptionRegistration\Entity::NOTES]);
                unset($tokenVar[SubscriptionRegistration\Entity::METHOD]);
                unset($tokenVar[SubscriptionRegistration\Entity::CURRENCY]);
                unset($tokenVar[SubscriptionRegistration\Entity::AUTH_TYPE]);
                unset($tokenVar[SubscriptionRegistration\Entity::FAILURE_REASON]);
                unset($tokenVar[SubscriptionRegistration\Entity::RECURRING_STATUS]);
                unset($tokenVar[SubscriptionRegistration\Entity::FIRST_PAYMENT_AMOUNT]);
            }

            $data['token'] = $tokenVar;
        }

        if (isset($input['convenience_fee_config']) === true)
        {
            $data['convenience_fee_config_id'] = $this->createConvenienceFeeConfigIfApplicable($input, $order);
        }

        return $data;
    }

    public function internalCreateOrderBankAccountRelations($input)
    {
        $order = (new Entity())->forceFill($input);

        $mutex = $this->app['api.mutex'];

        return $mutex->acquireAndRelease('bank_account_' . $order->getId(),
            function () use ($input, $order)
            {
                $data = null;

                $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

                $this->merchant = $merchant;

                $apiBankAccount = $this->repo->bank_account->getBankAccountsForOrder($order->getId());

                if (isset($apiBankAccount) === true)
                {
                    $data['already_exist'] = $apiBankAccount->getId();

                    return $data;
                }

                $bankAccount = $this->createBankAccountForTpv($input, $order);

                if (empty($bankAccount) === false)
                {
                    $data['bank_account_number'] = $bankAccount->getAccountNumber();

                    $data['bank_account_beneficiary'] = $bankAccount->getBeneficiaryName();
                }

                return $data;
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    private function createBankAccountForTpv($input, $order)
    {
        if (isset($input[Entity::BANK_ACCOUNT]) === false)
        {
            return null;
        }

        $ba = new BankAccount\Entity;

        $ba->merchant()->associate($this->merchant);

        $ba->setAttribute(BankAccount\Entity::ENTITY_ID, $order->getId());

        $ba->setAttribute(BankAccount\Entity::TYPE, E::ORDER);

        $ba = $ba->build($input[Entity::BANK_ACCOUNT], 'addTpvBankAccount');

        $this->repo->bank_account->saveOrFail($ba);

        (new Beneficiary)->enqueueForBeneficiaryRegistration($ba);

        return $ba;
    }

    private function validateAndCreateEntityOffer($order, $input, $merchant)
    {
        $offerCore = (new Offer\Core);

        $offerCore->merchant = $merchant;

        $offers = array();

        if(($order->isOfferForced()) === null or ($order->isOfferForced() === false))
        {
            $defaultOffers = (new Offer\Core)->fetchDefaultOffersForMerchant($order->getMerchantId());

            foreach($defaultOffers as $offer)
            {
                $offer = $offerCore->validateDefaultOfferForOrder($order, $offer);

                if($offer !== null)
                {
                    array_push($offers, $offer);
                }
            }
        }

        if (isset($input[Entity::OFFERS]) === true)
        {
            foreach (array_unique($input[Entity::OFFERS]) as $offerId)
            {
                $offer = $offerCore->fetchAndValidateOfferForOrder($offerId, $order);

                if(($offer->isDefaultOffer() === false) or ($order->isOfferForced() === true))
                {
                    array_push($offers, $offer);
                }
            }
        }

        if (count($offers) > 0)
        {
            $this->saveEntityOffer($order, $offers);
        }

        return $offers;
    }

    private function saveEntityOffer($order, $offers)
    {
        $data = array();

        foreach ($offers as $offer)
        {
            $entityOfferData = [
                Offer\EntityOffer\Entity::ENTITY_ID         => $order->getId(),
                Offer\EntityOffer\Entity::ENTITY_TYPE       => 'order',
                Offer\EntityOffer\Entity::OFFER_ID          => $offer->getId(),
                Offer\EntityOffer\Entity::ENTITY_OFFER_TYPE => 'offer',
                Offer\EntityOffer\Entity::CREATED_AT        => Carbon::now()->getTimestamp(),
                Offer\EntityOffer\Entity::UPDATED_AT        => Carbon::now()->getTimestamp()
            ];

                array_push($data, $entityOfferData);
        }

        Offer\EntityOffer\Entity::insert($data);
    }

    public function internalOrderValidateTransferParams($input)
    {
        $preCreateHooks = new PreCreateHook($input);

        $preCreateHooks->publicKey = $input['public_key'] ?? null;

        if (isset($input['transfers']) === true)
        {
            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

            $preCreateHooks->merchant = $merchant;

            $preCreateHooks->validateTransferParams($input['transfers']);
        }

        return true;
    }

    public function getOrderPublicKey($merchant)
    {
        $ba = App::getFacadeRoot()['basicauth'];

        $publicKey = $ba->getPublicKey();

        if ($ba->isProxyAuth() === true)
        {
            $keyEntity = $this->repo->key->getLatestActiveKeyForMerchant($merchant->getId());

            if (isset($keyEntity) === false)
            {
                $parentMerchant = $merchant->parent;

                if (isset($parentMerchant) === true)
                {
                    $parentKeyEntity = $this->repo->key->getLatestActiveKeyForMerchant($parentMerchant->getId());

                    if (isset($parentKeyEntity) === true)
                    {
                        $publicKey = $parentKeyEntity->getPublicKey();
                    }
                }
            }
            else
            {
                $publicKey = $keyEntity->getPublicKey();
            }
        }

        return $publicKey;
    }

    /**
     * update receipt for 1cc orders
     *
     * @param Entity $order
     *
     * @return void
     */
    public function updateReceipt(Entity $order, string $receipt): void
    {
        $order->setReceipt($receipt);

        if ($order->isExternal() === true)
        {
            $this->app['pg_router']->updateInternalOrder(
                [
                    'receipt' => $order->getReceipt(),
                ],
                $order->getId(),
                $order->getMerchantId(),
                true);
        }
        else
        {
            $this->repo->saveOrFail($order);
        }
    }
}
