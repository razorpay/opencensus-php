<?php

namespace RZP\Models\P2p\Preferences;

use Monolog\Logger;
use RZP\Models\Order;
use RZP\Constants\Mode;
use RZP\Models\P2p\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Customer;
use RZP\Models\P2p\Device;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Jobs\UpiTurboErrorMappingUpdater;
use RZP\Models\BankAccount as BankAccount;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Customer\Entity as CustomerEntity;
use RZP\Services\Dcs\Configurations as DcsConfig;
use RZP\Models\P2p\BankAccount\Type as AccountType;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Services\Dcs\Features\Constants as DcsConstants;

/**
 *
 * Class Processor
 */
class Processor extends Base\Processor
{
    /**
     * This is a function to get preferences for a razorpay SDK to choose client side bank sdk
     * This will be generalized more later with multi bank setup
     * @param array $input
     *
     * @return array
     */
    public function getPreferences(array $input): array
    {

        try
        {
            $this->initialize(Action::GET_PREFERENCES, $input);

            $preferencesResponse = array_merge($this->getGatewayPreferencesForSDK(), $this->getSDKVersionLimitations());

            if ((isset($input[Entity::CUSTOMER_ID]) === true) and (isset($input[Entity::ORDER_ID]) === true))
            {
                $this->validateOrderAndCustomerIdInRequest($input);
            }

            if (isset($input[Entity::CUSTOMER_ID]) === true)
            {
                // In Preferences, For number less verification flow,
                // we may receive merchant's customer id in the customer id field
                try {
                    $customer = (new Device\Core)->getDeviceCustomer($input[Entity::CUSTOMER_ID]);
                    $preferencesResponse = array_merge($this->getCustomerData($customer), $preferencesResponse);
                }
                catch(\Throwable $t)
                {
                    $this->trace()->warning(TraceCode::CUSTOMER_NOT_FOUND,
                        ['customer_id' => $input[Entity::CUSTOMER_ID], '$customer' => $customer]);
                }

                // In TPV, the merchant should pass the valid customer id
                if($this->context()->getMerchant()->isTPVRequired() && !isset($customer))
                {
                    throw new BadRequestValidationFailureException(
                        'The ' . $input[Entity::CUSTOMER_ID] . ' is not a valid customer id.'
                    );
                }
            }

            $this->setMerchantInfoInResponse($preferencesResponse);

            $this->setMerchantFeaturesInResponse($preferencesResponse);

            $this->setExperimentsInResponse($preferencesResponse);

            $this->setPrefetchConfigsInResponse($preferencesResponse);

            // if order id and customer id are empty
            if ((isset($input[Entity::ORDER_ID]) === false) and (isset($input[Entity::CUSTOMER_ID]) === false))
            {
                return $this->postProcess($preferencesResponse);
            }

            // If TPV is enabled for the merchant
            if ($this->context()->getMerchant()->isTPVRequired() === true)
            {
                // marking is tpv flag as true
                $preferencesResponse[Entity::IS_TPV] = true;

                $preferencesResponse[Entity::TPV] = $this->getTPVContents($input);
            }

            return $this->postProcess($preferencesResponse);
        }
        catch (\Throwable $e)
        {
            $this->pushGatewayActionMetric($this->context(), true);

            throw $e;
        }
    }

    private function validateOrderAndCustomerIdInRequest($input)
    {
        $doesOrderBelongToCustomer = $this->doesOrderBelongToCustomer($input[Entity::ORDER_ID], $input[Entity::CUSTOMER_ID]);

        if ($doesOrderBelongToCustomer === false)
        {
            $this->trace()->warning(
                TraceCode::ORDER_ID_NOT_BELONG_TO_CUSTOMER,
                [
                    'message' => Constants::ORDER_ID_NOT_BELONG_TO_CUSTOMER,
                ]);

            throw new BadRequestValidationFailureException( Constants::ORDER_ID_NOT_BELONG_TO_CUSTOMER);
        }
    }

    public function doesOrderBelongToCustomer($orderId, $customerId): bool
    {
        $order = (new Order\Service())->fetchCompleteOrderById($orderId);

        $customerIdInOrder = $order[Entity::CUSTOMER_ID];

        if ($customerIdInOrder === $customerId)
        {
            return true;
        }
        return  false;
    }

    protected function postProcess($preferencesResponse)
    {
        $this->gatewayInput->put(Entity::PREFERENCES, $preferencesResponse);

        return $this->callGateway();
    }

