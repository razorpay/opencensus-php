<?php

namespace RZP\Models\Feature;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use Illuminate\Support\Arr;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Jobs\DCS\AssignFeatures;
use RZP\Models\Merchant\Credits;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\DCS\ValidateFeaturesAPIAndDCS;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Feature\Metric as FeatureMetric;
use RZP\Models\Merchant\CapitalSubmerchantUtility;
use RZP\Models\Merchant\Balance\Type as BalanceType;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Service extends Base\Service
{
    const PAYOUT_SERVICE_IDEMPOTENCY_KEY_INTERMEDIATE_FEATURES_FETCH_LIMIT = 500;

    const ALLOWED_INTERNAL_APPS_FOR_ENTITY_TYPE = [
        'workflows',
        'banking_account_service',
        'capital_collections_client',
        'capital_cards_client',
        'loc'
    ];

    public function addFeatures(
        array $input,
        string $routeEndpoint = null,
        string $entityId = null): array
    {

        $entityType = null;
        if ($routeEndpoint !== null)
        {
            $entityType = Type::getEntityTypeFromRoute($routeEndpoint);
        }

        $this->validateMCCForBulkPaymentPageFeature($input, $entityType, $entityId);

        $validator = new Validator();

        $validator->validateForRouteLaPennyTestingFeature($input[Constants::NAMES]);

        $this->validateIfDisabledFeaturesArePresent($input, $entityType, $entityId);

        $featureParams = $this->buildFeatureParams($input, $entityType, $entityId);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureCore = new Core;

        $features = $featureParams->map(function ($item) use ($featureCore, $shouldSync)
        {
            /** @var Entity $featureToAssign */
            $featureToAssign = (new Entity)->build($item);

            $featureCore->checkAndDisableRxLedgerAndPayoutFeatureChanges($featureToAssign->getName());

            $featureCore->checkAndDisableFeatureChangesForPayoutServiceIdempotencyFeatures($featureToAssign->getName());

            $featureCore->checkAndDisableSubAccountFeatureChange($featureToAssign->getName());

            return $featureCore->create($item, $shouldSync);
        });

        return $features->toArray();
    }

    /**
     * Currently the feature 'file_upload_pp' which enables bulk payment page product, cannot be enabled for merchants with finanace business
     * In case of multi-assign error will be thrown even if one of the features is 'file_upload_pp'
    */
    protected function validateMCCForBulkPaymentPageFeature(array $input, $entityType, $entityId)
    {
        $entityType = $entityType ?? $input[Entity::ENTITY_TYPE];

        $entityId = $entityId ?? $input[Entity::ENTITY_ID];

        if($entityType !== Constants::MERCHANT)
        {
            return;
        }

        if (isset($input[Entity::NAMES]) === false)
        {
            return;
        }

        if (is_string($input[Entity::NAMES]) and ($input[Entity::NAMES] !== Constants::FILE_UPLOAD_PP))
        {
            return;
        }

        if (is_array($input[Entity::NAMES]) and
            (in_array(Constants::FILE_UPLOAD_PP, $input[Entity::NAMES]) === false))
        {
            return;
        }

        $merchant = $this->repo->merchant->find($entityId);

        if(isset($merchant) === false)
        {
            return;
        }

        $disabledMCCs = [
            "4829",
            "6010",
            "6011",
            "6012",
            "6050",
            "6051",
            "6211",
            "6300",
            "6532",
            "6533",
            "6536",
            "6537",
            "6538",
            "6539",
            "6540"
        ];

        if (in_array($merchant->getCategory(), $disabledMCCs) === true)
        {
            throw new BadRequestValidationFailureException(
                'Financial services are not allowed for bulk payment pages');
        }

    }

    protected function validateIfDisabledFeaturesArePresent($input, $entityType, $entityId)
    {
        $entityType = $entityType ?? $input[Entity::ENTITY_TYPE];

        $entityId = $entityId ?? $input[Entity::ENTITY_ID];

        if($entityType !== Constants::MERCHANT)
        {
            return;
        }

        $merchant = $this->repo->merchant->find($entityId);

        if(isset($merchant) === false)
        {
            return;
        }

        if($merchant->isFeatureEnabled(Constants::ONLY_DS) === false)
        {
            return;
        }

        $disabledFeatures =  [
            Features::WHITE_LABELLED_ROUTE,
            Features::WHITE_LABELLED_MARKETPLACE,
            Features::WHITE_LABELLED_VA,
            Features::WHITE_LABELLED_QRCODES,
        ];

        $check = array_intersect($disabledFeatures, $input[Entity::NAMES]);

        if(count($check) === 0)
        {
            return;
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT);
        }
    }

    public function addFeatureAndOnboardOldAccountsToLedger(array $input)
    {
        $response = new Base\PublicCollection;

        foreach ($input as $request) {

            $result = [
                Constants::IDEMPOTENCY_KEY  => $request[Constants::IDEMPOTENCY_KEY],
                Constants::MERCHANT_ID      => $request[Constants::MERCHANT_ID],
                Constants::STATUS           => 'success'
            ];

            try
            {
                $merchantId = $request[Constants::MERCHANT_ID];
                $action = $request['action'];

                $this->trace->info(
                    TraceCode::LEDGER_JOURNAL_WRITES_FEATURE_ASSIGNED,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                        Constants::MODE        => $this->mode,
                        'action'               => $action,
                    ]);

                // fetch merchant entity
                // not fetching merchant incase of pg_gateway_onboarding
                $merchant = ($action !== Constants::PG_GATEWAY_ONBOARD) ? $this->repo->merchant->findOrFailPublic($merchantId) : null;

                switch ($action)
                {
                    case 'shadow_onboard':
                        // first sending the request to ledger because if anything fails we don't add the feature
                        $this->ledgerAccountCreateRequest($merchant);

                        // Add LEDGER_JOURNAL_WRITES feature to merchant
                        (new Core)->create(
                            [
                            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                            Entity::ENTITY_ID => $merchant->getId(),
                            Entity::NAME => Constants::LEDGER_JOURNAL_WRITES,
                                ]);
                        break;

                    case 'shadow_onboard_with_balance_lock':

                        if ((empty($merchant) === false) and
                            ($merchant->isFeatureEnabled(Constants::LEDGER_REVERSE_SHADOW) === true))
                        {
                            throw new Exception\ServerErrorException(
                                'ledger_journal_writes can not be enabled as merchant is on reverse shadow already',
                                ErrorCode::SERVER_ERROR,
                                [
                                    Entity::MERCHANT_ID => $merchant->getId(),
                                ]);
                        }

                            // first sending the request to ledger because if anything fails we don't add the feature
                            $this->ledgerAccountCreateRequestWithBalanceLock($merchant);

                            // Add LEDGER_JOURNAL_WRITES feature to merchant
                            (new Core)->create(
                                [
                                    Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                    Entity::ENTITY_ID => $merchant->getId(),
                                    Entity::NAME => Constants::LEDGER_JOURNAL_WRITES,
                                ]);

                        break;

                    case 'reverse_shadow':
                        // Add `ledger_journal_reads` feature flag
                        (new Core)->create(
                            [
                                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                Entity::ENTITY_ID => $merchant->getId(),
                                Entity::NAME => Constants::LEDGER_JOURNAL_READS,
                            ]);

                        //Add `ledger_reverse_shadow` feature flag
                        (new Core)->create(
                            [
                                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                Entity::ENTITY_ID => $merchant->getId(),
                                Entity::NAME => Constants::LEDGER_REVERSE_SHADOW,
                            ]);

                        //Delete `ledger_journal_writes` feature flag
                        $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                            EntityConstants::MERCHANT,
                            $merchant->getId(),
                            Constants::LEDGER_JOURNAL_WRITES);

                        if (!empty($feature)) {
                            (new Core)->delete($feature);
                        }
                        break;

                    case 'shadow_offboard':
                        //Delete `ledger_journal_writes` feature flag
                        $featureFlags = [
                            Constants::LEDGER_JOURNAL_WRITES,
                        ];
                        foreach ($featureFlags as $featureFlag) {
                            $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                                EntityConstants::MERCHANT,
                                $merchant->getId(),
                                $featureFlag);

                            if (!empty($feature)) {
                                (new Core)->delete($feature);
                            }
                        }
                        break;

                    case 'reverse_shadow_offboard':

                        //Check if payout_service_enabled flag is assigned to the merchant or not.
                        //As payout_service_enabled is dependent on ledger_reverse_shadow so we can't remove it blindly
                        //before removing payout_service_enabled otherwise it will lead to inconsistency.
                        if ((empty($merchant) === false) and
                            ($merchant->isFeatureEnabled(Constants::PAYOUT_SERVICE_ENABLED) === true))
                        {
                            throw new Exception\ServerErrorException(
                                'ledger_reverse_shadow can not be removed if payout_service_enabled is assigned to the merchant.',
                                ErrorCode::SERVER_ERROR,
                                [
                                    Entity::MERCHANT_ID => $merchant->getId(),
                                ]);
                        }

                        //Delete `ledger_journal_reads` feature flag
                        //Delete `ledger_reverse_shadow` feature flag
                        $featureFlags = [
                            Constants::LEDGER_JOURNAL_READS,
                            Constants::LEDGER_REVERSE_SHADOW,
                        ];
                        foreach ($featureFlags as $featureFlag) {
                            $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                                EntityConstants::MERCHANT,
                                $merchant->getId(),
                                $featureFlag);

                            if (!empty($feature)) {
                                (new Core)->delete($feature);
                            }
                        }
                        break;

                    case 'pg_shadow_onboard':

                        // first sending the request to ledger because if anything fails we don't add the feature
                        $this->ledgerPGAccountCreateRequest($merchant);

                        // Add PG_LEDGER_JOURNAL_WRITES feature to merchant
                        (new Core)->create(
                            [
                                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                Entity::ENTITY_ID => $merchant->getId(),
                                Entity::NAME => Constants::PG_LEDGER_JOURNAL_WRITES,
                            ]);
                        break;

                    case 'pg_gateway_onboard':

                        $gateway = $request['gateway'];
                        $this->ledgerPGGatewayAccountCreateRequest($merchantId, $gateway);
                        break;

                    //The case below removes feature flag from merchant
                    case 'pg_shadow_merchant_offboard':

                        $featureFlag = Constants::PG_LEDGER_JOURNAL_WRITES;
                        $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                            EntityConstants::MERCHANT,
                            $merchant->getId(),
                            $featureFlag);

                        if (!empty($feature)) {
                            (new Core)->delete($feature);
                        }
                        break;

                    case 'da_shadow_merchant_onboard':
                        // first sending the request to ledger because if anything fails we don't add the feature
                        $this->ledgerAccountCreateRequestForDirect($merchant, Constants::DA_LEDGER_JOURNAL_WRITES);

                        // Add DA_LEDGER_JOURNAL_WRITES feature to merchant
                        (new Core)->create(
                            [
                                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                Entity::ENTITY_ID => $merchant->getId(),
                                Entity::NAME => Constants::DA_LEDGER_JOURNAL_WRITES,
                            ]);
                        break;

                    case 'da_shadow_merchant_onboard_with_balance_lock':
                        // first sending the request to ledger because if anything fails we don't add the feature
                        $this->ledgerAccountCreateRequestForDirectWithBalanceLock($merchant, Constants::DA_LEDGER_JOURNAL_WRITES);

                        // Add DA_LEDGER_JOURNAL_WRITES feature to merchant
                        (new Core)->create(
                            [
                                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                Entity::ENTITY_ID => $merchant->getId(),
                                Entity::NAME => Constants::DA_LEDGER_JOURNAL_WRITES,
                            ]);
                        break;

                    // The case below removes feature flag from merchant
                    case 'da_shadow_merchant_offboard':
                        $featureFlag = Constants::DA_LEDGER_JOURNAL_WRITES;
                        $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                            EntityConstants::MERCHANT,
                            $merchant->getId(),
                            $featureFlag);

                        if (!empty($feature)) {
                            (new Core)->delete($feature);
                        }
                        break;
                }
            } catch(\Exception $e) {

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_ADD_FEATURE_OR_ACCOUNT_CREATE_ERROR,
                    [
                        Constants::MERCHANT_ID    => $request[Constants::MERCHANT_ID],
                    ]);

                $result[Constants::STATUS] = 'failed';

                $result['error'] = [
                    'description' => $e->getMessage(),
                ];
            }

            $response->add($result);
        }

        return $response;
    }

    //Calls ledger service to create merchant sub accounts for PG tenant.
    //Auto Loads Credits and Balances from current
    private function ledgerPGAccountCreateRequest($merchant)
    {
            $merchantId = $merchant->getId();

            // Fetch Merchant balance. Required to generate request body for account creation on ledger
            $balance = $this->repo->balance->getBalanceLockForUpdate($merchantId);

            //fetches fee, amount and refund credits from credits table
            $creditBalances = $this->repo->credits->getTypeAggregatedMerchantCreditsLockForUpdate($merchantId);

            $isAccountCreated = (new Merchant\Balance\Ledger\Core)->createPGLedgerAccount(
                $merchant,
                $this->mode,
                $balance->getBalance(),
                $creditBalances
            );
            return $isAccountCreated;

    }

    private function updatePGMerchantBalanceAccount($merchant): array
    {
        $merchantId = $merchant->getId();

        // Taking lock on balance table
        $balance = $this->repo->balance->getBalanceLockForUpdate($merchantId);

        return (new Merchant\Balance\Ledger\Core)->updatePGMerchantBalance($merchant, $balance->getBalance());
    }

    private function updatePGMerchantCreditsAccounts($merchant): array
    {
        $merchantId = $merchant->getId();

        //fetches fee, amount and refund credits from credits table
        $creditBalances = $this->repo->credits->getTypeAggregatedMerchantCreditsLockForUpdate($merchantId);

        return (new Merchant\Balance\Ledger\Core)->updatePGLedgerMerchantCreditBalances($merchant, $creditBalances);
    }

    private function ledgerPGGatewayAccountCreateRequest(string $merchantId, string $gateway)
    {

        (new Merchant\Balance\Ledger\Core)->createPGLedgerGatewayAccount(
            $merchantId,
            $this->mode,
            $gateway
        );
    }

    private function ledgerAccountCreateRequest($merchant)
    {
        // Fetch Merchant balance. Required to generate request body for account creation on ledger
        $balance = $this->repo->balance->getMerchantBalanceByTypeAndAccountType(
            $merchant->getId(),
            BalanceType::BANKING,
            AccountType::SHARED,
            $this->mode);

        // Fetch Merchant banking account. Required to generate request body for account creation on ledger
        $bankingAcc = $this->repo->banking_account->getFromBalanceId($balance->getId());

        // credit balance initialized (rewards)
        $currentMerchantCredits = 0;

        // Fetch Merchant Credit balance. And update the balance value if the credit has 1 element
        $creditBalances = $this->repo->credits->getTypeAggregatedMerchantCreditsForProductForDashboard($merchant->getId(), BalanceType::BANKING);

        foreach ($creditBalances as $creditBalance)
        {
            $currentMerchantCredits += $creditBalance[BalanceEntity::BALANCE];
        }

        $this->trace->info(TraceCode::LEDGER_JOURNAL_WRITES_FEATURE_ASSIGNED,
            [
                Constants::MERCHANT_ID => $merchant->getId(),
                Constants::MODE        => $this->mode,
                'balance_id'           => $balance->getId(),
                'banking_account_id'   => $bankingAcc->getId(),
            ]);

        (new Merchant\Balance\Ledger\Core)->createXLedgerAccount(
            $merchant,
            $bankingAcc,
            $this->mode,
            AccountType::SHARED,
            $balance->getBalance(),
            $currentMerchantCredits);
    }

    private function ledgerAccountCreateRequestWithBalanceLock($merchant)
    {
        $this->repo->transaction(function () use ($merchant) {
            // Fetch Merchant balance. Required to generate request body for account creation on ledger
            $balance = $this->repo->balance->getMerchantBalanceByTypeAndAccountTypeForUpdate(
                $merchant->getId(),
                BalanceType::BANKING,
                AccountType::SHARED,
                $this->mode);

            // Fetch Merchant banking account. Required to generate request body for account creation on ledger
            $bankingAcc = $this->repo->banking_account->getFromBalanceId($balance->getId());

            // credit balance initialized (rewards)
            $currentMerchantCredits = 0;

            // Fetch Merchant Credit balance by taking lock on credit rows.
            $creditBalances = $this->repo->credits->getCreditsSortedByExpiryForProduct(time(),
                $merchant->getId(),
                Merchant\Credits\Type::REWARD_FEE,
                Product::BANKING);

            foreach ($creditBalances as $creditBalance)
            {
                // This way of locking by id is better than locking on getCreditsSortedByExpiryForProduct
                // Main idea behind this is locking by primary key will be quicker and is
                // recommended by DBA
                $this->repo->credits->getCreditLockForUpdate($creditBalance);

                $currentMerchantCredits += $creditBalance[Credits\Entity::VALUE] - $creditBalance[Credits\Entity::USED];
            }

            $this->trace->info(TraceCode::LEDGER_JOURNAL_WRITES_FEATURE_ASSIGNED,
                [
                    Constants::MERCHANT_ID => $merchant->getId(),
                    Constants::MODE        => $this->mode,
                    'balance_id'           => $balance->getId(),
                    'banking_account_id'   => $bankingAcc->getId(),
                ]);

            // passing isReverseShadow parameter as true to ensure sync onboarding of merchant on ledger
            (new Merchant\Balance\Ledger\Core)->createXLedgerAccount(
                $merchant,
                $bankingAcc,
                $this->mode,
                AccountType::SHARED,
                $balance->getBalance(),
                $currentMerchantCredits,
                true);
        });
    }

    private function ledgerAccountCreateRequestForDirect($merchant, $featureName)
    {
        // Fetch Merchant balances. Required to generate request body for direct accounts creation on ledger
        $balances = $this->repo->balance->getMerchantBalancesByTypeAndAccountType(
            $merchant->getId(),
            BalanceType::BANKING,
            AccountType::DIRECT,
            $this->mode);

        // onboard all accounts of the merchant
        foreach ($balances as $balance) {
            // Fetch Merchant banking account. Required to generate request body for account creation on ledger
            $bankingAccStmtDetails = $this->repo->banking_account_statement_details->getDirectBasDetailEntityByMerchantAndBalanceId($merchant->getId(), $balance->getId());

            // credit balance initialized (rewards)
            $currentMerchantCredits = 0;

            // Fetch Merchant Credit balance. And update the balance value if the credit has 1 element
            $creditBalances = $this->repo->credits->getTypeAggregatedMerchantCreditsForProductForDashboard($merchant->getId(), BalanceType::BANKING);

            foreach ($creditBalances as $creditBalance)
            {
                $currentMerchantCredits += $creditBalance[BalanceEntity::BALANCE];
            }

            $this->trace->info(TraceCode::DA_LEDGER_FEATURE_ASSIGNED,
                [
                    Constants::MERCHANT_ID              => $merchant->getId(),
                    Constants::MODE                     => $this->mode,
                    'balance_id'                        => $balance->getId(),
                    'banking_account_stmt_detail_id'    => $bankingAccStmtDetails->getId(),
                    'feature_name'                      => $featureName
                ]);

            (new Merchant\Balance\Ledger\Core)->createXLedgerAccountForDirect(
                $merchant,
                $bankingAccStmtDetails,
                $this->mode,
                $balance->getBalance(),
                $currentMerchantCredits);
        }
    }

    private function ledgerAccountCreateRequestForDirectWithBalanceLock($merchant, $featureName)
    {
        $this->repo->transaction(function () use ($merchant, $featureName) {
            // Fetch Merchant balances. Required to generate request body for direct accounts creation on ledger
            $balances = $this->repo->balance->getMerchantBalanceByTypeAndAccountTypeForUpdate(
                $merchant->getId(),
                BalanceType::BANKING,
                AccountType::DIRECT,
                $this->mode);

            // onboard all accounts of the merchant
            foreach ($balances as $balance) {
                // Fetch Merchant banking account. Required to generate request body for account creation on ledger
                $bankingAccStmtDetails = $this->repo->banking_account_statement_details->getDirectBasDetailEntityByMerchantAndBalanceId($merchant->getId(), $balance->getId());

                // credit balance initialized (rewards)
                $currentMerchantCredits = 0;

                // Fetch Merchant Credit balance by taking lock on credit rows.
                $creditBalances = $this->repo->credits->getCreditsSortedByExpiryForProduct(time(),
                    $merchant->getId(),
                    Merchant\Credits\Type::REWARD_FEE,
                    Product::BANKING);

                foreach ($creditBalances as $creditBalance) {
                    // This way of locking by id is better than locking on getCreditsSortedByExpiryForProduct
                    // Main idea behind this is locking by primary key will be quicker and is
                    // recommended by DBA
                    $this->repo->credits->getCreditLockForUpdate($creditBalance);

                    $currentMerchantCredits += $creditBalance[Credits\Entity::VALUE] - $creditBalance[Credits\Entity::USED];
                }

                $this->trace->info(TraceCode::DA_LEDGER_FEATURE_ASSIGNED,
                    [
                        Constants::MERCHANT_ID => $merchant->getId(),
                        Constants::MODE => $this->mode,
                        'balance_id' => $balance->getId(),
                        'banking_account_stmt_detail_id' => $bankingAccStmtDetails->getId(),
                        'feature_name' => $featureName
                    ]);

                (new Merchant\Balance\Ledger\Core)->createXLedgerAccountForDirect(
                    $merchant,
                    $bankingAccStmtDetails,
                    $this->mode,
                    $balance->getBalance(),
                    $currentMerchantCredits);
            }
        });
    }

    public function addAccountFeatures(array $input): array
    {
        $features = $input[Entity::NAMES] ?? [];

        $data[Constants::FEATURES] = [];

        foreach ($features as $feature)
        {
            $data[Constants::FEATURES][$feature] = '1';
        }

        $merchant = $this->merchant;

        $EsOnDemandFeature = $this->repo->feature->findByEntityTypeEntityIdAndName(
            $merchant->getEntity(),
            $merchant->getId(),
            Constants::ES_ON_DEMAND);

        $data['es_enabled'] = ($EsOnDemandFeature === null) ? false : true;

        $merchantValidator = new Merchant\Validator;

        $merchantValidator->validateVisibleAndEditableFeatures($data);

        // Do not allow the merchant to update the product features in live mode. Eg: marketplace
        $merchantValidator->validateModeForProductFeatures($data);

        return $this->addFeatures($input);
    }

    public function getFeatures($routeEndpoint, $entityId)
    {
        //
        // Allow only the admins to provide the entity_type and entity_id from the input.
        // If the merchant is hitting the route directly, only allow him to update his own account features.
        //
        if (($this->app['basicauth']->isAdminAuth() === true) or
            ($this->app['basicauth']->isCapitalCollectionsApp() === true) or
            ($this->app['basicauth']->isCapitalLOSApp() === true))
        {
            $entityType = Type::getEntityTypeFromRoute($routeEndpoint);
        }
        else if ($this->app['basicauth']->isAppAuth() === true)
        {
            $entityType = Type::getEntityTypeFromRoute($routeEndpoint);
        }
        else
        {
            $entityType = Constants::MERCHANT;

            $entityId = $this->merchant->getId();
        }

        $response = new Base\Collection;

        $response['assigned_features'] = $this->repo
            ->feature
            ->fetchByEntityTypeAndEntityId($entityType, $entityId);

        $response['all_features'] =  array_keys(Constants::$featureValueMap);

        return $response;
    }

    public function getOrgFeatures($entityType, $entityId)
    {
        $response = new Base\Collection;

        $response['assigned_features'] = $this->repo
            ->feature
            ->fetchByEntityTypeAndEntityId($entityType, $entityId);

        $response['all_features'] =  array_keys(Constants::$featureValueMap);

        return $response;
    }

    /**
     * get the feature status associated with an entity
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $featureName
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function checkFeatureEnabled($entityType = Constants::MERCHANT, $entityId, $featureName): array
    {
        // Not removed from params
        // As in future iteration, there will be some checks added for Entity Type and ID
        // Exact requirement is not finalised yet. Only this will be rolled out in Iteration 1

        $featureCore = new Core;

        $entityId = $entityId ?? $this->merchant->getId();

        return $featureCore->getStatus($entityType, $entityId, $featureName);
    }

    public function bulkFetchFeatures(array $input): array
    {
        (new Validator())->validateInput('bulk_fetch_features', $input);

        $featuresEnabled = $this->repo->feature
            ->findMerchantWithFeatures($input[Entity::ENTITY_ID], $input['features'])
            ->pluck(Entity::NAME)
            ->toArray();

        $featuresEnabled = array_flip($featuresEnabled);

        $featuresStatus = [];

        foreach ($input['features'] as $feature) {
            if (array_key_exists($feature, $featuresEnabled)) {
                $featuresStatus[$feature] = true;
            } else {
                $featuresStatus[$feature] = false;
            }
        }

        return ['features' => $featuresStatus];
    }

    /**
     * Delete the feature association with an entity
     *
     * @param string $routeEndpoint
     * @param string $entityId
     * @param string $featureName
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function deleteEntityFeature(
        string $routeEndpoint,
        string $entityId,
        string $featureName,
        array $input): array
    {
        $entityType = Type::getEntityTypeFromRoute($routeEndpoint);

        $feature = $this->repo
            ->feature
            ->findByEntityTypeEntityIdAndNameOrFail(
                $entityType,
                $entityId,
                $featureName);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $core = new Core();

        $core->checkAndDisableRxLedgerAndPayoutFeatureChanges($feature->getName());

        $core->checkAndDisableFeatureChangesForPayoutServiceIdempotencyFeatures($featureName);

        $core->checkAndDisableSubAccountFeatureChange($featureName);

        $core->delete($feature, $shouldSync);

        // We delete the tag also along with feature.
        $this->deleteTagIfApplicable($entityType, $entityId, $feature->getName());

        return $feature->toArrayDeleted();
    }

    /**
     * Delete the tag if the entity type is merchant
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $featureName
     */
    protected function deleteTagIfApplicable(string $entityType, string $entityId, string $featureName)
    {
        if ($entityType !== Constants::MERCHANT)
        {
            return;
        }

        (new Merchant\Service)->deleteTag($entityId, $featureName);
    }

    public function multiAssignFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_ASSIGN_REQUEST, $input);

        $this->increaseAllowedSystemLimits();

        $entityIds = $input[Constants::ENTITY_IDS];

        foreach ($entityIds as $entityId)
        {
            $validateInput = $input;

            $validateInput['names'] = $input['name'];

            $this->validateIfDisabledFeaturesArePresent($validateInput,$input['entity_type'],$entityId);

            $this->validateMCCForBulkPaymentPageFeature($validateInput,$input['entity_type'],$entityId);
        }

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $names = $input[Entity::NAME];

        // Will separately update dashboard to start
        // sending a list of features in a single request
        $names = (is_array($input[Entity::NAME]) ? $names : [$input[Entity::NAME]]);

        $validator = new Validator();

        $validator->validateForRouteLaPennyTestingFeature($names);

        $opsResponse = $successResponse = $failedResponse = [];

        foreach ($names as $featureName)
        {
            $failedMerchant = $successfulMerchant = [];

            $dimension = [
                Entity::ENTITY_TYPE     => $input[Entity::ENTITY_TYPE],
                Entity::NAME            => $featureName,
            ];

            foreach ($entityIds as $entityId)
            {
                $featureParam = [
                    Entity::ENTITY_TYPE => $input[Entity::ENTITY_TYPE],
                    Entity::ENTITY_ID   => $entityId,
                    Entity::NAME        => $featureName,
                    'tokenization_gateways' => Arr::wrap($input['tokenization_gateways'] ?? []),
                ];

                try
                {
                    $core = new Core();

                    $core->checkAndDisableRxLedgerAndPayoutFeatureChanges($featureName);

                    $core->checkAndDisableFeatureChangesForPayoutServiceIdempotencyFeatures($featureName);

                    $core->checkAndDisableSubAccountFeatureChange($featureName);

                    $core->create($featureParam, $shouldSync);

                    $successfulMerchant[] = $entityId;

                    $this->trace->count(FeatureMetric::FEATURE_ASSIGN_TOTAL, $dimension);
                }
                catch (\Exception $e)
                {
                    $failedMerchant[] = $entityId;

                    $this->trace->traceException($e);

                    $this->trace->warning(
                        TraceCode::FEATURE_ASSIGNMENT_EXCEPTION,
                        [
                            'msg' => $e->getMessage()
                        ]);

                    $this->trace->count(FeatureMetric::FEATURE_ASSIGN_FAILURE_TOTAL, $dimension);
                }
            }
            if (count($failedMerchant) > 0)
            {
                $failedResponse[$featureName] = $failedMerchant;
            }

            if (count($successfulMerchant) > 0)
            {
                $successResponse[$featureName] = $successfulMerchant;
            }
        }

        $opsResponse['successful'] = $successResponse;

        $opsResponse['failed'] = $failedResponse;

        $this->trace->info(TraceCode::MERCHANT_MULTI_FEATURE_ASSIGN_RESPONSE, $opsResponse);

        return $opsResponse;
    }

    public function multiRemoveFeature($input)
    {
        $this->trace->info(TraceCode::FEATURE_MULTI_REMOVE_REQUEST, $input);

        $this->increaseAllowedSystemLimits();

        $entityIds = $input[Constants::ENTITY_IDS];

        $entityType = $input[Entity::ENTITY_TYPE];

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $names = $input[Entity::NAME];

        // Will separately update dashboard to start
        // sending a list of features in a single request
        $names = (is_array($input[Entity::NAME]) ? $names : [$input[Entity::NAME]]);

        $opsResponse = $successResponse = $failedResponse = [];

        $entityType = $input[Constants::ENTITY_TYPE] ?? Constants::MERCHANT;

        $core = new Core();

        foreach ($names as $featureName)
        {
            $failedMerchant = $successfulMerchant = [];

            $dimension = [
                Entity::ENTITY_TYPE     => $input[Entity::ENTITY_TYPE],
                Entity::NAME            => $featureName,
            ];

            foreach ($entityIds as $entityId)
            {
                try
                {
                    $core->checkAndDisableRxLedgerAndPayoutFeatureChanges($featureName);

                    $core->checkAndDisableFeatureChangesForPayoutServiceIdempotencyFeatures($featureName);

                    $core->checkAndDisableSubAccountFeatureChange($featureName);

                    $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                        $entityType,
                        $entityId,
                        $featureName);

                    if (!empty($feature))
                    {
                        (new Core)->delete($feature, $shouldSync);

                        array_push($successfulMerchant, $entityId);

                        $this->trace->count(FeatureMetric::FEATURE_REMOVE_TOTAL, $dimension);
                    }
                }
                catch (\Throwable $e)
                {
                    array_push($failedMerchant, $entityId);
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::MERCHANT_FEATURE_NOT_EXIST,
                        $failedMerchant);

                    $this->trace->count(FeatureMetric::FEATURE_REMOVE_FAILURE_TOTAL, $dimension);
                }
            }
            if (count($failedMerchant) > 0)
            {
                $failedResponse[$featureName] = $failedMerchant;
            }

            if (count($successfulMerchant) > 0)
            {
                $successResponse[$featureName] = $successfulMerchant;
            }
        }

        $opsResponse['successful'] = $successResponse;

        $opsResponse['failed'] = $failedResponse;

        $this->trace->info(TraceCode::MERCHANT_MULTI_FEATURE_REMOVE_RESPONSE, $opsResponse);

        return $opsResponse;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(300);
    }

    public function getFeaturesForMerchantPublic(Merchant\Entity $merchant, $roleEnabledFeatures = [])
    {
        $data['features'] = [];

        $enabledFeatures = $merchant->getEnabledFeatures();

        $enabledFeatures = array_unique(array_merge($enabledFeatures, $roleEnabledFeatures));

        foreach (Constants::$visibleFeaturesMap as $visibleFeature => $featureDetails)
        {
            $feature = $featureDetails['feature'];

            $isEnabled = in_array($feature, $enabledFeatures, true);

            $data['features'][] = [
                'feature'      => $visibleFeature,
                'value'        => $isEnabled,
                'display_name' => $featureDetails['display_name']
            ];
        }

        return $data;
    }

    /**
     * Returns all the questions required for onboarding features
     *
     * @param  array $input
     *
     * @return array
     */
    public function getOnboardingDetails(array $input): array
    {
        $response['questions'] = $this->getOnboardingQuestions($input);

        $response['submissions'] = $this->getOnboardingSubmissions();

        return $response;
    }

    /**
     * Returns all the questions required for onboarding features
     *
     * @param  array $input
     *
     * @return array
     */
    public function getOnboardingQuestions(array $input): array
    {
        (new Validator)->validateInput('onboarding_questions', $input);

        $features = $input[Constants::FEATURES];

        return (new Core)->getOnboardingQuestions($features);
    }

    /**
     * Saves the merchant responses to the onboarding questions
     *
     * @param array  $input
     * @param string $feature
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function postOnboardingSubmissions(array $input, string $feature): bool
    {
        // Product onboarding submissions must only be inserted in the live mode. Force set the connection to live.
        $liveMode = $this->app['basicauth']->getLiveConnection();

        // Sets the mode for the request, and database connection
        $this->core()->setModeAndDefaultConnection($liveMode);

        $status = $this->core()->postOnboardingSubmissions($this->merchant, $input, $feature);

        return $status;
    }

    /**
     * Updates the merchant responses to the onboarding questions
     *
     * @param array  $input
     * @param string $feature
     *
     * @return bool
     */
    public function updateOnboardingSubmissions(array $input, string $feature): bool
    {
        // Product onboarding submissions must only be inserted in the live mode. Force set the connection to live.
        $liveMode = $this->app['basicauth']->getLiveConnection();

        // Sets the mode for the request, and database connection
        $this->core()->setModeAndDefaultConnection($liveMode);

        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        unset($input['merchant_id']);

        $data[$feature] = $input;

        $status = (new Core)->processOnboardingSubmissions(Constants::UPDATE, $data, $merchant);

        return $status;
    }

    /**
     * @param string|null $feature
     *
     * @return array
     */
    public function getOnboardingSubmissions(string $feature = null)
    {
        $settings = (new Core)->getOnboardingSubmissions($this->merchant, $feature);

        return $settings;
    }

    /**
     * Allow only the admins to provide the entity_type and entity_id from the input.
     * If the merchant is hitting the route directly, only allow him to update his own account features.
     * Allowing services mentioned in ALLOWED_INTERNAL_APPS_FOR_ENTITY_TYPE to add the feature:
     *
     * Allowing partners to add some capital features to submerchants.
     *
     * temp: Currently have to allow enabling of ONLY_DS flag for unauthenticated merchants to stick with compliance
     *
     * @param array $featureNames
     *
     * @return bool
     */
    protected function allowEntityTypeInInput(array $featureNames): bool
    {
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            return true;
        }

        $internalApp = $this->app['basicauth']->getInternalApp();

        if(in_array($internalApp, self::ALLOWED_INTERNAL_APPS_FOR_ENTITY_TYPE) === true)
        {
            return true;
        }

        if ($this->canCreateWithoutMerchantAuth($featureNames) === true)
        {
            return true;
        }

        // if partner uses batch upload feature to add sub-merchants,
        // we receive X-Entity-Id header with partner ID.
        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;

        // if the header is not present, partner might be adding sub-merchant from partner dashboard
        // in this case get the partner ID from basicauth
        if(empty($merchantId) === true)
        {
            $merchantId = optional($this->merchant)->getId() ?? null;
        }

        if(empty($merchantId) === false)
        {
            return (new CapitalSubmerchantUtility())->canPartnerAddFeatureForSubmerchant($featureNames, $merchantId);
        }

        return false;
    }

    protected function buildFeatureParams(
        array $input,
        string $entityType = null,
        string $entityId = null): Base\Collection
    {
        $featureParams = new Base\Collection;

        $featureNames = $input[Constants::NAMES];

        if ($this->allowEntityTypeInInput($featureNames) === true)
        {
            $entityType = $entityType ?? $input[Entity::ENTITY_TYPE];

            $entityId = $entityId ?? $input[Entity::ENTITY_ID];
        }
        else
        {
            $entityType = Constants::MERCHANT;

            $entityId = $this->merchant->getId();
        }

        foreach ($featureNames as $featureName)
        {
            $featureParams->push([
                                     Entity::ENTITY_TYPE => $entityType,
                                     Entity::ENTITY_ID   => $entityId,
                                     Entity::NAME        => $featureName
                                 ]);
        }

        return $featureParams;
    }

    private function canCreateWithoutMerchantAuth($featureNames)
    {
        foreach ($featureNames as $featureName)
        {
            if (in_array($featureName, Constants::FEATURES_WITHOUT_MERCHANT_AUTHENTICATION) === true)
            {
                return true;
            }
        }

        return false;
    }
    /**
     * @deprecated by getFeatureOnboardingRequests()
     *
     * @param array $input
     *
     * @return array
     */
    public function getFeatureOnboardingRequestsByStatus(array $input): array
    {
        $status = $input[Constants::STATUS];

        return $this->repo->merchant_detail->getFeatureOnboardingRequestsByStatus($status);
    }

    /**
     * Returns the feature activation requests based on the status
     *
     * @param array $input
     *
     * @return array
     */
    public function getFeatureOnboardingRequests(array $input): array
    {
        (new Validator)->validateInput(Constants::ONBOARDING_SUBMISSIONS_FETCH, $input);

        return $this->repo->merchant_detail->getFeatureOnboardingRequests($input);
    }

    /**
     * @param string $featureName
     * @param array  $input
     *
     * @return array
     */
    public function updateFeatureActivationStatus(string $featureName, array $input): array
    {
        $status = $input[Constants::STATUS];

        $merchantId = $input['merchant_id'];

        $response = (new Core)->updateFeatureActivationStatus($merchantId, $featureName, $status);

        return $response;
    }

    /**
     * @param string $featureName
     * @param array  $input
     *
     * @return array
     */
    public function getFeatureActivationStatus(string $featureName, array $input)
    {
        $merchantId = $input['merchant_id'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $status =  $this->repo->merchant_detail->getFeatureActivationStatus(
            $merchant,
            $featureName
        );

        $response[Constants::STATUS] = $status;

        return $response;
    }

    /**
     * Bulk updates the feature activation status for multiple merchants
     *
     * @param array $input
     *
     * @return array
     */
    public function bulkUpdateFeatureActivationStatus(array $input): array
    {
        $success   = 0;
        $failed    = 0;
        $failedIds = [];

        $core = new Core;

        foreach (Constants::PRODUCT_FEATURES as $productFeature)
        {
            if (isset($input[$productFeature]) === true)
            {
                $productResponse = $core->bulkUpdateFeatureActivationStatus($productFeature, $input[$productFeature]);

                $success += $productResponse['success'];

                $failed += $productResponse['failed'];

                if ($productResponse['failed'] > 0)
                {
                    $failedIds[$productFeature] = $productResponse['failed_ids'];
                }
            }
        }

        $response = [
            'success'    => $success,
            'failed'     => $failed,
            'failed_ids' => $failedIds
        ];

        return $response;
    }

    /**
     *
     * Gets merchant IDs having the given features
     *
     * @param array $input
     * @return array
     */
    public function getMerchantIdsHavingFeatures(array $input)
    {
        (new Validator)->validateInput('merchants_with_features', $input);

        $featureNames = $input[Constants::FEATURES];

        return $this->repo->feature->findMerchantIdsHavingFeatures($featureNames);
    }

    /**
     *
     * Validates if featureNames are valid
     *
     * @param array $featureNames
     */
    public function validateFeatureNames(array $featureNames)
    {
        (new Validator)->validateFeatureNames($featureNames);
    }

    /**
     *
     * Onboards merchant onto ledger service
     * Creates feature for the merchant
     *
     * @param array $input
     */
    public function onboardMerchantOnPG(array $input)
    {
        $response = new Base\PublicCollection;
        $merchantIds = $input["merchant_ids"];

        if(empty($merchantIds))
        {
            return [
              Constants::MESSAGE => Constants::BAD_REQUEST_MERCHANT_ID_ABSENT
            ];
        }

        foreach ($merchantIds as $merchantId)
        {

            $result = [
                Constants::MERCHANT_ID     => $merchantId,
                Constants::STATUS          => Constants::SUCCESS,
                CONSTANTS::FEATURE         => CONSTANTS::PG_LEDGER_JOURNAL_WRITES
            ];

            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if($merchant->isFeatureEnabled(Constants::PG_LEDGER_JOURNAL_WRITES))
                {
                    throw new \Exception(Constants::MERCHANT_FEATURE_ALREADY_ENABLED);
                }

                $this->repo->transaction(function () use ($merchant, $merchantId)
                {
                    // Create PG account on ledger service
                    $isAccountCreated = $this->ledgerPGAccountCreateRequest($merchant);

                    if (!$isAccountCreated)
                    {
                        throw new \Exception(Constants::ACCOUNT_CREATION_FAILED);
                    }

                    // Add PG_LEDGER_JOURNAL_WRITES feature to merchant
                    (new Core)->create(
                        [
                            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                            Entity::ENTITY_ID => $merchant->getId(),
                            Entity::NAME => Constants::PG_LEDGER_JOURNAL_WRITES,
                        ]);

                    $this->trace->info(
                        TraceCode::MERCHANT_ONBOARDED_TO_PG_LEDGER,
                        [
                            Constants::MERCHANT_ID => $merchantId,
                            CONSTANTS::FEATURE => CONSTANTS::PG_LEDGER_JOURNAL_WRITES
                        ]
                    );
                });

                $result[Constants::MESSAGE] = CONSTANTS::MERCHANT_ONBOARDED;

            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::ACCOUNT_CREATE_OR_FEATURE_ADD_FAILED,
                    [
                        "exception"             => $e,
                        "message"               => $e->getMessage(),
                        Constants::MERCHANT_ID  => $merchantId
                    ]
                );

                $result[Constants::STATUS] = Constants::FAILURE;
                $result[Constants::MESSAGE] = $e->getMessage();
            }
            $response->add($result);
        }
        return $response;
    }

    /**
     *
     * Offboards merchant from ledger service
     * Removes feature for the merchant
     *
     * @param array $input
     */
    public function offboardMerchantOnPG(array $input)
    {
        $response = new Base\PublicCollection;

        if(!isset( $input['merchant_ids']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                'merchant_ids',
                null,
                "merchant_ids key is missing"
            );
        }

        $merchantIds = $input["merchant_ids"];

        if(empty($merchantIds))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMPTY_PAYLOAD_ERROR,
                null,
                null
            );
        }
        foreach ($merchantIds as $merchantId)
        {
            $result = [
                Constants::MERCHANT_ID     => $merchantId,
                Constants::STATUS          => Constants::SUCCESS,
                CONSTANTS::FEATURE         => CONSTANTS::PG_LEDGER_JOURNAL_WRITES
            ];

            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if(!$merchant->isFeatureEnabled(Constants::PG_LEDGER_JOURNAL_WRITES))
                {
                    throw new \Exception(Constants::MERCHANT_FEATURE_ALREADY_DISABLED);
                }

                //Remove PG_LEDGER_JOURNAL_WRITES feature from  merchant
                $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                    EntityConstants::MERCHANT,
                    $merchant->getId(),
                    Constants::PG_LEDGER_JOURNAL_WRITES);

                if (!empty($feature))
                {
                    (new Core)->delete($feature);
                }

                $this->trace->info(
                    TraceCode::MERCHANT_OFFBOARDED_FROM_PG_LEDGER,
                    [
                        Constants::MERCHANT_ID  => $merchantId,
                        CONSTANTS::FEATURE => CONSTANTS::PG_LEDGER_JOURNAL_WRITES
                    ]
                );

                $result[Constants::MESSAGE] = CONSTANTS::MERCHANT_OFFBOARDED;

            }
            catch(\Exception $e)
            {
                $this->trace->error(
                    TraceCode::FEATURE_REMOVE_FAILED,
                    [
                        "exception"             => $e,
                        "message"               => $e->getMessage(),
                        Constants::MERCHANT_ID  => $merchantId
                    ]
                );

                $result[Constants::STATUS] = Constants::FAILURE;
                $result[Constants::MESSAGE] = $e->getMessage();
            }

            $response->add($result);
        }
        return $response;
    }


    /**
     *
     * Syncs api balances of merchant with ledger balance
     *
     * @param array $input
     */
    public function syncMerchantBalancesOnPgLedger(array $input)
    {
        $response = new Base\PublicCollection;
        $merchantIds = $input["merchant_ids"];

        if(empty($merchantIds))
        {
            return [
                Constants::MESSAGE => Constants::BAD_REQUEST_MERCHANT_ID_ABSENT
            ];
        }

        foreach ($merchantIds as $merchantId)
        {

            $result = [
                Constants::MERCHANT_ID     => $merchantId,
                Constants::STATUS          => Constants::SUCCESS
            ];

            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                $this->repo->transaction(function () use ($merchant, $merchantId, &$result)
                {
                   $result[Constants::BALANCE_RESPONSE] = $this->updatePGMerchantBalanceAccount($merchant);
                });

                $this->repo->transaction(function () use ($merchant, $merchantId, &$result)
                {
                    $result[Constants::CREDITS_RESPONSE] = $this->updatePGMerchantCreditsAccounts($merchant);
                });
            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::MERCHANT_BALANCE_SYNC_FAILED,
                    [
                        "exception"             => $e,
                        "message"               => $e->getMessage(),
                        Constants::MERCHANT_ID  => $merchantId
                    ]
                );

                $result[Constants::STATUS] = Constants::FAILURE;
                $result[Constants::MESSAGE] = $e->getMessage();
            }
            $response->add($result);
        }
        return $response;
    }

    public function processDcsMigrationJob(array $input): array
    {
        if ((isset($input['flow']) === true) and ($input['flow'] === 'validate'))
        {
            // Job to validate the entities in DCS and API for a flag
            ValidateFeaturesAPIAndDCS::dispatchNow($input, $this->mode);
            return [
                'response' => 'DCS Features validate Job dispatched',
            ];
        }
        else
        {
            AssignFeatures::dispatchNow($input, $this->mode);
            return [
                'response' => 'DCS Features Assign Job dispatched',
            ];
        }
    }

    public function removePayoutServiceIntermediateIdempotencyFeatures()
    {
        // Get start of day (i.e. 00 hours) timestamp for 7th previous day, features enabled before this time should be
        // removed.
        $beforeTimestamp = Carbon::now(Timezone::IST)->subDays(7)->startOfDay()->getTimestamp();

        $merchantIdsWithIdempotencyApiToPs = $this->repo->feature->fetchMerchantIdsWithFeatureWithPagination(
            Constants::IDEMPOTENCY_API_TO_PS,
            0,
            self::PAYOUT_SERVICE_IDEMPOTENCY_KEY_INTERMEDIATE_FEATURES_FETCH_LIMIT,
            null,
            $beforeTimestamp
        );

        $merchantIdsWithIdempotencyPsToApi = $this->repo->feature->fetchMerchantIdsWithFeatureWithPagination(
            Constants::IDEMPOTENCY_PS_TO_API,
            0,
            self::PAYOUT_SERVICE_IDEMPOTENCY_KEY_INTERMEDIATE_FEATURES_FETCH_LIMIT,
            null,
            $beforeTimestamp
        );

        $successfulMerchantIdsWithIdempotencyApiToPs = [];

        $failedMerchantIdsWithIdempotencyApiToPs = [];

        foreach ($merchantIdsWithIdempotencyApiToPs as $merchantId)
        {
            try
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_API_TO_PS_FEATURE_REMOVE_REQUEST,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                    ]
                );

                $this->deleteEntityFeature(Type::ACCOUNTS, $merchantId, Constants::IDEMPOTENCY_API_TO_PS, []);

                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_API_TO_PS_FEATURE_REMOVE_SUCCESS,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                    ]
                );

                array_push($successfulMerchantIdsWithIdempotencyApiToPs, $merchantId);
            }

            catch (\Throwable $throwable)
            {
                $this->trace->error(
                    TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_API_TO_PS_FEATURE_REMOVE_FAILED,
                    [
                        "exception"            => $throwable,
                        "message"              => $throwable->getMessage(),
                        "code"                 => $throwable->getCode(),
                        Constants::MERCHANT_ID => $merchantId,
                    ]
                );

                array_push($failedMerchantIdsWithIdempotencyApiToPs, $merchantId);
            }
        }

        $successfulMerchantIdsWithIdempotencyPsToApi = [];

        $failedMerchantIdsWithIdempotencyPsToApi = [];

        foreach ($merchantIdsWithIdempotencyPsToApi as $merchantId)
        {
            try
            {
                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_PS_TO_API_FEATURE_REMOVE_REQUEST,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                    ]
                );

                $this->deleteEntityFeature(Type::ACCOUNTS, $merchantId, Constants::IDEMPOTENCY_PS_TO_API, []);

                $this->trace->info(
                    TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_PS_TO_API_FEATURE_REMOVE_SUCCESS,
                    [
                        Constants::MERCHANT_ID => $merchantId,
                    ]
                );

                array_push($successfulMerchantIdsWithIdempotencyPsToApi, $merchantId);
            }

            catch (\Throwable $throwable)
            {
                $this->trace->error(
                    TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_PS_TO_API_FEATURE_REMOVE_FAILED,
                    [
                        "exception"            => $throwable,
                        "message"              => $throwable->getMessage(),
                        "code"                 => $throwable->getCode(),
                        Constants::MERCHANT_ID => $merchantId,
                    ]
                );

                array_push($failedMerchantIdsWithIdempotencyPsToApi, $merchantId);
            }
        }

        $response = [
            Constants::IDEMPOTENCY_API_TO_PS => [
                Constants::SUCCESS => count($successfulMerchantIdsWithIdempotencyApiToPs),
                Constants::FAILURE => count($failedMerchantIdsWithIdempotencyApiToPs),
            ],
            Constants::IDEMPOTENCY_PS_TO_API => [
                Constants::SUCCESS => count($successfulMerchantIdsWithIdempotencyPsToApi),
                Constants::FAILURE => count($failedMerchantIdsWithIdempotencyPsToApi),
            ]
        ];

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_IDEMPOTENCY_KEY_INTERMEDIATE_FEATURE_REMOVE_RESPONSE,
            $response
        );

        return $response;
    }
}
