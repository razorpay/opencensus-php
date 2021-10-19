<?php

namespace RZP\Models\Order;

use App;
use Illuminate\Support\Arr;
use RZP\Base\ConnectionType;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Offer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Services\PGRouter;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Payment\Config;
use RZP\Jobs\SyncOrderPgRouter;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Jobs\UpdateSyncedOrderPgRouter;
use RZP\Models\Feature\Constants as FeatureConstants;

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

        $order = new Entity;

        // Needs to be associated first cause merchant entity is required
        // in orders create validators.
        $order->merchant()->associate($merchant);

        $order->build($input);

        $order->setPublicKey(App::getFacadeRoot()['basicauth']->getPublicKey());

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

            $configEntity = $configCore->create($config);

            $order->setLateAuthConfigId($configEntity->getId());
        }
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

        $this->createConvenienceFeeConfig($inputConfig, $order);
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

        $orderMethod = $order->getMethod();

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

                $data[Entity::BANK_ACCOUNT] = $bankAccount->getDataForCheckout();
            }

            $data[Entity::AUTH_TYPE] = $tokenRegistration->getAuthType();
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

        $duplicateOrders = $this->repo->order->fetch($params, $merchant->getId(), ConnectionType::SLAVE);

        if (count($duplicateOrders) > 0)
        {
            $duplicateOrderIds = $duplicateOrders->pluck(Entity::ID)->all();

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

    private function validateCheckoutConfigId($configId)
    {
        $this->repo->config->findByPublicIdAndMerchant($configId, $this->merchant);
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
}