    /**
     * @param array $input
     * This is a function to return same preferences data from previous action
     * @return array
     */
    public function getPreferencesSuccess(array $input): array
    {
        try
        {
            $this->initialize(Action::GET_PREFERENCES_SUCCESS, $input);

            return $input;

        }
        catch (\Throwable $e)
        {
            $this->pushGatewayActionMetric($this->context(), true);

            throw $e;
        }
    }

    private function getCustomerData(CustomerEntity $customer)
    {
        return [
            Entity::CUSTOMER => [
                    CustomerEntity::NAME => $customer->getName(),
                ]
            ];
    }

    private function getGatewayPreferencesForSDK()
    {
        return [
            Entity::GATEWAYS                       => [
                [
                    Entity::PRIORITY => '0',
                    Entity::GATEWAY  => $this->getGateway(),
                ],
            ],
            Entity::POPULAR_BANKS                  => $this->getPopularBankListForSDK(),
            Constants::TIMEOUTS                    => $this->fetchSDKTimeoutConfigs(),
            Entity::ERROR_MAPPING_HASH             => $this->getErrorMappingHash(),
            Constants::PAYER_ACCOUNT_TYPE_MAPPINGS => Constants::getPayerAccountTypeMappings($this->getGateway())
        ];
    }

    private function fetchSDKTimeoutConfigs()
    {
        $sdkTimeouts =  ConfigKey::get(ConfigKey::UPI_TURBO_SDK_TIMEOUTS, []);

        if (empty($sdkTimeouts) === true)
        {
            $sdkTimeouts = Constants::getDefaultTimeoutsForSDK();
        }

        switch ($this->getGateway())
        {
            case EntityConstants::P2M_UPI_AXIS_OLIVE:
                return [
                    Constants::OLIVE_SDK_TIMEOUT => $sdkTimeouts[Constants::OLIVE_SDK_TIMEOUT] ?? 0
                ];
            default:
                throw new LogicException("Unknown gateway!");
        }
    }

    private function getPopularBankListForSDK()
    {
        $popularBanksList = ConfigKey::get(ConfigKey::UPI_TURBO_POPULAR_BANK_LIST, []);

        if (empty($popularBanksList) === true)
        {
            $this->trace()->info(TraceCode::TURBO_POPULAR_BANK_LIST_NOT_FOUND_IN_CACHE, [
                'action' => 'turbo popular bank list is not found in cache'
            ]);
            return Constants::getStaticPopularBanksList();
        }

        return  $popularBanksList;
    }

    private function getErrorMappingHash()
    {
        $errorMappingHash = ConfigKey::get(ConfigKey::TURBO_SDK_ERROR_MAPPINGS_HASH, '');

        if (empty($errorMappingHash) === true)
        {
            $this->trace()->warning(TraceCode::TURBO_ERROR_MAPPINGS_NOT_FOUND_IN_CACHE);

            // In case redis fetch fails, we will push a message to queue to update redis configs asynchronously.
            try
            {
                UpiTurboErrorMappingUpdater::dispatch($this->mode() ?? Mode::LIVE);

                $this->trace()->info(TraceCode::TURBO_ERROR_MAPPING_UPDATER_QUEUE_PUSH_SUCCESS);
            }
            catch (\Throwable $ex)
            {
                $this->trace()->traceException($ex, Logger::CRITICAL, TraceCode::TURBO_ERROR_MAPPING_UPDATER_QUEUE_PUSH_FAILURE);
            }
        }

        return $errorMappingHash;
    }

    private function getSDKVersionLimitations()
    {
        return [
            Entity::SDK_VERSIONS => [
                Entity::ANDROID => [
                    Entity::MIN       => '1.1.0',
                    Entity::BLOCKED   => [],
                ],
                Entity::IOS     => [
                    Entity::MIN       => '1.0.0',
                    Entity::BLOCKED   => [],
                ],
            ],
        ];
    }

