<?php

namespace RZP\Models\Feature;

use Illuminate\Support\Arr;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\Feature\Metric as FeatureMetric;
use RZP\Models\Merchant\Balance\Type as BalanceType;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Service extends Base\Service
{
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

        $featureParams = $this->buildFeatureParams($input, $entityType, $entityId);

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $featureCore = new Core;

        $features = $featureParams->map(function ($item) use ($featureCore, $shouldSync)
        {
            return $featureCore->create($item, $shouldSync);
        });

        return $features->toArray();
    }

    public function addFeatureAndOnboardOldAccountsToLedger(array $input)
    {
        $response = new Base\PublicCollection;

        foreach ($input as $request) {

            $result = [
                Constants::IDEMPOTENCY_KEY => $request[Constants::IDEMPOTENCY_KEY],
                Constants::MERCHANT_ID     => $request[Constants::MERCHANT_ID],
                Constants::STATUS          => 'success'
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

                    case 'offboard':
                        //Delete `ledger_journal_reads` feature flag
                        //Delete `ledger_reverse_shadow` feature flag
                        //Delete `ledger_journal_writes` feature flag
                        $featureFlags = [
                            Constants::LEDGER_JOURNAL_READS,
                            Constants::LEDGER_REVERSE_SHADOW,
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
                        $this->ledgerAccountCreateRequestForDirect($merchant);

                        // Add LEDGER_JOURNAL_WRITES feature to merchant
                        (new Core)->create(
                            [
                                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                                Entity::ENTITY_ID => $merchant->getId(),
                                Entity::NAME => Constants::DA_LEDGER_JOURNAL_WRITES,
                            ]);
                        break;

                    //The case below removes feature flag from merchant
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
        // Fetch Merchant balance. Required to generate request body for account creation on ledger
        $balance = $this->repo->balance->getMerchantBalanceByType(
            $merchant->getId(),
            BalanceType::PRIMARY,
            $this->mode);

        //fetches fee and amount credits from credits table
        $creditBalances = $this->repo->credits->getTypeAggregatedMerchantCredits($merchant->getId());

        (new Merchant\Balance\Ledger\Core)->createPGLedgerAccount(
            $merchant,
            $this->mode,
            $balance->getBalance(),
            $creditBalances
        );
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

    private function ledgerAccountCreateRequestForDirect($merchant)
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

            $this->trace->info(TraceCode::DA_LEDGER_JOURNAL_WRITES_FEATURE_ASSIGNED,
                [
                    Constants::MERCHANT_ID => $merchant->getId(),
                    Constants::MODE => $this->mode,
                    'balance_id' => $balance->getId(),
                    'banking_account_stmt_detail_id' => $bankingAccStmtDetails->getId(),
                ]);

            (new Merchant\Balance\Ledger\Core)->createXLedgerAccountForDirect(
                $merchant,
                $bankingAccStmtDetails,
                $this->mode,
                $balance->getBalance());
        }
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

        (new Core)->delete($feature, $shouldSync);

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

        $shouldSync = (bool) ($input[Entity::SHOULD_SYNC] ?? false);

        $names = $input[Entity::NAME];

        // Will separately update dashboard to start
        // sending a list of features in a single request
        $names = (is_array($input[Entity::NAME]) ? $names : [$input[Entity::NAME]]);

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
                    $feature = (new Core())->create($featureParam, $shouldSync);

                    $successfulMerchant[] = $entityId;

                    $this->trace->count(FeatureMetric::FEATURE_ASSIGN_TOTAL, $dimension);
                }
                catch (\Exception $e)
                {
                    $failedMerchant[] = $entityId;

                    $this->trace->traceException($e);

                    $this->trace->warn(
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

    public function getFeaturesForMerchantPublic(Merchant\Entity $merchant)
    {
        $data['features'] = [];

        $enabledFeatures = $merchant->getEnabledFeatures();

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

    protected function buildFeatureParams(
        array $input,
        string $entityType = null,
        string $entityId = null): Base\Collection
    {
        $featureParams = new Base\Collection;

        //
        // Allow only the admins to provide the entity_type and entity_id from the input.
        // If the merchant is hitting the route directly, only allow him to update his own account features.
        //Allowing Banking account service to add the feature
        if (($this->app['basicauth']->isAdminAuth() === true) or
            ($this->app['basicauth']->isBankingAccountServiceApp() === true) or
            ($this->app['basicauth']->isCapitalCollectionsApp() === true) or
            ($this->app['basicauth']->isCapitalCardsApp() === true))
        {
            $entityType = $entityType ?? $input[Entity::ENTITY_TYPE];

            $entityId = $entityId ?? $input[Entity::ENTITY_ID];
        }
        else
        {
            $entityType = Constants::MERCHANT;

            $entityId = $this->merchant->getId();
        }

        $featureNames = $input[Constants::NAMES];

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
}