    /**
     * Get TPV contents
     * @param array $input
     *
     * @return array
     */
    private function getTPVContents(array $input)
    {
        $CUSTOMER_BANKACCOUNT_FETCH_LIMIT = 10;

        $tpvContents = [
            Entity::RESTRICT_BANK_ACCOUNTS => true,
        ];

        // order id  will always be given preference over customer id
        // if order id is passed to tpv contents then pull bank accounts for the order id
        if(isset($input[Entity::ORDER_ID]) === true)
        {
            $order = (new Order\Core)->findByPublicIdAndMerchant($input[Entity::ORDER_ID], $this->context()->getMerchant());

            $bank_account_array[0] = $order->bankAccount->toArray();

            // this check is only done for tpv merchants
            // if its a tpv order and bank account is not passed exception will be thrown
            if ((isset($bank_account_array) != true) or
                (count($bank_account_array) === 0))
            {
                throw new \RZP\Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT, Entity::ID,
                 [
                     'id' => $input[Entity::ORDER_ID]
                 ]);
            }

            $tpvContents = array_merge($tpvContents,[Entity::BANK_ACCOUNTS => $this->getBankAccountContentsForPreferences($bank_account_array)]);

            return $tpvContents;

        }// if customer id is passed to tpv contents then pull bank accounts for the customer id
        else if(isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $bankAccounts = (new BankAccount\Core)->fetchCustomerBankAccountByCustomerIdAndMerchantId(CustomerEntity::stripDefaultSign($input[Entity::CUSTOMER_ID]),
                              $this->context()->getMerchant()->getId(), $CUSTOMER_BANKACCOUNT_FETCH_LIMIT);

            $tpvContents = array_merge($tpvContents,[Entity::BANK_ACCOUNTS => $this->getBankAccountContentsForPreferences($bankAccounts->toArray())]);

        }

        return $tpvContents;
    }


    public function getBankAccountContentsForPreferences(array $bank_account_array)
    {
        $bank_account_contents = [];

        foreach($bank_account_array as $bankaccount)
        {
            $bank_account_content = [];

            $bank_account_content[BankAccount\Entity::IFSC] = $bankaccount[BankAccount\Entity::IFSC];

            $bank_account_content[BankAccount\Entity::ACCOUNT_NUMBER] = $this->getMaskedAccountNumber($bankaccount[BankAccount\Entity::ACCOUNT_NUMBER]);

            $bank_account_content[BankAccount\Entity::BANK_NAME] = $bankaccount[BankAccount\Entity::BANK_NAME];

            array_push($bank_account_contents,$bank_account_content);
        }

        return $bank_account_contents;
    }

    /**
     * This is the method to mask last 4 characters for account number
     * @param $accountNumber
     *
     * @return string
     */
    public function getMaskedAccountNumber($accountNumber)
    {
        $accountNumberLength = strlen($accountNumber);

        $last4Digits = substr($accountNumber, -4);

        $formattedNumber = str_repeat('X', $accountNumberLength - 4) . $last4Digits;

        return $formattedNumber;
    }

    private function setMerchantInfoInResponse(&$response)
    {
        try
        {
            /** @var \RZP\Models\Merchant\Entity $merchant */
            $merchant = $this->app['basicauth']->getMerchant();

            if ($merchant === null)
            {
                return;
            }

            $merchantDisplayName = $merchant->getDisplayName();

            if ($merchantDisplayName === null)
            {
                $this->trace()->warning(
                    TraceCode::MERCHANT_DISPLAY_NAME_NULL,
                    [
                        'merchant_id' => $merchant->getId(),
                    ]
                );

                $merchantDisplayName = $merchant->getName();
            }

            $response[Constants::MERCHANT] = [
                Constants::DISPLAY_NAME => $merchantDisplayName
            ];
        }
        catch (\Throwable $exception)
        {
            $this->trace()->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::MERCHANT_INFO_SET_FAILED_IN_TURBO_PREFERENCES_RESPONSE
            );
        }
    }

    private function updateFeatureNamesForSDK(&$feature)
    {
        if (isset($feature[DcsConstants::PrefetchAccountsDisabled]))
        {
            $feature[Feature::PREFETCH_ACCOUNT_DISABLED] = !$feature[DcsConstants::PrefetchAccountsDisabled];
            unset($feature[DcsConstants::PrefetchAccountsDisabled]);
        }
    }

    private function setMerchantFeaturesInResponse(&$preferencesResponse)
    {
        $mode = app('rzp.mode') ?? Mode::LIVE;
        $merchantId = $this->context()->getMerchant()->getId();
        $features = [];
        $key     = '';

        foreach (Feature::TURBO_UPI_FEATURES as $featureName => $configurations)
        {
            try
            {
                switch ($featureName)
                {
                    case Constants::DISPLAY_CONTROLS:
                        $key = DcsConfig\Constants::UpiInAppDisplayControls;
                        break;

                    case Constants::PREFETCH:
                        $key = DcsConfig\Constants::UpiInAppPrefetch;
                        break;
                    case Constants::REWARD_CONFIGS:
                        $key = DcsConfig\Constants::UpiInAppRewardConfigs;
                        break;
                }

                $dcsResponse = app('dcs_config_service')->fetchConfiguration($key, $merchantId, $configurations, $mode);

                $features += $dcsResponse;

                $this->updateFeatureNamesForSDK($features);
            }
            catch (\Throwable $ex)
            {
                $this->trace()->traceException(
                    $ex,
                    Logger::ERROR,
                    TraceCode::FAILED_TO_FETCH_CONFIGS_FROM_DCS,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        'mode'                 => $mode,
                        'fields'               => $configurations
                    ]
                );
            }
        }

        // If TPV is enabled for the merchant
        if ($this->context()->getMerchant()->isTPVRequired()) {
            // marking is tpv flag as true
            $features[Entity::TPV] = true;
        }

        $features[Constants::SUPPORTED_PAYER_ACCOUNT_TYPES]
            = $this->getSupportedPayerAccountTypesForMerchant();

        $preferencesResponse[Constants::FEATURES] = $features;
    }

    private function setExperimentsInResponse(&$preferencesResponse)
    {
        try
        {
            $preferencesResponse[Constants::METADATA] = [
                Constants::X_PG_SERVICE => Constants::API
            ];
        }
        catch (\Throwable $ex)
        {
            $this->trace()->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::FAILED_TO_ADD_TURBO_METADATA
            );
        }
    }

    private function setPrefetchConfigsInResponse(&$preferencesResponse)
    {
        $defaultPrefetchConfigs  = Constants::getDefaultPrefetchConfigs();
        $merchantId              = $this->context()->getMerchant()->getId();

        $fetchTestRedisKey = ((app()->isEnvironmentQA() === true) or (app()->isEnvironmentBeta() === true));

        $dynamicPrefetchConfigs  = $fetchTestRedisKey
            ? ConfigKey::get(ConfigKey::UPI_TURBO_PRE_FETCH_BANK_ACCOUNT_TEST, []) :
            ConfigKey::get(ConfigKey::UPI_TURBO_PRE_FETCH_BANK_ACCOUNT, []);

        $merchantPrefetchBankListConfig = $dynamicPrefetchConfigs[$merchantId][Constants::BANKS] ?? null;

        // First we try to check if there's a merchant level config defined in Redis and use the same.
        // If not, we try to see if there's an overall config defined in Redis and use the same.
        // If not, we use fallback default configs defined in app/Models/P2p/Preferences/Constants.php
        $preferencesResponse[Constants::PREFETCH] = [
            Constants::CONSENT_MESSAGE    => $dynamicPrefetchConfigs[Constants::CONSENT_MESSAGE] ??
                                             $defaultPrefetchConfigs[Constants::CONSENT_MESSAGE],
            Constants::FETCH_TIMEOUT      => $dynamicPrefetchConfigs[Constants::FETCH_TIMEOUT] ??
                                             $defaultPrefetchConfigs[Constants::FETCH_TIMEOUT],
            Constants::FETCH_RETRY        => $dynamicPrefetchConfigs[Constants::FETCH_RETRY] ??
                                             $defaultPrefetchConfigs[Constants::FETCH_RETRY],
            Constants::FETCH_CONCURRENT   => $dynamicPrefetchConfigs[Constants::FETCH_CONCURRENT] ??
                                             $defaultPrefetchConfigs[Constants::FETCH_CONCURRENT],
            Constants::BANKS              => $merchantPrefetchBankListConfig ??
                                             $dynamicPrefetchConfigs[Constants::BANKS] ??
                                             $defaultPrefetchConfigs[Constants::BANKS],
        ];
    }

    private function getSupportedPayerAccountTypesForMerchant(): array
    {
        $supportedPayerAccountTypes = Constants::getSupportedPayerAccountTypes();

        try
        {
            $merchantMethods = $this->context()->getMerchant()->getMethods();

            if ($merchantMethods->isInAppCreditCardEnabled() === true)
            {
                $supportedPayerAccountTypes[] = AccountType::CREDIT;
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace()->traceException(
                $exception,
                Logger::ERROR,
                TraceCode::IN_APP_CREDIT_CARD_METHOD_ENABLED_CHECK_FAILED
            );
        }

        return $supportedPayerAccountTypes;
    }
}
