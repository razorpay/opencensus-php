<?php

namespace RZP\Models\Merchant;

use App;
use Mail;
use Config;
use Throwable;
use ApiResponse;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\PartnerMigrationAuditJob;
use RZP\Jobs\CrossBorderCommonUseCases;
use Razorpay\OAuth\Client\Repository as OAuthRepo;
use \WpOrg\Requests\Exception as RequestsException;

use RZP\Http\RequestHeader;
use RZP\Constants\Entity as E;
use RZP\Constants\Environment;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\ClientAuthCreds;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Http\Route;
use RZP\Constants\HyperTrace;
use RZP\Models\VirtualAccount;
use RZP\Jobs\SyncStakeholder;
use RZP\Mail\User as UserMail;
use RZP\Jobs\CapturePartnershipConsents;
use RZP\Models\BankingAccount;
use RZP\Exception\LogicException;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Exception\BadRequestValidationFailureException;
use Razorpay\OAuth\Application as OAuthApp;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\User\Core as UserCore;
use RZP\Jobs\EsSync;
use RZP\Models\Batch;
use RZP\Models\Partner;
use RZP\Models\Terminal;
use RZP\Models\Pricing;
use RZP\Diag\EventCode;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Jobs\MerchantSync;
use RZP\Models\BankAccount;
use RZP\Models\Admin\Group;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Admin\Admin;
use RZP\Base\RuntimeManager;
use RZP\Models\Admin\Action;
use Illuminate\Http\Response;
use RZP\Constants\BankingDemo;
use RZP\Constants\Entity as CE;
use RZP\Jobs\MailingListUpdate;
use RZP\Models\Admin\AdminLead;
use RZP\Models\Merchant\Detail;
use RZP\Models\Terminal\Category;
use RZP\Models\User\BankingRole;
use RZP\Models\Admin\Permission;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Settings\Accessor;
use RZP\Models\Partner\Activation;
use RZP\Models\BankingAccountService;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Models\Merchant\Attribute;
use RZP\Models\Order;
use RZP\Models\Adjustment;
use RZP\Models\Payment\Refund;
use RZP\Jobs\MerchantHoldFundsSync;
use RZP\Models\Merchant\LegalEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;
use RZP\Mail\Merchant\PartnerOnBoarded;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Mail\Payout\Payout as PayoutMail;
use RZP\Jobs\BackFillReferredApplication;
use RZP\Jobs\BackFillMerchantApplications;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Models\Merchant\Fraud\HealthChecker;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Schedule\Task as ScheduleTask;
use Razorpay\OAuth\Exception\DBQueryException;
use RZP\Models\Comment\Entity as CommentEntity;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Jobs\BulkMigrateAggregatorToResellerJob;
use RZP\Jobs\SubMerchantSupportEntitiesCreateJob;
use RZP\Models\Workflow\Action as WorkflowAction;
use RZP\Jobs\MerchantSupportingEntitiesCreateJob;
use RZP\Models\Feature\Service as FeatureService;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Partner\Validator as PartnerValidator;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Services\Segment\Constants as SegmentConstants;
use RZP\Models\Merchant\Balance\Repository as BalanceRepo;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData;
use RZP\Models\Merchant\Detail\InternationalActivationFlow;
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Mail\Merchant\SecondFactorAuth as SecondFactorAuthMail;
use RZP\Models\RiskWorkflowAction\Constants as RiskActionConstants;
use RZP\Models\Merchant\ProductInternational\ProductInternationalField;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Merchant\Credits;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Mail\Merchant\CreditsAdditionSuccess;
use RZP\Mail\Merchant\ReserveBalanceAdditionSuccess;
use RZP\Models\Merchant\AccessMap\Core as AccessMapCore;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Partner\Config\Core as PartnerConfigCore;
use RZP\Models\Merchant\Consent\Constants as MerchantConsentConstants;
use RZP\Trace\Tracer;
use RZP\Models\Typeform\Core as TypeformCore;
use RZP\Models\Typeform\Constants as TypeformConstant;
use RZP\Models\Merchant\Analytics\Constants as AnalyticsConstants;

class Core extends Base\Core
{
    use Notify;

    const DEFAULT_SUBMERCHANT_FETCH_LIMIT = 25;

    const LAST_MONTH_GMV = 'last_month_gmv';
    const CUSTOMER_COUNT = 'customer_count';

    const OPTED_OUT = 'opted_out';
    const OPT_OUT = 'opt_out';


    // This is used in case for
    // IRCTC for sending payout
    // mails
    const MASTER_ID_MAPPING = [
        '8YPFnW5UOM91H7' => 'WMRAZOR00000',
    ];

    // in minutes
    const DEFAULT_MERCHANT_ES_SYNC_INTERVAL = 15;

    const MAX_ES_MERCHANT_SYNC_LIMIT = 1000;

    const FUND_ADDITION_DESCRIPTION_MUTEX_TIMEOUT = 10;

    const MAX_NO_OF_IPS_ALLOWED = 20;

    const VAS_MERCHANT      = 'VAS_MERCHANT';
    const SUB_MERCHANT      = 'SUB_MERCHANT';
    const PARTNER_MERCHANT  = 'PARTNER_MERCHANT';
    const LINKED_ACCOUNT    = 'LINKED_ACCOUNT';

    const DEFAULT_IP_WHITELIST = '*';

    const MAX_ARRAY_LIMIT_FOR_MERCHANT_ENTITIES_INFO = 10;

    /**
     * @var CapitalSubmerchantUtility
     */
    protected CapitalSubmerchantUtility $capitalSubmerchantUtility;

    /**
     * @return CapitalSubmerchantUtility
     */
    protected function capitalSubmerchantUtility(): CapitalSubmerchantUtility
    {
        if(empty($this->capitalSubmerchantUtility) === true)
        {
            $this->capitalSubmerchantUtility = new CapitalSubmerchantUtility();
        }

        return $this->capitalSubmerchantUtility;
    }

    public function create($input, $merchantDetailInputData = [])
    {
        (new UserCore())->validateAccountCreation(array_merge($input,$merchantDetailInputData));

        unset($input['token_data']);

        $tokenData=$merchantDetailInputData['token_data'];

        unset($merchantDetailInputData['token_data']);

        $merchant = (new Merchant\Entity)->build($input);

        $this->trace->info(
            TraceCode::MERCHANT_CREATE,
            [
                'data' => $input
            ]);

        $merchant->setAuditAction(Action::CREATE_MERCHANT);

        if (isset($input['email']) === true)
        {
            (new Merchant\Validator())->validateUniqueEmailExceptLinkedAccount(
                $input[Entity::EMAIL], $input[Entity::ORG_ID]
            );
        }
        $merchant->setPricingPlan(Pricing\DefaultPlan::PROMOTIONAL_PLAN_ID);

        $org = $this->repo->org->findOrFailPublic($input[Entity::ORG_ID]);

        $planId = $org->getDefaultPricingPlanId();

        if (empty($planId) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_NO_DEFAULT_PLAN_IN_ORG,
                null,
                [
                    'org_id'      => $org->getId(),
                ]
            );
        }

        $merchant->setPricingPlan($planId);

        $merchant->org()->associate($org);

        $this->repo->saveOrFail($merchant);

        $merchantId = $merchant->getId();

        // Create Workflow For Merchant in PGOS
        try {

            $createWorkflowRequestBody = [
                'account_id' => $merchantId,
                'account_type' => "merchant"
            ];

            $pgosProxyController = new MerchantOnboardingProxyController();

            $response = $pgosProxyController->handlePGOSProxyRequests('merchant_sign_up', $createWorkflowRequestBody, $merchant);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'merchant_id' => $merchantId,
                'response' => $response,
            ]);
        }
        catch (RequestsException $e) {

            if (checkRequestTimeout($e) === true) {
                $this->trace->info(TraceCode::PGOS_PROXY_TIMEOUT, [
                    'merchant_id' => $merchantId,
                ]);
            } else {
                $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id' => $merchantId,
                    'error_message' => $e->getMessage()
                ]);
            }

        }
        catch (\Throwable $exception) {
            // this should not introduce error counts as it is running in shadow mode
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchantId,
                'error_message' => $exception->getMessage()
            ]);
        }

        $this->savePartnerIntentInSettings($input, $merchant);

        $this->addMerchantSupportingEntities(
            $merchant,
            null,
            false,
            $merchantDetailInputData
        );

        $this->syncHeimdallRelatedEntities($merchant, $input, true);

        $this->upsertLegalEntity($merchant, []);

        $this->addToDefaultUnclaimedGroup($merchant);

        $this->addMerchantRelevantFeatures($merchant,$tokenData);

        // Updating the existing customer info and setting activated to false
        $this->app['drip']->sendDripMerchantInfo($merchant, Merchant\Action::CREATED);

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::CREATED, $merchant->toArrayEvent());

        // activation save for regular onboarding for pgos
        try
        {
            $pgosProxyController = new MerchantOnboardingProxyController();

            // merge input with detail input
            $pgosPayload = array_merge($input, $merchantDetailInputData);

            $pgosPayload['merchantId'] = $merchantId;

            $response = $pgosProxyController->handlePGOSProxyRequests('merchant_activation_save', $pgosPayload, $merchant);

            $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                'response' => $response
            ]);
        }
        catch (RequestsException $e) {
            if (checkRequestTimeout($e) === true) {
                $this->trace->info(TraceCode::PGOS_PROXY_TIMEOUT, [
                    'merchant_id' => $merchantId,
                ]);
            } else {
                $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                    'merchant_id' => $merchantId,
                    'error_message' => $e->getMessage()
                ]);
            }
        }
        catch (\Throwable $exception) {
            // this should not introduce error counts as it is running in shadow mode
            $this->trace->info(TraceCode::PGOS_PROXY_ERROR, [
                'merchant_id' => $merchantId,
                'error_message' => $exception->getMessage()
            ]);
        }
        finally {
            unset($input['merchantId']);
        }

        return $merchant;
    }

    private function addMerchantRelevantFeatures($merchant,$tokenData)
    {
        if ($tokenData !== null)
        {
            $merchantType = $tokenData[AdminLead\Constants::FORM_DATA][AdminLead\Constants::MERCHANT_TYPE] ?? null;

            if (empty($merchantType) === false)
            {
                if ((array_key_exists($merchantType, AdminLead\Constants::ALLOWED_MERCHANT_TYPE_FEATURE_MAPPING) === true) and
                    empty(AdminLead\Constants::ALLOWED_MERCHANT_TYPE_FEATURE_MAPPING[$merchantType]) === false)
                {
                    $featureParams = [
                        Feature\Entity::ENTITY_ID   => $merchant['id'],
                        Feature\Entity::ENTITY_TYPE => 'merchant',
                        Feature\Entity::NAMES       => AdminLead\Constants::ALLOWED_MERCHANT_TYPE_FEATURE_MAPPING[$merchantType],
                        Feature\Entity::SHOULD_SYNC => false
                    ];

                    (new Feature\Service)->addFeatures($featureParams);
                }
            }
        }
    }

    public function syncStakeholderFromMerchant(array $input)
    {
        RuntimeManager::setTimeLimit(1800);

        if (empty($input['merchant_ids']) === false)
        {
            SyncStakeholder::dispatch($this->mode, $input['merchant_ids']);

            return [];
        }

        $afterId = null;

        $count = 0;

        while (true)
        {
            $repo = $this->repo;

            $merchantIds = $repo->useSlave(function () use ($afterId, $repo) {
                return $repo->merchant_detail->findMerchantsWithoutStakeholders(1000, $afterId);
            });

            if (empty($merchantIds) === true)
            {
                break;
            }

            $afterId = end($merchantIds);

            $count += count($merchantIds);

            SyncStakeholder::dispatch($this->mode, $merchantIds);
        }

        return ['count' => $count];
    }

    /**
     * Any merchant created will be assign to Default Unclaimed Group for SalesForce.
     *
     * @param Entity $merchant
     */
    public function addToDefaultUnClaimedGroup(Entity $merchant)
    {
        $group = (new Group\Entity())->getSalesForceGroupId();

        $unClaimedGroupId = $group[Group\Constant::SALESFORCE_UNCLAIMED_GROUP_ID];

        $this->trace->info(TraceCode::MERCHANT_DEFAULT_GROUP_ATTACH,
                           [
                               'action'   => 'attach_in_merchant_map',
                               'merchant' => $merchant->getId(),
                               'groupId'  => $unClaimedGroupId,
                           ]);

        $this->repo->sync($merchant, 'groups', [$unClaimedGroupId], false);
    }

    public function upsertLegalEntity(Entity $merchant, array $input)
    {
        $legalEntity = (new LegalEntity\Core)->upsert($merchant, $input);

        $merchant->legalEntity()->associate($legalEntity);

        $this->repo->saveOrFail($merchant);
    }

    /**
     * For any code getting added here, consider whether it is applicable for a submerchant
     * getting linked via an admin or by a referral code and update code in those cases too
     *
     * @param array  $input
     * @param Entity $aggregatorMerchant
     * @param bool   $linkedAccount
     * @param bool   $accountEntity
     * @param bool   $optimizeCreationFlow
     *
     * @return Account\Entity|Entity
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function createSubMerchant(
        array $input,
        Entity $aggregatorMerchant,
        bool $linkedAccount = true,
        bool $accountEntity = false,
        bool $optimizeCreationFlow = false)
    {
        $this->validateCodeIfPresent($input, $aggregatorMerchant, $linkedAccount);

        if (empty($input['email']) === false)
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $aggregatorMerchant->getValidator()->validateSubMerchantInput($input, $linkedAccount, $aggregatorMerchant);

        // validate that external id passed is unique for that partner
        if (empty($input[Entity::EXTERNAL_ID]) === false)
        {
            (new Partner\Core)->validateExternalIdForPartnerSubmerchant($aggregatorMerchant, $input[Entity::EXTERNAL_ID]);
        }

        $legalEntity           = null;
        $externalLegalEntityId = null;

        $input['email'] = empty($input['email']) ? $aggregatorMerchant->getEmail() : $input['email'];

        if ($accountEntity === true)
        {
            $entity = new Account\Entity;
        }
        else
        {
            $entity = new Entity;

            if (empty($input[Entity::LEGAL_ENTITY_ID]) === false)
            {
                $legalEntity = $this->repo->legal_entity->findOrFailPublic($input[Entity::LEGAL_ENTITY_ID]);

                unset($input[Entity::LEGAL_ENTITY_ID]);
            }
            else if (empty($input[Entity::LEGAL_EXTERNAL_ID]) === false)
            {
                $externalLegalEntityId = $input[Entity::LEGAL_EXTERNAL_ID];

                unset($input[Entity::LEGAL_EXTERNAL_ID]);
            }
        }

        $jobInput = $this->getInputDataForSubMSupportEntities($input);

        $contactMobile = $input[Detail\Entity::CONTACT_MOBILE] ?? null;
        unset($input[Detail\Entity::CONTACT_MOBILE]);

        if ($linkedAccount === true)
        {
            $this->trace->info(
                TraceCode::LINKED_ACCOUNT_BUILD_ENTITY,
                [
                    'name'          => $input[Entity::NAME] ?? null,
                    'merchant_id'   => $aggregatorMerchant->getId(),
                ]
            );
        }

        $jobInput[Detail\Entity::BUSINESS_TYPE] = $input[Detail\Entity::BUSINESS_TYPE] ?? '';
        unset($input[Detail\Entity::BUSINESS_TYPE]);

        $subMerchant = $entity->build($input);

        if ($linkedAccount === true)
        {
            $this->trace->info(
                TraceCode::LINKED_ACCOUNT_DEBUG_LOG_1,
                [
                    'name'              => $subMerchant->getName(),
                    'linked_account_id' => $subMerchant->getId(),
                    'merchant_id'       => $aggregatorMerchant->getId(),
                ]
            );
        }

        // The parent Id has to be linked only when it's a marketplace
        // If both market place and referral are present when creating a referral account we should not link parentId.
        if ($aggregatorMerchant->isMarketplace() === true and $linkedAccount === true)
        {
            $subMerchant->setMaxPaymentAmount($aggregatorMerchant->getMaxPaymentAmount());
            $subMerchant->setMaxInternationalPaymentAmount($aggregatorMerchant->getMaxPaymentAmountTransactionType(true));

            $subMerchant->parent()->associate($aggregatorMerchant);

            $this->trace->info(
                TraceCode::LINKED_ACCOUNT_DEBUG_LOG_2,
                [
                    'name'              => $subMerchant->getName(),
                    'linked_account_id' => $subMerchant->getId(),
                    'parent_id'         => $subMerchant->getParentId(),
                    'merchant_id'       => $aggregatorMerchant->getId(),
                ]
            );
        }

        $aggregatorOrgId = $aggregatorMerchant->getOrgId();

        if ($aggregatorOrgId !== null)
        {
            $org = $this->repo->org->findOrFailPublic($aggregatorOrgId);

            // Link sub-merchant to its aggregator's org
            $subMerchant->org()->associate($org);
        }

        if ($linkedAccount === true)
        {
            $this->trace->info(
                TraceCode::LINKED_ACCOUNT_SAVE_ENTITY,
                [
                    'name'              => $subMerchant->getName(),
                    'linked_account_id' => $subMerchant->getId(),
                    'merchant_id'       => $subMerchant->getParentId(),
                ]
            );
        }

        $this->trace->info(
            TraceCode::CREATE_SUB_MERCHANT_SAVE_OR_FAIL_METHOD_CALL,
            [
                'entity_name'       => $entity->getEntityName(),
                'connection_name'   => $entity->getConnectionName(),
                'merchant_id'       => $entity->getMerchantId(),
                'parent_id'         => $entity->getParentId(),
            ]
        );

        $properties = [
            'id'            => $aggregatorMerchant->getId(),
            'experiment_id' => $this->app['config']->get('app.optimise_submerchant_create_exp_id'),
            'request_data'  => json_encode(
                [
                    'partner_id' => $aggregatorMerchant->getId(),
                ]),
        ];

        $experimentEnable = $this->isSplitzExperimentEnable($properties, 'enable');

        $subMerchant->setAuditAction(Action::CREATE_SUBMERCHANT);

        $subMerchantDetailInput = !empty($contactMobile) ? [Detail\Entity::CONTACT_MOBILE => $contactMobile] : [];

        if($experimentEnable == true)
        {
            $this->repo->saveOrFail($subMerchant);

            Tracer::inspan(['name' => HyperTrace::ADD_SUBMERCHANT_SUPPORTING_ENTITIES], function() use ($subMerchant, $aggregatorMerchant, $jobInput, $linkedAccount) {
                $jobInput['merchant_id']    = $subMerchant->getId();
                $jobInput['partner_id']     = $aggregatorMerchant->getId();
                $jobInput['linked_account'] = $linkedAccount;
                SubMerchantSupportEntitiesCreateJob::dispatch($this->mode, $jobInput);
            });

            Tracer::inspan(['name' => HyperTrace::CREATE_MERCHANT_DETAILS_CORE], function () use ($subMerchant, $subMerchantDetailInput) {

                (new Detail\Core)->createMerchantDetails($subMerchant, $subMerchantDetailInput);
            });

        }
        else
        {
            $this->associateLegalEntityToSubmerchant($subMerchant, $jobInput);

            Tracer::inspan(['name' => HyperTrace::ASSIGN_SUBMERCHANT_PRICING_PLAN], function () use ($aggregatorMerchant, $subMerchant, $linkedAccount) {

                $this->assignSubMerchantPricingPlan($aggregatorMerchant, $subMerchant, $linkedAccount);
            });

            $this->setSubMerchantMaxPaymentAmount($aggregatorMerchant,$subMerchant, $jobInput[Detail\Entity::BUSINESS_TYPE]);

            $this->repo->saveOrFail($subMerchant);

            Tracer::inspan(['name' => HyperTrace::ADD_MERCHANT_SUPPORTING_ENTITIES], function() use ($subMerchant, $aggregatorMerchant, $subMerchantDetailInput, $optimizeCreationFlow) {

                $this->addMerchantSupportingEntities($subMerchant, $aggregatorMerchant, $optimizeCreationFlow, $subMerchantDetailInput);

            });

            $this->syncHeimdallRelatedEntities($subMerchant, $input);

        }

        return $subMerchant;
    }

    private function getInputDataForSubMSupportEntities(array &$input): array
    {
        $jobInput = [];

        if (isset($input[Entity::GROUPS]) == true)
        {
            $jobInput[Entity::GROUPS] = $input[Entity::GROUPS];
        }

        if (isset($input[Entity::ADMINS]) == true)
        {
            $jobInput[Entity::ADMINS] = $input[Entity::ADMINS];
        }

        if (empty($input[Entity::LEGAL_ENTITY_ID]) === false)
        {
            $jobInput[Entity::LEGAL_ENTITY_ID] = $input[Entity::LEGAL_ENTITY_ID];
            unset($input[Entity::LEGAL_ENTITY_ID]);
        }
        else if (empty($input[Entity::LEGAL_EXTERNAL_ID]) === false)
        {
            $jobInput[Entity::LEGAL_EXTERNAL_ID]  = $input[Entity::LEGAL_EXTERNAL_ID];
            unset($input[Entity::LEGAL_EXTERNAL_ID]);
        }

        $jobInput[Detail\Entity::BUSINESS_TYPE] = $input[Detail\Entity::BUSINESS_TYPE] ?? '';

        return $jobInput;
    }


    public function associateLegalEntityToSubmerchant(Entity $subMerchant, array $input)
    {
        if (empty($input[Entity::LEGAL_ENTITY_ID]) === false)
        {
            $legalEntity = $this->repo->legal_entity->findOrFailPublic($input[Entity::LEGAL_ENTITY_ID]);

            $subMerchant->legalEntity()->associate($legalEntity);
        }
        else if (empty($input[Entity::LEGAL_EXTERNAL_ID]) === false)
        {
            $legalEntityInput[Entity::LEGAL_EXTERNAL_ID] = $input[Entity::LEGAL_EXTERNAL_ID];

            $this->upsertLegalEntity($subMerchant, $legalEntityInput);
        }
    }

    public function pushSettleToPartnerSubmerchantMetrics(string $partnerId, string $submerchantId)
    {
        $dimensions = [
            'partner_id'     => $partnerId,
            'submerchant_id' => $submerchantId
        ];

        try
        {
            $properties = [
                'id'            => $partnerId,
                'experiment_id' => $this->app['config']->get('app.settle_to_partner_alerting_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'id' => $partnerId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === "enabled")
            {
                $this->trace->info(TraceCode::SETTLE_TO_PARTNER_SUBMERCHANT_METRIC_PUSH, $dimensions);

                $this->trace->count(Metric::SETTLE_TO_PARTNER_SUBMERCHANT_TOTAL, $dimensions);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SETTLE_TO_PARTNER_SUBMERCHANT_METRIC_PUSH_FAILURE,
                $dimensions);
            $this->trace->count(Metric::SETTLE_TO_PARTNER_SUBMERCHANT_METRIC_PUSH_FAILURE, []);
        }
    }

    private function getRzpMerchantDetailsBasedOnFundAdditionType($type)
    {
        $fundAdditionAccount = 'banking_account.razorpay_fund_addition_accounts.'. $type;

        return [
            "merchant_id" => Config::get($fundAdditionAccount.'.merchant_id')
        ];
    }

    public function getRazorpayMerchantBasedOnType(String $fundAdditionType) : array
    {
        switch($fundAdditionType) {
            case Type::REFUND_CREDIT :
                return $this->getRzpMerchantDetailsBasedOnFundAdditionType(Type::REFUND_CREDIT);
            case Type::FEE_CREDIT :
                return $this->getRzpMerchantDetailsBasedOnFundAdditionType(Type::FEE_CREDIT);
            case Type::RESERVE_BALANCE :
                return $this->getRzpMerchantDetailsBasedOnFundAdditionType(Type::RESERVE_BALANCE);
        }

        $this->trace->info(Tracecode::BAD_REQUEST_INVALID_FUND_ADDITION_TYPE,[
            "merchant_id" => $this->merchant->getPublicId(),
            "type" => $fundAdditionType
        ]);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_FUND_ADDITION_TYPE_IS_INVALID,
            null,
            [
                "merchant_id" => $this->merchant->getPublicId(),
                "type" => $fundAdditionType
            ]
        );

    }

    public function setRazorpayMerchantForFundAddition($razorpayMerchant)
    {
        $this->app['basicauth']->setMerchant($this->repo->merchant->findOrFail($razorpayMerchant['merchant_id']));
    }

    public function createOrderForFundAddition(array $input)
    {
        $transactingMerchant = $this->merchant;

        $razorpayMerchant = $this->getRazorpayMerchantBasedOnType($input['type']);

        $bankAccount = (new Merchant\Service)->getBankAccount($transactingMerchant->getId());

        $this->trace->info(Tracecode::MERCHANT_DETAILS_FOR_ORDER_CREATION,[
            "transacting_merchant" => $transactingMerchant->getId(),
            "rzp_merchant" => $razorpayMerchant['merchant_id']
        ]);


        $orderInput = [
            "amount" => $input['amount'],
            "currency" => "INR",
            "payment_capture" => true,
            "bank_account" => [
                "account_number" => $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER],
                "name" => $bankAccount[BankAccount\Entity::BENEFICIARY_NAME],
                "ifsc" => $bankAccount[BankAccount\Entity::IFSC_CODE]
            ],
            "notes" => [
                "merchant_id" => $transactingMerchant->getId(),
                "type" => $input["type"]
            ],
            "method" => "upi"
        ];

        $this->setRazorpayMerchantForFundAddition($razorpayMerchant);

        $order = (new Order\Service)->createOrder($orderInput);

        $this->trace->info(Tracecode::ORDER_CREATED_FOR_FUND_ADDITION, [
            "order_id" => $order->getPublicId(),
            "merchant_id" => $transactingMerchant->getId(),
            "type" => $input['type']
        ]);

        return [
            "order_id" => $order->getPublicId()
        ];
    }

    private function getCreditType($inputType)
    {
        switch($inputType)
        {
            case Type::REFUND_CREDIT :
                return Credits\Type::REFUND;
            case Type::FEE_CREDIT :
                return Credits\Type::FEE;
        }
        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_FUND_ADDITION_TYPE_IS_INVALID,
            null,
            [
                "merchant_id" => $this->merchant->getPublicId(),
                "type" => $inputType
            ]
        );
    }

    private function addFundsToCreditBalance($campaignId, $creditType, $paymentInput, $merchantId)
    {
        $mutex =  App::getFacadeRoot()['api.mutex'];

        $mutexAcquired = $mutex->acquire($merchantId."-".$campaignId, self::FUND_ADDITION_DESCRIPTION_MUTEX_TIMEOUT);

        if ($mutexAcquired === false)
        {
            $this->trace->info(Tracecode::ANOTHER_OPERATION_IN_PROGRESS_FOR_GIVEN_CAMPAIGN,[
                "merchant_id" => $merchantId,
                "campaign_id" => $campaignId
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAMPAIGN_ANOTHER_OPERATION_IN_PROGRESS,
                null,
                ['resource' => $merchantId."-".$campaignId]
            );
        }

        (new Merchant\Validator())->validateIfFundAlreadyAddedForGivenCampaign($campaignId, $merchantId);

        $creditType = $this->getCreditType($creditType);

        $amountAfterFee = $paymentInput['amount'] - $paymentInput['fee'];

        $creditInput = [
            'type'     => $creditType,
            'value'    => $amountAfterFee,
            'campaign' => $campaignId,
        ];

        (new Merchant\Validator())->validateIfAmountForFundAdditionIsValid($creditInput['value'], $creditInput, $merchantId );

        $this->trace->info(TraceCode::CREDIT_FUND_ADDITION_INITIATED, $creditInput);

        $this->sendFundAdditionInitiatedEvent($creditInput, $merchantId, EventCode::CREDIT_ADDITION_INITIATED);

        $payment =  $this->repo->payment->findByPublicId($paymentInput['id']);

        try
        {
            $response =  ((new Credits\Service)->grantCreditsForMerchant($merchantId, $creditInput, $payment));
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CREDITS_ADDITION_FAILED,
                [
                    'merchant_id'    => $merchantId,
                    "input"          => $creditInput
                ]);

            $this->sendFundAdditionFailedEvent($creditInput, $merchantId, $e, EventCode::CREDIT_ADDITION_FAILED);

            throw $e;
        }

        $this->trace->info(Tracecode::CREDIT_FUND_ADDITION_RESPONSE, [
            "merchant_id" => $merchantId,
            "response" => $response
        ]);

        $this->sendFundAdditionSuccessEvent($creditInput, $merchantId, $response, EventCode::CREDIT_ADDITION_SUCCESS);

        $this->sendAlertIfCreditFundAdditionIsSuccessful($merchantId, $amountAfterFee, $creditType.'_credit');

        return $response;
    }

    public function setModeForFundAdditionViaOrders($orderInput)
    {
        try
        {
            $this->app['basicauth']->setModeAndDbConnection('live');

            $this->repo->order->findByPublicId($orderInput['id']);
        }

        catch (\Exception $e)
        {
            $this->app['basicauth']->setModeAndDbConnection('test');

            $this->repo->order->findByPublicId($orderInput['id']);
        }
    }

    public function sendFundAdditionInitiatedEvent($input, $merchantId, $eventCode)
    {
        try
        {
            $properties['merchant_id'] = $merchantId;

            $properties['input_request'] = $input;

            $this->app['diag']->trackPaymentEventV2($eventCode, null, null, [], $properties);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e);
        }
    }

    public function sendFundAdditionFailedEvent($input, $merchantId, $exception, $eventCode)
    {

        try
        {
            $properties['merchant_id'] = $merchantId;

            $properties['input_request'] = $input;

            $this->app['diag']->trackPaymentEventV2($eventCode, null, $exception, [], $properties);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e);
        }
    }

    public function sendFundAdditionSuccessEvent($input, $merchantId, $response, $eventCode)
    {
        try
        {
            $properties['merchant_id'] = $merchantId;

            $properties['input_request'] = $input;

            $properties['response'] = $response;

            $this->app['diag']->trackPaymentEventV2($eventCode, null, null, [],$properties);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e);
        }
    }

    public function fundAdditionViaOrders($input)
    {
        if(isset($input['payload']['order']) === false or
            isset($input['payload']['payment']) === false or
            isset($input['payload']['order']['entity']['id']) === false or
            isset($input['payload']['payment']['entity']['id']) === false
        )
        {
            $this->trace->info(TraceCode::INVALID_INPUT_FOR_FUND_ADDITION,
            [
                "type" => "online_payment",
                "input" => $input
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_INPUT_FOR_FUND_ADDITION);
        }

        $orderInput = $input['payload']['order']['entity'];

        $paymentInput = $input['payload']['payment']['entity'];

        $this->trace->info(TraceCode::FUND_ADDITION_WEBHOOK_REQUEST, [
            "order_id" => $orderInput['id'],
            "payment_id" => $paymentInput['id']
        ]);

        $this->setModeForFundAdditionViaOrders($orderInput);

        (new Merchant\Validator())->validateInputDetailsForFundAdditionViaOrder($orderInput, $paymentInput);

        $merchantId = $orderInput['notes']['merchant_id'];

        switch($orderInput['notes']['type'])
        {
            case Type::FEE_CREDIT :
            case Type::REFUND_CREDIT :
                return $this->addFundsToCreditBalance($orderInput['id'], $orderInput['notes']['type'], $paymentInput, $merchantId);
            case Type::RESERVE_BALANCE :
                return $this->addFundsToReserveBalance($orderInput['id'], $paymentInput, $merchantId);
        }
    }

    public function getVirtualAccountDetailsForMerchantIfPresentAlready($merchant, $creditType)
    {
        $merchantVAIds = $merchant->merchantDetail->getFundAdditionVAIds();

        if(isset($merchantVAIds[$creditType]) === true)
        {
            $razorpayMerchant = $this->getRazorpayMerchantBasedOnType($creditType);

            $this->setRazorpayMerchantForFundAddition($razorpayMerchant);

            return (new VirtualAccount\Service)->fetch($merchantVAIds[$creditType]);
        }

        return null;
    }

    public function saveVirtualAccountIdForMerchantForFundAddition($id, $merchant, $type)
    {
        $merchantDetails = $merchant->merchantDetail;

        $virtualAccountConfig = $merchantDetails->getFundAdditionVAIds();

        if(isset($virtualAccountConfig) === false)
        {
            $virtualAccountConfig = [];
        }

        $virtualAccountConfig[$type] = $id;

        $merchantDetails->setFundAdditionVAIds(json_encode($virtualAccountConfig));

        $this->repo->merchant_detail->saveOrFail($merchantDetails);

    }

    public function createVirtualAccountForMerchant( $merchant, $input)
    {
        $bankAccount = (new Merchant\Service)->getBankAccount($merchant->getId());

        $razorpayMerchant = $this->getRazorpayMerchantBasedOnType($input['type']);


        $virtualAccountInput = [
            "receivers"=> [
                "types" => [
                    "bank_account"
                ]
            ],
            "allowed_payers" => [
                [
                    "type" => "bank_account",
                    "bank_account"=> [
                        "account_number"=>  $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER],
                        "ifsc" =>  $bankAccount[BankAccount\Entity::IFSC_CODE],
                    ]
                ]
            ],
            "notes" => [
                "merchant_id" => $merchant->getId(),
                "type" => $input['type']
            ]
        ];

        $this->setRazorpayMerchantForFundAddition($razorpayMerchant);

        $response = (new VirtualAccount\Service)->create($virtualAccountInput);

        $this->saveVirtualAccountIdForMerchantForFundAddition($response['id'], $merchant, $input['type']);

        return $response;
    }

    public function addPayerBankAccountDetails($virtualAccount)
    {
        $payerBankAccount = $this->repo->bank_account->findOrFail(BankAccountEntity::verifyIdAndStripSign($virtualAccount['allowed_payers'][0]['id']));

        $virtualAccount['allowed_payers'][0]['bank_account']['name'] = $payerBankAccount->getBeneficiaryName();

        $virtualAccount['allowed_payers'][0]['bank_account']['bank_name'] = $payerBankAccount->getBankName();

        return $virtualAccount;
    }

    public function getVirtualAccountForFundAddition($input)
    {
        $transactingMerchant = $this->merchant;

        $virtualAccount = $this->getVirtualAccountDetailsForMerchantIfPresentAlready($transactingMerchant, $input['type']);

        if(isset($virtualAccount) === false)
        {
            $virtualAccount = $this->createVirtualAccountForMerchant($transactingMerchant, $input);
        }

        $virtualAccount = $this->addPayerBankAccountDetails($virtualAccount);

        return $virtualAccount;
    }

    private function setModeForFundAdditionViaBankTransfer($bankTransferInput)
    {
        try
        {
            $this->app['basicauth']->setModeAndDbConnection('live');

            $this->repo->bank_transfer->findByPublicId($bankTransferInput['id']);
        }

        catch (\Exception $e)
        {
            $this->app['basicauth']->setModeAndDbConnection('test');

            $this->repo->bank_transfer->findByPublicId($bankTransferInput['id']);
        }
    }

    public function fundAdditionViaBankTransfer($input)
    {
        if(isset($input['payload']['virtual_account']) === false or
            isset($input['payload']['payment']) === false or
            isset($input['payload']['bank_transfer']) === false or
            isset($input['payload']['virtual_account']['entity']['id']) === false or
            isset($input['payload']['payment']['entity']['id']) === false or
            isset($input['payload']['bank_transfer']['entity']['id']) === false
        )
        {
            $this->trace->info(TraceCode::INVALID_INPUT_FOR_FUND_ADDITION,[
                "type" => "account_transfer",
                "input" => $input
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_INPUT_FOR_FUND_ADDITION);
        }

        $virtualAccountInput = $input['payload']['virtual_account']['entity'];

        $paymentInput = $input['payload']['payment']['entity'];

        $bankTransferInput = $input['payload']['bank_transfer']['entity'];

        $this->trace->info(TraceCode::FUND_ADDITION_WEBHOOK_REQUEST, [
            "va_id" => $virtualAccountInput['id'],
            "payment_id" => $paymentInput['id'],
            "bank_transfer_id" => $bankTransferInput['id']
        ]);

        $this->setModeForFundAdditionViaBankTransfer($bankTransferInput);

        (new Merchant\Validator())->validateInputDetailsForFundAdditionViaBankTransfer($bankTransferInput, $virtualAccountInput, $paymentInput);

        $merchantId = $virtualAccountInput['notes']['merchant_id'];

        switch($virtualAccountInput['notes']['type'])
        {
            case Type::FEE_CREDIT :
            case Type::REFUND_CREDIT :
                return $this->addFundsToCreditBalance($paymentInput['id'], $virtualAccountInput['notes']['type'],$paymentInput, $merchantId);
            case Type::RESERVE_BALANCE :
                return $this->addFundsToReserveBalance($paymentInput['id'],$paymentInput, $merchantId);
        }
    }

    private function addFundsToReserveBalance($description, $paymentInput, $merchantId)
    {
        $description = 'Reserve Balance|'.$description;

        $mutex =  App::getFacadeRoot()['api.mutex'];

        $mutexAcquired = $mutex->acquire($merchantId."-".$description, self::FUND_ADDITION_DESCRIPTION_MUTEX_TIMEOUT);

        if ($mutexAcquired === false)
        {
            $this->trace->info(Tracecode::ANOTHER_OPERATION_IN_PROGRESS_FOR_GIVEN_DESC,[
                "merchant_id" => $merchantId,
                "description" => $description
            ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_DESCRIPTION_ANOTHER_OPERATION_IN_PROGRESS,
                null,
                ['resource' => $merchantId."-".$description]
            );
        }

        (new Merchant\Validator())->validateIfReserveBalanceAlreadyAdded($description, $merchantId);
        $amountAfterFee = $paymentInput['amount'] - $paymentInput['fee'];

        $payment =  $this->repo->payment->findByPublicId($paymentInput['id']);

        $input = [
            "amount" => $amountAfterFee,
            "type" => "reserve_primary",
            "currency" => "INR",
            "description" => $description
        ];

        (new Merchant\Validator())->validateIfAmountForFundAdditionIsValid($input['amount'], $input, $merchantId);

        $this->trace->info(Tracecode::RESERVE_BALANCE_FUND_ADDITION_REQUEST, [
            "merchant_id" => $merchantId,
            "input" => $input
        ]);

        $this->sendFundAdditionInitiatedEvent($input, $merchantId, EventCode::RESERVE_BALANCE_ADDITION_INITIATED);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        try
        {
            $response =  (new Adjustment\Core)->createAdjustment($input, $merchant, $payment);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::RESERVE_BALANCE_FUND_ADDITION_FAILED,
                [
                    'merchant_id'    => $merchantId,
                    "input"          => $input
                ]);

            $this->sendFundAdditionFailedEvent($input, $merchantId, $e, EventCode::RESERVE_BALANCE_ADDITION_FAILED);

            throw $e;
        }

        $this->trace->info(Tracecode::RESERVE_BALANCE_ADJUSTMENT_RESPONSE, [
            "merchant_id" => $merchant->getId(),
            "response" => $response
        ]);

        $this->sendFundAdditionSuccessEvent($input, $merchantId, $response, EventCode::RESERVE_BALANCE_ADDITION_SUCCESS);

        $this->sendAlertIfReserveBalanceAdditionIsSuccessful($merchantId, $amountAfterFee, Type::RESERVE_BALANCE );

        return $response;
    }

    private function sendAlertIfCreditFundAdditionIsSuccessful($merchantId, $amount, $accountType)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $accountDetails =  $this->getAccountTypeLabelAndBalance($merchant, $accountType);

        $data = $this->getDataForFundAdditionMail($merchant, $accountDetails, $amount);

        $this->trace->info(TraceCode::CREDITS_ADDITION_MAIL, $data);

        $createAlertMail = new CreditsAdditionSuccess($data);

        Mail::queue($createAlertMail);
    }

    private function sendAlertIfReserveBalanceAdditionIsSuccessful($merchantId, $amount, $accountType)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $accountDetails =  $this->getAccountTypeLabelAndBalance($merchant, $accountType);

        $data = $this->getDataForFundAdditionMail($merchant, $accountDetails, $amount);

        $this->trace->info(TraceCode::RESERVE_BALANCE_ADDITION_MAIL, $data);

        $reserveBalanceAdditionMail = new ReserveBalanceAdditionSuccess($data);

        Mail::queue($reserveBalanceAdditionMail);
    }

    private function getDataForFundAdditionMail($merchant, $accountDetails, $amount)
    {
        return  [
            'email'             => $merchant->getTransactionReportEmail(),
            'merchant_id'       => $merchant->getId(),
            'merchant_dba'      => $merchant->getBillingLabel(),
            'account_type'      => $accountDetails['label'],
            'org_hostname'      => $merchant->org->getPrimaryHostName(),
            'timestamp'         => Carbon::now(Timezone::IST)->format('d-m-Y H:i:s'),
            'fund_balance'      => $accountDetails['amount']/100,
            'amount'            => $amount/100
        ];
    }

    public function getMerchantSegment($lastMonthTotalTransactions)
    {
        $activation_status = $this->merchant->merchantDetail->getActivationStatus();

        if (in_array($activation_status, [Detail\Status::ACTIVATED, Detail\Status::ACTIVATED_MCC_PENDING, Detail\Status::INSTANTLY_ACTIVATED]) === true)
        {
            if ($lastMonthTotalTransactions > 10)
            {
                return Constants::PAYMENTS_ENABLED_AND_FREQUENTLY_TRANSACTED;
            }
            elseif ($lastMonthTotalTransactions > 0)
            {
                return Constants::PAYMENTS_ENABLED_AND_TRANSACTED;
            }

            return Constants::PAYMENTS_ENABLED_AND_NOT_TRANSACTED;
        }

        return Constants::PAYMENTS_NOT_ENABLED;
    }

    public function getCurrentSegmentWidgetData($merchantAppSegment)
    {
        switch ($merchantAppSegment)
        {
            case Constants::PAYMENTS_NOT_ENABLED :
                return [
                    Constants::PAYMENT_HANDLE      => [Constants::PRIORITY => 1, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::ONBOARDING_CARD     => [Constants::PRIORITY => 2, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::FINANCE]],
                    Constants::ACCEPT_PAYMENTS     => [Constants::PRIORITY => 3, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::RECENT_TRANSACTIONS => [Constants::PRIORITY => 4, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                ];

            case Constants::PAYMENTS_ENABLED_AND_NOT_TRANSACTED :
                return [
                    Constants::PAYMENT_HANDLE      => [Constants::PRIORITY => 1, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::ONBOARDING_CARD     => [Constants::PRIORITY => 2, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::FINANCE]],
                    Constants::ACCEPT_PAYMENTS     => [Constants::PRIORITY => 3, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::RECENT_TRANSACTIONS => [Constants::PRIORITY => 4, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                    Constants::SETTLEMENTS         => [Constants::PRIORITY => 5, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                ];

            case Constants::PAYMENTS_ENABLED_AND_TRANSACTED :
                return [
                    Constants::PAYMENT_HANDLE      => [Constants::PRIORITY => 1, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::ONBOARDING_CARD     => [Constants::PRIORITY => 2, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::FINANCE]],
                    Constants::ACCEPT_PAYMENTS     => [Constants::PRIORITY => 3, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::RECENT_TRANSACTIONS => [Constants::PRIORITY => 6, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                    Constants::SETTLEMENTS         => [Constants::PRIORITY => 4, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                    Constants::PAYMENT_ANALYTICS   => [Constants::PRIORITY => 5, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP, User\Role::FINANCE, User\Role::SUPPORT]],
                ];

            case Constants::PAYMENTS_ENABLED_AND_FREQUENTLY_TRANSACTED :
                return [
                    Constants::PAYMENT_HANDLE      => [Constants::PRIORITY => 1, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::ONBOARDING_CARD     => [Constants::PRIORITY => 2, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::FINANCE]],
                    Constants::ACCEPT_PAYMENTS     => [Constants::PRIORITY => 3, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP]],
                    Constants::SETTLEMENTS         => [Constants::PRIORITY => 4, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                    Constants::PAYMENT_ANALYTICS   => [Constants::PRIORITY => 5, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::SELLERAPP, User\Role::FINANCE, User\Role::SUPPORT]],
                    Constants::RECENT_TRANSACTIONS => [Constants::PRIORITY => 6, Constants::USER_ROLES => [User\Role::OWNER, User\Role::ADMIN, User\Role::MANAGER, User\Role::OPERATIONS, User\Role::FINANCE, User\Role::SUPPORT]],
                ];
        }
    }

    /**
     * @throws \Exception
     */
    public function getWidgetProperty($widget, $merchantId, $userId)
    {
        $property[Constants::PROPS] = Constants::APP_SCALABILITY_CONFIG_STATIC_PROPS[$widget];

        $func = 'get' . studly_case($widget) . 'DynamicProps';

        if (method_exists($this, $func) === true)
        {
            $property[Constants::PROPS] = array_merge($property[Constants::PROPS], $this->$func($merchantId, $userId));
        }

        return $property;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    private function getPaymentHandleDynamicProps($merchantId, $userId)
    {
        $response['payment_handle_slug'] = ((new \RZP\Models\PaymentLink\Service())
            ->getPaymentHandleByMerchant());

        $this->trace->info(TraceCode::MERCHANT_USER_APP_CONFIG_PAYMENT_HANDLE,
                           [
                               'merchant_id'         => $merchantId,
                               'user_id'             => $userId,
                           ]);

        return $response;
    }

    private function getAcceptPaymentsDynamicProps($merchantId, $userId)
    {
        $products = [];

        $currentProducts = $this->getCurrentProducts();

        foreach ($currentProducts as $product)
        {
            $properties   = Constants::APP_SCALABILITY_CONFIG_STATIC_PROPS[$product];

            $isNewProduct = $properties[Constants::IS_NEW_PRODUCT];

            unset($properties[Constants::IS_NEW_PRODUCT]);

            $products[Constants::PRODUCTS][] = array_merge($properties,
                                                           $this->getFTUX($merchantId, $userId, $product, $isNewProduct));
        }

        $this->trace->info(TraceCode::MERCHANT_USER_APP_CONFIG_PRODUCTS_INFO,
                           [
                               'merchant_id' => $merchantId,
                               'user_id'     => $userId,
                               'products'    => $products,
                           ]);

        return $products;
    }

    private function isPaymemtLinkEnabled()
    {
        $enabledFeatures = $this->merchant->getEnabledFeatures();

        if ((in_array(FeatureConstants::PAYMENTLINKS_V2, $enabledFeatures, true) === true) or
            (in_array(FeatureConstants::PAYMENTLINKS_COMPATIBILITY_V2, $enabledFeatures, true) === true))
        {
            return true;
        }

        return false;
    }

    private function isPaymentPagesEnabled()
    {
        /*
         * Currently the feature is true to all product conditions
         * */
        return true;
    }

    private function isQrCodeEnabled()
    {
        $enabledFeatures = $this->merchant->getEnabledFeatures();

        if (in_array(FeatureConstants::QR_CODES, $enabledFeatures, true) === true)
        {
            return true;
        }

        return false;
    }

    private function isSubscriptionsEnabled()
    {
        if ((in_array($this->app['basicauth']->getUserRole(), Constants::VALID_ROLES_FOR_SUBSCRIPTIONS, true) === true) and
            (in_array($this->merchant->merchantDetail->getActivationStatus(), Constants::VALID_ACTIVATION_STATUS_FOR_SUBSCRIPTIONS, true) === true))
        {
            return true;
        }

        return false;
    }

    private function isPaymentButtonEnabled()
    {
        if ((in_array($this->app['basicauth']->getUserRole(), Constants::VALID_ROLES_FOR_PAYMENT_BUTTON, true) === true) and
            (in_array($this->merchant->merchantDetail->getActivationStatus(), Constants::VALID_ACTIVATION_STATUS_FOR_PAYMENT_BUTTON, true) === true))
        {
            return true;
        }

        return false;
    }

    private function isPaymemtGatewayEnabled()
    {
        $activation_status = $this->merchant->merchantDetail->getActivationStatus();
        $business_website  = $this->merchant->merchantDetail->getWebsite();
        $has_key_access    = $this->merchant->getHasKeyAccess();

        $currentUserRole = $this->app['basicauth']->getUserRole();

        if (((in_array($activation_status, [Detail\Status::ACTIVATED_MCC_PENDING, Detail\Status::INSTANTLY_ACTIVATED]) === true)
             or (($activation_status === Detail\Status::ACTIVATED) and (is_null($business_website) === false))
            ) and (in_array($currentUserRole, [User\Role::OWNER, User\Role::ADMIN]) === true)
                  and ($has_key_access === true))
        {
            return true;
        }

        return false;
    }

    private function getCurrentProducts()
    {
        /*
        * Any new products will be added in the currentProducts array.
        * These products are removed as products are not yet ready: Constants::QR_CODE, Constants::TAP_AND_PAY
        * */
        $currentProducts = [];

        if ($this->isPaymemtLinkEnabled() === true)
        {
            $currentProducts[] = Constants::PAYMENT_LINK;
        }

        if ($this->isPaymemtGatewayEnabled() === true)
        {
            $currentProducts[] = Constants::PAYMENT_GATEWAY;
        }

        if ($this->isPaymentPagesEnabled() === true)
        {
            $currentProducts[] = Constants::PAYMENT_PAGES;
        }

        if ($this->isQrCodeEnabled() === true)
        {
            $currentProducts[] = Constants::QR_CODE;
        }

        if ($this->isSubscriptionsEnabled() === true)
        {
            $currentProducts[] = Constants::SUBSCRIPTIONS;
        }

        if ($this->isPaymentButtonEnabled() === true)
        {
            $currentProducts[] = Constants::PAYMENT_BUTTON;
        }

        return $currentProducts;
    }

    private function getCacheKeyForAppScalability($merchantId, $userId, $prefix)
    {
        return $prefix . ':' . $merchantId . ':' . $userId;
    }

    private function getUserProductStatusInCache($merchantId, $userId, $product)
    {
        $key = $this->getCacheKeyForAppScalability($merchantId, $userId, $product);

        $value = $this->cache->get($key);

        $this->trace->info(TraceCode::MERCHANT_USER_APP_PRODUCT_CACHE_DATA,
                           [
                               'merchant_id' => $merchantId,
                               'user_id'     => $userId,
                               'product'     => $product,
                               'cache_key'   => $key,
                               'cache_value' => $value,
                           ]);

        if (is_null($value) === false)
        {
            return $value;
        }

        return false;
    }

    private function incrementSessionBasedOnSessionCount($merchantId, $userId, $product)
    {
        $key = $this->getCacheKeyForAppScalability($merchantId, $userId, Constants::SESSION_COUNT_PREFIX);

        $value = $this->cache->get($key);

        if ((is_null($value) === false) and
            ($value['product'] === $product))
        {
            $this->cache->pull($key);

            $value['count'] = $value['count'] + 1;

            $this->cache->set($key, $value);
        }
        else
        {
            $this->cache->set($key, [
                'count'   => 1,
                'product' => $product,
            ]);
        }

        $this->trace->info(TraceCode::MERCHANT_USER_APP_INTRO_SESSION_CACHE_DATA,
                           [
                               'merchant_id' => $merchantId,
                               'user_id'     => $userId,
                               'product'     => $product,
                               'cache_key'   => $key,
                               'cache_value' => $value,
                           ]);
    }

    private function getIntroducingStatusBasedOnSessionCount($merchantId, $userId, $product)
    {
        $key = $this->getCacheKeyForAppScalability($merchantId, $userId, Constants::SESSION_COUNT_PREFIX);

        $value = $this->cache->get($key);

        // product decides the threshold to show it in introducing
        $threshold = 30;

        $this->trace->info(
            TraceCode::MERCHANT_USER_APP_INTRO_SESSION_CACHE_DATA,
            [
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'product'     => $product,
                'threshold'   => $threshold,
                'cache_key'   => $key,
                'cache_value' => $value,
            ]);

        if ((is_null($value) === false) and
            ($value['product'] === $product))
        {
            return ($value['count'] < $threshold);
        }

        return true;
    }

    private function getFTUX($merchantId, $userId, $product, $isNewProduct)
    {
        $ftux = [
            Constants::FTUX_COMPLETE => $this->getUserProductStatusInCache($merchantId, $userId, $product),
            Constants::INTRODUCING   => $isNewProduct
        ];

        if ($ftux[Constants::FTUX_COMPLETE] === true)
        {
            $ftux[Constants::INTRODUCING] = false;
        }
        elseif ($isNewProduct === true)
        {
            $ftux[Constants::INTRODUCING] = $this->getIntroducingStatusBasedOnSessionCount($merchantId, $userId, $product);
        }

        return $ftux;
    }

    public function changeMerchantUserFTUX($input, $merchantId, $userId)
    {
        $key = $this->getCacheKeyForAppScalability($merchantId, $userId, $input[Constants::PRODUCT]);

        $value = $this->cache->get($key);

        $this->trace->info(TraceCode::MERCHANT_USER_APP_CHANGE_FTUX_CACHE_DATA,
                           [
                               'merchant_id' => $merchantId,
                               'user_id'     => $userId,
                               'product'     => $input[Constants::PRODUCT],
                               'cache_key'   => $key,
                               'cache_value' => $value,
                           ]);

        if (is_null($value) === false)
        {
            $this->cache->pull($key);
        }

        $this->cache->set($key, (bool) $input[Constants::FTUX_COMPLETE]);
    }

    public function merchantUserIncrementProductSession($merchantId, $userId)
    {
        $products = $this->getAcceptPaymentsDynamicProps($merchantId, $userId);

        if (empty($products[Constants::PRODUCTS]) === false)
        {
            foreach ($products[Constants::PRODUCTS] as $product)
            {
                if ($product[Constants::INTRODUCING] === true)
                {
                    $this->incrementSessionBasedOnSessionCount($merchantId, $userId, $product[Constants::TYPE]);
                }
            }
        }
    }

    public function merchantPaymentsWithOrderSource($input)
    {
        $merchantPayments = (new \RZP\Models\Payment\Service())->fetchMultiple($input);

        $merchantOrders   = [];

        foreach ($merchantPayments["items"] as $payment)
        {
            if (is_null($payment['order_id']) === false)
            {
                $order_id = explode('_', $payment['order_id']);

                $merchantOrders[] = end($order_id);
            }
        }

        $merchantOrders = array_unique($merchantOrders);

        $merchantOrders = $this->repo->order->fetchMultipleOrdersBasedOnIds($merchantOrders);

        $orderArray     = $merchantOrders->toArray();

        $merchantOrderAndSource = [];

        foreach ($orderArray as $order)
        {
            $merchantOrderAndSource['order_' . $order['id']] = $order['product_type'];
        }

        foreach ($merchantPayments["items"] as &$payment)
        {
            $payment['product_type'] = $merchantOrderAndSource[$payment['order_id']] ?? null;
        }

        $this->trace->info(TraceCode::MERCHANT_USER_APP_PAYMENT_WITH_SOURCE_END,
                           [
                               'merchantOrderIdAndSource' => $merchantOrderAndSource,
                           ]);

        return $merchantPayments;
    }

    private function getAccountTypeLabelAndBalance($merchant, $accountType)
    {
        $balanceType = $accountType === Type::RESERVE_BALANCE ? Type::RESERVE_PRIMARY : Type::PRIMARY;

        $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), $balanceType);

        switch($accountType)
        {
            case 'fee_credit' :
                return [
                    "label" => "Fee Credit",
                    "amount" => $merchantBalance->getFeeCredits()
                ];

            case 'refund_credit' :
                return [
                    "label" => "Refund Credit",
                    "amount" => $merchantBalance->getRefundCredits()
                ];

            case 'reserve_balance' :
                return [
                    "label" => "Reserve Balance",
                    "amount" => $merchantBalance->getBalance()
                ];
        }

    }

    public function updateVirtualAccountForFundAddition($merchant)
    {
        $merchantDetails = $merchant->merchantDetail;

        $merchantVAIds = $merchantDetails->getFundAdditionVAIds();

        if($merchantVAIds === null)
        {
            return;
        }

        $merchantDetails->setFundAdditionVAIds(null);

        $this->repo->merchant_detail->saveOrFail($merchantDetails);

        $this->closeVirtualAccountForFundAddition($merchantVAIds, $merchant);
    }

    private function closeVirtualAccountForFundAddition($merchantVAIds, $merchant)
    {
        $this->trace->info(Tracecode::VIRTUAL_ACCOUNT_CLOSE_REQUEST_FOR_FUND_ADDITION, [
            'input' => $merchantVAIds
        ]);

        foreach($merchantVAIds as $type => $VAId)
        {

            $rzpMerchant = $this->repo->merchant->findOrFail($this->getRazorpayMerchantBasedOnType($type)['merchant_id']);

            $response = (new VirtualAccount\Service)->closeVirtualAccountByPublicIdAndMerchant($VAId, $rzpMerchant);

            $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CLOSED_FOR_FUND_ADDITION, [
                'type' => $type,
                'response' => $response
            ]);
        }

        $this->trace->info(Tracecode::VIRTUAL_ACCOUNT_CLOSED_ON_BANK_UPDATION, [
            'input' => $merchantVAIds
        ]);
    }

    /**
     * @param Entity $merchant
     * @param Entity $subMerchant
     * @param bool $linkedAccount
     * @param string|null $appType
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function assignSubMerchantPricingPlan(Entity $merchant, Entity $subMerchant, bool $linkedAccount = false, string $appType = null)
    {
        // assign parent pricing plan by default
        $pricingPlan = $merchant->getPricingPlanId();

        // Use Startup Plan as the default for linked accounts where transfer method pricing is 0
        if (($merchant->isMarketplace() === true) and ($linkedAccount === true))
        {
            $pricingPlan = Pricing\DefaultPlan::PROMOTIONAL_PLAN_ID;
        }
        elseif ($merchant->isPartner() === true)
        {
            // for partner, assign based on config defined on partner, if available
            $application = $this->fetchPartnerApplication($merchant, $appType);

            $config      = (new PartnerConfig\Core)->fetch($application);

            $pricingPlan = optional($config)->getDefaultPlanId() ?:  $pricingPlan;
        }

        $subMerchant->setPricingPlan($pricingPlan);
    }

    public function updateSubMerhantPricingPlanBasedOnFeeBearerAndSubcategory($subMerchant, $useCategory2 = false)
    {
        if ($useCategory2 === true)
        {
            $businessCategory = $subMerchant->getCategory2();
        }
        else {
            $subMerchantDetails = (new Detail\Core)->getMerchantDetails($subMerchant);

            $businessCategory = $subMerchantDetails->getBusinessCategory();
        }

        $feeBearer = $subMerchant->getFeeBearer();

        $feeBearer = strtolower($feeBearer);

        if ($feeBearer === FeeBearer::DYNAMIC)
        {
            return;
        }

        $pricingPlanName = Pricing\DefaultPlan::SUB_MERCHANT_DEFAULT_PRICING_PLAN_MAP_CAT2[$feeBearer][Category::ECOMMERCE];

        //In edit, use category2, in bulk upload - use business category
        if ($useCategory2 === true)
        {
            $pricingPlanName = Pricing\DefaultPlan::SUB_MERCHANT_DEFAULT_PRICING_PLAN_MAP_CAT2[$feeBearer][$businessCategory] ?? $pricingPlanName;
        }
        else
        {
            $pricingPlanName = Pricing\DefaultPlan::SUB_MERCHANT_DEFAULT_PRICING_PLAN_MAP_BUS_CAT[$feeBearer][$businessCategory] ?? $pricingPlanName;
        }

        $pricingPlans = (new Pricing\Repository)->getPlanByName($pricingPlanName);

        if ($pricingPlans->count() === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT);
        }

        (new Merchant\Service())->validatePricingPlanForFeeBearer($subMerchant, $pricingPlans);

        $pricingPlanId = $pricingPlans->getId();

        $subMerchant->setPricingPlan($pricingPlanId);

        $this->repo->saveOrFail($subMerchant);

    }

    public function updateSubMerchantFeeBearer($subMerchant, $feeBearer = FeeBearer::PLATFORM)
    {
        $feeBearer = strtolower($feeBearer);

        if ($feeBearer === FeeBearer::DYNAMIC)
        {
            return;
        }

        if (in_array($feeBearer, array_keys(FeeBearer::FEE_BEARER_TYPE_MAP)))
        {
            $feeBearer = FeeBearer::FEE_BEARER_TYPE_MAP[$feeBearer];
        }

        $subMerchant->setFeeBearer($feeBearer);

        $this->repo->saveOrFail($subMerchant);
    }

    public function addMerchantSupportingEntitiesAsync(Entity $merchant, Entity $aggregatorMerchant = null)
    {
        $isExpEnabled = $this->isExpEnabledForProductConfigIssue($aggregatorMerchant);

        $this->repo->transactionOnLiveAndTest(function() use($merchant, $aggregatorMerchant, $isExpEnabled) {

            $merchantBalance = $this->createBalance($merchant, Mode::TEST);

            $this->createBalanceConfig($merchantBalance, Mode::TEST);

            (new BankAccount\Core)->createTestBankAccount($merchant);

            if($isExpEnabled === false)
            {
                (new Methods\Core)->setDefaultMethods($merchant, $aggregatorMerchant);
            }

            (new ScheduleTask\Core)->createDefaultSettlementSchedule($merchant);

            $this->addDefaultFeatures($merchant);

        });

        if($isExpEnabled === true)
        {
            (new Methods\Core)->setMethods($merchant, $aggregatorMerchant);
        }

        $this->addPartnerAddedFeaturesToSubmerchant($merchant, $aggregatorMerchant);
    }

    private function addDefaultFeatures(Entity $merchant)
    {
        $defaultFeatures = [Feature\Constants::OTP_AUTH_DEFAULT, Feature\Constants::PAYMENTLINKS_V2];

        if($merchant->isRazorpayOrgId() === false)
        {
            array_push($defaultFeatures, Feature\Constants::DISABLE_NATIVE_CURRENCY);
        }

        $featuresEnabled = $this->repo->feature->findMerchantWithFeatures($merchant->getId(), $defaultFeatures);

        if(count($featuresEnabled) !== count($defaultFeatures))
        {
            $this->setDefaultFeatureForMerchant($merchant);

            $this->setPaymentLinkServiceDefaultForMerchant($merchant);
        }
    }

    /**
     * @param Entity      $merchant
     * @param Entity|null $partner
     *
     * Propagate all PARTNER_APPLICATION type features of partner to sub merchants.
     * @return null
     */
    public function addPartnerAddedFeaturesToSubmerchant(Entity $merchant, Entity $partner = null)
    {
        if (empty($partner))
        {
            return null;
        }

        $this->addPartnerAddedFeaturesToSubmerchantOnMode($merchant, $partner, Mode::LIVE);

        $this->addPartnerAddedFeaturesToSubmerchantOnMode($merchant, $partner, Mode::TEST);
    }

    public function getDefaultFeaturesToPropagate(Entity $partner, string $mode)
    {
        $merchantEnabledFeatures = $this->repo
            ->feature
            ->fetchByEntityTypeAndEntityId(
                Feature\Constants::MERCHANT,
                $partner->getId(),
                $mode
            )
            ->pluck(Feature\Entity::NAME);

        $data = $merchantEnabledFeatures->toArray();

        return array_intersect($data, CONSTANTS::$defaultFeaturesToPropagate);
    }

    public function addPartnerAddedFeaturesToSubmerchantOnMode(Entity $merchant, Entity $partner, string $mode)
    {
        $this->repo->transactionOnConnection(function() use ($merchant, $partner, $mode) {
            $merchantApplicationCore = new MerchantApplications\Core();
            $appType                 = $merchantApplicationCore->getDefaultAppTypeForPartner($partner);
            $appIds                   = $merchantApplicationCore->getMerchantAppIds($partner->getId(), [$appType]);

            $featureParams = new Base\Collection;
            $featureNames = [];

            if(empty($appIds) === false)
            {
                $appId = $appIds[0];

                $featureNames   =  $this->repo
                                        ->feature
                                        ->fetchByEntityTypeAndEntityId(
                                            Feature\Constants::PARTNER_APPLICATION,
                                            $appId,
                                            $mode
                                        )
                                        ->pluck(Feature\Entity::NAME);
            }

            $featuresToPropagate = $this->getDefaultFeaturesToPropagate($partner, $mode);

            foreach ($featureNames as $featureName)
            {
                if(in_array($featureName, $featuresToPropagate) === false)
                {
                    array_push($featuresToPropagate, $featureName);
                }
            }

            foreach ($featuresToPropagate as $featureName)
            {
                $featureParams->push([
                    Feature\Entity::ENTITY_TYPE => Feature\Constants::MERCHANT,
                    Feature\Entity::ENTITY_ID   => $merchant->getId(),
                    Feature\Entity::NAME        => $featureName
                ]);
            }

            $featureCore       = new Feature\Core();
            $featureCore->mode = $mode;
            $featureParams->map(function($item) use ($featureCore, $mode) {
                return $featureCore->create($item, false, true, $mode);
            });
        }, $mode);
    }

    public function addMerchantSupportingEntities(Entity $merchant, Entity $aggregatorMerchant = null,
                                                  bool $optimizeCreationFlow = false, array $input = [])
    {
        Tracer::inspan(['name' => HyperTrace::CREATE_MERCHANT_DETAILS_CORE], function () use ($merchant, $input) {

            (new Detail\Core)->createMerchantDetails($merchant, $input);
        });

        if ($optimizeCreationFlow === true)
        {
            MerchantSupportingEntitiesCreateJob::dispatch($this->mode, $merchant->getId(), $aggregatorMerchant->getId());
        }
        else
        {
            $this->addMerchantSupportingEntitiesAsync($merchant, $aggregatorMerchant);
        }
    }

    protected function setDefaultFeatureForMerchant(Entity $merchant)
    {
        // Removing this feature is complicated, but
        // blindly assigning the feature to everybody is not
        (new Feature\Core)->create([
                                       Feature\Entity::ENTITY_TYPE     => E::MERCHANT,
                                       Feature\Entity::ENTITY_ID       => $merchant->getId(),
                                       Feature\Entity::NAME            => Feature\Constants::OTP_AUTH_DEFAULT,
                                   ], $shouldSync = true);

        (new Feature\Core)->create([
                                       Feature\Entity::ENTITY_TYPE     => E::MERCHANT,
                                       Feature\Entity::ENTITY_ID       => $merchant->getId(),
                                       Feature\Entity::NAME            => Feature\Constants::NEW_BANKING_ERROR,
                                   ], $shouldSync = true);

        if ($merchant->isRazorpayOrgId() === false) {

            (new Feature\Core)->create([
                                           Feature\Entity::ENTITY_TYPE     => E::MERCHANT,
                                           Feature\Entity::ENTITY_ID       => $merchant->getId(),
                                           Feature\Entity::NAME            => Feature\Constants::DISABLE_NATIVE_CURRENCY,
                                       ], $shouldSync = true);

        }
    }

    public function isExpEnabledForProductConfigIssue(Entity $aggregatorMerchant = null)
    {
        if(is_null($aggregatorMerchant) === true)
        {
            return false;
        }

        $properties = [
            'id'            => $aggregatorMerchant->getId(),
            'experiment_id' => $this->app['config']->get('app.product_config_issue_exp_id'),
        ];

        return $this->isSplitzExperimentEnable($properties, 'enable');
    }

    protected function setPaymentLinkServiceDefaultForMerchant(Entity $merchant)
    {
        // adding this feature for all merchants registering.
        // by default they should get payment link service feature
        (new Feature\Core)->create([
                                       Feature\Entity::ENTITY_TYPE     => E::MERCHANT,
                                       Feature\Entity::ENTITY_ID       => $merchant->getId(),
                                       Feature\Entity::NAME            => Feature\Constants::PAYMENTLINKS_V2,
                                   ], $shouldSync = true);
    }

    /**
     * Saves partner_intent if present in settings table
     *
     * @param array $input
     * @param array $merchant
     *
     */
    protected function savePartnerIntentInSettings(array $input, Entity $merchant)
    {
        if (isset($input[Constants::PARTNER_INTENT]) and $input[Constants::PARTNER_INTENT] === true)
        {
            $data = [
                Constants::PARTNER_INTENT       => true,
            ];

            Accessor::for($merchant, Constants::PARTNER)
                    ->upsert($data)
                    ->save();
        }
    }

    /**
     * Resets merchants settlements to default settlement schedule for linked accounts
     *
     * Schedule will be same as its parent settlement schedule
     *
     * @param array $merchantIds
     * @return array
     * @throws Throwable
     */
    public function resetSettlementSchedule(array $merchantIds): array
    {
        $failed = $invalid = $processed = [];

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIds);

        foreach ($merchants as $merchant)
        {
            if ($merchant->isLinkedAccount() === false)
            {
                $invalid[] = $merchant->getId();

                continue;
            }

            try
            {
                (new ScheduleTask\Core)->createDefaultSettlementSchedule($merchant);

                $this->trace->info(
                    TraceCode::MERCHANT_SCHEDULE_UPDATED,
                    [
                        'merchant_id' => $merchant->getId()
                    ]);

                $processed[] = $merchant->getId();
            }
            catch (Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::FAILED_TO_ASSIGN_SCHEDULE,
                    [
                        'merchant_id' => $merchant->getId()
                    ]);

                $failed[] = $merchant->getId();
            }
        }

        $summary = [
            'invalid_count'   => count($invalid),
            'failed_count'    => count($failed),
            'processed_count' => count($processed),
            'invalid'         => $invalid,
            'failed'          => $failed,
            'processed'       => $processed,
        ];

        $this->trace->info(
            TraceCode::RESET_SCHEDULES_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function syncHeimdallRelatedEntities(Entity $merchant, array $input, $create = false)
    {
        if (isset($input[Entity::GROUPS]) === true)
        {
            $this->repo->group->validateExists($input[Entity::GROUPS]);

            $this->repo->sync($merchant, Entity::GROUPS, $input[Entity::GROUPS]);
        }

        if (isset($input[Entity::ADMINS]) === true)
        {
            $this->repo->admin->validateExists($input[Entity::ADMINS]);

            $this->repo->sync($merchant, Entity::ADMINS, $input[Entity::ADMINS]);

            if ($create === true)
            {
                $firstAdminId = current($input[Entity::ADMINS]);

                if ($firstAdminId !== false)
                {
                    // Update admin leads
                    $adminLead = (new AdminLead\Core)->getByAdminId($firstAdminId);

                    if (empty($adminLead) === false)
                    {
                        $adminLead->merchant()->associate($merchant);

                        $this->repo->saveOrFail($adminLead);
                    }
                }
            }
        }
    }

    public function get($id, $relations = [])
    {
        return $this->repo->merchant->findOrFailPublicWithRelations(
            $id, $relations);
    }

    /**
     * updates billing label and dba to same value
     * @param $merchant
     * @param $input
     * @throws Throwable
     */
    public function editMerchantBillingLabelAndDba($merchant, $input)
    {
        $this->repo->transactionOnLiveAndTest(function () use($merchant, $input)
        {
            $merchant->edit($input, 'edit_billing_label');

            $dba[Detail\Entity::BUSINESS_DBA] = $input[Entity::BILLING_LABEL];

            $merchant->merchantDetail->edit($dba, 'edit');

            $this->repo->merchant->saveOrFail($this->merchant);

            $this->repo->merchant_detail->saveOrFail($this->merchant->merchantDetail);

            [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Brand Name Updated';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );

            $this->trace->info(
                TraceCode::MERCHANT_BILLING_LABEL_UPDATE, [
                Entity::ID => $merchant->getId(),
                Entity::BILLING_LABEL => $merchant->getBillingLabel(),
                Detail\Entity::BUSINESS_DBA => $merchant->getDbaName(),
            ]);
        });
    }

    /**
     * Generates suggestions for billing labels based on Business and website name
     * @param $merchant
     * @return array of suggestions
     */
    public function getBillingLabelSuggestions($merchant): array
    {
        $suggestionsByBusinessName = $this->getBillingLabelSuggestionsByBusinessName($merchant);

        $suggestionsByWebsite = $this->getBillingLabelSuggestionsByWebsite($merchant);

        return array_merge($suggestionsByBusinessName, $suggestionsByWebsite);
    }

    /**
     * Generates suggestions for billing labels based on website name
     * generate suggestions if website url is valid and host is not google play store
     * @param $merchant
     * @return array of suggestions, empty if url is invalid or merchant has no website
     */
    protected function getBillingLabelSuggestionsByWebsite($merchant): array
    {
        $websiteUrl = $merchant->merchantDetail->getWebsite();

        $websiteUrl = $this->preProcessStringForBillingLabelUpdate($websiteUrl);

        $suggestions = [];

        if ((isset($websiteUrl) === false) or
            ($this->isValidSchemeAndHostForBillingLabelUpdate($websiteUrl) === false))
        {
            return $suggestions;
        }

        $host = parse_url($websiteUrl, PHP_URL_HOST);

        $extractedDomains = (new TLDExtract())->extract($host);

        if (count($extractedDomains) >= 2)
        {
            $topLevelDomain = $extractedDomains[1];

            $hostWithoutTld = $extractedDomains[0];

            // divide in subdomain and second level domain
            $hostParts = explode('.', $hostWithoutTld);

            // take second level domain(just below top level domain) as website name
            $websiteName = $hostParts[count($hostParts)-1];

            array_push($suggestions, $websiteUrl);

            array_push($suggestions, $websiteName);

            array_push($suggestions, strtoupper($websiteName));

            array_push($suggestions, ucfirst($websiteName));

            array_push($suggestions, $websiteName . '.' . $topLevelDomain);
        }

        return $suggestions;
    }

    /**
     * checks if scheme is valid(http and https) and
     * host is not 'play.google.com' for billing label update
     * @param $websiteUrl
     * @return bool top level domain and domain name
     */
    public function isValidSchemeAndHostForBillingLabelUpdate($websiteUrl):bool
    {
        $host = parse_url($websiteUrl, PHP_URL_HOST);

        $scheme = parse_url($websiteUrl, PHP_URL_SCHEME);

        // allow only http, https scheme
        // do not consider play store app link as merchant website
        if (($host === null) or
            ($scheme === null) or
            ((($scheme === 'http') or ($scheme === 'https')) === false) or
            ($host === 'play.google.com'))
        {
            return false;
        }

        return true;
    }

    /**
     * Generates suggestions for billing labels based on business name
     *
     * @param $merchant
     * @return array of suggestions, empty if merchant has no business name
     */
    protected function getBillingLabelSuggestionsByBusinessName($merchant): array
    {
        $businessName = $merchant->merchantDetail->getBusinessName();

        $suggestions = [];

        if (isset($businessName) === true)
        {
            $businessName = $this->preProcessStringForBillingLabelUpdate($businessName);

            array_push($suggestions, ucwords($businessName));

            array_push($suggestions, strtoupper($businessName));

            $businessNameWithoutBusinessType = $this->removeBusinessTypesForBillingLabelUpdate($businessName);

            // if business name is not same after removing business type
            if ($businessName !== $businessNameWithoutBusinessType)
            {
                array_push($suggestions, ucwords($businessNameWithoutBusinessType));
            }
        }

        return $suggestions;
    }

    /**
     * Remove Pvt, Pvt., Ltd, Ltd., Private, Limited, liability,
     * company, partnership words
     * @param string Business name
     * @return string Business name without above words
     */
    public function removeBusinessTypesForBillingLabelUpdate($businessName): string
    {
        $businessTypes = [
            'pvt.',
            'pvt',
            'llc.',
            'llc',
            'ltd.',
            'ltd',
            'llp.',
            'llp',
            'private',
            'limited',
            'liability company',
            'liability partnership'
        ];

        foreach ($businessTypes as $businessType)
        {
            $businessName = str_replace($businessType, '', $businessName);
        }

        $businessName = trim(preg_replace('/\s+/', ' ', $businessName));;

        return $businessName;
    }

    /**
     * trims a string and makes it lowercase
     * @param $string
     * @return string
     */
    public function preProcessStringForBillingLabelUpdate($string): string
    {
        $string =  trim($string);

        return strtolower($string);
    }

    /**
     * @param $merchant
     * @param $input
     *
     * @return mixed
     * @throws Throwable
     */
    public function edit($merchant, $input)
    {
        $merchant->setAuditAction(Action::EDIT_MERCHANT);

        $input = $this->modifyEditInput($input);

        $merchant->edit($input);

        $plan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($merchant->getPricingPlanId());

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input)
        {
            if (isset($input[Merchant\Entity::CATEGORY]) === true)
            {
                $forceIgnoreValidation = false;

                if (isset($input['reset_methods']) === true)
                {
                    $forceIgnoreValidation = $input['reset_methods'];
                }

                (new Methods\Core)->validateCategoryUpdateForMerchant($input[Merchant\Entity::CATEGORY], $merchant, $forceIgnoreValidation);
                (new Terminal\Core)->processMerchantMccUpdate($merchant, $input);
            }

            if (isset($input['reset_methods']) === true)
            {
                // reset merchant methods if category or category2 is being updated
                if (($input['reset_methods'] === true) and ((isset($input[Entity::CATEGORY]) === true) or (isset($input[Entity::CATEGORY2]) === true)))
                {
                    $merchant->setDefaultMethodsBasedOnCategory();
                }
                unset($input['reset_methods']);
            }

            if (empty($input['reset_pricing_plan']) !== true)
            {
                if ($merchant->org->isFeatureEnabled(FeatureConstants::SUB_MERCHANT_PRICING_AUTOMATION))
                {
                    if ((isset($input[Entity::FEE_BEARER]) === true) or (isset($input[Entity::CATEGORY2]) === true))
                    {
                        $this->updateSubMerhantPricingPlanBasedOnFeeBearerAndSubcategory($merchant, true);
                    }
                }
            }


            $merchantDetailCore = new Detail\Core;
            // This is used to sync fields transaction_report_email and website in merchant and merchantDetail
            $merchantDetailCore->syncToMerchantDetailFields($merchant, $input);

            $merchantDetailCore->updateLegalEntity($input, $merchant);

            $this->saveAndNotify($merchant);
        });

        $this->syncHeimdallRelatedEntities($merchant, $input);

        // Groups have to be saved separately
        //
        // Also since we're doing a fetch again it's better we save
        // the previous version of $merchant entity first and then fetch it.
        if ((empty($input[Entity::GROUPS]) === false) or
            (empty($input[Entity::ADMINS]) === false))
        {
            // If groups has been edited, fetch the entity again with relations.
            // Simple entity edit does not contain updated relations
            $merchant = $this->get(
                $merchant->getId(),
                [Entity::GROUPS, Entity::ADMINS]);
        }
        return $merchant;
    }

    public function modifyEditInput(array $input): array
    {
        if (array_key_exists('category', $input))
        {
            $input['category'] = (string) $input['category'];
        }
        return $input;
    }

    /**
     * Edit merchant email
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array $input
     *
     * @param string $validationRule
     * @return \RZP\Models\Merchant\Entity
     * @throws BadRequestException
     */
    public function editEmail($merchant, $input, $operation = 'edit_email')
    {
        $oldEmail = $merchant->getEmail();

        $parentId = $merchant->getReferrer();

        if (empty($parentId) === false)
        {
            $parent = $this->repo->merchant->find($parentId);

            if ((empty($parent) === false) and (strtolower($merchant->getEmail()) === strtolower($parent->getEmail())))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                    Merchant\Entity::EMAIL,
                    $input[Merchant\Entity::EMAIL]
                );
            }
        }

        $merchant->edit($input, $operation);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'old_email' => $oldEmail,
                'new_email' => $input['email']
            ]);

        $this->saveAndNotify($merchant);

        $newEmail = $merchant->getEmail();

        $this->editEmailInMailingList($oldEmail, $newEmail, $merchant);

        return $merchant;
    }

    /**
     * Edit merchant configuration
     *
     * @param Entity $merchant
     * @param array  $input
     *
     * @return Entity
     */
    public function editConfig($merchant, $input): Entity
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'activated' => $merchant->isActivated(),
                'live'      => $merchant->isLive(),
                'input'     => $input,
            ]);

        $merchant->edit($input, 'editConfig');

        $this->saveAndNotify($merchant);

        $this->pushSelfServeActionForAnalyticsForMerchantConfigUpdate($input, $merchant);

        $this->pushSelfServeActionForAnalyticsForEnablingInstantRefund($input, $merchant);

        return $merchant;
    }

    public function editEmailInMailingList($oldEmail, $newEmail, $merchant)
    {
        $this->removeMerchantEmailToMailingList($merchant, [], [$oldEmail]);

        $this->addMerchantEmailToMailingList($merchant, [], [$newEmail]);
    }

    public function createBalance($merchant, $mode)
    {
        $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), Type::PRIMARY, $mode);

        // Avoid Creating a new Balance of type Primary if already exists for the merchant,
        // This a Safety Check to avoid multiple primary balances being created,
        // applicable (rare scenarios, due to unknown bug) when a Whitelist Merchant is instantly activated
        // with primary balance is created but activated flag not set
        if ($merchantBalance !== null)
        {
            $this->trace->info(TraceCode:: MERCHANT_BALANCE_ID,
                               [
                                   'balance_id' => $merchantBalance->getId()
                               ]);

            return $merchantBalance;
        }

        $merchantBalance = Merchant\Balance\Entity::buildFromMerchant($merchant);

        $merchantBalance->setConnection($mode);

        $this->repo->balance->createBalance($merchantBalance);

        /*
         * adding logs to see if balance is getting saved to DB
         * Thread: https://razorpay.slack.com/archives/CNXC0JHQF/p1634534875388500
         */
        $savedBalance = $this->repo->balance->getMerchantBalanceByType(
            $merchant->getId(), Type::PRIMARY, $mode);

        if(empty($savedBalance) === true)
        {
            throw new Exception\ServerErrorException(
                'balance entity could not be saved',
                ErrorCode::SERVER_ERROR,
                [
                    'merchant_id'   => $merchant->getId(),
                    'mode'          => $mode
                ]);
        }
        else
        {
            $this->trace->info(TraceCode:: MERCHANT_BALANCE_ID, [
                'balance_id'    => $savedBalance->getId(),
                'merchant_id'   => $merchant->getId(),
                'mode'          => $mode
            ]);
        }

        return $merchantBalance;
    }

    public function createBalanceConfig($merchantBalance, $mode)
    {
        $balanceConfig = $this->repo->balance_config->connection($mode)->getBalanceConfigsForBalanceIds([$merchantBalance->getId()]);

        if(count($balanceConfig) > 0)
        {
            return $balanceConfig->get(0);
        }

        $balanceConfig = Merchant\Balance\BalanceConfig\Entity::buildFromBalance($merchantBalance);

        $balanceConfig->setConnection($mode);

        $this->repo->balance_config->createBalanceConfig($balanceConfig);

        return $balanceConfig;
    }

    public function getUsers(Entity $merchant, string $product = Product::PRIMARY)
    {
        $users = $merchant->users()
                          ->wherePivot(User\Entity::PRODUCT, $product)
                          ->get()
                          ->callOnEveryItem('toArrayMerchant');

        return $users;
    }

    public function getUsersByRole(Entity $merchant, string $role, string $product = Product::BANKING)
    {
        $users = $merchant->users()
            ->where(User\Entity::ROLE, $role)
            ->wherePivot(User\Entity::PRODUCT, $product)
            ->get()
            ->callOnEveryItem('toArrayMerchant');

        return $users;
    }

    /**
     * Save merchant entity and notify on slack
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @return null
     */
    protected function saveAndNotify($merchant)
    {
        $this->repo->saveOrFail($merchant);

        // Dont notify for linked account changes
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $data = $this->getEditedMerchantDifference($merchant);

        if (empty($data) === false)
        {
            $label   = $merchant->getBillingLabel();
            $message = $merchant->getDashboardEntityLinkForSlack($label);
            $user    = $this->getInternalUsernameOrEmail();

            $message .= ' ' . $merchant->getEntity() . ' edited by ' . $user;

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:'
                ]
            );
        }
    }

    /**
     * Get difference between the original and updated attributes
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @return array|null
     */
    protected function getEditedMerchantDifference($merchant)
    {
        $original = $merchant->getOriginalAttributesAgainstDirty();

        if ($original !== null)
        {
            $dirtyAttributes = $merchant->getDirty();

            $data = array();

            foreach ($original as $key => $value)
            {
                $data[$key] = '*Old*: ' . $value . PHP_EOL . '*New*: ' . $dirtyAttributes[$key];
            }

            return $data;
        }
    }


    protected function deleteRiskTags($merchantTags, $merchant)
    {
        $riskTags = explode(',', RiskActionConstants::RISK_TAGS_CSV);

        foreach ($merchantTags as $merchantTag)
        {
            if (in_array(strtolower($merchantTag),$riskTags))
            {
                $this->deleteTag($merchant->getId(), $merchantTag);
            }
        }
    }

    protected function updateFraudTypeIfApplicable($merchant, $action, $fraudType)
    {
        if ((in_array($action, Merchant\Action::RISK_ACTIONS_LIST_FOR_SETTING_FRAUD_TYPE) === false) or
            (in_array($fraudType, RiskActionConstants::FRAUD_WHITELIST_TAGS) === true))
        {
            return;
        }

        if (strlen($fraudType) === 0)
        {
            $merchant->merchantDetail->setFraudType('');
        }
        else
        {
            $merchant->merchantDetail->setFraudType(sprintf(Constants::FRAUD_TYPE_TAG_TPL, $fraudType));
        }

        $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);
    }

    public function addOrClearRiskTagAndSetFraudType($merchant, $riskAttributes, $action)
    {
        $merchantTags = $merchant->tagNames();

        if ((isset($riskAttributes[RiskActionConstants::CLEAR_RISK_TAGS]) === true)
            and (int)($riskAttributes[RiskActionConstants::CLEAR_RISK_TAGS]) === 1)
        {
            $this->deleteRiskTags($merchantTags, $merchant);

            $this->updateFraudTypeIfApplicable($merchant, $action, '');
        }
        else if (isset($riskAttributes[RiskActionConstants::RISK_TAG]) === true)
        {
            array_push($merchantTags, $riskAttributes[RiskActionConstants::RISK_TAG]);

            $this->updateFraudTypeIfApplicable($merchant, $action, $riskAttributes[RiskActionConstants::RISK_TAG]);

            $this->addTags($merchant->getId(), [
                'tags'  => $merchantTags,
            ], false);
        }
    }

    protected function getAdminEntityForBulkAction($input)
    {
        try
        {
            if (isset($input[Constants::BULK_WORKFLOW_ACTION_ID]) === true)
            {
                $bulkAction = $this->repo->workflow_action->findOrFailPublic($input[Constants::BULK_WORKFLOW_ACTION_ID]);

                if (isset($bulkAction) === true)
                {
                    return $this->repo->admin->findByPublicId(sprintf('%s_%s', Admin\Entity::getSign(), $bulkAction->getStateChangerId()));
                }
            }
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BULK_RISK_ACTION_ADMIN_FETCH_FAILED, [
                    'bulk_action_id'    => $input[Constants::BULK_WORKFLOW_ACTION_ID] ?? null,
                ]);
        }
        return null;
    }

    public function action($merchant, $input, bool $useWorkflows = true)
    {
        $merchant->getValidator()->validateInput('action', $input);

        $action = $input['action'];

        if ($this->shouldValidateTag($useWorkflows) === true)
        {
            $adminEntity = $this->getAdminEntityForBulkAction($input);

            (new Validator())->validateRiskPermissionForAction($merchant, $action, $adminEntity);
        }

        $internationalProducts = array_key_exists(ProductInternationalMapper::INTERNATIONAL_PRODUCTS, $input) ?
            $input[ProductInternationalMapper::INTERNATIONAL_PRODUCTS] :
            null;

        $riskAttributes = $input[RiskActionConstants::RISK_ATTRIBUTES] ?? null;

        $originalMerchant = clone $merchant;

        $function = camel_case($action);

        $this->repo->transactionOnLiveAndTest(function() use (
            $merchant,
            $function,
            $useWorkflows,
            $originalMerchant,
            $action,
            $internationalProducts,
            $riskAttributes
        ) {
            $this->handleInternationalAction($action, $merchant, $internationalProducts);

            $merchant->$function();

            if ($useWorkflows === true)
            {
                $this->triggerWorkFlowForMerchantEditAction($originalMerchant, $merchant, $action);
            }

            $this->addOrClearRiskTagAndSetFraudType($merchant, $riskAttributes, $action);

            $this->repo->saveOrFail($merchant);
        });

        if (isset($riskAttributes[RiskActionConstants::TRIGGER_COMMUNICATION]) === true)
        {
            $this->triggerCommunicationIfApplicable($merchant,$action,$riskAttributes[RiskActionConstants::TRIGGER_COMMUNICATION]);
        }
        // updating merchant details here because merchant entity is getting updated above

        $merchantDetails = $merchant->merchantDetail;

        if ($action === Merchant\Action::RELEASE_FUNDS)
        {
            /* If settlements are to be released for the merchant we need to make sure that bank
            account is created in the bank account table */

            if (empty($merchantDetails) === false)
            {
                if ($merchantDetails->getBankDetailsVerificationStatus() === Detail\Constants::VERIFIED)
                {
                    (new Activate())->createBankAccountEntry($merchant);
                }
            }
            $this->addMerchantToSettlementBucketOnFundsRelease($merchant);
        }
        else if ($action === Constants::SUSPEND)
        {
            $this->removeMerchantEmailToMailingList($merchant);
        }
        else if ($action === Constants::UNSUSPEND)
        {
            $this->addMerchantEmailToMailingList($merchant);
        }

        //
        // sync linked accounts' hold_funds with that of parent merchant's . https://docs.google.com/document/d/1ePztfh9GG4ImVzKlnQVaJ0GKCid0nLx_FRAyJGJDyTc/edit?usp=sharing
        //
        $linkedAccountCount = $this->repo->merchant->fetchLinkedAccountsCount($merchant->getId());

        if (($linkedAccountCount > 0) and
            (in_array($action, [Merchant\Action::HOLD_FUNDS, Merchant\Action::RELEASE_FUNDS]) === true))
        {
            $holdFunds = ($action === Merchant\Action::HOLD_FUNDS) ? 1 : 0;

            MerchantHoldFundsSync::dispatch($this->mode, $merchant->getId(), $holdFunds);
        }

        // pipe to slack if the action is defined
        if (empty(SlackActions::$actionMsgMap[$action]) === false)
        {
            $this->logActionToSlack($merchant, $action);
        }

        //need to remove notification if added through bulk update
        if (isset($riskAttributes[RiskActionConstants::TRIGGER_COMMUNICATION]) === false)
        {
            (new MerchantActionNotification())->removeNotificationTag($merchant, $action);
        }

        return $merchant;
    }

    private function shouldValidateTag($useWorkflows)
    {
        if($useWorkflows === false ||
           $this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            return true;
        }

        return false;
    }
    public function merchantAction(string $merchantId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant->getValidator()->validateInput('action', $input);

        return (new Merchant\Service())->action($merchantId, $input, false);
    }

    /*
     check if merchant is subMerchant to aggregator/fully Managerd partner
     and if partner is settle to partner
     and then check partner's bank account exists or not.
    */
    public function getSettledToPartnersTypeOfMerchantIfExists(Entity $merchant)
    {
        $partners = $this->fetchAffiliatedPartners($merchant->getId());

        //
        // subMerchant can belong to only one aggregator or fully managed at a time.
        // settlementPartnerTypes are aggregator and fully Managed.
        //
        $partner = $partners->filter(function(Entity $partner)
        {
            return (in_array($partner->getPartnerType(), PartnerConstants::$settlementPartnerTypes, true) === true);

        })->first();

        if (empty($partner) === true)
        {
            return null;
        }

        return $partner;
    }

    public function isValidBankAccountForSettledToPartner(Entity $submerchant, Entity $partner = null)
    {
        if ($partner === null)
        {
            return false;
        }

        $application = $this->fetchPartnerApplication($partner);

        $config      = (new PartnerConfig\Core)->fetch($application, $submerchant);

        if ($config === null)
        {
            return false;
        }

        $shouldSettleToPartner = $config->shouldSettleToPartner();

        if ($shouldSettleToPartner === true)
        {
            // validate Partner's Bank account
            $bankAccount = $partner->bankAccount;

            if ($bankAccount === null)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_NO_BANK_ACCOUNT_FOUND);
            }
        }

        return $shouldSettleToPartner;
    }

    /**
     * Adds merchant to settlement bucket when funds are released for the merchant.
     *
     * @param Merchant\Entity $merchant
     */
    protected function addMerchantToSettlementBucketOnFundsRelease(Merchant\Entity $merchant)
    {
        $settlementTime = Carbon::now(Timezone::IST)->getTimestamp();

        (new Bucket\Core)->addMerchantToSettlementBucket('', $merchant->getId(), $settlementTime);

        $this->trace->info(
            TraceCode::MERCHANT_ADDED_TO_BUCKET_ON_RELEASE_FUNDS,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $this->mode,
            ]);
    }

    /**
     * @param Entity $oldMerchant
     * @param Entity $newMerchant
     * @param string $action
     */
    protected function triggerWorkFlowForMerchantEditAction(Entity $oldMerchant, Entity $newMerchant, string $action)
    {
        $admin = $this->app['basicauth']->getAdmin();

        // Check for admin permissions
        $admin->hasMerchantActionPermissionOrFail($action);

        $routePermission = Permission\Name::$actionMap[$action];

        $this->app['workflow']->setPermission($routePermission)->handle($oldMerchant, $newMerchant);
    }

    /**
     * This function is used for getting the activation status change log of a merchant
     * @param Entity $merchant
     *
     * @return PublicCollection
     */
    public function getActivationStatusChangeLog(Entity $merchant, $mode = null): PublicCollection
    {
        if (empty($mode) === false)
        {
            return $merchant->setConnection($mode)->getActivationStatusChangeLog();
        }

        return $merchant->getActivationStatusChangeLog();
    }

    /**
     * This function is used for updating key access of a merchant
     * @param Entity $merchant
     * @param array $input
     *
     * @return Entity
     */
    public function updateKeyAccess(Entity $merchant, array $input): Entity
    {
        $merchant->getValidator()->validateInput('keyAccess', $input);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_KEY_ACCESS,
            ['input'       => $input,
             'merchant_id' => $merchant->getId()
            ]);

        $oldMerchant = clone $merchant;

        $merchant->setHasKeyAccess($input[Entity::HAS_KEY_ACCESS]);

        $this->app['workflow']
            ->setEntity($merchant->getEntity())
            ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        return $merchant;
    }

    public function markGratisTransactionPostpaid(string $merchantId, int $from)
    {
        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        $transactions = $this->repo->transaction->fetchGratisTransactions($merchantId, $from);

        $transactionCore = (new Transaction\Core);

        foreach($transactions as $txn)
        {
            try
            {
                $transactionCore->markGratisTransactionPostpaid($txn, $merchant);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::GRATIS_TO_POSTPAID_FAILED,
                    ['transaction_id' => $txn->getId()]
                );
            }
        }
    }

    public function processMerchantAnalyticsQuery(string $merchantId, array $input): array
    {
        if (isset($input[Entity::FILTERS]) === false)
        {
            $input = $this->addDefaultAnalyticsFilter($merchantId, $input);

            return $input;
        }

        //
        // Iterates through input filters and
        // - If one filter is empty, adds one sub filter with merchant id clause
        // - If there are sub filters, adds merchant id clause in each of them
        //
        $filters = & $input[Entity::FILTERS];

        foreach ($filters as $filterName => &$filter)
        {
            if ($this->isIndustryLevelQuery($filterName))
            {
                // Skips adding default merchant_id clause in industry level queries
                continue;
            }

            if (empty($filter) === true)
            {
                $filter[] = [Entity::KEY_MERCHANT_ID => $merchantId];
            }
            else
            {
                foreach ($filter as & $subFilter)
                {
                    $subFilter[Entity::KEY_MERCHANT_ID] = $merchantId;
                }
            }
        }

        // This is a temporary solution to hide junk data from X Demo accounts.
        if ((is_null($this->merchant) === false) and $this->merchant->isXDemoAccount())
        {
            foreach ($filters as & $filter)
            {
                foreach ($filter as & $subFilter)
                {
                    $paramType = '';

                    if (isset($subFilter[Entity::CREATED_AT]))
                    {
                        $paramType = Entity::CREATED_AT;
                    }
                    elseif (isset($subFilter[Entity::POSTED_AT]))
                    {
                        $paramType = Entity::POSTED_AT;
                    }
                    elseif (isset($subFilter[Entity::REVERSED_AT]))
                    {
                        $paramType = Entity::REVERSED_AT;
                    }
                    else
                    {
                        continue;
                    }

                    $lowerBoundType = '';

                    if (isset($subFilter[$paramType][Entity::GT]))
                    {
                        $lowerBoundType = Entity::GT;
                    }
                    elseif (isset($subFilter[$paramType][Entity::GTE]))
                    {
                        $lowerBoundType = Entity::GTE;
                    }
                    else
                    {
                        // Force creation of gte param. This ensures all requests have lower bound timeframe
                        $lowerBoundType = 'gte';

                        $subFilter[$paramType][$lowerBoundType] = 0;
                    }

                    $prevDt = $subFilter[$paramType][$lowerBoundType];

                    $maxFrom = max($prevDt, Carbon::now(Timezone::IST)->timestamp - BankingDemo::MAX_TIME_DURATION);

                    $subFilter[$paramType][$lowerBoundType] = (string)$maxFrom;

                }

            }
        }

        return $input;
    }

    protected function addDefaultAnalyticsFilter(string $merchantId, array $input = []): array
    {
        $defaultFilter[] = [Entity::KEY_MERCHANT_ID => $merchantId];

        $input[Entity::FILTERS] = [Entity::DEFAULT_FILTER => $defaultFilter];

        return $input;
    }

    /**
     * If a merchant user has a role as owner and has confirm_token set to null
     * then the user will be considered as a confirmed owner.
     *
     * @param $merchant
     * @return mixed
     */
    public function getMerchantConfirmedOwner(Merchant\Entity $merchant)
    {
        return $merchant->users()->where(Merchant\Detail\Entity::ROLE, '=', User\Role::OWNER)
                        ->whereNull(User\Entity::CONFIRM_TOKEN)
                        ->first();
    }

    /**
     * Pushes MerchantSync job onto queue for given event with given payload.
     *
     * Events e.g. Group got edited/deleted and we need to handle the hierarchy
     * updates in Es docs.
     *
     * This method is here at once place and will be called from few other places
     * where merchant's es doc is getting affected
     *
     * @param string $event
     * @param array  $payload
     */
    public function syncEventToEs(string $event, array $payload)
    {
        MerchantSync::dispatch($this->mode, $event, $payload)->delay(Repository::ES_JOB_DELAY);
    }

    public function createBatches(Entity $merchant, array $input): array
    {
        $merchant->getValidator()->validateInput('create_batch', $input);

        $type = $input['type'];

        $input = $input['data'];

        $merchant->getValidator()->validateInput($type, $input);

        $batches = $this->repo->transaction(function() use ($input, $type, $merchant)
        {
            $batches = [];

            foreach ($input as $key => $file)
            {
                $batchType =  $type . '_' . $key;

                $params = [
                    Batch\Entity::FILE        => $file,
                    Batch\Entity::TYPE        => $batchType
                ];

                $batch = (new Batch\Core)->create($params, $merchant);

                $batches[$batchType] = $batch->getId();
            }

            return $batches;
        });

        $class = 'RZP\\Jobs\\' . studly_case($type) . 'Batch';

        $class::dispatch($this->mode, $batches);

        return $batches;
    }

    public function createNewUserAndTransferOwnerShip($input, $merchant, $currentOwnerUser)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($input, $merchant, $currentOwnerUser)
        {
            (new User\Validator())->validatePasswordResetToken($currentOwnerUser, $input['token']);

            // invalidate Token
            (new User\Service())->setAndSaveResetPasswordToken($currentOwnerUser, null);

            $this->createNewUserForMerchantEmailUpdate($input, $currentOwnerUser);

            // set contact mobile verified field same as current owner
            $newOwnerUser = $this->repo->user->getUserFromEmailOrFail($input['email']);
            $newOwnerUser->setContactMobileVerified($currentOwnerUser->isContactMobileVerified());
            $this->repo->saveOrFail($newOwnerUser);

            (new User\Service())->confirm($newOwnerUser->getId());

            $this->editMerchantEmailAndTransferOwnershipToUser($newOwnerUser, $currentOwnerUser, $merchant, $input);
        });
    }

    protected function createNewUserForMerchantEmailUpdate($input, $currentOwnerUser)
    {
        $input = array_only($input, [
            User\Entity::PASSWORD,
            User\Entity::PASSWORD_CONFIRMATION,
            User\Entity::EMAIL]);

        $input[User\Entity::CONTACT_MOBILE] = $currentOwnerUser->getContactMobile();

        $input[User\Entity::NAME] = $currentOwnerUser->getName();

        $input[User\Entity::CAPTCHA_DISABLE] = User\Validator::DISABLE_CAPTCHA_SECRET;

        (new User\Service())->create($input);
    }

    public function editMerchantEmailAndTransferOwnershipToUser($user, $currentOwner, $merchant, $input)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($user, $merchant, $input, $currentOwner)
        {
            $updateContactEmail   = (bool) ($input[Constants::SET_CONTACT_EMAIL] ?? false);

            $reAttachCurrentOwner = (bool) ($input[Constants::REATTACH_CURRENT_OWNER] ?? true);

            $this->transferOwnerShipToUserForEmailUpdate($merchant, $user, $currentOwner, $reAttachCurrentOwner);

            if ($updateContactEmail === true)
            {
                $this->editEmail($merchant, [
                    'email' => $input['email']
                ], 'editEmailNonUnique');

                $merchantDetail = $merchant->merchantDetail;

                $merchantDetail->setContactEmail($input['email']);

                $this->repo->saveOrFail($merchantDetail);
            }

            [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Login Details Updated';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );

            $this->trace->info(TraceCode::OWNERSHIP_TRANSFER_FOR_EMAIL_UPDATE, [
                'new_owner_id'          => $user->getId(),
                'new_owner_email'       => $user->getEmail(),
                'old_owner_id'          => $currentOwner->getId(),
                'old_owner_email'       => $currentOwner->getEmail(),
                'contact_email_updated' => $updateContactEmail,
                'old_owner_reattached'  => $reAttachCurrentOwner,
            ]);

            $orgId = $this->app['basicauth']->getOrgId();

            //get Org and send it to mailer, deal with other orgs as well.
            $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

            $emailChangedMail = new MerchantMail\OwnerEmailChange($currentOwner->toArrayPublic(),
                                                                  $org,
                                                                  $input['email'],
                                                                  $merchant->getId(),
                                                                  false
            );

            Mail::queue($emailChangedMail);
        });
    }

    protected function transferOwnerShipToUserForEmailUpdate($merchant, $user, $currentOwner, $reAttachCurrentOwner)
    {
        // transfer ownership for PG
        $this->transferOwnerShipToUser($merchant, $user, $currentOwner, Product::PRIMARY, $reAttachCurrentOwner);

        $isOwnerForBanking = (new User\Service())->doesUserHaveRoleForMerchantAndProduct(
            $currentOwner->getId(),
            $merchant->getId(),
            Role::OWNER,
            Product::BANKING);

        // if current owner is owner on X : transfer ownership for X to user
        if ($isOwnerForBanking === true)
        {
            $this->transferOwnerShipToUser($merchant, $user, $currentOwner, Product::BANKING, $reAttachCurrentOwner);
        }

        $properties = [
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.submerchant_ownership_transfer_experiment_id'),
        ];

        $isExpEnabled = $this->isSplitzExperimentEnable($properties, 'enable');

        if($isExpEnabled === true)
        {
            $this->detachAndAttachSubmerchantsOwnersForEmailUpdate($merchant, $currentOwner, $user);
        }
    }

    protected function transferOwnerShipToUser($merchant, $user, $currentOwner, $product, $reAttachCurrentOwner)
    {
        // detach current owner
        $this->detachUserForMerchant($merchant->getId(), $currentOwner, $product);

        // if user is in team member : detach before attaching as owner
        if ($this->hasUserAnyRoleForMerchantAndProduct($user->getEmail(), $merchant, $product) === true)
        {
            $this->detachUserForMerchant($merchant->getId(), $user, $product);
        }

        // attach user as owner.
        $this->attachUserForMerchant($merchant->getId(), $user, Role::OWNER, $product);

        // if current user need to be in team
        if ($reAttachCurrentOwner === true)
        {
            // Assign Manager role to the current owner on PG.
            // Assign Finance L1 role to the current owner on X.
            $currentOwnerNewRole = $product === Product::PRIMARY ? Role::MANAGER : BankingRole::FINANCE_L1;

            $this->attachUserForMerchant($merchant->getId(), $currentOwner, $currentOwnerNewRole, $product);
        }
    }

    protected function attachUserForMerchant($merchantId, $user, $role, $product)
    {
        $userMerchantMappingInputData = [
            Entity::ACTION      => 'attach',
            Entity::ROLE        => $role,
            Entity::MERCHANT_ID => $merchantId,
            Entity::PRODUCT     => $product,
        ];

        (new User\Core)->updateUserMerchantMapping($user, $userMerchantMappingInputData);
    }

    protected function detachUserForMerchant($merchantId, $user, $product)
    {
        // Detach the existing merchant User.
        $userMerchantMappingData = [
            Entity::ACTION       => 'detach',
            Entity::MERCHANT_ID => $merchantId,
            Entity::PRODUCT     => $product,
        ];

        (new User\Core())->updateUserMerchantMapping($user, $userMerchantMappingData);
    }

    public function getUserStatusForEmailUpdateSelfServe($userEmail, $merchant, $product)
    {
        return [
            Constants::IS_USER_EXIST    => $this->isUserExistForEmail($userEmail),
            Constants::IS_TEAM_MEMBER   => $this->hasUserAnyRoleForMerchantAndProduct($userEmail, $merchant, $product),
            Constants::IS_OWNER         => $this->isOwnerRoleExistForEmailUserAndProduct($userEmail, $product),
        ];
    }

    protected function isUserExistForEmail($userEmail)
    {
        $existingUser = $this->repo->user->getUserFromEmail($userEmail);

        if (empty($existingUser) === true)
        {
            return false;
        }

        return true;
    }

    protected function isOwnerRoleExistForEmailUserAndProduct($userEmail, $product)
    {
        $existingUser = $this->repo->user->getUserFromEmail($userEmail);

        if (empty($existingUser) === true)
        {
            return false;
        }

        return  $this->repo->merchant_user->isOwnerRoleExistForUserIdAndProduct($existingUser->getId(), $product);;
    }

    /**
     *  checks if email user is having any role in merchant team for given product
     * @param $userEmail
     * @param $merchant
     * @param $product
     * @return bool
     */
    protected function hasUserAnyRoleForMerchantAndProduct($userEmail, $merchant, $product)
    {
        $teamUser = $merchant->users()
                             ->where(Entity::EMAIL, $userEmail)
                             ->where(Entity::PRODUCT, $product)
                             ->first();

        if (empty($teamUser) === true)
        {
            return false;
        }

        return true;
    }

    public function sendMailForEditMerchantEmailSelfServe($user, $email)
    {
        $orgId = $this->app['basicauth']->getOrgId();

        $merchantId = $this->app['basicauth']->getMerchantId();

        //get Org and send it to mailer, deal with other orgs as well.
        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->app['basicauth']->getOrgHostName();

        $this->sendOwnerEmailChangeRequestMailForEmailUpdateSelfServe($user, $org, $email, $merchantId);

        $this->sendPasswordResetMailForEmailUpdateSelfServe($user, $org, $email, $merchantId);
    }

    protected function sendOwnerEmailChangeRequestMailForEmailUpdateSelfServe($user, $org, $email, $merchantId)
    {
        $emailChangeRequestMail = new MerchantMail\OwnerEmailChange($user, $org, $email, $merchantId);

        Mail::queue($emailChangeRequestMail);
    }

    protected function sendPasswordResetMailForEmailUpdateSelfServe($user, $org, $email, $merchantId)
    {
        $passwordAndEmailResetMail = new UserMail\PasswordAndEmailReset($user->toArrayPublic(), $org, $email, $merchantId);

        Mail::queue($passwordAndEmailResetMail);
    }


    public function sendPayoutMail(Entity $merchant, int $from, int $to, string $email)
    {
        $payouts = $this->repo->payout->fetchPayoutsWithUtrNotNull($from, $to, $merchant->getId());

        $recipients = $merchant->getTransactionReportEmail();

        $merchantId = $merchant->getId();

        if (empty($email) === false)
        {
            array_push($recipients, $email);
        }

        $processed = false;

        foreach ($payouts as $payout)
        {
            $body = 'Settlement Processed<br />';
            $body = $body . 'Total Amount : Rs.' . number_format($payout->getAmount() / 100, 2, '.', '') . '<br />';

            if (empty($payout->getUtr()) === false)
            {
                $body = $body . 'UTR : ' . $payout->getUtr() . '<br />';
            }

            $payoutBankAccount = $payout->destination;

            if (empty($payoutBankAccount) === false)
            {
                $body = $body . '<br />' . $payoutBankAccount->getBeneficiaryName() . '<br />';
                $body = $body . 'Bank Account Number : ' . $payoutBankAccount->getAccountNumber() . '<br />';
                $body = $body . $payoutBankAccount->source->merchantDetail->getBusinessRegisteredAddress() . '<br />';
            }

            $body = $body . '<br />'
                    . 'Razorpay Software Pvt Ltd' . '<br />'
                    . 'Bank Account Number : 7911547334' . '<br />'
                    . 'Kotak Mahindra Bank 5 C/ II, <br />'
                    . 'MITTAL COURT,224, NARIMAN POINT,MUMBAI - 400 021, <br/>'
                    . 'GREATER BOMBAY,MAHARASHTRA <br /><br />';

            if (array_key_exists($merchantId, self::MASTER_ID_MAPPING) === true)
            {
                $body = $body . 'Master ID :' . self::MASTER_ID_MAPPING[$merchantId] . '<br />';
            }

            $date= Carbon::createFromTimestamp($payout->getCreatedAt(), Timezone::IST)->format('d-m-Y');

            $body = $body . 'Date Of Deposit : ' . $date . '<br />';

            $body = $body . 'Date Of Credit : ' . $date . '<br />';

            $mailData = ['body' => $body];

            $payoutMail = new PayoutMail(
                $mailData,
                $recipients);

            Mail::queue($payoutMail);

            $processed = true;
        }

        return $processed;
    }

    /**
     * This handles 3 possible cases when changing user email.
     * 1. There exists a team member with the new email
     *    Here, we swap the roles of the team member(manager) with new email and the original owner
     * 2. There exists a user(not team member) with the new email
     *    Here, we change the original owner to manager and then add the user with new email as owner
     * 3. The new email is unique so far
     *    Here, we just change the email of the original user(owner).
     *
     * @param Entity $merchant
     * @param string $originalEmail
     * @param string $newEmail
     * @param string $product
     *
     * @return bool
     */
    public function changeMerchantUsersEmail(Entity $merchant, string $originalEmail, string $newEmail, string $product)
    {
        $merchantUsersCount = $merchant->users()->where(Entity::PRODUCT, $product)->count();

        if ($merchantUsersCount === 0)
        {
            return false;
        }

        $teamUser = $merchant->users()
                             ->where(Entity::EMAIL, $newEmail)
                             ->where(Entity::PRODUCT, $product)
                             ->first();

        $existingUser = $this->repo->user->getUserFromEmail($newEmail);

        $selfUser = $this->repo->user->getUserFromEmail($originalEmail);

        $oldOwner = $merchant->primaryOwner($product);

        $traceData = [
            'team_user' => empty($teamUser) ? null : $teamUser->getEmail(),
            'existing_user' => empty($existingUser) ? null : $existingUser->getEmail(),
            'self_user' => empty($selfUser) ? null : $selfUser->getEmail(),
            'old_owner' => empty($oldOwner) ? null : $oldOwner->getEmail(),
        ];

        $this->trace->info(TraceCode::MERCHANT_USER_EMAIL_CHANGE, $traceData);

        if ((empty($oldOwner) === false) and ((empty($teamUser) === false) or (empty($existingUser) === false)))
        {
            // Assign Manager role to the old owner on PG.
            // Assign Finance L1 role to the old owner on X.
            $oldOwnerNewRole = $product === Product::PRIMARY ? Role::MANAGER : BankingRole::FINANCE_L1;

            (new User\Core)->detachAndAttachMerchantUser($oldOwner, $merchant->getId(), $oldOwnerNewRole, $product);
        }

        if (empty($teamUser) === false)
        {
            // Assign Owner role to the team user.
            (new User\Core)->detachAndAttachMerchantUser($teamUser, $merchant->getId(), ROLE::OWNER, $product);

            $this->detachAndAttachSubmerchantOwners($merchant, $oldOwner->getId(), $teamUser->getId(), $product);
        }
        elseif (empty($existingUser) === false)
        {
            // Assign owner to existing user.
            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => ROLE::OWNER,
                'merchant_id' => $merchant->getId(),
                'product'     => $product,
            ];

            (new User\Core)->updateUserMerchantMapping($existingUser, $userMerchantMappingInputData);

            $this->detachAndAttachSubmerchantOwners($merchant, $oldOwner->getId(), $existingUser->getId(), $product);
        }
        elseif (empty($selfUser) === false)
        {
            $userData = [
                'email' => $newEmail,
            ];

            (new User\Core)->edit($selfUser, $userData, 'edit_email_for_merchant');
        }
    }

    public function enableEmiMerchantSubvention(Entity $merchant, Emi\Entity $emiPlan, array $input)
    {
        $emiMerchantSub = (new EmiPlans\Entity)->build($input);

        $emiMerchantSub->merchant()->associate($merchant);

        if ($emiPlan->isExternal())
        {
            $emiMerchantSub[EmiPlans\Entity::EMI_PLAN_ID] = $emiPlan[Emi\Entity::ID];
        }
        else
        {
            $emiMerchantSub->emiPlan()->associate($emiPlan);
        }

        $emiMerchantSub->generateId();

        $this->repo->saveOrFail($emiMerchantSub);

        return $emiMerchantSub->toArray();
    }

    public function getEmailsOfOwnersAndAdmins(Entity $merchant)
    {
        $emails = $merchant->users()
                           ->whereIn(User\Entity::ROLE, [User\Role::ADMIN, User\Role::OWNER])
                           ->pluck(User\Entity::EMAIL)
                           ->all();

        return $emails;
    }

    /**
     * Saves data from partner activation/deactivation requests into the settings table.
     *
     * @param Request\Entity $request
     * @param array          $submissions
     */
    public function postPartnerSubmissions(MerchantRequest\Entity $request, array $submissions)
    {
        $partnerType = $submissions[Entity::PARTNER_TYPE];

        $data[Entity::PARTNER_TYPE] = $partnerType;

        $this->trace->info(
            TraceCode::PARTNER_REQUEST_SUBMITTED,
            [
                Entity::PARTNER_TYPE       => $partnerType,
                MerchantRequest\Entity::ID => $request->getId(),
            ]);

        $dimensions = [Entity::PARTNER_TYPE => $partnerType];

        $this->trace->count(Metric::PARTNER_MARK_REQUEST, $dimensions);

        Accessor::for ($request, Constants::PARTNER)
                ->upsert($data)
                ->save();
    }

    /**
     * @param Request\Entity $merchantRequest
     *
     * @return array
     */
    public function getPartnerSubmissions(MerchantRequest\Entity $merchantRequest): array
    {
        $settings = Accessor::for($merchantRequest, Constants::PARTNER)->all();

        $response = $settings->toArray();

        return $response;
    }

    /**
     * Returns an array of the partner's application ids.
     *
     * @param Entity $merchant
     *
     * @return array
     * @throws BadRequestException
     */
    public function getPartnerApplicationIds(Entity $merchant, array $types = []): array
    {
        (new Validator)->validateIsPartner($merchant);

        return (new MerchantApplications\Core)->getMerchantAppIds($merchant->getId(), $types);
    }

    /**
     * @param Entity $merchant
     * @param string $partnerType
     *
     * @return Entity
     */
    public function markAsPartner(Entity $merchant, string $partnerType): Entity
    {
        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Constants::MARK_AS_PARTNER_IN_PROGRESS.$merchant->getId();

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchant, $partnerType)
            {
                return $this->processMarkAsPartner($merchant, $partnerType);
            },
            Constants::MARK_AS_PARTNER_LOCK_TIME_OUT,
            ErrorCode::BAD_REQUEST_MARK_AS_PARTNER_ALREADY_IN_PROGRESS);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws Throwable
     * @throws BadRequestException
     */
    protected function processMarkAsCaOnboardingPartner(Entity $merchant, string $partnerType): Entity
    {
        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Constants::MARK_AS_PARTNER_IN_PROGRESS . $merchant->getId();

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchant, $partnerType) {
                $validator = new Validator;

                $validator->validateIfAlreadyPartner($merchant);

                $validator->validateIsNotLinkedAccount($merchant);

                $this->repo->transactionOnLiveAndTest(function() use ($merchant, $partnerType) {
                    $merchant->setPartnerType($partnerType);

                    $this->repo->saveOrFail($merchant);

                    $app = $this->createPartnerApp($merchant);

                    $applicationType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($merchant);

                    $this->createMerchantApplication($merchant, $app[OAuthApp\Entity::ID], $applicationType);
                });

                return $merchant;
            },
            Constants::MARK_AS_PARTNER_LOCK_TIME_OUT,
            ErrorCode::BAD_REQUEST_MARK_AS_PARTNER_ALREADY_IN_PROGRESS);
    }

    protected function processMarkAsPartner(Entity $merchant, string $partnerType): Entity {

        $validator = new Validator;

        $validator->validateIfAlreadyPartner($merchant);

        $validator->validateIsNotLinkedAccount($merchant);

        $validator->validatePartnerType($partnerType);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $partnerType)
        {
            $merchant->setPartnerType($partnerType);

            $this->repo->saveOrFail($merchant);


            Tracer::inspan(['name' => HyperTrace::CREATE_DEFAULT_FEATURE_FOR_PARTNER], function () use ($merchant) {
                $this->setDefaultFeatureForPartner($merchant);
            });

            Tracer::inspan(['name' => HyperTrace::CREATE_PARTNER_ACTIVATION], function () use ($merchant) {
                (new Activation\Core())->createOrFetchPartnerActivationForMerchant($merchant, false);
            });

            $app = $this->createPartnerApp($merchant);
            Tracer::inspan (['name' => HyperTrace::PROCESS_MARK_AS_PARTNER], function () use ($merchant, $app) {
                if ($merchant->isPurePlatformPartner() === false)
                {
                    // create new partner config for aggregator/reseller partners
                    if ($merchant->isFullyManagedPartner() === false)
                    {
                        $application = (new OAuthApp\Repository())->findOrFail($app[OAuthApp\Entity::ID]);

                        $this->createPartnerConfig($application, $merchant);
                    }

                    $applicationType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($merchant);

                    $this->createMerchantApplication($merchant, $app[OAuthApp\Entity::ID], $applicationType);

                    if (($merchant->isAggregatorPartner() === true) or ($merchant->isFullyManagedPartner() === true))
                    {
                        // create referred app and partner config for aggregator/fully_managed partners to give them reseller functionality
                        $this->createReferredAppAndPartnerConfigForManaged($merchant);
                    }

                    $partnerType = $merchant->getPartnerType();

                    if(in_array($partnerType, Constants::$referralPartnerTypes) === true)
                    {
                        (new Referral\Core)->createOrFetch($merchant);
                    }

                    $dimensionsForMerchantApplication = [
                        Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                        'application_type'   => $applicationType
                    ];

                    $this->trace->count(Metric::PARTNER_MERCHANT_APPLICATION_CREATE_TOTAL, $dimensionsForMerchantApplication);

                    $dimensionsForDefaultPartnerConfig = [
                        Entity::PARTNER_TYPE => $merchant->getPartnerType(),
                    ];

                    $this->trace->count(Metric::PARTNER_CONFIG_CREATE_TOTAL, $dimensionsForDefaultPartnerConfig);
                }
            });
        });

        $dimensions = [Entity::PARTNER_TYPE => $merchant->getPartnerType()];

        $this->trace->count(Metric::PARTNER_MARKED_TOTAL, $dimensions);

        return $merchant;
    }

    /**
     * Creates a merchant_application for a given merchant and applicationId provided
     *
     * @param Entity $merchant
     * @param string $applicationId
     * @param string $applicationType
     * @return MerchantApplications\Entity
     */
    public function createMerchantApplication(Entity $merchant, string $applicationId, string $applicationType)
    {
        $appConfig = [
            MerchantApplications\Entity::TYPE => $applicationType,
            MerchantApplications\Entity::APPLICATION_ID => $applicationId,
        ];

        return (new MerchantApplications\Core)->create($merchant, $appConfig);
    }

    public function createReferredAppAndPartnerConfigForManaged(Entity $merchant)
    {
        $appInput = [
            OAuthApp\Entity::NAME => Entity::REFERRED_APPLICATION,
        ];

        $app = $this->createPartnerApp($merchant, $appInput);

        $this->createMerchantApplication($merchant, $app[OAuthApp\Entity::ID], MerchantApplications\Entity::REFERRED);

        $referredApp = (new OAuthApp\Repository)->findOrFail($app[OAuthApp\Entity::ID]);

        // create new partner config for referred app for aggregator/fully_managed partners
        $this->createPartnerConfig($referredApp, $merchant);

        $dimensionsForMerchantApplication = [
            Entity::PARTNER_TYPE => $merchant->getPartnerType(),
            'application_type'   => MerchantApplications\Entity::REFERRED
        ];

        $this->trace->count(Metric::PARTNER_MERCHANT_APPLICATION_CREATE_TOTAL, $dimensionsForMerchantApplication);

        $dimensionsForDefaultPartnerConfig = [
            Entity::PARTNER_TYPE => $merchant->getPartnerType(),
        ];

        $this->trace->count(Metric::PARTNER_CONFIG_CREATE_TOTAL, $dimensionsForDefaultPartnerConfig);
    }

    protected function setDefaultFeatureForPartner(Entity $partner)
    {
        // add feature flags if commissions are not yet created or if commission balance is zero
        $commissionBalance = $partner->commissionBalance;

        if (($commissionBalance !== null) and ($commissionBalance->getBalance() > 0))
        {
            return;
        }

        $featureCore = new Feature\Core;

        $features = [
            Feature\Constants::GENERATE_PARTNER_INVOICE,
        ];

        foreach ($features as $feature)
        {
            $featureCore->create(
                [
                    Feature\Entity::ENTITY_TYPE     => E::MERCHANT,
                    Feature\Entity::ENTITY_ID       => $partner->getId(),
                    Feature\Entity::NAME            => $feature,
                ], $shouldSync = true
            );
        }
    }

    /**
     * Sets the Partner type attribute as null
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function unmarkAsPartner(Entity $merchant): Entity
    {
        (new Validator)->validateIsPartner($merchant);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $this->deleteSupportingEntities($merchant);

            // removes partner application and associated merchant access map entries for non-platform partners
            $this->deletePartnerApp($merchant);

            $merchant->setPartnerType();

            $this->repo->saveOrFail($merchant);
        });


        return $merchant;
    }

    /**
     * Updates partner type on merchant's request
     *
     * @param Entity $merchant
     * @param String $partnerType
     *
     * @return array
     * @throws Throwable
     */
    public function updatePartnerType(Entity $merchant, string $partnerType): array
    {

        $partner = Tracer::inspan(['name' => HyperTrace::MARK_AS_PARTNER,
            'attributes' => array ( 'partnerType'=> $partnerType, 'merchantId'=> $this->merchant->getId())], function () use ($merchant, $partnerType) {

            $partner = $this->repo->transactionOnLiveAndTest(function () use ($merchant, $partnerType)
            {
                return $this->markAsPartner($merchant, $partnerType);
            });

            return $partner;
        });

        $eventData = [
            'status'        => 'success',
            'partner_id'    => $partner->getId(),
            'partner_type'  => $partnerType,
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_PARTNER_SIGNUP, $partner, null, $eventData);

        $hasCommissionConfigs = ($partnerType === Constants::PURE_PLATFORM) ? false : true;

        $this->sendPartnerOnBoardedEmail($partner);

        $this->sendPartnerInfoToSalesforce($partner);

        $this->trace->info(
            TraceCode::PARTNER_CREATION_SUCCESSFUL,
            [
                Entity::PARTNER_ID    => $partner->getId(),
                Entity::PARTNER_TYPE  => $partnerType,
            ]
        );

        return [
            'partner_type'              => $partnerType,
            'has_commission_configs'    => $hasCommissionConfigs,
        ];
    }

    public function updatePartnerTypeToBankCaOnboarding(Entity $merchant, string $partnerType): array
    {
        $partner = Tracer::inspan(['name'       => HyperTrace::MARK_AS_PARTNER,
                                   'attributes' => array('partnerType' => $partnerType, 'merchantId' => $merchant->getId())],
            function() use ($merchant, $partnerType) {
                $partner = $this->repo->transactionOnLiveAndTest(function() use ($merchant, $partnerType) {
                    return $this->processMarkAsCaOnboardingPartner($merchant, $partnerType);
                });

                return $partner;
            });

        $this->trace->info(TraceCode::PARTNER_CREATION_SUCCESSFUL, [Entity::PARTNER_ID   => $partner->getId(), Entity::PARTNER_TYPE => $partnerType,]);

        return ['partner_type' => $partner->getPartnerType()];
    }

    public function createPartnerConfig(OAuthApp\Entity $application, Entity $partner, array $config = [])
    {
        $defaultConfig = $this->getPartnerDefaultConfig($partner);
        //If partner type is fully managed then commissions are disabled.
        if($partner->getPartnerType() === Constants::FULLY_MANAGED)
        {
            $defaultConfig [PartnerConfig\Entity::COMMISSIONS_ENABLED] = false;
        }

        $config = array_merge($defaultConfig, $config);

        (new PartnerConfig\Core)->create($application, $config);
    }

    public function backFillMerchantApplications(array $merchantIds = null, $limit = null, $afterId = null)
    {

        while (true)
        {
            $partnerMerchants = $this->repo->merchant->fetchPartnerIdsInBatches($merchantIds, $limit, $afterId);

            if ($partnerMerchants->isEmpty() === true)
            {
                break;
            }

            $afterId = $partnerMerchants->last()->getId();

            $partnerMerchants = $partnerMerchants->toArray();

            $merchantIdsChunks = array_chunk($partnerMerchants, 500);

            foreach ($merchantIdsChunks as $merchantBatch)
            {
                BackFillMerchantApplications::dispatch($this->mode, $merchantBatch);
            }
        }

        return [];
    }

    public function backFillReferredApplication(array $merchantIds = null, $limit = null, $afterId = null)
    {
        while (true)
        {
            $aggregatorAndFullyManagedPartners = $this->repo->merchant->fetchAggregatorAndFullManagedPartners($merchantIds, $limit, $afterId);

            if ($aggregatorAndFullyManagedPartners->isEmpty() === true)
            {
                break;
            }

            $afterId = $aggregatorAndFullyManagedPartners->last()->getId();

            $aggregatorAndFullyManagedPartners = $aggregatorAndFullyManagedPartners->getIds();

            $merchantIdsChunks = array_chunk($aggregatorAndFullyManagedPartners, 500);

            foreach ($merchantIdsChunks as $merchantBatch)
            {
                BackFillReferredApplication::dispatch($this->mode, $merchantBatch);
            }
        }
    }

    protected function sendPartnerOnBoardedEmail(Entity $partner)
    {
        $data = [
            'name'         => $partner->getName(),
            'email'        => $partner->getEmail(),
            'partner_type' => $partner->getPartnerType(),
            'country_code' => $partner->getCountry(),
        ];

        $email = new PartnerOnBoarded($data);

        Mail::queue($email);
    }

    protected function sendPartnerInfoToSalesForce(Entity $partner)
    {
        $this->app->salesforce->sendPartnerInfo($partner);
    }

    protected function getPartnerDefaultConfig(Entity $partner): array
    {
        $env = ($this->app->isProduction()) ? Environment::PRODUCTION : Environment::DEV;
        $defaultPlanId = Pricing\DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS[
            $partner->getCountry()][$env][Pricing\DefaultPlan::SUBMERCHANT_PRICING_OF_ONBOARDED_PARTNERS_KEY];
        $implicitPlanId  = Pricing\DefaultPlan::DEFAULT_PARTNERS_PRICING_PLANS[
            $partner->getCountry()][$env][Pricing\DefaultPlan::PARTNER_COMMISSION_PLAN_ID_KEY];
        return [
            PartnerConfig\Entity::DEFAULT_PLAN_ID       => $defaultPlanId,
            PartnerConfig\Entity::IMPLICIT_PLAN_ID      => $implicitPlanId,
            PartnerConfig\Entity::COMMISSIONS_ENABLED   => true,
            PartnerConfig\Constants::PARTNER_ID         => $partner->getId(),
        ];
    }

    /**
     * This function also adds ref-tag and creates user-merchant mapping in addition to the
     * access map. The aggregator user is mapped to submerchant as an owner in cases of
     * fully managed and aggregator type partners. The aggregator type will not get mapped
     * in the future, it is only kept for backward compatibility.
     *
     * @param Entity      $partner
     * @param Entity      $submerchant
     *
     * @param null        $appType
     * @param string|null $role
     *
     * @return array
     * @throws BadRequestException
     * @throws Throwable
     */
    public function createPartnerSubmerchantAccessMap(Entity $partner, Entity $submerchant, $appType = null, string $role = null): array
    {
        $merchantValidator = new Validator;

        $merchantValidator->validateIsNotLinkedAccount($submerchant);
        $merchantValidator->validatePartnerIsNotSubmerchant($partner, $submerchant);

        if ($appType === null)
        {
            $appType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($partner);
        }

        $isMapped = (new AccessMap\Core())->isMerchantMappedToPartnerWithAppType($partner, $submerchant, $appType);

        if ($isMapped === true)
        {
            // since a submerchant can have multiple tokens for a partner, auth-service can call this function
            // multiple times and because of this exception is not thrown here in order to handle multiple calls
            // even though access map already exists between the partner and submerchant.

            $this->trace->info(
                TraceCode::PARTNER_MERCHANT_MAPPING_ALREADY_EXISTS,
                [
                    Entity::PARTNER_ID  => $partner->getId(),
                    Entity::MERCHANT_ID => $submerchant->getId(),
                    Constants::APP_TYPE => $appType,
                ]
            );
        }
        else
        {
            $this->trace->info(
                TraceCode::PARTNER_CREATE_ACCESS_MAP_REQUEST,
                [
                    Entity::PARTNER_ID  => $partner->getId(),
                    Entity::MERCHANT_ID => $submerchant->getId(),
                    Constants::APP_TYPE => $appType,
                ]);
        }

        $accessMap = $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant, $appType, $role) {

            $this->removeFromAggSettlementIfApplicable($partner, $submerchant);

            $partnerApp = $this->fetchPartnerApplication($partner, $appType);

            $config = (new PartnerConfig\Core)->fetch($partnerApp);

            if (($config !== null) and
                ($config->getDefaultPlanId() !== null) and
                ($config->getDefaultPlanId() !== $submerchant->getPricingPlanId()))
            {
                // TODO: currently just logging, will have to send mail later to ops team
                $this->trace->info(
                    TraceCode::SUBMERCHANT_PLAN_DEFAULT_PLAN_NOT_EQUAL,
                    [
                        'partner_id'       => $partner->getId(),
                        'submerchant_id'   => $submerchant->getId(),
                        'submerchant_plan' => $submerchant->getPricingPlanId(),
                        'default_plan'     => $config->getDefaultPlanId(),
                    ]);
            }

            // Maintained for backward compatibility
            $this->addSubMerchantReferral($partner, $submerchant);

            $this->assignSubmerchantDashboardAccessIfApplicable($partner, $submerchant, $appType, $role);

            // If the mapping already exists, the existing entity is returned
            $accessMap = (new AccessMap\Core)->addMappingForOAuthApp(
                $partner,
                $submerchant,
                [
                    AccessMap\Entity::APPLICATION_ID => $partnerApp->getId(),
                ]);

            return $accessMap;
        });

        $this->sendAccountMappedToPartnerWebhook($submerchant);

        return $accessMap->toArrayPublic();
    }

    /**
     * We are checking if there is an existing partner for the submerchant in merchant_access_map
     * If so, then we are removing the submerchant from the aggregate settlement.
     */
    public function removeFromAggSettlementIfApplicable(Entity $partner, Entity $submerchant)
    {
        if($this->isExpEnableForSendingUnlinkingRequestToNSS($partner) === false)
        {
            // Using the same experiment here that was used in unlinking funtionality.
            return ;
        }

        $merchantAccessMapList = $this->repo
            ->merchant_access_map
            ->fetchAffiliatedPartnersForSubmerchant($submerchant->getId());

        if ($merchantAccessMapList->isEmpty() === true)
        {
            return ;
        }

        $nssFeature = $this->repo
            ->feature
            ->findByEntityTypeEntityIdAndName(Constants::MERCHANT, $submerchant->getId(), FeatureConstants::NEW_SETTLEMENT_SERVICE);

        $featureResult = ($nssFeature === null) ? false : true;

        if($featureResult === false)
        {
            $this->trace->info(TraceCode::SUBMERCHANT_NOT_ONBOARDED_ON_NSS,[
                'sub-merchant_id' => $submerchant->getId()
            ]);

            return ;
        }

        $dimensions = [
            'partner_id'     => $partner->getId(),
            'action'         => 'disable_settle_to',
            'reason_code'    => 'Removing subM from agg. settlement, if more than 1 partner are linked.'
        ];

        try
        {
            $req = [
                'merchant_id' => $submerchant->getId()
            ];

            $response = app('settlements_api')->merchantConfigGet($req, $this->mode);

            $this->trace->info(TraceCode::AGGREGATE_SETTLEMENT_SUBMERCHANT_LINKING_REQUEST,[
                'submerchant_id'    => $submerchant->getId(),
                'merchant_config'   => $response
            ]);

            if ($response['config']['types']['aggregate']['enable'] === true and
                $response['config']['types']['aggregate']['settle_to'] !== $partner->getId())
            {
                $response['config']['types']['aggregate']['enable'] = false;
                $response['config']['types']['default']['enable'] = true;
                $response['config']['types']['aggregate']['settle_to'] = '';

                unset($response['config']['active']);

                $request = array_merge($req, $response);

                $result = app('settlements_api')->migrateMerchantConfigUpdate($request, $this->mode);

                $this->trace->info(TraceCode::AGGREGATE_SETTLEMENT_LINK_REQUEST_CONFIG_UPDATE_SUCCESS,[
                    'submerchant_id'   => $submerchant->getId(),
                    'updated_config'   => $result
                ]);

                $this->trace->count(Metric::AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_SUCCESS_TOTAL, $dimensions);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::AGGREGATE_SETTLEMENT_LINK_REQUEST_CONFIG_UPDATE_FAILURE,[
                'sub_merchant_id' => $submerchant->getId(),
                'partner_id'      => $partner->getId()
            ]);

            $this->trace->count(Metric::AGG_SETTLEMENT_SUBM_MULTIPLE_PARTNER_LINK_REQUEST_FAILURE_TOTAL, $dimensions);

            throw (new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AGG_SUBMERCHANT_CONFIG_UPDATE_FAILED));
        }
    }

    /**
     * @throws Exception\LogicException
     * @throws BadRequestException|BadRequestValidationFailureException
     * @throws BadRequestValidationFailureException
     */
    public function attachSubMerchantToBankCaPartner(Entity $partner, Entity $submerchant): array
    {
        (new Validator())->validateBankCaPartnerType($partner->getPartnerType());

        $appType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($partner);

        $partnerApp = $this->fetchPartnerApplication($partner, $appType);

        $accessMap = (new AccessMap\Core)->addMappingForOAuthApp( $partner, $submerchant, [AccessMap\Entity::APPLICATION_ID => $partnerApp->getId()]);

        return $accessMap->toArrayPublic();
    }

    /**
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @throws Throwable
     */
    public function deletePartnerAccessMap(Entity $partner, Entity $submerchant)
    {
        $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant) {

            $isExpEnabled = $this->isExpEnableForSendingUnlinkingRequestToNSS($partner);

            if ($isExpEnabled === true)
            {
                $this->sendUnlinkRequestToNSS($partner, $submerchant);
            }

            $this->deletePartnerSubmerchantAccessMap($partner, $submerchant);

            $this->detachSubMerchantOwnerIfApplicable($partner, $submerchant);
        });
    }

    public function isExpEnableForSendingUnlinkingRequestToNSS(Entity $partner)
    {
        $properties = [
            'id'            => $partner->getId(),
            'experiment_id' => $this->app['config']->get('app.subm_unlinking_request_to_nss_exp_id'),
        ];

        return $this->isSplitzExperimentEnable($properties, 'enable');
    }

    public function sendUnlinkRequestToNSS(Entity $partner, Entity $submerchant)
    {
        $nssFeature = $this->repo
            ->feature
            ->findByEntityTypeEntityIdAndName(Constants::MERCHANT, $submerchant->getId(), FeatureConstants::NEW_SETTLEMENT_SERVICE);

        $featureResult = ($nssFeature === null) ? false : true;

        $this->trace->info(TraceCode::UNLINK_AGGREGATE_SETTLEMENT_REQUEST,[
            'submerchant_id'            => $submerchant->getId(),
            'subM_feature_enabled'      => $featureResult
        ]);

        if($featureResult === false)
        {
            return ;
        }

        $dimensions = [
            'partner_id'     => $partner->getId(),
        ];

        try
        {
            $req = [
                'merchant_id' => $submerchant->getId()
            ];

            $response =  app('settlements_api')->merchantConfigGet($req, $this->mode);

            if($response['config']['types']['aggregate']['enable'] === true and
               $response['config']['types']['aggregate']['settle_to'] === $partner->getId())
            {
                $response['config']['types']['aggregate']['enable'] = false;
                $response['config']['types']['default']['enable'] = true;
                $response['config']['types']['aggregate']['settle_to'] = '';

                unset($response['config']['active']);

                $request = array_merge($req, $response);

                $result = app('settlements_api')->migrateMerchantConfigUpdate($request, $this->mode);

                $this->trace->info(TraceCode::AGGREGATE_SETTLEMENT_UNLINKING_UPDATE_SUCCESS,[
                    'request' => $request,
                    'result'  => $result
                ]);

                $this->trace->count(Metric::AGGREGATE_SETTLEMENT_UNLINKING_REQUEST_SUCCESS, $dimensions);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::AGGREGATE_SETTLEMENT_UNLINK_REQUEST_UNSUCCESSFUL);

            $this->trace->count(Metric::AGGREGATE_SETTLEMENT_UNLINKING_REQUEST_FAILURE, $dimensions);

            throw (new Exception\BadRequestException(ErrorCode::BAD_REQUEST_SUBMERCHANT_UNLINKING_FAILED));
        }
    }

    /**
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @throws Throwable
     */
    public function deletePartnerSubmerchantAccessMap(Entity $partner, Entity $submerchant)
    {
        $this->trace->info(
            TraceCode::PARTNER_DELETE_ACCESS_MAP_REQUEST,
            [
                'partner_id'     => $partner->getId(),
                'submerchant_id' => $submerchant->getId(),
            ]);

        $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant)
        {
            $appIds = $this->getPartnerApplicationIds($partner);

            $accessMapCore = new AccessMap\Core;

            foreach ($appIds as $appId)
            {
                $accessMapCore->deleteMappingForOAuthApp($submerchant, $appId);
            }

            $this->removeSubMerchantReferralTag($submerchant, $partner->getId());
        });
    }


    /**
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @throws Throwable
     */
    protected function detachSubMerchantOwnerIfApplicable(Entity $partner, Entity $submerchant)
    {
        if ($partner->getPartnerType() === Constants::BANK_CA_ONBOARDING_PARTNER)
        {
            return;
        }

        // For some cases, we observed that partner was a submerchant of itself. In such cases,
        // we cant delete the user access entry, but we need to delete the access map entry. Hence bypassing this flow.
        // Thread: https://razorpay.slack.com/archives/C7WEGELHJ/p1674646080762319
        if($submerchant->getId() === $partner->getId())
        {
            return;
        }

        $this->repo->assertTransactionActive();

        $partnerUserId = $partner->primaryOwner()->getId();

        if (($partner->isFullyManagedPartner() === true) or
            ($partner->isAggregatorPartner() === true))
        {
            $this->repo->transactionOnLiveAndTest(function() use ($partnerUserId, $submerchant) {

                $this->detachSubMerchantOwner($partnerUserId, $submerchant);

                if ($submerchant->primaryOwner() === null)
                {
                    $this->trace->info(TraceCode::SUBMERCHANT_PRIMARY_OWNER_NOT_PRESENT,
                                       [
                                           'submerchant' => $submerchant,
                                       ]);

                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_OWNER_NOT_PRESENT_FOR_USER);
                }
            });
        }
    }

    /**
     * @param array $input
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @return array
     * @throws Throwable
     */
    public function updatePartnerAccessMap(array $input, Entity $partner, Entity $submerchant)
    {
        $fromAppType = $input['from_app_type'] ?? MerchantApplications\Entity::REFERRED;

        $toAppType = $input['to_app_type'] ?? MerchantApplications\Entity::MANAGED;

        $this->trace->info(
            TraceCode::PARTNER_UPDATE_ACCESS_MAP_REQUEST,
            [
                Entity::PARTNER_ID  => $partner->getId(),
                Entity::MERCHANT_ID => $submerchant->getId(),
                'from_app_type'     => $fromAppType,
                'to_app_type'       => $toAppType,
            ]);

        $accessMap = $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant, $fromAppType, $toAppType) {

            (new PartnerValidator())->validateIfAggregatorOrFullyManagedPartner($partner);

            (new PartnerValidator())->validateAppTypeChange($fromAppType, $toAppType);

            $isMapped = (new AccessMap\Core())->isMerchantMappedToPartnerWithAppType($partner, $submerchant, $fromAppType);

            if ($isMapped === false)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND,
                    [
                        Entity::PARTNER_ID  => $partner->getId(),
                        Entity::MERCHANT_ID => $submerchant->getId(),
                        Constants::APP_TYPE => $fromAppType,
                    ]
                );
            }

            $this->deletePartnerAccessMap($partner, $submerchant);

            return $this->createPartnerSubmerchantAccessMap($partner, $submerchant, $toAppType);
        });

        return $accessMap;
    }

    /**
     * Creates OAuth application via AuthService for merchant
     * @param Entity $merchant
     * @param array  $appInput
     */
    public function createPartnerApp(Entity $merchant, array $appInput = [])
    {
        if ($merchant->isPurePlatformPartner() === true)
        {
            // Don't create a dummy application for pure platforms
            return;
        }

        $name = $merchant->getName();

        // Default value is required because website is a required field to create oauth applications
        $website = $merchant->getWebsite() ?: $this->getDefaultPartnerWebsite($merchant);

        $defaultAppInput = [
            'name'     => $name,
            'website'  => $website,
        ];

        $logoUrl = $merchant->getLogoUrl();

        // Do not send the logo_url parameter if it is null. Auth service will reject it.
        if ($logoUrl !== null)
        {
            $defaultAppInput['logo_url'] = $logoUrl;
        }

        $appInput = array_merge($defaultAppInput, $appInput);

        $app = app('authservice')->createApplication($appInput, $merchant->getId(), OAuthApp\Type::PARTNER);

        return $app;
    }

    public function getDefaultPartnerWebsite(Entity $merchant){
        return Org::isOrgCurlec($merchant->getOrgId()) ?  "https://www.curlec.com" : "https://www.razorpay.com";
    }

    /**
     * @param Entity $merchant
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function deletePartnerApp(Entity $merchant)
    {
        if ($merchant->isPurePlatformPartner() === true)
        {
            //
            // A dummy internal application for pure platforms does not exist
            // un-marking a platform partner should not delete applications and access mappings
            // only explicit delete application deletes mapping for platform partners
            //
            return;
        }

        $appIds = $this->getPartnerApplicationIds($merchant);

        foreach ($appIds as $appId)
        {
            // deletes the application and access mapping
            app('authservice')->deleteApplication($appId, $merchant->getId());
        }
    }

    public function addSubMerchantReferral($aggregratorMerchant, $account)
    {
        $existingTags = $account->tagNames();

        $refTag = Constants::PARTNER_REFERRAL_TAG_PREFIX . $aggregratorMerchant->getId();

        if (in_array(strtolower($refTag), array_map('strtolower', $existingTags)) === true)
        {
            return;
        }

        $this->appendTag($account, $refTag);
    }

    public function addSubmerchantOnboardingV2Feature($submerchant)
    {
        $featureParams = [
            Feature\Entity::ENTITY_ID   => $submerchant->getId(),
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAME        => Feature\Constants::CREATE_SOURCE_V2,
        ];

        (new Feature\Core)->create($featureParams, true);
    }

    /**
     * The function was earlier used to just attach `owner` hence the name.
     * It now takes role as an optional input and hence user of any role can
     * be attached.
     *
     * @param string $ownerId
     * @param Entity $subMerchant
     * @param string|null $product
     * @param string|null $role
     */
    public function attachSubMerchantUser(string $ownerId, Entity $subMerchant, string $product = null, string $role = null)
    {
        $userMerchantMappingInputData = [
            'action'      => 'attach',
            'role'        => $role ?? $subMerchant->getUserOwnerRole(),
            'merchant_id' => $subMerchant->getId(),
            'product'     => $product,
        ];

        (new User\Service)->updateUserMerchantMapping($ownerId, $userMerchantMappingInputData);
    }

    /**
     * @param string $partnerUserId
     * @param Entity $subMerchant
     */
    public function detachSubMerchantOwner(string $partnerUserId, Entity $subMerchant)
    {
        $userMerchantMappingInputData = [
            'action'      => 'detach',
            'role'        => $subMerchant->getUserOwnerRole(),
            'merchant_id' => $subMerchant->getId(),
        ];

        (new User\Service)->updateUserMerchantMapping($partnerUserId, $userMerchantMappingInputData);
    }

    /**
     * used for deleting a single tag of a merchant
     * @param string $id
     * @param string $tagName tag which has to be deleted
     */
    public function deleteTag($id, $tagName)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant->untag($tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        return $merchant->tagNames();
    }

    /**
     * used for adding tags to merchant
     * This function uses retag(), which overwrites all previous tags
     * with the ones passed in the $input array
     *
     * @param string $id
     * @param array  $input which contains the tags of the merchant
     * @param bool   $slackNotify
     *
     * @return
     */
    public function addTags($id, $input, $slackNotify = false)
    {
        (new Validator)->validateInput('addTags', $input);

        $this->trace->info(TraceCode::MERCHANT_TAGS_ADD, $input);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $tags = $input['tags'];

        (new Validator)->validateTagsForOnlyDSMerchants($merchant, $tags);

        $merchant->retag($tags);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        if ($slackNotify === true)
        {
            $this->logActionToSlack($merchant, SlackActions::TAGGED, $input);
        }

        return $merchant->tagNames();
    }

    /**
     * Adds a new tag to the merchant
     *
     * @param Entity $merchant
     * @param string $tagName
     */
    public function appendTag(Entity $merchant, string $tagName)
    {
        $this->trace->info(TraceCode::MERCHANT_TAGS_APPEND, ['tag' => $tagName]);

        $merchant->tag($tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        $this->trace->info(TraceCode::MERCHANT_TAGS_APPEND_COMPLETED);
    }

    /**
     * Changes the 2fa setting of the merchant.
     *
     * Only an owner can enable/disable 2fa
     * for the merchant id.
     *
     * In all cases, the owner's mobile should be setup
     *
     * In case of restricted merchant, all the users
     * associated with the merchant should have their mobile setup.
     *
     *
     * @param Entity $user
     * @param Entity $merchant
     * @param array $input
     *
     * @return array
     */
    public function change2faSetting(User\Entity $user, Entity $merchant, array $input): array
    {
        $action = $input[Entity::SECOND_FACTOR_AUTH];

        //owner should have their own 2fa setup done
        if ($user->isSecondFactorAuthSetup() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY);
        }

        if ($merchant->getRestricted() === true)
        {
            $query = $merchant->users()
                              ->where(function ($q)
                              {
                                  $q->where(User\Entity::CONTACT_MOBILE_VERIFIED, 0)
                                    ->orWhereNull(User\Entity::CONTACT_MOBILE);
                              });

            $totalUsersWithNo2faSetup = $query->get()->count();

            if ($totalUsersWithNo2faSetup !== 0)
            {
                $maxUserDetailsInError = 20;

                $usersWithNo2faSetup = $query->get()->take($maxUserDetailsInError);

                $usersWithNo2faSetupToArrayMerchant = $usersWithNo2faSetup->callOnEveryItem('toArrayMerchant');

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                                                        null,
                                                        [
                                                            'total_users'   => $totalUsersWithNo2faSetup,
                                                            'users'         => $usersWithNo2faSetupToArrayMerchant,
                                                        ]);
            }
        }

        $merchant->setSecondFactorAuth($action);
        $this->repo->saveOrFail($merchant);

        if ($action === true)
        {
            [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = '2FA Verification Created';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );
        }

        $mailData = [
            'merchant' => [
                Entity::ID                 => $merchant->getId(),
                Entity::EMAIL              => $merchant->getEmail(),
                Entity::NAME               => $merchant->getBillingLabel(),
                Entity::SECOND_FACTOR_AUTH => $merchant->isSecondFactorAuth(),
            ],
            'user' => [
                User\Entity::CONTACT_MOBILE => $user->getMaskedContactMobile()
            ]
        ];

        $secondFactorMail = new SecondFactorAuthMail($mailData);

        Mail::send($secondFactorMail);

        return [
            Entity::SECOND_FACTOR_AUTH => $merchant->isSecondFactorAuth(),
        ];
    }

    protected function removeSubMerchantReferralTag(Entity $merchant, string $partnerId): array
    {
        $tag = Constants::PARTNER_REFERRAL_TAG_PREFIX . $partnerId;

        $tags = $this->deleteTag($merchant->getPublicId(), $tag);

        return $tags;
    }

    /**
     * Returns the submerchant with the partner context set.
     *
     * @param Entity $partner
     * @param string $submerchantId
     * @param array  $input
     *
     * @return Entity
     * @throws BadRequestException
     */
    public function getSubmerchant(Entity $partner, string $submerchantId, array $input = []): Entity
    {
        $partnerAppIds = $this->getPartnerApplicationIds($partner);

        //
        // Apps not being present is only possible in case of pure platforms where
        // the partner manually creates and deletes the oauth applications.
        // If the partner tries to access the submerchant detail api without creating an app, throw an error.
        //
        if (empty($partnerAppIds) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_OAUTH_APP_NOT_FOUND,
                null,
                [
                    Entity::ID           => $partner->getId(),
                    Entity::PARTNER_TYPE => $partner->getPartnerType(),
                ]);
        }

        if ($partner->isPurePlatformPartner() === true)
        {
            //
            // For pure platforms, a submerchant could have authorized multiple oauth applications
            // and we need to know which app mapping is being requested.
            // Hence, throw an error if the application id is missing.
            //
            if (empty($input[AccessMap\Entity::APPLICATION_ID]) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_MISSING_APPLICATION_ID,
                    AccessMap\Entity::APPLICATION_ID,
                    [
                        Entity::ID           => $partner->getId(),
                        Entity::PARTNER_TYPE => $partner->getPartnerType(),
                    ]);
            }

            $inputAppId = $input[AccessMap\Entity::APPLICATION_ID];

            // raise an exception if the input app id does not belong to the list of oauth apps created by the partner.
            (new Validator)->validatePartnerApplicationId($inputAppId, $partnerAppIds);

            $appId = $inputAppId;
        }
        else
        {
            $accessMaps = $this->repo->merchant_access_map->fetchAccessMapForMerchantIdAndOwnerId($submerchantId, $partner->getId());

            $appId = $accessMaps->first()->getEntityId();
        }

        $product = $input[Entity::PRODUCT] ?? Product::PRIMARY;

        $actualProduct = $product;

        $params = array();

        if ($this->capitalSubmerchantUtility()->isCapitalPartnershipEnabledForPartner($partner->getId()) === true)
        {
            if ($product === Product::CAPITAL)
            {
                $product                 = Product::BANKING;
                $params[ENTITY::PRODUCT] = Product::BANKING;
                $params[Constants::TAGS] = [Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId()];
            }
            else
            {
                $params[ENTITY::PRODUCT] = $product;
                $params[Constants::WITHOUT_TAGS] = [
                    Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId(),
                    Constants::CAPITAL_CORPORATE_CARD_PARTNERSHIP_TAG_PREFIX . $partner->getId(),
                ];
            }
        }

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANT_BY_ID_REQUEST,
            [
                "input"            => $input,
                "product"          => $product,
                "actual_product"   => $actualProduct,
                "submerchant_id"   => $submerchantId
            ],
        );

        $merchant = $this->repo->merchant->findSubmerchantByIdAndConnectedAppId($submerchantId, $appId, $params);

        $partnerUser = $partner->primaryOwner();

        $merchant = $this->getPartnerSubmerchantData($merchant, $partner, $partnerUser, $product);

        $products = $this->fetchProductForMerchants([$merchant->getId()]);

        if (count($products) > 0)
        {
            $merchant[Entity::PRODUCT] = $products;
        }

        return $merchant;
    }

    /**
     * This function checks for a mapping between the partner merchant's dummy app from
     * auth database and the submerchant. This is stored in the `merchant_access_map` table
     * on API side.
     *
     * @param  string $merchantId
     * @param  string $partnerId
     *
     * @return bool
     */
    public function isMerchantMappedToNonPurePlatformPartner(string $merchantId, string $partnerId): bool
    {
        $partner = $this->repo->merchant->find($partnerId);

        $applications = (new OAuthApp\Repository)
            ->findActiveApplicationsByMerchantIdAndType($partner->getId(), OAuthApp\Type::PARTNER);

        $appIds = $applications->getIds();

        if (empty($appIds) === true)
        {
            return false;
        }

        // there will be at most two appIds here

        $mappings = (new AccessMap\Repository)
            ->findMerchantAccessMapOnEntityIds($merchantId, $appIds, AccessMap\Entity::APPLICATION);

        return ($mappings->isEmpty() === false);
    }

    public function isMerchantManagedByPartner(string $merchantId, string $partnerId): bool
    {
        $partner = $this->repo->merchant->find($partnerId);

        (new Validator)->validateIsNonPurePlatformPartner($partner);

        $appTypes = [MerchantApplications\Entity::MANAGED];

        $appIds = (new MerchantApplications\Core)->getMerchantAppIds($partnerId, $appTypes);

        if (empty($appIds) === true)
        {
            return false;
        }

        $mapping = (new AccessMap\Repository)
            ->findMerchantAccessMapOnEntityIds($merchantId, $appIds, AccessMap\Entity::APPLICATION);

        return $mapping->isNotEmpty();
    }

    public function isMerchantReferredByPartner(string $merchantId, string $partnerId): bool
    {
        $mapping = (new AccessMap\Repository)->fetchSubMerchantReferredByPartner($merchantId, $partnerId);

        return (empty($mapping) === false);
    }

    public function canSkipWorkflowToAccessSubmerchantKyc(Entity $partner, Entity $merchant): bool
    {
        // if merchant is not referred by partner, return false
        $mapping = (new AccessMap\Repository)->fetchSubMerchantReferredByPartner($merchant->getId(), $partner->getId());
        if (empty($mapping) === true)
        {
            return false;
        }
        if ($partner->canSkipWorkflowToAccessSubmerchantKyc() === true)
        {
            return true;
        }

        return ($mapping->hasKycAccess() === true);
    }

    /**
     * @param Entity $merchant
     *
     * @param null $appType
     * @return OAuthApp\Entity
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function fetchPartnerApplication(Entity $merchant, $appType = null)
    {
        (new Validator)->validateIsNonPurePlatformPartner($merchant);

        if ($appType === null)
        {
            $appType = (new MerchantApplications\Core)->getDefaultAppTypeForPartner($merchant);
        }

        $appIds = (new MerchantApplications\Core)->getMerchantAppIds($merchant->getId(), [$appType]);

        if (empty($appIds) === true)
        {
            throw new Exception\LogicException('merchant application not found for the partner');
        }

        // for aggregator and fully_managed partners, there can be two applications.
        //  - one for referral and one for managed sub-merchants
        // for other non-pure platform partners there will be only one application
        // but there can be only one application for each type for non-pure platform partners
        $appId = $appIds[0];

        try
        {
            $app = (new OAuthApp\Repository)->findActiveApplicationByIdAndMerchantId($appId, $merchant->getId());
        }
        catch (DBQueryException $ex)
        {
            throw new Exception\LogicException(
                'Server error app not found',
                ErrorCode::SERVER_ERROR_PARTNER_APP_NOT_FOUND,
                [
                    Entity::MERCHANT_ID              => $merchant->getId(),
                    AccessMap\Entity::APPLICATION_ID => $appId,
                ]);
        }

        return $app;
    }

    public function listSubmerchantsV2(Entity $partner, array $params, string $isExpEnabled) : array
    {
        $merchantAppIds = $this->getMerchantAppIdsForPartner($partner, $params);

        $params                  = $this->preProcessInput($params, $partner);
        $product                 = $params[ENTITY::PRODUCT] ?? Product::PRIMARY;
        $params[ENTITY::PRODUCT] = $product;

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANTS,
            [
                'partner_id' => $partner->getId(),
                'app_ids'    => $merchantAppIds,
                'params'     => $params,
                'product'    => $product,
            ]
        );

        $reqStartAt               = millitime();
        $subMerchants             = $this->fetchPartnerSubMerchantsByAppIDs($partner, $merchantAppIds, $params);
        $fetchSubMerchantsLatency = millitime() - $reqStartAt;

        $partnerUser = $partner->primaryOwner();

        $reqStartAt                    = millitime();
        $merchantsData                 = $this->getPartnerSubMerchantDataV2($subMerchants, $partner, $partnerUser, $product, $isExpEnabled);
        $fetchSubMerchantsDataLatency  = millitime() - $reqStartAt;

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANTS_DATA_LATENCY,
            [
                'partner_id'                        => $partner->getId(),
                'product'                           => $product,
                'merchants'                         => $subMerchants->getIds(),
                'app_ids'                           => $merchantAppIds,
                'fetch_sub_merchants_latency'       => $fetchSubMerchantsLatency,
                'fetch_sub_merchants_data_latency'  => $fetchSubMerchantsDataLatency,
                'overall_latency'                   => $fetchSubMerchantsLatency + $fetchSubMerchantsDataLatency,
                'exp_enabled'                       => $isExpEnabled
            ]
        );

        $applyProductFilter = array_key_exists(ENTITY::PRODUCT, $params);
        return $applyProductFilter ? [$merchantsData, 'offset' => $params['skip']] : [ $merchantsData ];
    }

    private function getMerchantAppIdsForPartner(Entity $partner, array &$params): array
    {
        $merchantAppCore = new MerchantApplications\Core();
        $types           = [];
        $applicationId   = null;
        if (empty($params[MerchantApplications\Entity::TYPE]) === false)
        {
            $types = [ $params[MerchantApplications\Entity::TYPE] ];
            unset($params[MerchantApplications\Entity::TYPE]);
        }
        if (empty($params[Constants::APPLICATION_ID]) === false)
        {
            $applicationId = $params[Constants::APPLICATION_ID];
            unset($params[Constants::APPLICATION_ID]);
        }

        $merchantAppIds = $merchantAppCore->getMerchantAppIds($partner->getId(), $types, $applicationId);

        if (empty($applicationId) === false && empty($merchantAppIds) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_ID,
                Constants::APPLICATION_ID,
                [
                    Constants::APPLICATION_ID => $merchantAppIds,
                ]
            );
        }

        return $merchantAppIds;
    }

    private function preProcessInput(array $params, $partner)
    {
        // add default params
        $params['skip'] = $params['skip'] ?? 0;
        $params['count'] = $params['count'] ?? self::DEFAULT_SUBMERCHANT_FETCH_LIMIT;

        return $this->preProcessForCapital($params, $partner);
    }

    private function preProcessForCapital(array $params, $partner)
    {
        if ($this->capitalSubmerchantUtility()->isCapitalPartnershipEnabledForPartner($partner->getId()) === true)
        {
            $product = $params[ENTITY::PRODUCT] ?? Product::PRIMARY;

            if ($product === Product::CAPITAL)
            {
                $params[ENTITY::PRODUCT] = Product::BANKING;
                $params[Constants::TAGS] = [Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId()];
            }
            else
            {
                $params[Constants::WITHOUT_TAGS] = [
                    Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId(),
                    Constants::CAPITAL_CORPORATE_CARD_PARTNERSHIP_TAG_PREFIX . $partner->getId(),
                ];
            }
        }

        return $params;
    }

    private function fetchPartnerSubMerchantsByAppIDs($partner, $merchantAppIds, $params): PublicCollection
    {
        return Tracer::inspan(
            ['name' => HyperTrace::FETCH_SUBMERCHANTS_ON_APP_IDS],
            function() use ($params, $merchantAppIds, $partner) {
                return $this->repo->merchant->listSubmerchantsDetailsAndUsers($merchantAppIds, $params);
            }
        );
    }

    /**
     * @param Entity $partner
     * @param array $params
     * @param bool $paginate
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function listSubmerchants(Entity $partner, array $params)
    {

        $offset = $params['skip'] ?? 0;

        $appIds = $this->getPartnerApplicationIds($partner);

        $product = $params[ENTITY::PRODUCT] ?? Product::PRIMARY;

        if ($this->capitalSubmerchantUtility()->isCapitalPartnershipEnabledForPartner($partner->getId()) === true)
        {
            if ($product === Product::CAPITAL)
            {
                $product                 = Product::BANKING;
                $params[ENTITY::PRODUCT] = Product::BANKING;
                $params[Constants::TAGS] = [Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId()];
            }
            else
            {
                $params[Constants::WITHOUT_TAGS] = [
                    Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId(),
                    Constants::CAPITAL_CORPORATE_CARD_PARTNERSHIP_TAG_PREFIX . $partner->getId(),
                ];
            }
        }

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANTS,
            [
                'partner_id' => $partner->getId(),
                'app_ids'    => $appIds,
                'params'     => $params,
                'product'    => $product,
            ]
        );

        if ((empty($params[MerchantApplications\Entity::TYPE]) === false) and (empty($appIds) === false))
        {
            $appIds = (new MerchantApplications\Core)->getMerchantAppIds($partner->getId(), [$params[MerchantApplications\Entity::TYPE]]);

            unset($params[MerchantApplications\Entity::TYPE]);
        }

        if (empty($params[Constants::APPLICATION_ID]) === false)
        {
            $inputAppId = $params[Constants::APPLICATION_ID];

            (new Validator)->validatePartnerApplicationId($inputAppId, $appIds);

            // Filter with only the input app id
            $appIds = [$inputAppId];

            // Filters will be applied based on $params. Since app id is already handled above, unsetting it here.
            unset($params[Constants::APPLICATION_ID]);
        }

        $applyProductFilter = array_key_exists(ENTITY::PRODUCT, $params);

        $checkingProductUsage = array_key_exists(Constants::IS_USED, $params);

        $isExpEnabled = $this->isRazorxExperimentEnable($partner->getId(), RazorxTreatment::SUBMERCHANTS_FETCH_API_LATENCY_IMPROVE);

        $reqStartAt = millitime();

        if ($applyProductFilter === true and ($isExpEnabled !== true or $checkingProductUsage === true))
        {
            list($offset, $merchants) = Tracer::inspan(['name' => HyperTrace::FILTER_SUBMERCHANTS_ON_PRODUCT], function() use ($params, $appIds, $partner) {

                return $this->filterSubmerchantsOnProduct($params, $appIds, $partner->getId());
            });
        }
        else
        {
            unset($params[Constants::IS_USED]);

            $merchants = Tracer::inspan(['name' => HyperTrace::FETCH_SUBMERCHANTS_ON_APP_IDS], function() use ($params, $appIds, $partner) {

                return $this->repo->merchant->fetchSubmerchantsByAppIds($appIds, $params);
            });
        }

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANTS_LIST,
            [
                'partner_id'           => $partner->getId(),
                'merchants'            => $merchants->getIds(),
                'apply_product_filter' => $applyProductFilter,
                'product_usage'        => $checkingProductUsage
            ]
        );

        $fetchSubMerchantsLatency = millitime() - $reqStartAt;

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANTS_LATENCY,
            [
                'partner_id'  => $partner->getId(),
                'app_ids'     => $appIds,
                'product'     => $product,
                'latency'     => $fetchSubMerchantsLatency,
                'exp_enabled' => $isExpEnabled
            ]
        );

        $partnerUser = $partner->primaryOwner();

        // if fetching sub-merchants based on product usage status, no need to fetch additional
        // details such as dashboard access, kyc access, banking account status etc.
        if ($checkingProductUsage === false)
        {
            $reqStartAt = millitime();

            $merchants = $merchants->map(function($submerchant) use ($partnerUser, $product, $partner, $isExpEnabled) {
                return Tracer::inspan(['name' => HyperTrace::GET_PARTNER_SUBMERCHANT_DATA], function() use ($submerchant, $partner, $partnerUser, $product, $isExpEnabled) {
                    return $this->getPartnerSubmerchantData($submerchant, $partner, $partnerUser, $product, $isExpEnabled);
                });
            });

            $fetchSubMerchantsDataLatency = millitime() - $reqStartAt;

            $this->trace->info(
                TraceCode::PARTNER_FETCH_SUBMERCHANTS_DATA_LATENCY,
                [
                    'partner_id'      => $partner->getId(),
                    'product'         => $product,
                    'latency'         => $fetchSubMerchantsDataLatency,
                    'overall_latency' => $fetchSubMerchantsLatency + $fetchSubMerchantsDataLatency,
                    'exp_enabled'     => $isExpEnabled
                ]
            );
        }

        $this->trace->info(
            TraceCode::PARTNER_FETCH_SUBMERCHANTS_DATA,
            [
                'partner_id' => $partner->getId(),
                'product'    => $product,
                'merchants'  => $merchants->getIds()
            ]
        );

        return $applyProductFilter ? [$merchants, 'offset' => $offset] : [$merchants];
    }

    /**
     * @param Entity $partner
     *
     * @return PublicCollection
     * @throws BadRequestException
     */
    public function fetchActivatedSubMerchantsForPartner(Entity $partner): Base\PublicCollection
    {
        $appIds = $this->getPartnerApplicationIds($partner);

        return $this->repo
            ->merchant
            ->fetchSubmerchantsByAppIds($appIds,
                                        [
                                            Detail\Entity::ACTIVATION_STATUS => Entity::ACTIVATED,
                                        ]);
    }

    /**
     * Fetch the list of all merchants the submerchant is associated with
     *
     * @param string $submerchantId
     *
     * @return PublicCollection
     */
    public function fetchAffiliatedPartners(string $submerchantId): PublicCollection
    {
        return $this->repo
            ->merchant_access_map
            ->fetchAffiliatedPartnersForSubmerchant($submerchantId)
            ->unique(function ($item)
            {
                return $item->entityOwner->getId();
            })
            ->map(function ($item)
            {
                return $item->entityOwner;
            });
    }

    public function isPartnerUserAddedToSubMUser(
        Entity $partner, Entity $submerchant, string $product, array $roles
    ): bool
    {
        $partnerUserId = $partner->primaryOwner()->getId();
        $subMUserIds = $submerchant->users()
                                   ->where('product', $product)
                                   ->whereIn('role', $roles)
                                   ->get()
                                   ->getIds();

        return (in_array($partnerUserId, $subMUserIds, true) === true);
    }

    /**
     * Maps the partner user to the submerchant account,
     * if the partner merchant should have access to the submerchant's dashboard, and,
     * if the partner user is not already mapped to the submerchant's account.
     *
     * @param Entity $partner
     * @param Entity $submerchant
     * @param null $appType
     */
    protected function assignSubmerchantDashboardAccessIfApplicable(Entity $partner, Entity $submerchant, $appType = null, string $role = null)
    {
        if ($partner->allowSubmerchantDashboardAccess($appType) === false)
        {
            return;
        }

        if ($this->isPartnerUserAddedToSubMUser($partner, $submerchant, Product::PRIMARY, [Role::OWNER]) === false)
        {
            // Attaches partners's user to the submerchant account as an owner
            $this->attachSubMerchantUser($partner->primaryOwner()->getId(), $submerchant, null, $role);
        }
        else
        {
            $this->trace->info(
                TraceCode::PARTNER_USER_ALREADY_OWNER_TO_SUBMERCHANT,
                [
                    'partner_id'     => $partner->getId(),
                    'submerchant_id' => $submerchant->getId(),
                ]);
        }
    }

    private function getPartnerSubMerchantDataV2(
        PublicCollection $submerchants, Entity $partner, User\Entity $partnerUser = null,
        string $product = null, bool $isExpEnabled = false
    ): PublicCollection
    {
        $accessRequests = null;
        if ($partner->isResellerPartner())
        {
            $accessRequests = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityIds(
                $partner->getId(), $submerchants->getIds()
            )->groupBy(\RZP\Models\Partner\KycAccessState\Entity::ENTITY_ID);
        }

        $dashboardAccesses = null;
        if ($partner->isAggregatorPartner() || $partner->isFullyManagedPartner())
        {
            $dashboardAccesses = $this->fetchSubmerchantDashboardAccesses($submerchants->getIds());
        }

        return Tracer::inspan( ['name' => HyperTrace::GET_PARTNER_SUBMERCHANT_DATA], function() use (
            $submerchants, $partnerUser, $accessRequests, $dashboardAccesses, $product, $partner, $isExpEnabled
        ) {
            foreach ($submerchants as $submerchant)
            {
                $submerchant[Entity::DETAILS] = [
                    Detail\Entity::ACTIVATION_STATUS => $submerchant->getAttribute(Detail\Entity::ACTIVATION_STATUS),
                ];
                unset($submerchant[Detail\Entity::ACTIVATION_STATUS]);

                $subMerchantOwner = ( empty($partnerUser) === false) ? $this->getNonPartnerOwnerV2($submerchant, $partnerUser, $product) : null;
                if (empty($subMerchantOwner) === true)
                {
                    $submerchant[Entity::USER] = null;
                }
                else
                {
                    $submerchant[Entity::USER] = Tracer::inspan(
                        ['name' => HyperTrace::GET_REDUCED_SUBMERCHANT_OWNER_DATA],
                        function () use ($subMerchantOwner
                        ) {
                            return [
                                User\Entity::ID             => $subMerchantOwner->id,
                                User\Entity::EMAIL          => $subMerchantOwner->email,
                                User\Entity::CONTACT_MOBILE => $subMerchantOwner->contact_mobile
                            ];
                        });
                }
                $submerchant->unsetRelation('owners');

                $submerchant[Entity::DASHBOARD_ACCESS] = (
                    empty($dashboardAccesses) === false &&
                    empty($dashboardAccesses[$submerchant->getId()]) === false
                );

                $submerchant[Entity::APPLICATION] = [OAuthApp\Entity::ID => $submerchant->getAttribute(Constants::APPLICATION_ID)];

                $submerchant[Entity::KYC_ACCESS] = null;
                $accessRequest = $accessRequests[$submerchant->getId()];
                if (empty($accessRequest) === false)
                {
                    $submerchant[Entity::KYC_ACCESS] = $accessRequest->first()->toArrayPublic();
                }

                if ($product === Product::BANKING)
                {
                    $caStatus = Tracer::inspan(['name' => HyperTrace::GET_BANKING_ACCOUNT_STATUS], function () use ($submerchant) {
                        return $this->getBankingAccountStatus($submerchant);
                    });
                    $submerchant[Entity::BANKING_ACCOUNT] = [ENTITY::CA_STATUS => $caStatus];
                }
            }

            return $submerchants;
        });
    }

    /**
     * Sets the partner attributes in the instance of Merchant\Entity so that toArrayPartner() can be used later.
     *
     * @param Entity             $submerchant
     * @param Entity             $partner
     * @param User\Entity|null   $partnerUser
     * @param string|null        $product
     * @param bool               $isExpEnabled // experiment to enable the low latency flow
     *
     * @return Entity
     */
    protected function getPartnerSubmerchantData(Entity $submerchant, Entity $partner, User\Entity $partnerUser = null,
                                                 string $product = null, bool $isExpEnabled = false): Entity
    {
        $submerchant[Entity::DETAILS] = [
            Detail\Entity::ACTIVATION_STATUS => $submerchant->getAttribute(Detail\Entity::ACTIVATION_STATUS),
        ];

        $subMerchantOwner = ( empty($partnerUser) === false) ? $this->getNonPartnerOwner($submerchant, $partnerUser, $product) : null;

        if (empty($subMerchantOwner) === true)
        {
            $submerchant[Entity::USER] = null;
        }
        else if ($isExpEnabled === true)
        {
            $submerchant[Entity::USER] = Tracer::inspan(['name' => HyperTrace::GET_REDUCED_SUBMERCHANT_OWNER_DATA], function () use ($subMerchantOwner) {

                // reducing the submerchant owner response and removing toArrayPublic call to improve latency
                return [
                    User\Entity::ID             => $subMerchantOwner->id,
                    User\Entity::EMAIL          => $subMerchantOwner->email,
                    User\Entity::CONTACT_MOBILE => $subMerchantOwner->contact_mobile
                ];
            });
        } else
        {
            $submerchant[Entity::USER] = Tracer::inspan(['name' => HyperTrace::GET_SUBMERCHANT_OWNER_DATA], function () use ($subMerchantOwner) {

                return $subMerchantOwner->toArrayPublic();
            });
        }

        $submerchant[Entity::DASHBOARD_ACCESS] = $this->hasSubmerchantDashboardAccess($submerchant);

        $submerchant[Entity::APPLICATION] = [OAuthApp\Entity::ID => $submerchant->getAttribute(Constants::APPLICATION_ID)];

        $submerchant[Entity::KYC_ACCESS] = null;

        $accessRequest = $this->repo->partner_kyc_access_state->findByPartnerIdAndEntityId($partner->getId(), $submerchant->getId())->first();

        if (empty($accessRequest) === false)
        {
            $submerchant[Entity::KYC_ACCESS] = $accessRequest->toArrayPublic();
        }

        if ($product === Product::BANKING)
        {
            $caStatus = Tracer::inspan(['name' => HyperTrace::GET_BANKING_ACCOUNT_STATUS], function () use ($submerchant) {

                return $this->getBankingAccountStatus($submerchant);
            });

            $submerchant[Entity::BANKING_ACCOUNT] = [ENTITY::CA_STATUS => $caStatus];
        }

        return $submerchant;
    }

    /**
     * A submerchant account can have at a max of 2 users with the `owner` role -
     * One being his own user and second being the partner merchant's user linked as an owner to the submerchant.
     *
     * This function returns the first type of given product owner.
     *
     * @param Entity $merchant
     * @param User\Entity $partnerUser
     * @param string|null $product
     *
     * @return null
     */
    protected function getNonPartnerOwnerV2(Entity $merchant, User\Entity $partnerUser, string $product = null)
    {
        $product = $product ?? Product::PRIMARY;

        $owners = $merchant->owners->filter(function ($item) use ($product) {
            return ($item['pivot']['product'] === $product);
        });

        foreach ($owners as $owner)
        {
            if ($owner->getEmail() !== $partnerUser->getEmail())
            {
                return $owner;
            }
        }

        return null;
    }

    /**
     * A submerchant account can have at a max of 2 users with the `owner` role -
     * One being his own user and second being the partner merchant's user linked as an owner to the submerchant.
     *
     * This function returns the first type of given product owner.
     *
     * @param Entity $merchant
     * @param User\Entity $partnerUser
     * @param string|null $product
     *
     * @return null
     */
    protected function getNonPartnerOwner(Entity $merchant, User\Entity $partnerUser, string $product = null)
    {
        $product = $product ?? Product::PRIMARY;

        $owners = $merchant->owners($product)->get();

        foreach ($owners as $owner)
        {
            if ($owner->getEmail() !== $partnerUser->getEmail())
            {
                return $owner;
            }
        }

        return null;
    }

    /**
     * Checks whether the logged in partner user has access over the submerchant's account
     *
     * @param Entity $submerchant
     *
     * @return array
     */
    protected function fetchSubmerchantDashboardAccesses(array $submerchantIds): array
    {
        $loggedInPartnerUser = $this->app['basicauth']->getUser();

        if ($loggedInPartnerUser !== null)
        {
            $merchantUsers = $this->repo->merchant_user->checkUserForMerchantIds(
                $submerchantIds, $loggedInPartnerUser->getId()
            );

            $res = $merchantUsers->groupBy(Merchant\MerchantUser\Entity::MERCHANT_ID);

            return $res->map->count()->toArray();
        }

        return [];
    }

    /**
     * Checks whether the logged in partner user has access over the submerchant's account
     *
     * @param Entity $submerchant
     *
     * @return bool
     */
    protected function hasSubmerchantDashboardAccess(Entity $submerchant): bool
    {
        $userIds = $submerchant->users->getIds();

        $loggedInPartnerUser = $this->app['basicauth']->getUser();

        if (($loggedInPartnerUser !== null) and
            (in_array($loggedInPartnerUser->getId(), $userIds, true) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * Cleans up all the supporting entities that were created when the merchant was a partner. This includes -
     * 1. All the ref tags that indicate that the merchant is a referral to a partner.
     * 2. All the mappings (merchant_users) that is currently allowing the partner user to access a submerchant.
     *
     * @param Entity $partner
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    protected function deleteSupportingEntities(Entity $partner)
    {
        if ($partner->isPurePlatformPartner() === true)
        {
            // A dummy internal application for pure platforms does not exist
            return;
        }


        // Fetch partner app and then access maps
        $appIds = $this->getPartnerApplicationIds($partner);

        foreach ($appIds as $appId)
        {
            $accessMaps = $this->repo->merchant_access_map->fetchMerchantAccessMapOnEntity(AccessMap\Entity::APPLICATION, $appId);

            //
            // Fetch subMerchants
            // access maps will be deleted as part of delete application flow
            //
            $submerchantIds = $accessMaps->pluck(AccessMap\Entity::MERCHANT_ID)->toArray();
            $submerchants   = $this->repo->merchant->findMany($submerchantIds);

            $this->deleteAllSubmerchantRefTags($submerchants, $partner);

            $this->deletePartnerDashboardAccessOnSubmerchants($partner, $submerchants);
        }
    }

    /**
     * @param PublicCollection $submerchants
     * @param Entity           $partner
     */
    protected function deleteAllSubmerchantRefTags(Base\PublicCollection $submerchants, Entity $partner)
    {
        $tagName = Constants::PARTNER_REFERRAL_TAG_PREFIX . $partner->getId();

        foreach ($submerchants as $merchant)
        {
            $merchant->untag($tagName);

            $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);
        }
    }

    /**
     * @param Entity           $partner
     * @param PublicCollection $submerchants
     */
    protected function deletePartnerDashboardAccessOnSubmerchants(Entity $partner, Base\PublicCollection $submerchants)
    {
        $partnerUsers = $partner->users()->get();

        //
        // if partner added himself as a submerchant which used to happen before but not anymore
        // then we should not remove his own user
        //
        $submerchantIds = $submerchants->reject(function($subMerchant) use ($partner) {
            return ($subMerchant->getId() === $partner->getId());
        })->pluck(Entity::ID)->toArray();

        foreach ($partnerUsers as $partnerUser)
        {
            $merchantIdsAccessible = $partnerUser->merchants()->get()->pluck(Entity::ID)->toArray();

            $submerchantIdsAccessible = array_intersect($merchantIdsAccessible, $submerchantIds);

            $this->repo->detach($partnerUser, User\Entity::MERCHANTS, $submerchantIdsAccessible);
        }
    }

    /**
     * handles cases for la merchant users.
     * 3 possible cases like the normal merchant edit email.
     * 1. There exists a team member with the new email , we swap the roles of the team member(linked_account_admin)
     * with new email and the original linked_account_owner.
     * 2. There exists a user(not team member) with the new email Here, we change the original linked_account_owner to
     * linked_account_admin and then add the user with new email as linked_account_owner
     * 3. The new email is completely new to the razorpay and doesn't have a user account associated with it, for
     * normal merchants we used to get edit email change requests via support and admin used to directly change
     * the email. but in LA dashboard case marketplace merchants will be able to change the linked account's email at
     * any time so for any new email we will have to assign the new email as linked_account_owner and send a
     * password reset link so that the user will generate a password and login to the LA dashboard.(this ensures that
     * email is also verified.) and promote the existing linked_account_owner role user to team member.
     *
     * @param Merchant\Entity $merchant
     * @param string          $product
     *
     * @return User\Entity
     */
    public function handleLinkedAccountMerchantsUsers(Merchant\Entity $merchant, string $product)
    {
        $newEmail = $merchant->getEmail();

        $teamUser = $merchant->users()->where('email', $newEmail)->first();

        $existingUser = $this->repo->user->getUserFromEmail($newEmail);

        $oldOwner = $merchant->primaryLinkedAccountOwner();

        if (empty($oldOwner) === false)
        {
            // Assign Linked Account Admin role to the old owner.
            (new User\Core)->detachAndAttachMerchantUser($oldOwner,
                                                         $merchant->getId(),
                                                         Role::LINKED_ACCOUNT_ADMIN,
                                                         $product);
        }

        if (empty($teamUser) === false)
        {
            // Assign Linked Account owner role to the team user.
            (new User\Core)->detachAndAttachMerchantUser($teamUser,
                                                         $merchant->getId(),
                                                         Role::LINKED_ACCOUNT_OWNER,
                                                         $product);
        }
        elseif (empty($existingUser) === false)
        {
            // Assign Linked Account owner to existing user.
            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => Role::LINKED_ACCOUNT_OWNER,
                'merchant_id' => $merchant->getId(),
            ];

            (new User\Core)->updateUserMerchantMapping($existingUser, $userMerchantMappingInputData);
        }
        else
        {
            list($subMerchantUser, $createdNew) =
                (new Merchant\Service)->createOrFetchUserAndAttachMerchant($merchant, $newEmail);

            // Sends Account linked communication emails to users.
            (new User\Service)->sendAccountLinkedCommunicationEmail($subMerchantUser, $merchant, $createdNew);
        }
    }

    /**
     * fetches subcategory metadata from business subcategory and business category
     * and updates merchant category and category2
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param string                      $category
     * @param null|string                 $subcategory
     *
     * @return \RZP\Models\Merchant\Entity
     * @throws \RZP\Exception\BadRequestException
     */
    public function autoUpdateCategoryDetails(
        Entity $merchant,
        string $category,
        string $subcategory = null,
        bool $shouldResetMethods = false): Entity
    {
        $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

        $oldData = [
            Entity::CATEGORY2 => $merchant->getCategory2(),
            Entity::CATEGORY  => $merchant->getCategory(),
        ];

        $category  = $subcategoryMetaData[Entity::CATEGORY];
        $category2 = $subcategoryMetaData[Entity::CATEGORY2];

        $merchant->setCategory2($category2);
        $merchant->setCategory($category);

        $this->repo->transaction(function () use ($merchant, $category){
            $input = [
                Entity::CATEGORY => $category,
            ];

            (new Terminal\Core)->processMerchantMccUpdate($merchant, $input);

            $this->repo->saveOrFail($merchant);

        });

        if ($shouldResetMethods === true)
        {
            $merchant->setDefaultMethodsBasedOnCategory();
        }

        $newData = [
            Entity::CATEGORY2 => $merchant->getCategory2(),
            Entity::CATEGORY  => $merchant->getCategory(),
        ];

        $this->trace->info(
            TraceCode::MERCHANT_AUTO_UPDATE_SUBCATEGORY_METADATA,
            compact('oldData', 'newData'));

        return $merchant;
    }

    /**
     * Extracts a few fields like business name and website from the input
     * and saves it to merchants as well as merchant details table.
     *
     * @param Entity $merchant
     * @param array  $input
     *
     * @return Entity
     */
    public function editPreSignupFields(Merchant\Entity $merchant, array $input): Entity
    {
        $businessWebsite = $input[Detail\Entity::BUSINESS_WEBSITE] ?? null;

        $preSignupInput = [
            Entity::NAME    => $input[Detail\Entity::BUSINESS_NAME],
            Entity::WEBSITE => $businessWebsite,
        ];

        if (isset($input[Detail\Entity::CONTACT_EMAIL]) === true)
        {
            $preSignupInput[Entity::EMAIL] = $input[Detail\Entity::CONTACT_EMAIL];
        }

        (new Validator)->validateInput('edit_pre_signup', $preSignupInput);

        $this->trace->info(TraceCode::MERCHANT_EDIT, ['input' => $preSignupInput]);

        $merchant = $this->edit($merchant, $preSignupInput);

        return $merchant;
    }

    /**
     * Disables live transactions if the merchant is (instantly) activated
     *
     * @param Entity $merchant
     */
    public function disableLiveIfAlreadyActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === true)
        {
            $this->disableLive($merchant);
        }
    }

    /**
     * Disables live transactions
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function disableLive(Entity $merchant): Entity
    {
        if ($merchant->isLive() === false)
        {
            return $merchant;
        }

        $this->trace->info(TraceCode::MERCHANT_LIVE_DISABLE_REQUEST);

        $merchant = $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $merchant->liveDisable();

            $this->repo->saveOrFail($merchant);

            return $merchant;
        });

        return $merchant;
    }

    /**
     * Enables live transactions
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function enableLive(Entity $merchant): Entity
    {
        // return if already live
        if ($merchant->isLive() === true)
        {
            return $merchant;
        }

        $this->trace->info(TraceCode::MERCHANT_LIVE_ENABLE_REQUEST);

        $merchant = $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $merchant->liveEnable();

            $this->repo->saveOrFail($merchant);

            return $merchant;
        });

        return $merchant;
    }

    /**
     * Pushes merchant ids to Es sync queue
     *
     * @param array $input
     *
     * @return array
     */
    public function syncMerchantsToEs(array $input): array
    {
        (new Validator)->validateInput('bulk_sync_balance', $input);

        $interval = $input[Constants::INTERVAL] ?? self::DEFAULT_MERCHANT_ES_SYNC_INTERVAL;

        $minUpdatedAtTimeStamp = Carbon::now(Timezone::IST)->subMinutes($interval)->getTimestamp();

        $merchantIds = $this->repo->balance->getMerchantsIdsForEsSync($minUpdatedAtTimeStamp);

        $batches = array_chunk($merchantIds, self::MAX_ES_MERCHANT_SYNC_LIMIT, true);

        foreach ($batches as $batch)
        {
            EsSync::dispatch($this->mode, EsRepository::UPDATE, E::MERCHANT, $batch);
        }

        $resultSummary = [
            Constants::RECORDS_PROCESSED => count($merchantIds),
            Constants::INTERVAL          => $interval,
        ];

        $this->trace->info(TraceCode::MERCHANT_ES_SYNC_RESPONSE, $resultSummary);

        return $resultSummary;
    }

    /**
     *  check if merchant name already exists and merchant is a submerchant of existing partner.
     *  if yes, then dont copy the name from business_name instead retain the same merchant name
     *  This is behind a feature flag to restrict across all the partners.
     *
     * @param Entity $merchant
     *
     * @return bool
     */
    public function shouldRetainMerchantName(Entity $merchant): bool
    {
        $partners = $this->fetchAffiliatedPartners($merchant->getId());

        $partners = $partners->filter(function(Merchant\Entity $partner) use ($merchant) {

            return ($partner->shouldRetainMerchantName() === true);

        })->first();

        if (empty($partners) === false and $merchant->getName() !== null)
        {
            return true;
        }

        return false;
    }
    /**
     * Update merchant data like international based on business category
     * and syncs merchant data with merchant_details website and business name
     *
     * @param Entity $merchant
     * @param array  $input
     *
     * @return Entity
     * @throws Throwable
     */
    public function syncMerchantEntityFields(Entity $merchant, array $input): Entity
    {
        $merchantInput = [];

        if ((isset($input[Detail\Entity::BUSINESS_WEBSITE]) === true) and
            ($merchant->getWebsite() !== $input[Detail\Entity::BUSINESS_WEBSITE]))
        {
            $merchantInput[Entity::WEBSITE] = $input[Detail\Entity::BUSINESS_WEBSITE];

            $this->updateWhitelistedDomain($merchant, $merchantInput);
        }

        if((array_key_exists(Detail\Entity::CONTACT_EMAIL, $input) === true)
            and (is_null($input[Detail\Entity::CONTACT_EMAIL]) === true))
        {
            if(empty($merchant->getEmail()) === false)
            {
                $merchantUser = $this->repo->user->getUserFromEmail($merchant->getEmail());

                $merchantUser->setAttribute(Entity::EMAIL, null);

                $this->repo->saveOrFail($merchantUser);
            }

            $merchant->setEmail($input[Detail\Entity::CONTACT_EMAIL]);
        }

        if (empty($input[Detail\Entity::BUSINESS_DBA]) === false)
        {
            $merchantInput[Entity::BILLING_LABEL] = $input[Detail\Entity::BUSINESS_DBA];
        }

        if (empty($input[Detail\Entity::BUSINESS_NAME]) === false)
        {
            if ($this->shouldRetainMerchantName($merchant) === true)
            {
                $merchantInput[Entity::NAME] = $merchant->getName();

                // adding this log for future debugging
                $this->trace->info(TraceCode::MERCHANT_NAME_RETAIN,
                                   [
                                       'merchant_name' => $merchantInput[Entity::NAME],
                                       'merchant_id'   => $merchant->getId(),
                                       'business_name' => $input[Detail\Entity::BUSINESS_NAME],
                                   ]);
            }
            else
            {
                $merchantInput[Entity::NAME] = $input[Detail\Entity::BUSINESS_NAME];
            }
        }
        else
        {
            // if business name is empty, copy billing label if it's not empty
            $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

            $businessName = $merchantDetails->getBusinessName();
            $dbaName      = $input[Detail\Entity::BUSINESS_DBA] ?? $merchant->getDbaName();

            if ((empty($businessName) === true) and
                (empty($input[Detail\Entity::BUSINESS_NAME]) === true) and
                (empty($dbaName) === false))
            {
                $merchantDetails->setBusinessName($dbaName);

                $this->repo->saveOrFail($merchantDetails);

                $merchantInput[Entity::NAME] = $dbaName;
            }
        }

        if (empty($merchantInput) === true)
        {
            return $merchant;
        }

        $merchant->setAuditAction(Action::EDIT_MERCHANT);

        $merchant->edit($merchantInput);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $this->saveAndNotify($merchant);
        });

        return $merchant;
    }

    /**
     * Extract domain and add it in whitelisted domain
     * if previously website is present then remove it's domain from whitelisted_domain
     * and update website in business website.
     *
     * @param Entity $merchant
     * @param array  $merchantInput
     */
    public function updateWhitelistedDomain(Entity $merchant, array $merchantInput)
    {
        $website = $merchantInput[Entity::WEBSITE];

        $domain = (new TLDExtract)->getEffectiveTLDPlusOne($website);

        $oldWebsite = $this->getOldWebsite($merchant, $website);

        if ($oldWebsite !== null)
        {
            $oldDomain = (new TLDExtract)->getEffectiveTLDPlusOne($oldWebsite);

            $this->removeDomainFromWhitelistedDomain($merchant, $oldDomain);
        }

        $this->addDomainInWhitelistedDomain($merchant, $domain);
    }

    public function addWhitelistedDomainForUrl($merchantId, $url)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->addDomainInWhitelistedDomainOrFail($merchant, (new TLDExtract)->getEffectiveTLDPlusOne($url));

        $this->repo->merchant->saveOrFail($merchant);

        $liveStatus = (new HealthChecker\Job)->isLive($url);

        if ($liveStatus['result'] !== HealthChecker\Constants::RESULT_LIVE)
        {
            return sprintf('Current merchant website status: %s', $liveStatus['result']);
        }

        return '';
    }

    public function removeWhitelistedDomainForUrl($merchantId, $url)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->removeDomainFromWhitelistedDomainOrFail($merchant, (new TLDExtract)->getEffectiveTLDPlusOne($url));

        $this->repo->merchant->saveOrFail($merchant);
    }

    /**
     * there are two flow where website can be updated
     * 1) edit website in merchant entity and then sync with merchant_details
     *    in this case $merchant->getWebsite() will be same as $newWebsite because we edited merchant entity with input.
     *    so to find old website we need to pick business_website from merchant_details.
     *
     * 2) edit website in merchant_details entity and then sync with merchant
     *    for old website we need to pick website from merchant entity.
     *
     * @param Entity $merchant
     * @param string $newWebsite
     *
     * @return mixed
     */
    private function getOldWebsite(Entity $merchant, string $newWebsite)
    {
        $oldWebsite = $merchant->getWebsite();

        if ($oldWebsite === $newWebsite)
        {
            $merchantDetails = $merchant->merchantDetail;

            $oldWebsite = $merchantDetails->getWebsite();
        }

        return $oldWebsite;
    }

    /**
     * add domain in whitelisted domain if domain is not in autoWhitelisted domain array
     *
     * @param Entity      $merchant
     * @param string|null $domain
     */
    public function addDomainInWhitelistedDomain(Entity $merchant, string $domain = null)
    {
        $whitelistedDomains = $merchant->getWhitelistedDomains() ?? [];

        if ((in_array($domain, Entity::AUTO_WHITELISTED_DOMAINS) === false) and
            (in_array($domain, $whitelistedDomains) === false) and
            (empty($domain) === false))
        {
            array_push($whitelistedDomains, $domain);

            $merchant->setWhitelistedDomains($whitelistedDomains);

            return true;
        }
        return false;
    }

    public function addDomainInWhitelistedDomainOrFail(Entity $merchant, string $domain = null)
    {
        if ($this->addDomainInWhitelistedDomain($merchant, $domain) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_DOMAIN_ALREADY_WHITELISTED);
        }
    }

    public function removeDomainFromWhitelistedDomainOrFail(Entity $merchant, string $domain = null)
    {
        if ($this->removeDomainFromWhitelistedDomain($merchant, $domain) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_WHITELISTED_DOMAIN_NOT_FOUND);
        }
    }

    /**
     * remove domain from whitelisted domain column
     *
     * @param Entity      $merchant
     * @param string|null $domain
     */
    public function removeDomainFromWhitelistedDomain(Entity $merchant, string $domain = null)
    {
        $whitelistedDomains = $merchant->getWhitelistedDomains() ?? [];

        $key = array_search($domain, $whitelistedDomains);

        if ($key !== false)
        {
            unset($whitelistedDomains[$key]);

            $whitelistedDomains = array_values($whitelistedDomains);

            $merchant->setWhitelistedDomains($whitelistedDomains);

            return true;
        }
        return false;
    }

    public function setSubMerchantMaxPaymentAmount(Entity $partner,Entity $subMerchant,string $subMerchantBusinessType)
    {
        // In case of linked account we create submerchants without partner
        if($partner->isPartner() === false)
        {
            return ;
        }

        $subMerchantMaxPaymentConfig = (new PartnerConfig\SubMerchantConfig\Core())->fetchPartnerSubMerchantConfig($partner, PartnerConfig\Constants::MAX_PAYMENT_AMOUNT);

        if(empty($subMerchantMaxPaymentConfig) === true)
        {
            return ;
        }

        foreach($subMerchantMaxPaymentConfig as $maxPaymentConfig)
        {
            if($maxPaymentConfig[PartnerConfig\Constants::BUSINESS_TYPE] === $subMerchantBusinessType)
            {

                // converting value to paisa
                $subMerchant->setMaxPaymentAmount($maxPaymentConfig[PartnerConfig\Constants::VALUE]*100);
            }
        }
    }

    /**
     * Returns maximum transaction amount for a merchant
     *
     * @param Entity $merchant
     *
     * @return int
     * @throws BadRequestException
     */
    public function getMaxPayAmount(Entity $merchant): int
    {
        //
        // for fetching merchant detail we can do $merchant->merchantDetail also
        // but this function is getting called from merchant entity so doing this will cache $merchant->merchantDetail
        // merchant detail object hence on subsequent call will get stale  merchantDetail object
        //

        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchant->getId());

        if (($merchantDetail !== null) and
            (empty($merchant->getCategory()) === false) and
            (Detail\BusinessType::isUnregisteredBusiness($merchantDetail->getBusinessType()) === true))
        {
           $amount = $merchant->getMaxPaymentAmountDefaultForUnregistered();

            //
            // Mcc can have values other then predefined values
            // for those cases we should return default values
            //
            if (BusinessSubCategoryMetaData::isMccPresentInPredefinedList($merchant->getCategory()) === false)
            {
                $this->trace->count(Metric::UNREGISTERED_BUSINESS_DEFAULT_LIMIT_USED_TOTAL);

                return $amount;
            }

            if ($merchant->getCountry() === "IN")
            {
            $amount = BusinessSubCategoryMetaData::getFeatureValueUsingMccCode(
                BusinessSubCategoryMetaData::NON_REGISTERED_MAX_PAYABLE_AMOUNT,
                $merchant->getCategory(),
                $amount);
            }
        }
        else
        {
            $amount = $merchant->getMaxPaymentAmountDefault();
        }

        return (int) $amount;
    }

    public function getPaymentTimeoutWindow(Entity $merchant)
    {
        $accessor = Accessor::for($merchant, Settings\Module::MERCHANT);

        if ($accessor->exists(Merchant\Constants::PAYMENT_TIMEOUT_WINDOW) === false)
        {
            return null;
        }

        return (int) ($accessor->get(Merchant\Constants::PAYMENT_TIMEOUT_WINDOW));
    }

    /**
     * Activates/Deactivates (international) as applicable
     *
     * We allow changing business type between L1 and L2 form .
     * As international activation flow is function of business type,
     * So disable international if  not allowed
     *
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function updateInternationalIfApplicable(Entity $merchant, Detail\Entity $merchantDetails)
    {
        $shouldActivateInternational = $this->shouldActivateInternational($merchant, $merchantDetails);

        if ($shouldActivateInternational === true)
        {
            (new Detail\InternationalCore())->activateInternational($merchant);
        }
        elseif ($merchant->isInternational() === true)
        {
            (new Detail\InternationalCore())->deactivateInternational($merchant);
        }

    }

    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function updateInternationalTypeform(Entity $merchant,
                                                Detail\Entity $merchantDetails)
    {
        $this->shouldActivateProductInternational($merchant, $merchantDetails);

        (new Detail\InternationalCore())->activateInternational($merchant);

    }


    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function shouldActivateProductInternational(Entity $merchant, Detail\Entity $merchantDetails)
    {
        $canBeInternationallyEnabled = false;

        $internationalAuthFlow = $this->app['basicauth']->isAdminAuth() === true ?
            ProductInternationalMapper::ADMIN_FLOW :
            ProductInternationalMapper::MERCHANT_FLOW;

        switch ($internationalAuthFlow)
        {

            case ProductInternationalMapper::MERCHANT_FLOW:

                $activationFlowImpl = InternationalActivationFlow\Factory::getActivationFlowImpl($merchant);

                $canBeInternationallyEnabled =
                    ($activationFlowImpl->shouldActivateTypeformInternational() === true) and
                ($this->checkInternationalEnablementPreconditions($merchant, $merchantDetails) === true);

                break;

            case ProductInternationalMapper::ADMIN_FLOW:

                if ($merchant->isRazorpayOrgId() === true)
                {
                    $activationFlowImpl = InternationalActivationFlow\Factory::getActivationFlowImpl($merchant);

                    $canBeInternationallyEnabled =
                        ($activationFlowImpl->shouldActivateTypeformInternational() === true) and
                    ($this->checkInternationalEnablingAdminFlowPreconditions($merchant, $merchantDetails) === true);
                }
                else
                {
                    $canBeInternationallyEnabled =
                        ($this->checkInternationalEnablingAdminFlowPreconditions($merchant, $merchantDetails) === true);
                }
                break;
        }

        if ($canBeInternationallyEnabled === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRODUCT_INTERNATIONAL_CANT_BE_ENABLED);
        }
    }

    /**
     * Here we are taking mode as input parameter instead of using $this->mode because
     * for webhook jobs we do not take mode as constructor argument and mode has to be passed for cases functionality depends on mode
     *
     * @param Entity $merchant
     * @param string $payload
     * @param string $mode
     *
     * @return array
     */
    public function translateWebhookPayloadIfApplicable(Entity $merchant, string $payload, string $mode): array
    {
        $partners = $this->fetchAffiliatedPartners($merchant->getId());

        // submerchant can belong to only one aggregator or fully managed at a time
        $partner = $partners->filter(function(Entity $partner)
        {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if (empty($partner) === true)
        {
            return [
                'headers' => [],
                'content' => $payload,
            ];
        }

        $translationGateway = $this->getTranslateWebhookGateway($partner);

        if (empty($translationGateway) === true)
        {
            return [
                'headers' => [],
                'content' => $payload,
            ];
        }

        $this->trace->info(TraceCode::PARTNER_WEBHOOK_TRANSLATION,
                           [
                               'translation_gateway' => $translationGateway,
                               'partner_id'          => $partner->getId(),
                           ]);

        try
        {
            return $this->app['mozart']->translateWebhook($translationGateway, $payload, $mode);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e);

            throw $e;
        }
    }

    protected function getTranslateWebhookGateway(Entity $partner)
    {
        return (new Settings\Service)->getForMerchant(Constants::PARTNER, Constants::TRANSLATE_WEBHOOK_GATEWAY, $partner);
    }

    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    protected function shouldActivateInternational(Entity $merchant, Detail\Entity $merchantDetails): bool
    {
        $generalInternationalEnablement = $this->checkInternationalEnablementPreconditions($merchant, $merchantDetails);

        if ($generalInternationalEnablement === false)
        {
            return false;
        }
        //
        // $activationFlowImpl will be an instance of the ActivationFlowInterface
        //
        $activationFlowImpl = InternationalActivationFlow\Factory::getActivationFlowImpl($merchant);

        return $activationFlowImpl->shouldActivateInternational();
    }

    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function checkInternationalEnablementPreconditions(Entity $merchant, Detail\Entity $merchantDetails): bool
    {
        $autoEnableInternational = $this->autoEnableInternational($merchant, $merchantDetails);

        if ($autoEnableInternational === false)
        {
            return false;
        }

        return $this->checkInternationalEnablingAdminFlowPreconditions($merchant, $merchantDetails);
    }

    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     * @throws Exception\BadRequestValidationFailureException
     */
    public function checkInternationalEnablingAdminFlowPreconditions(Entity $merchant,
                                                                     Detail\Entity $merchantDetails): bool
    {
        // Enable international for merchant if
        // 1) Merchant has a valid website
        // 2) If international activation flow is set for Razorpay Org merchants
        // For Non Razorpay org merchants, the international activation flow can be null.

        if (($this->validateWebsiteCheckForInternationalActivation($merchant, $merchantDetails) === false) or
            (empty($merchantDetails->getInternationalActivationFlow()) === true and
             $merchant->isRazorpayOrgId() === true ))
        {
            return false;
        }

        $plan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($merchant->getPricingPlanId());

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        return true;
    }

    public function validateWebsiteCheckForInternationalActivation(Entity $merchant, Detail\Entity $merchantDetails): bool
    {
        // If business_website is empty, then don't allow international by default
        $businessWebsite = $merchant->getWebsite() ?? $merchantDetails->getWebsite();

        if (empty($businessWebsite) === false)
        {
            return true;
        }

        $partners = $this->fetchAffiliatedPartners($merchant->getId());

        // Filter partners whose feature (SKIP_WEBSITE_INTERNAT) is present.
        $partner = $partners->filter(function(Entity $partner) {
            return ($partner->skipWebsiteForInternational() === true);

        })->first();

        //
        // If partner is not present and businessWebsite is absent
        // then we do not allow international Activation.
        //
        if ($partner === null)
        {
            return false;
        }

        return true;
    }

    /**
     * Auto Enable International for merchant if
     *  1) Merchant belongs to Razorpay org Or
     *  2) Merchant belongs to Razorpay org and Merchant is not in unregistered business onBoarding flow
     *  3) If Submerchant is getting activated using a submerchant batch and if the submerchant
     *     batch parameters define to not auto-enable international attribute, false will be returned.
     *
     * @param Entity        $merchant
     *
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     */
    public function autoEnableInternational(Entity $merchant, Detail\Entity $merchantDetails): bool
    {
        $isRazorpayOrg = $merchant->isRazorpayOrgId();

        if (($isRazorpayOrg === false) or
            ($this->isUnRegisteredOnBoardingEnabled($merchant, $merchantDetails->isUnregisteredBusiness()) === true))
        {
            return false;
        }

        //
        // SubMerchant batch upload flow defines a way to disable the auto-enabling international feature
        // If the submerchant is getting activated using a submerchant batch and if the submerchant
        // batch parameters define to not auto-enable international attribute, false will be returned.
        //
        $isBatchFlow = (app('basicauth')->isBatchFlow() === true);

        if ($isBatchFlow === true)
        {
            $batchContext = app('basicauth')->getBatchContext();

            $batchName               = $batchContext['type'] ?? null;
            $autoEnableInternational = $batchContext['data'][Merchant\Entity::AUTO_ENABLE_INTERNATIONAL] ?? false;

            return ($batchName === Batch\Type::SUB_MERCHANT)
                   and ($autoEnableInternational === true);
        }

        return true;
    }

    /*
     * Returns the partner merchant from the partner app entity passed as an argument
     *
     * @param $partnerApp
     *
     * @return null
     */
    public function getPartnerFromApp($partnerApp)
    {
        $partnerId = $partnerApp->getMerchantId();

        $partner = $this->repo->merchant->find($partnerId);

        return $partner;
    }

    /**
     * Toogles international flag on the merchant if appilicable
     *
     * @param Entity    $merchant
     * @param bool      $toggleValue
     *
     * @return Entity
     */
    public function toggleInternational(Entity $merchant, bool $toggleValue): Entity
    {
        if ($toggleValue === true)
        {
            $this->internationalEnable($merchant);
        }
        else
        {
            $this->internationalDisable($merchant);
        }

        return $merchant;
    }

    public function getPartnerBankAccountIdsForSubmerchants(array $merchantIds): array
    {
        $merchants = $this->repo->merchant->getAllPartnerBankAccountsForSubmerchants($merchantIds);

        $submerchants = [];

        // Attributes
        $partnerBankAccountId         = 'partner_bank_account_id';
        $partnerConfigOriginId        = 'partner_config_origin_id';
        $partnerConfigSettleToPartner = 'partner_config_settle_to_partner';

        foreach ($merchants as $merchant)
        {
            $merchantId = $merchant->getId();

            if (array_key_exists($merchantId, $submerchants) === true)
            {
                if (empty($merchant->getAttribute($partnerConfigOriginId)) === false)
                {
                    // App config was applied to the map but now we have a submerchant config, so unset the app config
                    unset($submerchants[$merchantId]);
                }
                else
                {
                    // Submerchant config has been applied to the map. Do nothing for the app config
                    continue;
                }
            }

            $submerchants[$merchantId] = [
                $partnerBankAccountId         => $merchant->getAttribute($partnerBankAccountId),
                $partnerConfigSettleToPartner => (bool) $merchant->getAttribute($partnerConfigSettleToPartner),
            ];
        }

        $merchantIdToPartnerBankAccountMap = [];

        foreach ($submerchants as $merchantId => $merchantObj)
        {
            if ($merchantObj[$partnerConfigSettleToPartner] === true)
            {
                $merchantIdToPartnerBankAccountMap[$merchantId] = $merchantObj[$partnerBankAccountId];
            }
        }

        $this->trace->info(TraceCode::PARTNER_BANK_ACCOUNT_MAP, $merchantIdToPartnerBankAccountMap);

        return $merchantIdToPartnerBankAccountMap;
    }

    /**
     * @param Entity $merchant
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    protected function internationalEnable(Entity $merchant)
    {
        (new Validator())->validateBeforeEnablingInternationalByMerchant($merchant);

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        $this->updateInternationalTypeform($merchant, $merchantDetails);

        $this->repo->saveOrFail($merchant);
    }

    protected function internationalDisable(Entity $merchant)
    {
        if ($merchant->isInternational() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED);
        }

        (new Detail\InternationalCore())->deactivateInternational($merchant);

        $this->repo->saveOrFail($merchant);
    }

    /**
     *  Restricted Merchant will have all its users associated to only itself.
     *  If Restricted cannot be applied, will return userIds which are associated
     *  with more than one merchant.
     *
     * @param Entity $merchant
     *
     * @return array
     */
    protected function getMerchantUsersWithMultipleMerchants(Entity $merchant): array
    {
        $users = $merchant->users()
                          ->get();

        $userAssociatedWithMoreMerchants = [];

        foreach ($users as $user)
        {
            $merchantIds = $user->merchants()->distinct()->get()->pluck(Entity::ID)->toArray();

            if (count($merchantIds) !== 1)
            {
                $userAssociatedWithMoreMerchants[] = $user->getId();
            }
        }

        return $userAssociatedWithMoreMerchants;
    }

    /**
     * This method does remove/apply restricted settings.
     *
     * @param Entity $merchant
     * @param string $action
     *
     * @return array
     */
    public function applyRestrictedSettings(Entity $merchant, string $action): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_RESTRICTED_SETTINGS,
            [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::ACTION      => $action,
                'admin_id'          => $this->app['basicauth']->getAdmin()->getId(),
            ]);

        if ($action === Constants::REMOVE)
        {
            return $this->addRestrictedSettingsToMerchant($merchant, false);
        }
        else
        {
            $userIds = $this->getMerchantUsersWithMultipleMerchants($merchant);

            // Will add restricted settings only if all users
            // are associated with one merchant itself.

            if (count($userIds) === 0)
            {
                return $this->addRestrictedSettingsToMerchant($merchant, true);
            }

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_RESTRICTED_SETTINGS_NOT_APPLIED,
                                                    null,
                                                    [
                                                        'count_users_with_multiple_merchants' => count($userIds),
                                                        'users_with_mutiple_merchants'        => $userIds,
                                                    ]);
        }
    }

    public function addMerchantEmailToMailingList($merchant , $lists = [Constants::LIVE], $emailsToAdd = [], $iterationNumber = 0)
    {
        $merchantEmailList = [];

        if(empty($emailsToAdd) === true)
        {
            $emailsToAdd = $merchant->getTransactionReportEmail();

            $emailsToAdd = array_merge($emailsToAdd, [$merchant->getEmail()]);
        }

        foreach ($emailsToAdd as $transactionReportEmail)
        {
            if (isset($merchantEmailList[$transactionReportEmail]) === false)
            {
                $merchantEmailList[$transactionReportEmail] = [
                    'address' => $transactionReportEmail,
                    'name'    => $merchant->getName()
                ];
            }
        }

        if((in_array(Constants::LIVE_SETTLEMENT_DEFAULT, $lists) === false) &&
           (in_array(Constants::LIVE_SETTLEMENT_ON_DEMAND, $lists) === false))
        {
            if($merchant->isfeatureEnabled(Feature\Constants::ES_ON_DEMAND) === false)
            {
                array_push($lists, Constants::LIVE, Constants::LIVE_SETTLEMENT_DEFAULT);
            }
            else
            {
                array_push($lists, Constants::LIVE, Constants::LIVE_SETTLEMENT_ON_DEMAND);
            }
        }

        $merchantEmailList = array_values($merchantEmailList);

        foreach ($lists as $list)
        {
            MailingListUpdate::dispatch(
                $this->mode,
                $merchantEmailList,
                true,
                $list)
                             ->delay($iterationNumber % 901);
        }
    }

    public function removeMerchantEmailToMailingList($merchant, $lists = [], $emailsToRemove = [], $iterationNumber = 0)
    {
        if(empty($emailsToRemove) === true)
        {
            $emailsToRemove = $merchant->getTransactionReportEmail();

            $emailsToRemove = array_merge($emailsToRemove, [$merchant->getEmail()]);

            $emailsToRemove = array_unique($emailsToRemove);
        }

        if(empty($lists) === true)
        {
            if($merchant->isfeatureEnabled(Feature\Constants::ES_ON_DEMAND) === false)
            {
                $lists = [Constants::LIVE, Constants::LIVE_SETTLEMENT_DEFAULT];
            }
            else
            {
                $lists = [Constants::LIVE, Constants::LIVE_SETTLEMENT_ON_DEMAND];
            }
        }

        foreach ($emailsToRemove as $transactionReportEmail)
        {
            foreach ($lists as $list)
            {
                MailingListUpdate::dispatch(
                    $this->mode,
                    [$transactionReportEmail],
                    false,
                    $list)
                                 ->delay($iterationNumber % 901);
            }
        }
    }

    protected function addRestrictedSettingsToMerchant(Entity $merchant, bool $action)
    {
        $merchant->setAttribute(Entity::RESTRICTED, $action);

        $this->repo->saveOrFail($merchant);

        return [
            Entity::MERCHANT_ID => $merchant->getId(),
            Entity::RESTRICTED  => $merchant->getRestricted(),
        ];
    }

    /**
     * Enable unregistered on-Boarding only for
     *
     * 1. If merchant belongs to Razorpay org Id
     * 2. if operation is being performed from banking dashboard
     * 3. if UNREGISTERED_ON_BOARDING razorx experiment is enabled for mid or merchant is already activated
     *
     *
     * @param Entity $merchant
     * @param bool   $isUnregisteredBusiness
     * @param null   $mode
     *
     * @return bool
     */
    public function isUnRegisteredOnBoardingEnabled(Entity $merchant, bool $isUnregisteredBusiness, $mode = null): bool
    {
        $isRazorpayOrgId = $merchant->isRazorpayOrgId();

        return (($isRazorpayOrgId === true) and
                ($this->app['basicauth']->getRequestOriginProduct() === Product::PRIMARY) and
                ($isUnregisteredBusiness === true));
    }

    public function isAutoKycEnabled(Detail\Entity $merchantDetails, Entity $merchant): bool
    {
        $isRazorpayOrgId = $merchant->isRazorpayOrgId();

        // if merchant belongs to some different org then don't to auto kyc
        if ($isRazorpayOrgId === false)
        {
            return false;
        }
        // if merchant belongs to unregistered business , request is coming from dashboard and belongs to razorpay org
        if ($this->isUnRegisteredOnBoardingEnabled($merchant, $merchantDetails->isUnregisteredBusiness()) === true)
        {
            return true;
        }

        // for other unregistered business don't do auto kyc
        if ($merchantDetails->isUnregisteredBusiness() === true)
        {
            return false;
        }

        $parentMerchant = $merchant->parent;

        //
        // In case of linked accounts, if KYC is required at parent merchant level or the `route_no_doc_kyc`
        // feature flag is assigned to the parent merchant, that implies auto KYC is enabled.
        //
        if (empty($parentMerchant) === false)
        {
            if (($parentMerchant->linkedAccountsRequireKyc() === true) or
                ($parentMerchant->isRouteNoDocKycEnabled() === true))
            {
                return true;
            }
            return false;
        }

        // if kyc is handled my partner then don't do auto kyc
        if ((new Partner\Core)->isKycHandledBYPartner($merchant) === true)
        {
            return false;
        }

        return true;
    }

    public function getAllMerchantsMappedToMerchantLegalEntity(Merchant\Entity $merchant): Base\PublicCollection
    {
        $legalEntityId = $merchant->getLegalEntityId();

        if (empty($legalEntityId) === false)
        {
            $legalEntity = $this->repo->legal_entity->findOrFailPublic($legalEntityId);

            return $legalEntity->merchants;
        }

        return new Base\PublicCollection;
    }

    public function getAllMerchantIds($input): Base\PublicCollection
    {
        return $this->repo->merchant->fetchAllMerchantIDs($input);
    }
    /**
     * @return array
     */
    public function getBatchActionEntities(): array
    {
        $batchActionEntities = BatchActionEntity::BATCH_ACTION_ENTITIES;

        return $batchActionEntities;
    }

    /**
     * @return array
     */
    public function getBatchActions(): array
    {
        $batchActions = BatchAction::BATCH_ACTIONS;

        return $batchActions;
    }

    public function requestInternationalProduct(array $input)
    {
        $productInternationalField = new Merchant\ProductInternational\ProductInternationalField($this->merchant);

        $this->trace->info(
            TraceCode::PRODUCT_INTERNATIONAL_REQUESTED,
            ['input' => $input]
        );

        foreach ($input['products'] as $requestedProduct)
        {
            $productInternationalField->requestEnablement($requestedProduct);
        }

        $this->repo->merchant->saveOrFail($this->merchant);

        return $this->merchant;
    }

    public function isEmailVerificationViaOtpRazorxEnabled(string $merchantId, $mode = null): bool
    {
        $mode = $mode ?? $this->mode;

        $status = $this->app['razorx']->getTreatment($merchantId, Merchant\RazorxTreatment::EMAIL_VERIFICATION_USING_OTP, $mode);

        return (strtolower($status) === 'on');
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function fetchProductWiseWorkflowStatusV2($permissionName, $productRequested, Entity $merchant): array
    {
        $productWiseWorkflowStatus = [];

        $productInternationalField = new Merchant\ProductInternational\ProductInternationalField($merchant);

        $productInternational = $merchant->getProductInternational();

        $productNames = Merchant\ProductInternational\ProductInternationalMapper::LIVE_PRODUCTS;

        // Iterating over each product and checking if product is approved or not ,
        // if not approved then we are checking whether the product is requested product
        // if it is then we are fetching the last workflow status raised for this product
        // else we are showing no action received.

        foreach ($productNames as $productName)
        {
            $isApproved = ($productInternationalField->getProductStatus($productName, $productInternational)
                           === Merchant\ProductInternational\ProductInternationalMapper::ENABLED);

            if ($isApproved === true)
            {
                $productWiseWorkflowStatus[$productName] = Constants::APPROVED;
            }
            else if (in_array($productName, $productRequested, true) === true)
            {
                $productWiseWorkflowStatus[$productName] = $this->getMerchantWorkflowStatus($permissionName, $merchant);
            }
            else
            {
                $productWiseWorkflowStatus[$productName] = Constants::NO_ACTION_RECEIVED;
            }
        }
        return $productWiseWorkflowStatus;
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws BadRequestException
     */
    public function getProductInternationalStatusV2(Entity $merchant): array
    {
        $workflowActions = ((new WorkflowAction\Core()))->fetchLastUpdatedWorkflowActionInPermissionList(
            $merchant->getId(), TypeformConstant::MERCHANT_KEY, [Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED]);

        $productRequested = [];

        if (is_null($workflowActions) === false)
        {
            $productRequested = (new TypeformCore)->getProductNamesFromActionEntityData($workflowActions);
        }

        $productWiseWorkflowStatus = $this->fetchProductWiseWorkflowStatusV2(Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED, $productRequested, $merchant);

        return $productWiseWorkflowStatus;
    }

    /**
     * @param Entity $merchant
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getProductInternationalStatus(Entity $merchant, array $input): array
    {
        $response = [];

        if (((isset($input['version'])) === true) and
            ($input['version'] === 'v2'))
        {
            $response = $this->getProductInternationalStatusV2($merchant);

            return $response;
        }
        else
        {
            $workflowsNotExistCount = 0;

            $internationalWorkflowList = Constants::INTERNATIONAL_WORKFLOW_LIST;

            $permissionProductCategories =
                array_flip(Merchant\ProductInternational\ProductInternationalMapper::PRODUCT_PERMISSION);

            foreach ($internationalWorkflowList as $workflowType)
            {
                $permission = Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION];

                $productCategory = $permissionProductCategories[$permission];

                $productNames =
                    Merchant\ProductInternational\ProductInternationalMapper::PRODUCT_CATEGORIES[$productCategory];

                $productWiseWorkflowStatus = $this->fetchProductWiseWorkflowStatus($productNames, $workflowType, $merchant);

                if (empty($productWiseWorkflowStatus) === false)
                {
                    if (array_values($productWiseWorkflowStatus)[0] === Constants::NO_ACTION_RECEIVED)
                    {
                        $workflowsNotExistCount += 1;
                    }
                    $response = array_merge($response, $productWiseWorkflowStatus);
                }
            }

            if ($workflowsNotExistCount === count($permissionProductCategories))
            {
                $response = $this->handleOldWorkflows($response, $merchant);
            }
        }

        return $response;
    }

    /**
     * @param $currentResponse
     * @param $merchant
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    private function handleOldWorkflows(array $currentResponse, Entity $merchant): array
    {
        $oldWorkflowStatus1 = $this->getMerchantWorkflowStatus(Constants::OLD_ENABLE_INTERNATIONAL, $merchant);

        $oldWorkflowStatus2 = $this->getMerchantWorkflowStatus(Constants::ENABLE_INTERNATIONAL, $merchant);

        $actualOldWorkflowStatus = ($oldWorkflowStatus1 === Constants::NO_ACTION_RECEIVED) === false ?
            $oldWorkflowStatus1:
            $oldWorkflowStatus2;

        $currentResponse = array_fill_keys(array_keys($currentResponse), $actualOldWorkflowStatus);

        return $currentResponse;
    }

    /**
     * @param $productNames
     * @param $workflowType
     * @param $merchant
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function fetchProductWiseWorkflowStatus(array $productNames, string $workflowType, Entity $merchant): array
    {
        $productInternationalField = new Merchant\ProductInternational\ProductInternationalField($merchant);

        $productInternational = $merchant->getProductInternational();

        $productWiseWorkflowStatus = [];

        foreach ($productNames as $productName)
        {
            $isApproved = ($productInternationalField->getProductStatus($productName, $productInternational)
                           === Merchant\ProductInternational\ProductInternationalMapper::ENABLED);

            $productWiseWorkflowStatus[$productName] =
                ($isApproved === true) ? Constants::APPROVED :
                    $this->getMerchantWorkflowStatus($workflowType, $merchant);
        }

        return $productWiseWorkflowStatus;
    }

    /**
     * @param string $workflowType
     * @param Entity $merchant
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function fetchWorkflowData(string $workflowType, Entity $merchant)
    {
        (new Validator())->validateMerchantWorkflowType($workflowType);

        [$entityId, $entity] = $this->getWorkflowMetaData
        (Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::ENTITY], $merchant);

        return [$entityId, $entity];
    }

    /**
     * @param string $workflowType
     * @param Entity $merchant
     *
     * @return string
     * @throws Exception\BadRequestValidationFailureException
     */
    public function getMerchantWorkflowStatus(string $workflowType, Entity $merchant): string
    {
        [$entityId, $entity] = $this->fetchWorkflowData($workflowType, $merchant);

        $state = (new WorkflowAction\Core())->fetchActionStatus(
            $entityId,
            $entity,
            Constants::MERCHANT_WORKFLOWS[$workflowType][Constants::PERMISSION]);

        switch ($state)
        {
            case false :
                return Constants::NO_ACTION_RECEIVED;

            case WorkflowAction\State\Entity::EXECUTED :
                return Constants::APPROVED;

            case WorkflowAction\State\Entity::CLOSED :
            case WorkflowAction\State\Entity::REJECTED:
                return WorkflowAction\State\Entity::REJECTED;

            default:
                return Constants::IN_REVIEW;
        }
    }

    /**
     * @param string $entity
     * @param Entity $merchant
     *
     * @return array
     */
    public function getWorkflowMetaData(string $entity, Entity $merchant)
    {
        switch ($entity)
        {
            case (CE::MERCHANT_DETAIL):

                $merchantDetail = $merchant->merchantDetail;

                return [$merchantDetail->getMerchantId(), $merchantDetail->getEntity()];

            case (CE::BANK_ACCOUNT):

                $bankAccount = $this->repo->bank_account->getBankAccount($merchant);

                if ($bankAccount === null)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
                }

                return [$bankAccount->getId(), $bankAccount->getEntity()];

            default:

                return [$merchant->getId(), $merchant->getEntity()];
        }
    }

    /**
     * @param $action
     * @param $merchant
     * @param $internationalProducts
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    private function handleInternationalAction($action, $merchant, $internationalProducts)
    {
        if ($action === \RZP\Models\Merchant\Action::ENABLE_INTERNATIONAL)
        {
            (new Validator)->validateEnableProductInternational($internationalProducts);

            $this->enableProductInternational($internationalProducts, $merchant);
        }
        elseif ($action === \RZP\Models\Merchant\Action::DISABLE_INTERNATIONAL)
        {
            $this->disableProductInternationalAction($merchant, $internationalProducts);
        }
    }

    /**
     * @param array  $internationalProducts
     * @param Entity $merchant
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    public function enableProductInternational(array $internationalProducts, Entity $merchant)
    {
        //if merchant is non org merchant then enable international for all the live products
        // irrespective of the input given.
        if ($merchant->isRazorpayOrgId() === false)
        {
            $internationalProducts = ProductInternationalMapper::LIVE_PRODUCTS;
        }
        else
        {
            $internationalProducts =
                ProductInternationalField::updateProductNamesThroughCategory($internationalProducts);
        }

        $productNameStatus = array_fill_keys($internationalProducts, ProductInternationalMapper::ENABLED);

        $productInternationalField = new ProductInternationalField($merchant);

        $productInternationalField->setMultipleProductStatus($productNameStatus);

        (new Detail\Core())->adminUpdateInternationalActivationFlow(
            $merchant, Constants::$internationalActionMapping[\RZP\Models\Merchant\Action::ENABLE_INTERNATIONAL]);
    }

    /**
     * @param Entity     $merchant
     * @param array|null $internationalProducts
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     *
     * In the admin flow, when international is disabled, then product_international
     * field is also disabled unlike merchant flow.
     * THIS APPROACH IS STRICTLY FOR ADMIN FLOW
     */
    private function disableProductInternationalAction(Entity $merchant, array $internationalProducts = null)
    {
        if (empty($internationalProducts) === true)
        {
            $internationalProducts = ProductInternationalMapper::LIVE_PRODUCTS;
        }
        else
        {
            $internationalProducts =
                ProductInternationalField::updateProductNamesThroughCategory($internationalProducts);
        }

        $productNameStatus = array_fill_keys($internationalProducts, ProductInternationalMapper::DISABLED);

        $productInternationalField = new ProductInternationalField($merchant);

        $productInternationalField->setMultipleProductStatus($productNameStatus);

        (new Detail\Core())->adminUpdateInternationalActivationFlow(
            $merchant,
            Constants::$internationalActionMapping[\RZP\Models\Merchant\Action::DISABLE_INTERNATIONAL]);
    }

    public function submerchantLink(Entity $partner, Entity $submerchant)
    {
        $output = $this->createPartnerSubmerchantAccessMap($partner, $submerchant);
        $data = [
            'status'       => 'success',
            'merchant_id'  => $submerchant->getId(),
            'partner_id'   => $partner->getId(),
            'source'       => PartnerConstants::BULK_LINKING_ADMIN
        ];

        $this->app['diag']->trackOnboardingEvent(EventCode::PARTNERSHIP_SUBMERCHANT_SIGNUP,
                                                 $partner, null,
                                                 $data);

        if ($partner->isFeatureEnabled(FeatureConstants::SKIP_SUBM_ONBOARDING_COMM) === true)
        {
            $this->app->hubspot->skipMerchantOnboardingComm($submerchant->getEmail());
        }

        $this->app->hubspot->trackSubmerchantSignUp($partner->getEmail());

        $dimension = [
            'partner_type' => $partner->getPartnerType(),
            'source'       => PartnerConstants::BULK_LINKING_ADMIN
        ];

        $this->trace->count(Partner\Metric::SUBMERCHANT_CREATE_TOTAL, $dimension);

        return $output;
    }

    public function submerchantDelink(Entity $partner, Entity $submerchant)
    {
        $appType = (new MerchantApplications\Core())->getDefaultAppTypeForPartner($partner);

        $isMapped = (new AccessMap\Core())->isMerchantMappedToPartnerWithAppType($partner, $submerchant, $appType);

        if ($isMapped === true)
        {
            $this->trace->info(TraceCode::SUBMERCHANT_DELINK_REQUEST_BATCH,
                               [
                                   "partner_id"     => $partner->getId(),
                                   "submerchant_id" => $submerchant->getId()
                               ]);

            return $this->deletePartnerAccessMap($partner, $submerchant);

        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND,
                [
                    Entity::PARTNER_ID  => $partner->getId(),
                    Entity::MERCHANT_ID => $submerchant->getId(),
                ]
            );
        }
    }

    public function submerchantTypeUpdate(Entity $partner, Entity $submerchant) {
        $input = [];
        $input['from_app_type'] = MerchantApplications\Entity::REFERRED;
        $input['to_app_type'] = MerchantApplications\Entity::MANAGED;
        $this->updatePartnerAccessMap($input, $partner, $submerchant);
    }

    protected function validateCodeIfPresent(array $input, Entity $parentMerchant, bool $isLinkedAccount)
    {
        if (isset($input[Entity::CODE]) === true)
        {
            $this->validateCode($input[Entity::CODE], $parentMerchant, $isLinkedAccount);
        }
    }

    protected function validateCode(string $code, Entity $parentMerchant, bool $isLinkedAccount)
    {
        $parentMerchant->getValidator()->validateCode(Entity::CODE, $code);

        if ($this->isCodeAlreadyInUse($code, $parentMerchant) === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_CODE_ALREADY_USED,
                Entity::CODE,
                $code,
                'This code is already in use, please try another.'
            );
        }
    }

    public function checkRouteCodeFeature(Entity $merchant)
    {
        if ($merchant->isRouteCodeEnabled() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_CODE_NOT_ENABLED,
                Entity::CODE,
                null,
                'code is not allowed for this merchant.'
            );
        }
    }

    protected function isCodeAlreadyInUse(string $code, Entity $parentMerchant) : bool
    {
        $count = $this->repo->merchant->countAccountCodeForMerchant($code, $parentMerchant->getId());

        return ($count !== 0);
    }

    /**
     * Checks if razorx experiment is enabled for the merchant
     *
     * @param string $merchantId
     * @param string $experimentName
     *
     * @return bool
     */
    public function isRazorxExperimentEnable(string $merchantId, string $experimentName): bool
    {
        $mode = $this->mode ?? Mode::LIVE;

        $variant = $this->app->razorx->getTreatment($merchantId,
                                                    $experimentName,
                                                    $mode,
                                                    2);

        return ($variant === Constants::RAZORX_EXPERIMENT_ON);
    }

    public function triggerMerchantBankingAccountsWebhook(Entity $merchant)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $merchant,
        ];

        return $this->app['events']->dispatch('api.banking_accounts.issued', $eventPayload);
    }


    public function isOrgCustomBranding(Entity $merchant) : bool
    {
        if ($merchant->isRazorpayOrgId() === true )
        {
            return false;
        }

        return $this->isOrgFeatureEnabled($merchant, Feature\Constants::ORG_CUSTOM_BRANDING);
    }

    public function isDisableFreeCreditsFeatureEnabled(Entity $merchant, string $featureName)
    {
        return $this->isOrgFeatureEnabled($merchant, $featureName);
    }

    public function isShowLateAuthAttributeFeatureEnabled(Entity $merchant) : bool
    {
        if ($merchant->isRazorpayOrgId() === true )
        {
            return false;
        }

        return $this->isOrgFeatureEnabled($merchant, Feature\Constants::SHOW_LATE_AUTH_ATTRIBUTES);
    }

    public function isShowRefundTypeParamFeatureEnabled(Entity $merchant) : bool
    {
        if ($merchant->isRazorpayOrgId() === true )
        {
            return false;
        }

        return $this->isOrgFeatureEnabled($merchant, Feature\Constants::SHOW_REFND_LATEAUTH_PARAM);
    }

    public function isShowReceiverTypeFeatureEnabled(Entity $merchant) : bool
    {
        if ($merchant->isRazorpayOrgId() === true )
        {
            return false;
        }

        return $this->isOrgFeatureEnabled($merchant, Feature\Constants::SHOW_PAYMENT_RECEIVER_TYPE);
    }

    protected function isOrgFeatureEnabled(Entity $merchant, string $featureName)
    {
        $org = $this->repo->org->find($merchant->getOrgId());

        return $org->isFeatureEnabled($featureName);
    }

    /**
     * fetches the product whether used or not used by a merchant for given merchant ids and product
     *
     * @param array $merchantIds
     * @param string|null $product
     * @param bool $isUsed
     * @param null $limit
     *
     * @return array
     */
    public function fetchProductForMerchants(array $merchantIds, string $product = null, bool $isUsed = true, $limit = null): array
    {
        $merchantsAndProducts = $this->repo->merchant_user->fetchProductUsedForMerchantIds($merchantIds, $product, $limit);

        if ($product !== null and $isUsed === false)
        {
            $merchantsUsingProduct = $merchantsAndProducts->pluck(Entity::MERCHANT_ID)->toArray();

            $merchantsNotUsingProduct = array_diff($merchantIds, $merchantsUsingProduct);

            return $merchantsNotUsingProduct;
        }

        $productUsedByMerchants = array();

        // if the product is passed in the input param then response format is {merchant_id1, merchant_id2, ..}
        // and if product is not passed then the response format is {merchant_id, [product1, product2, ..]}
        foreach ($merchantsAndProducts as $merchantAndProduct)
        {
            $merchantId = $merchantAndProduct[Entity::MERCHANT_ID];
            $productUsed = $merchantAndProduct[Entity::PRODUCT];

            // if the product is not passed than create a mapping of merchant id and products used by it in the merchantProducts array
            // if the product is passed than simply store the merchant ids using that product
            if (empty($product) === true)
            {
                if (empty($productUsedByMerchants[$merchantId]) === true)
                {
                    $productUsedByMerchants[$merchantId] = array();
                }

                array_push($productUsedByMerchants[$merchantId], $productUsed);
            }
            else
            {
                array_push($productUsedByMerchants, $merchantId);
            }
        }

        return $productUsedByMerchants;
    }

    /**
     * This method fetches sub-merchants for a partner and applies
     * product filter on the fetched result set.
     * Why two separate call? To avoid a direct join on the two result sets,
     * in future we may want to move one/both of repository calls to api.
     *
     * @param array $params
     * @param array $appIds
     * @param string $partnerId
     *
     * @return array
     */
    public function filterSubmerchantsOnProduct(array $params, array $appIds, string $partnerId): array
    {
        $skip = $params['skip'] ?? 0;

        $count = $params['count'] ?? 25;

        $product = $params[ENTITY::PRODUCT];

        $isUsed = boolval($params[Constants::IS_USED] ?? 1);

        unset($params[ENTITY::PRODUCT]);

        unset($params[Constants::IS_USED]);

        $recordsToTake = $count;

        $result = new PublicCollection();

        // fetch sub-merchants from the app ids and further filter them on product
        // Do this until the desired number of records are fetched or
        // no further records are available to fetch
        do {
            $merchants = $this->repo->merchant->fetchSubmerchantsByAppIds($appIds, $params);

            if (count($merchants) == 0 || $recordsToTake == 0)
            {
                break;
            }

            $merchantIds = $merchants->getIds();

            $filteredMerchantIds = $this->fetchProductForMerchants($merchantIds, $product, $isUsed);

            $recordsRead = 0;

            foreach ($merchants as $merchant)
            {
                if ($recordsToTake == 0)
                {
                    break;
                }

                if ((in_array($merchant->getId(), $filteredMerchantIds) === true) && $recordsToTake > 0)
                {
                    $result->push($merchant);

                    $recordsToTake--;
                }
                $recordsRead++;
            }

            $skip += $recordsRead;

            $params['skip'] = $skip;

            $this->trace->debug(TraceCode::PARTNER_FETCH_SUBMERCHANTS_FILTER, [
                'partnerId'                 => $partnerId,
                'params'                    => $params,
                'product'                   => $product,
                'recordsFromApplicationIds' => $merchants->count(),
                'recordsAfterProductFilter' => count($filteredMerchantIds),
                'recordsTakenSoFar'         => count($result),
            ]);

        } while (count($result) < $count);

        return array($skip, $result);
    }

    /**
     * This method fetches the current account status for a merchant
     * This is applicable only for merchants with banking products.
     *
     * @param Entity $merchant
     *
     * @return string
     */
    public function getBankingAccountStatus(Entity $merchant, string $mode = Mode::LIVE): ?string
    {
        try
        {
            // fetch the CA channel from 'banking_accounts' table first and if there is no data then check 'merchant_attributes'
            $bankingAccounts = $this->repo->banking_account->connection($mode)->fetchMerchantBankingAccounts($merchant->getId());

            $currentAccount = current(array_filter($bankingAccounts, function ($account) {
                return $account[BankingAccount\Entity::ACCOUNT_TYPE] === 'current';
            }));

            if (empty($currentAccount) === true)
            {
                $merchantAttributes = (new Attribute\Core())->fetchKeyValuesByMode($merchant, Product::BANKING,
                    Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS, [Merchant\Attribute\Type::CA_PROCEEDED_BANK], null, 'asc', Mode::LIVE);

                $merchantAttribute = $merchantAttributes->first();

                // if channel is not found in both banking_accounts and merchant_attributes tables then return status as 'Application not initiated'
                if (empty($merchantAttribute) === true)
                {
                    return Constants::CA_STATUS_MAP[Constants::DEFAULT];
                }

                $channel = $merchantAttribute[Attribute\Entity::VALUE];
            }
            else
            {
                $channel = $currentAccount[BankingAccount\Entity::CHANNEL];
            }

            $caStatus = Tracer::inspan(['name' => HyperTrace::GET_MERCHANT_BANKING_ACCOUNT_STATUS], function () use ($channel, $merchant, $mode) {

                return (new BankingAccount\Core())->getMerchantBankingAccountStatus($channel, $merchant, $mode);
            });

            $transformedCaStatus = $this->getTransformedCaStatus($merchant, $caStatus, $channel);

            return $transformedCaStatus;
        }
        catch (Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ERROR_FETCHING_SUB_MERCHANT_CA_STATUS,
                [
                    'mode'        => $this->mode,
                    'merchant_id' => $merchant->getId(),
                    'error'       => $ex->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * 1. Transform CA status of a merchant to a more human readable state
     * 2. If the status mapping is not found for a channel then use the default status
     * 3. If the transformed status is 'Application completion pending' then
     *    update the status based on PAN verification status
     *
     * @param Entity $merchant
     * @param string|null $caStatus
     * @param string $channel
     *
     * @return string
     */
    private function getTransformedCaStatus(
        Entity $merchant,
        ?string $caStatus,
        string $channel): ?string
    {
        //lower casing channel value since some entities are storing bank names in upper cases
        $channel = strtolower($channel);

        if (isset(Constants::CA_STATUS_MAP[$channel][$caStatus]) === true)
        {
            $transformedCaStatus = Constants::CA_STATUS_MAP[$channel][$caStatus];

            if ($transformedCaStatus !== BankingAccount\Status::APPLICATION_COMPLETION_PENDING)
            {
                return $transformedCaStatus;
            }
        }
        else
        {
            if (array_key_exists($channel, Constants::CA_STATUS_MAP) === true)
            {
                $transformedCaStatus = Constants::CA_STATUS_MAP[Constants::DEFAULT];

                return $transformedCaStatus;
            }

            // if channel or mapping not found then return the original CA status
            return $caStatus;
        }

        // transform CA status based on PAN verification
        $panVerificationStatus = (new BankingAccount\Core())->getMerchantBankingAccountPanStatus($channel, $merchant);

        switch ($panVerificationStatus)
        {
            case Merchant\BvsValidation\Constants::PENDING:
            case Merchant\BvsValidation\Constants::INITIATED:
                $transformedCaStatus = BankingAccount\Status::PAN_VERIFICATION_IN_PROGRESS;
                break;

            case Merchant\BvsValidation\Constants::FAILED:
            case Merchant\BvsValidation\Constants::NOT_MATCHED:
            case Merchant\BvsValidation\Constants::INCORRECT_DETAILS:
                $transformedCaStatus = BankingAccount\Status::PAN_VERIFICATION_FAILED;
                break;

            case Merchant\BvsValidation\Constants::VERIFIED:
                $transformedCaStatus = BankingAccount\Status::TELEPHONIC_VERIFICATION;
                break;
        }

        return $transformedCaStatus;
    }

    public function isCurrentAccountActivated(Entity $merchant): bool
    {
        return $this->checkIfCurrentAccountIsActivated($merchant);
    }


    public function isRblCurrentAccountActivated(Entity $merchant) : bool
    {
        $activatedBankingAccountExists = Tracer::inspan(['name' => HyperTrace::MERCHANT_CORE_FETCH_BANKING_ACCOUNT_BY_MERCHANT_ID_ACCOUNT_TYPE_CHANNEL_AND_STATUS], function () use ($merchant)
        {
            // handling the filtering here instead of the repo layer to help with API-decomp for current-accounts
            foreach ($merchant->bankingAccounts as $bankingAccount)
            {
                if ($bankingAccount->getChannel() === BankingAccount\Channel::RBL &&
                    $bankingAccount->getAccountType() === BankingAccount\AccountType::CURRENT &&
                    $bankingAccount->getStatus() === BankingAccount\Status::ACTIVATED)
                {
                    return true;
                }
            }

            return false;
        });

        return $activatedBankingAccountExists;
    }

    public function isXVaActivated(Entity $merchant) : bool
    {
        return (new Attribute\Core())->isXVaActivated($merchant);
    }

    /**
     * @param Entity $merchant
     *
     * @return bool
     * For ICICI as balance entity doesn't support archiving we are checking businessId in merchant detail
     */
    public function checkIfCurrentAccountIsActivated(Entity $merchant): bool
    {
        // RBL
        if($this->isRblCurrentAccountActivated($merchant))
        {
            return true;
        }
        else
        {
            // ICICI, Axis, Yes Bank (CAs implemented in BAS)
            $repo = new BalanceRepo();

            $balance = Tracer::inspan(['name' => HyperTrace::MERCHANT_CORE_GET_BALANCE_BY_MERCHANT_ID_CHANNELS_AND_ACCOUNT_TYPE], function () use ($repo, $merchant)
                {
                    return  $repo->getBalanceByMerchantIdChannelsAndAccountType($merchant->getMerchantId(), BankingAccountService\Channel::getDirectTypeChannels(), Balance\AccountType::DIRECT);
                });

            $businessId = '';

            if(empty($merchant->merchantDetail) === false)
            {
                $businessId = $merchant->merchantDetail->getBasBusinessId();
            }

            return (empty($balance) === false && empty($businessId) === false);
        }
    }

    public function getMerchantRiskData(string $merchantId): array
    {
        $experimentResult       = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            Merchant\RazorxTreatment::DRUID_MIGRATION,
            Mode::LIVE);

        $isDruidMigrationEnabled = ( $experimentResult === 'on' ) ? true : false;

        $pinotClient             = $this->app['eventManager'];

        $queryResult             = [];

        if ($isDruidMigrationEnabled === true)
        {
            try
            {
                $query = sprintf(Constants::MERCHANT_RISK_SCORE_DATA_PINOT_QUERY, $merchantId);

                $res = $pinotClient->getDataFromPinot(
                    [
                        'query' => $query
                    ]
                );

                if (empty($res) === true || count($res) === 0)
                {
                    return ['status' => 404];
                }

                $queryResult = $res[0];
            }
            catch(\Throwable $e)
            {
                // No need to trace error as its harvester client already logs it.
                return ['error' => $e->getMessage(), 'status' => 503];
            }

            $queryResult = $pinotClient->parsePinotDefaultType($queryResult, 'risk_scoring_fact');
        }
        else
        {
            $query = sprintf(Constants::MERCHANT_RISK_SCORE_DATA_DRUID_QUERY, $merchantId);

            list($error, $res) = $this->app['druid.service']->getDataFromDruid(['query' => $query]);

            if (empty($error) === false)
            {
                $this->trace->info(TraceCode::GET_MERCHANT_RISK_DATA_DRUID_ERROR, ['error' => $error]);
                return ['error' => $error, 'status' => 503];
            }

            if (is_null($res) === true || count($res) === 0)
            {
                return ['status' => 404];
            }

            $queryResult = $res[0];
        }

        $returnArray = ['status' => 200];

        foreach (Constants::MERCHANT_RISK_SCORE_DRUID_KEY_MAPPING as $key => $returnKey)
        {
            if (array_key_exists($key, $queryResult) === true)
            {
                $returnVal = $queryResult[$key];

                if (empty($returnVal) === false &&
                    in_array($returnKey, Constants::MERCHANT_RISK_SCORE_DATA_MONEY_FIELDS))
                {
                    $returnVal = '₹' . number_format($returnVal);
                }
            }
            else
            {
                $returnVal = 'Data not present in druid';
            }

            array_set($returnArray, $returnKey, $returnVal);
        }

        return $returnArray;
    }

    /**
     * @param Base\Entity $merchant
     * @throws Exception\RuntimeException
     */
    public function addHasKeyAccessToMerchantIfApplicable(Merchant\Entity $merchant)
    {
        // Merchant's has_key_access is set to true when website Url or App Store url or PlayStore url is set.

        if (((new Merchant\Detail\Core())->hasWebsite($merchant) === true) and
            ($merchant->getHasKeyAccess() === false))
        {
            $merchant->setHasKeyAccess(true);

            $this->repo->saveOrFail($merchant);
        }
    }

    public function skipMerchantOnboardingCommFromHubSpot(string $email)
    {
        $this->app->hubspot->skipMerchantOnboardingComm($email);
    }

    public function enableM2MReferral($merchantId)
    {
        try
        {
            (new Feature\Core)->create([

                                           Feature\Entity::ENTITY_TYPE => E::MERCHANT,
                                           Feature\Entity::ENTITY_ID   => $merchantId,
                                           Feature\Entity::NAME        => Feature\Constants::M2M_REFERRAL,
                                       ], $shouldSync = true);

            $data = [
                Store\Constants::NAMESPACE                    => Store\ConfigKey::ONBOARDING_NAMESPACE,
                Store\ConfigKey::REFERRED_COUNT               => 0,
                Store\ConfigKey::REFERRAL_SUCCESS_POPUP_COUNT => 0,
                Store\ConfigKey::REFEREE_SUCCESS_POPUP_COUNT => 0
            ];

            (new Store\Core())->updateMerchantStore($merchantId, $data, Store\Constants::INTERNAL);

            $properties = [
                'experiment_timestamp' => Carbon::now()->getTimestamp(),
            ];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $this->app['segment-analytics']->pushTrackEvent(
                $merchant, $properties, SegmentEvent::M2M_ENABLED_EXPERIMENT);

        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::M2M_REFERRALS_ENABLE_CRON_FAILED, [
                'reason'      => 'something went wrong while enabling m2m referral feature',
                'trace'       => $e->getMessage(),
                'merchant_id' => $merchantId
            ]);
        }
    }

    public function uploadInvoiceForIncreaseTransactionLimit(Detail\Entity $merchantDetails, $invoiceProof)
    {
        $fileInputs = [
            Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL => $invoiceProof
        ];

        $fileAttributes = (new Detail\Service())->storeActivationFile($merchantDetails, $fileInputs);

        if ((is_array($fileAttributes) === false) or
            (isset($fileAttributes[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL]) === false))
        {
            throw new Exception\ServerErrorException(
                'Invoice URL upload failed',
                ErrorCode::SERVER_ERROR);
        }

        return $fileAttributes[Constants::TRANSACTION_LIMIT_INCREASE_INVOICE_URL][Document\Constants::FILE_ID];
    }

    public function associateCodSlab(array $slabs)
    {
        $this->associateSlab($slabs, Slab\Type::COD_SLAB);
    }

    public function associateShippingSlab(array $slabs)
    {
        $this->associateSlab($slabs, Slab\Type::SHIPPING_SLAB);
    }

    public function associateCodServiceabilitySlab(array $slabs)
    {
        $this->associateSlab($slabs, Slab\Type::COD_SERVICEABILITY_SLAB);
    }

    protected function associateSlab(array $slabs, string $type)
    {
        $input = [
            'slab' => $slabs,
            'type' => $type,
        ];

        $this->repo->transaction(
            function () use($input)
            {
                $slab = $this->merchant->slab($input['type']);
                if ($slab !== null)
                {
                    $slab->delete();
                }
                (new Merchant\Slab\Core())->createAndSaveSlab($this->merchant, $input);
            }
        );
    }

    public function associateMerchant1ccConfig(string $type, string $value, array $value_json = [])
    {
        $input = [
            'config'     => $type,
            'value'      => $value,
            'value_json' => $value_json,
        ];

        return $this->transaction(
            function () use ($input)
            {
                $config = $this->repo->merchant_1cc_configs->findByMerchantAndConfigType(
                    $this->merchant->getId(),
                    $input['config']
                );
                if ($config !== null)
                {
                    $config->delete();
                }
                return (new Merchant1ccConfig\Core())->createAndSaveConfig($this->merchant, $input);
            }
        );
    }

    public function associateMerchant1ccIntelligenceConfig(string $type, string $value, array $value_json = [])
    {
        $input = [
            'config'     => $type,
            'value'      => $value,
            'value_json' => $value_json,
        ];

        return $this->transaction(
            function () use ($input)
            {
                $configs = $this->repo->merchant_1cc_configs->findAllByMerchantAndConfigType(
                    $this->merchant->getId(),
                    $input['config']
                );

                foreach ($configs as $config)
                {
                    $config->delete();
                }

                $newConfig =  (new Merchant1ccConfig\Core())->createAndSaveConfig($this->merchant, $input);
                if ($newConfig == null)
                {
                    $this->trace->info(TraceCode::MERCHANT_1CC_INTELLIGENCE_CONFIG_CREATE_FAILED);
                }
                return $newConfig;
            }
        );
    }

    public function associateMerchant1ccCODConfig(string $type, string $value, array $value_json = [])
    {
        $input = [
            'config'     => $type,
            'value'      => $value,
            'value_json' => $value_json,
        ];

        return $this->transaction(
            function () use ($input)
            {
                $configs = $this->repo->merchant_1cc_configs->findAllByMerchantAndConfigType(
                    $this->merchant->getId(),
                    $input['config']
                );

                if ( $configs !== null && $input['value'] !== '1')
                {
                    $input['value_json'] = $configs[0]['value_json'];
                }

                foreach ($configs as $config)
                {
                    $config->delete();
                }

                $newConfig =  (new Merchant1ccConfig\Core())->createAndSaveConfig($this->merchant, $input);
                if ($newConfig == null)
                {
                    $this->trace->info(TraceCode::MERCHANT_1CC_PREPAY_COD_CONFIG_CREATE_FAILED);
                }
                return $newConfig;
            }
        );
    }


    public function associateMerchant1ccComments(string $type, string $value)
    {
        $input = [
            'flow' => $type,
            'comment' => $value,
        ];

        $this->repo->transaction(
            function () use($input)
            {
                $comment = $this->repo->merchant_1cc_comments->findByMerchantAndFlowType(
                    $this->merchant->getId(),
                    $input['flow']
                );
                if ($comment !== null)
                {
                    $comment->delete();
                }
                return (new Merchant1ccComments\Core())->createAndSaveComments($this->merchant, $input);
            }
        );
    }

    protected function addMerchantWorkflowClarificationComments(WorkflowAction\Entity $action, string $clarification , array $documentIds = [])
    {
        $clarificationCommentEntity = (new CommentCore)->create([
            CommentEntity::COMMENT  => $clarification
        ]);

        $clarificationCommentEntity->entity()->associate($action);

        $this->repo->saveOrFail($clarificationCommentEntity);

        if (empty($documentIds) === false)
        {
            $documentUrls = Constants::MERCHANT_WORKFLOW_CLARIFICATION_FILES_PREFIX;

            foreach ($documentIds as $documentId)
            {
                $documentUrls .= sprintf(Constants::UFH_FILE_URL,
                    $this->app->config->get('applications.dashboard.url'),
                    str_replace('doc_' , '' , $documentId)
                );
            }

            $documentsUrlCommentEntity = (new CommentCore)->create([
                CommentEntity::COMMENT =>  $documentUrls
            ]);

            $documentsUrlCommentEntity->entity()->associate($action);

            $this->repo->saveOrFail($documentsUrlCommentEntity);
        }

        $this->trace->info(TraceCode::MERCHANT_CLARIFICATION_COMMENT_ADDED);
    }

    public function postMerchantWorkflowClarification(WorkflowAction\Entity $action, array $input)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($action, $input) {
            $clarificationDocumentIds =  $input[Constants::WORKFLOW_CLARIFICATION_DOCUMENTS_IDS] ?? [];

            $this->addMerchantWorkflowClarificationComments($action, $input[Constants::MERCHANT_WORKFLOW_CLARIFICATION], $clarificationDocumentIds);

            $action->untag(WorkflowAction\Constants::WORKFLOW_NEEDS_MERCHANT_CLARIFICATION_TAG);

            $action->tag(WorkflowAction\Constants::WORKFLOW_MERCHANT_RESPONDED_TAG);

            $this->repo->workflow_action->saveOrFail($action);
        });
    }

    private function triggerCommunicationIfApplicable($merchant,$action,$triggerCommunication)
    {
        /*
             * in international disabling, there will be two types of disabling: Permanent and Temporary,
             * the only thing difference b/w both will be communication part.
             */
        if ($action === Merchant\Action::DISABLE_INTERNATIONAL)
        {
            if ((int)$triggerCommunication === 1)
            {
                (new MerchantActionNotification())->sendMerchantRiskActionNotifications($merchant, Merchant\Action::DISABLE_INTERNATIONAL_TEMPORARY);
            }
            else
            {
                if ((int)$triggerCommunication === 2)
                {
                    (new MerchantActionNotification())->sendMerchantRiskActionNotifications($merchant, Merchant\Action::DISABLE_INTERNATIONAL_PERMANENT);
                }
            }

        }
        else
        {
            if ((int)$triggerCommunication  === 1)
            {
                (new MerchantActionNotification())->sendMerchantRiskActionNotifications($merchant, $action);
            }
        }
    }

    public function updateLinkedAccountBankAccount(string $id, array $input)
    {
        //
        // Changing bank account details on test mode is
        // pointless, hence we are forcing live mode here.
        // Dashboard may implement a tooltip to mention this.
        //
        $this->setModeAndDefaultConnection(Mode::LIVE);

        $linkedAccount = $this->repo->account->findOrFail($id);

        if ((empty($linkedAccount) === true) or
            ($linkedAccount->getParentId() !== $this->merchant->getId()))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_ID_DOES_NOT_EXIST,
                'linked_account_id',
                [
                    'linked_account_id'     => $id,
                    'la.parent_id'          => $linkedAccount->getParentId(),
                    'parent_merchant_id'    => $this->merchant->getId(),
                ]
            );
        }

        //
        // If a linked account is not activated, bank account is
        // not present yet. Hence, there is no question of update.
        //
        if ($linkedAccount->isActivated() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_CANNOT_UPDATE_BANK_ACCOUNT_FOR_LINKED_ACCOUNT_NOT_ACTIVATED,
                'linked_account_id',
                [
                    'linked_account_id' => $id,
                    'activated'         => $linkedAccount->getActivated(),
                ]
            );
        }

        $combinedActivationStatus = (new Detail\Core)->getCombinedActivationStatusForLinkedAccounts($linkedAccount->merchantDetail);

        //
        // Bank account update without penny testing is synchronous. But the penny testing flow is
        // asynchronous and linked account funds are put on hold during the same. If bank account update
        // is requested while the previous penny testing flow is still not complete, we'll throw this error.
        // When the previous bank account's verification is in progress the linked account's activation status
        // will be "verification_pending". Hence we only allow bank account update when activation status is
        // other than verification_pending.
        //
        if ($combinedActivationStatus === Merchant\Account\Constants::VERIFICATION_PENDING)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANK_ACCOUNT_UPDATE_ALREADY_IN_PROGRESS,
                'linked_account_id',
                [
                    'linked_account_id' => $id,
                ]
            );
        }

        $bankAccountCore = new BankAccount\Core();

        $data = $bankAccountCore->buildBankAccountArrayFromMerchantDetail($linkedAccount->merchantDetail, true);

        $data = array_merge($data, $input);

        //
        // LA funds need to be put on hold before initiating penny testing so that no new
        // settlements are created after the merchant has requested for a bank account change.
        //
        $this->transaction(function () use ($linkedAccount, $bankAccountCore, $data)
        {
            $linkedAccount->setHoldFundsReason(Constants::LINKED_ACCOUNT_PENNY_TESTING);

            $this->repo->saveOrFail($linkedAccount);

            (new Service())->edit($linkedAccount->getId(), [Entity::HOLD_FUNDS => true]);

            //
            // This will propagate the bank account change to the new settlements service as well.
            // The old settlements service (in api) reads bank account details from the bank_accounts table
            // in the api DB. The new settlements service reads bank account details from the bank_accounts
            // table in the settlements DB. Hence, it is necessary to maintain the changes at both places.
            //
            $bankAccountCore->createOrChangeBankAccount($data, $linkedAccount, false, false);
        });

        if ($this->merchant->isFeatureEnabled(FeatureConstants::ROUTE_LA_PENNY_TESTING) === true)
        {
            $this->initiatePennyTestingForLinkedAccount($linkedAccount);

            // ToDo: Should not send hardcoded status. Use getCombinedActivationStatusForLinkedAccounts() instead, after backfilling bank_details_verification_status column.
            // https://github.com/razorpay/api/pull/26779#discussion_r806820311
            return array_merge(['status' => Account\Constants::VERIFICATION_PENDING], $input);
        }

        //
        // To ensure we have the latest hold_funds_reason.
        //
        $linkedAccount->reload();

        if ($linkedAccount->getHoldFundsReason() === Constants::LINKED_ACCOUNT_PENNY_TESTING)
        {
            $linkedAccount->setHoldFunds(false);

            $this->repo->account->saveOrFail($linkedAccount);
        }

        // ToDo: Should not send hardcoded status. Use getCombinedActivationStatusForLinkedAccounts() instead, after backfilling bank_details_verification_status column.
        // https://github.com/razorpay/api/pull/26779#discussion_r806820311
        return array_merge(['status' => Account\Constants::ACTIVATED], $input);
    }

    protected function initiatePennyTestingForLinkedAccount($linkedAccount)
    {
        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_INITIATE_PENNY_TESTING_FOR_BANK_ACCOUNT_UPDATE,
            [
                'linked_account_id'     => $linkedAccount->getId(),
                'parent_merchant_id'    => $linkedAccount->getParentId(),
                'initiator'             => 'merchant',
            ]
        );

        $merchantDetailCore = new Detail\Core();

        $merchantDetailCore->publicAttemptPennyTesting($linkedAccount->merchantDetail, $linkedAccount, true);

        $merchantDetailCore->publicTriggerValidationRequests($linkedAccount, $linkedAccount->merchantDetail);

        $this->repo->saveOrFail($linkedAccount->merchantDetail);
    }

    public function eventLinkedAccountUpdated(string $merchantId)
    {
        $account = $this->repo->account->findOrFail($merchantId);

        $bankAccount = $account->bankAccount;

        $eventPayload = [
            ApiEventSubscriber::MAIN => $account,
            ApiEventSubscriber::WITH => $bankAccount
        ];
        $this->trace->info(
            TraceCode::LINKED_ACCOUNT_UPDATED_WEBHOOK_EVENT_DISPATCH,
            [
                'linked_account_id'                 =>  $merchantId,
                'bank_details_verification_status'  =>  $account->merchantDetail->getBankDetailsVerificationStatus()
            ]
        );
        $this->app['events']->dispatch('api.account.updated', $eventPayload);
    }

    public function sendPartnerLeadInfoToSalesforce(string $merchantId, string $partnerId, string $product, array $extraData = [])
    {
        $merchantId = Account\Entity::SilentlyStripSign($merchantId);

        // send only X leads
        if ($product !== Product::BANKING)
        {
            return;
        }

        try
        {
            $this->app->salesforce->sendPartnerLeadInfo($merchantId, $partnerId, $product, $extraData);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SALESFORCE_FAILED_TO_DISPATCH_JOB,
                [
                    Entity::MERCHANT_ID => $merchantId,
                    Entity::PARTNER_ID  => $partnerId,
                    Entity::PRODUCT     => $product
                ]
            );
        }
    }

    public function isBlockedMerchantType(Entity $merchant,array $blockedTypes)
    {
        foreach ($blockedTypes as $blockedType)
        {
            switch ($blockedType)
            {
                case self::VAS_MERCHANT:
                    if ($merchant->isBusinessBankingEnabled() === true)
                    {
                        return true;
                    }
                    break;
                case self::PARTNER_MERCHANT:
                    if ($merchant->isPartner()===true)
                    {
                        return true;
                    }
                    break;
                case self::LINKED_ACCOUNT:
                    if ($merchant->isLinkedAccount() === true)
                    {
                        return true;
                    }
                    break;
                case self::SUB_MERCHANT:
                    if ((new AccessMapCore)->isSubMerchant($merchant->getMerchantId()) === true)
                    {
                        return true;
                    }
                    break;
                default:
                    throw new \Exception('Unexpected value');

            }
        }

        return false;
    }

    public function isRegularMerchant(Entity $merchant): bool
    {
        // RazorpayX
        if ($merchant->isBusinessBankingEnabled() === true)
        {
            return false;
        }

        // Linked Accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        // Partnership Merchant
        if ($merchant->isPartner() === true)
        {
            return false;
        }

        // subMerchant
        $isSubMerchant = (new AccessMapCore)->isSubMerchant($merchant->getMerchantId());

        return !$isSubMerchant;
    }

    public function  checkAndPushMessageToMetroForNetworkOnboard($merchantId)
    {
        $networks = [];

        foreach(Constants::listOfNetworksSupportedOn3ds2 as $network)
        {
            array_push($networks,$network);
        }

        $payload = [
            'action' => CrossBorderCommonUseCases::MERCHANT_ONBOARD_NETWORK,
            'mode' => $this->mode ?? Mode::LIVE,
            'body' => [
                'merchant_id' => $merchantId,
                'networks'    => $networks
            ]
        ];

        try
        {
            CrossBorderCommonUseCases::dispatch($payload)->delay(rand(60,1000) % 601);

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCHED,[
                'payload' => $payload,
            ]);
        }
        catch(\Exception $ex)
        {
            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DISPATCH_FAILED,[
                'payload' => $payload,
            ]);
        }
    }

    public function bulkMigrateAggregatorToResellerPartner(array $input)
    {
        (new Validator())->validateInput('bulkAggregatorToResellerMigration', $input);

        $traceInfo = ['merchant_ids' => $input['merchant_ids']];

        $this->trace->info(TraceCode::BULK_MIGRATE_AGGREGATOR_TO_RESELLER_REQUEST, $traceInfo);

        $batches = array_chunk($input['merchant_ids'], $input['batch_size']);
        $actorDetails = $this->getActorDetails();

        foreach ($batches as $batch)
        {
            BulkMigrateAggregatorToResellerJob::dispatch($batch,$actorDetails);
        }

        $this->trace->info(TraceCode::BULK_MIGRATE_AGGREGATOR_TO_RESELLER_SUCCESS, $traceInfo);
    }

    /**
     * Acquires mutex lock on reseller partner's merchantID and migrates to aggregator partner
     *
     * @param   string   $merchantId        Merchant ID of partner
     *
     * @return  bool
     * @throws  Throwable|LogicException    It will throw an error when updating of partner mapping fails.
     */
    public function migrateAggregatorToResellerPartner(string $merchantId, array $actorDetails = []) : bool
    {

        $mutex = App::getFacadeRoot()['api.mutex'];

        $mutexKey = Constants::AGGREGATOR_TO_RESELLER_UPDATE.$merchantId;

        if(empty($actorDetails) == true)
        {
            $actorDetails = $this->getActorDetails();
        }

        return $mutex->acquireAndRelease(
            $mutexKey,
            function() use ($merchantId, $actorDetails)
            {
                return $this->updateAggregatorToReseller($merchantId, $actorDetails);
            },
            Constants::AGGREGATOR_TO_RESELLER_UPDATE_LOCK_TIME_OUT,
            ErrorCode::BAD_REQUEST_AGGREGATOR_TO_RESELLER_MIGRATION_IN_PROGRESS);
    }

    /**
     * Validates partner's existing details and creates supporting entities as required
     *
     * @param   string   $merchantId    The partner.
     *
     * @return  bool
     *
     * @throws  LogicException
     * @throws  Throwable
     */
    private function updateAggregatorToReseller(string $merchantId, array $actorDetails) : bool
    {
        $this->trace->info(
            TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_PARTNER_REQUEST,
            ['merchant_id' => $merchantId]);

        $merchant = $this->repo->merchant->find($merchantId);
        $oldPartnerType = $merchant->getPartnerType();

        if ($merchant === null || $merchant->isAggregatorPartner() === false)
        {
            $this->trace->info(
                TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_INVALID_PARTNER,
                [ 'merchant_id' => $merchantId  ]
            );
            $this->trace->count(
                Metric::AGGREGATOR_TO_RESELLER_MIGRATION_FAILURE,
                [ 'code' => TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_INVALID_PARTNER ]
            );

            return false;
        }

        $result = $this->validateAndUpdateAggregatorToResellerEntities($merchant);

        if ($result === true)
        {
            $this->trace->info(TraceCode::MIGRATE_AGGREGATOR_TO_RESELLER_SUCCESS, ['merchant_id' => $merchant->getId()]);
            $this->trace->count(Metric::AGGREGATOR_TO_RESELLER_MIGRATION_SUCCESS);
            PartnerMigrationAuditJob::dispatch($merchantId, $actorDetails, $oldPartnerType);
        }
        else
        {
            $this->trace->info(
                TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_ERROR,
                ['merchant_id' => $merchant->getId()]
            );
        }
        return $result;
    }

    private function updateExistingApplicationMappings(string $existingAppId, string $updatedAppId, Base\PublicCollection $accessMaps, bool $managedApp = false )
    {
        if ($managedApp === true)
        {
            (new PartnerConfigCore())->updateApplicationsForPartnerConfigs($existingAppId, $updatedAppId);
        }

        (new AccessMap\Core())->updateApplications($accessMaps, $updatedAppId, MerchantApplicationsEntity::REFERRED);

        $this->deleteWebhooksForApplication($existingAppId);
    }

    private function deleteWebhooksForApplication(string $ownerId)
    {
        (new Stork('live'))->deleteWebhooksByOwnerId($ownerId);

        (new Stork('test'))->deleteWebhooksByOwnerId($ownerId);
    }


    /**
     * Validates existing entities on live and test DB and creates supporting entities to migrate aggregator to reseller
     * @param Merchant\Entity $partner aggregator partner
     *
     * @return bool
     *
     * @throws Exception\LogicException
     * @throws Throwable
     */
    private function validateAndUpdateAggregatorToResellerEntities(Merchant\Entity $partner) : bool
    {
        try
        {
            $applications = $this->repo->merchant_application->fetchMerchantAppInSyncOrFail($partner->getId());
            $accessMaps[] = $subMerchants[] = new Base\PublicCollection();

            $this->repo->merchant_user->fetchMerchantUsersByMerchantIdsAndRoles([$partner->primaryOwner()->getId()], [Role::OWNER]);

            if ((new Merchant\Validator())->validateAggregatorApplications($applications) === false)
            {
                return false;
            }
            $managedAppId = $applications->where(MerchantApplicationsEntity::TYPE, MerchantApplicationsEntity::MANAGED)
                                         ->pluck(MerchantApplicationsEntity::APPLICATION_ID)->first();
            $existingAppIds = $applications->pluck(MerchantApplicationsEntity::APPLICATION_ID)->toArray();

            foreach ($existingAppIds as $existingAppId)
            {
                $configs = $this->repo->partner_config->fetchAllConfigsInSyncOrFail([$existingAppId]);
                $accessMaps[$existingAppId] = $this->repo->merchant_access_map->fetchAccessMapsInSyncOrFail(
                    $existingAppId, $partner->getId()
                );
                $subMerchants[$existingAppId] = $this->repo->merchant->getSubMerchantsForPartnerAndAppInSyncOrFail(
                    $existingAppId, $partner->getId()
                );
            }

            return $this->createAndUpdateAggregatorToResellerEntities(
                $partner, $managedAppId, $existingAppIds, $accessMaps, $subMerchants
            );
        }
        catch (Exception\LogicException $e)
        {
            $this->trace->error(TraceCode::AGGREGATOR_TO_RESELLER_DATA_MISMATCH);
            $this->trace->count(
                Metric::AGGREGATOR_TO_RESELLER_MIGRATION_FAILURE,
                ['code' => TraceCode::AGGREGATOR_TO_RESELLER_DATA_MISMATCH]
            );
            throw $e;
        }
    }

    /**
     * Creates new application for reseller.
     * Updates existing application mappings.
     * delete submerchants dashboard access to reseller partners.
     * Deletes old app and Merchant application for aggregator partner.
     *
     * @param Merchant\Entity       $partner aggregator partner
     * @param string                $managedAppId partner app of type managed
     * @param array                 $existingAppId partner app ids
     * @param array                 $accessMaps partner sub merchant maps
     * @param array                 $subMerchants array of partner submerchants
     *
     * @return bool
     *
     * @throws Exception\LogicException
     * @throws Throwable
     */
    private function createAndUpdateAggregatorToResellerEntities(
        Merchant\Entity $partner, string $managedAppId, array $existingAppIds,
        array $accessMaps, array $subMerchants) : bool
    {
        $app = $this->createPartnerApp($partner);
        try
        {
            $this->repo->transactionOnLiveAndTest(function() use ($partner, $app, $existingAppIds, $accessMaps, $subMerchants, $managedAppId)
            {
                $this->createMerchantApplication(
                    $partner, $app[OAuthApp\Entity::ID], MerchantApplicationsEntity::REFERRED
                );
                $this->trace->info(TraceCode::AGGREGATOR_TO_RESELLER_APPLICATION_CREATED, [
                        'old_application_ids' => $existingAppIds,
                        'new_application_id' => $app[OAuthApp\Entity::ID]
                    ]
                );

                foreach ($existingAppIds as $existingAppId)
                {
                    $isManagedApp = ($managedAppId === $existingAppId);
                    $this->updateExistingApplicationMappings(
                        $existingAppId, $app[OAuthApp\Entity::ID], $accessMaps[$existingAppId], $isManagedApp
                    );
                    // delete merchant and application mapping
                    (new MerchantApplications\Core)->deleteByApplication($existingAppId);
                }

                $partner->setPartnerType(Constants::RESELLER);
                $this->repo->merchant->saveOrFail($partner);

                $this->deletePartnerDashboardAccessOnSubmerchants($partner, $subMerchants[$managedAppId]);

                foreach ($existingAppIds as $existingAppId)
                {
                    app('authservice')->deleteApplication($existingAppId, $partner->getId(), false);
                }
            });

            $this->trace->info(
                TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_PARTNER_SUCCESS,
                ['merchant_id' => $partner->getId()]
            );
        } catch (\Throwable $e)
        {
            app('authservice')->deleteApplication($app[OAuthApp\Entity::ID], $partner->getId(), false);

            $this->trace->error(
                TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_ERROR,
                [ 'error' => $e ]
            );
            $this->trace->count(
                Metric::AGGREGATOR_TO_RESELLER_MIGRATION_FAILURE,
                ['code' => TraceCode::AGGREGATOR_TO_RESELLER_UPDATE_ERROR]
            );
            throw $e;
        }
        return true;
    }


    public function get1ccMerchantPreferences(Merchant\Entity $merchant): array
    {
        $data = ['mode' => $this->mode];

        $this->fillEnabled1ccFeatures($merchant, $data);

        return $data;
    }

    protected function fillEnabled1ccFeatures(Merchant\Entity $merchant, array &$data)
    {
        foreach (FeatureConstants::ONE_CC_FEATURES as $feature)
        {
            if ($merchant->isFeatureEnabled($feature) === true)
            {
                $data['features'][$feature] = true;
            }
        }
    }

    public function removeSubmerchantDashboardAccessOfPartner(string $partnerId)
    {
        $this->trace->info(TraceCode::REMOVE_SUBMERCHANT_DASHBOARD_ACCESS_PARTNER_REQUEST,
                           [ 'partner_id' => $partnerId ]);

        $partner = $this->repo->merchant->find($partnerId);

        if ($partner == null)
        {
            $this->trace->info(TraceCode::REMOVE_SUBMERCHANT_DASHBOARD_ACCESS_INVALID_PARTNER,
                               [ 'partner_id' => $partnerId ]);
        }

        $submerchantIds = $this->repo->merchant_access_map->getMerchantIdForSubmerchantsOfAPartner($partnerId)->toArray();

        $submerchants = $this->repo->merchant->findMany($submerchantIds);

        $this->deletePartnerDashboardAccessOnSubmerchants($partner, $submerchants);

        $this->trace->info(TraceCode::REMOVE_SUBMERCHANT_DASHBOARD_ACCESS_PARTNER_SUCCESS,
                           [ 'partner_id' => $partnerId ]);
    }

    /**
     *  This function is used for fetching partner/parent Id for given submerchants
     *  in case of partners enabled with aggregate settlement.
     *
     *  We return an empty string in case:
     *       If zero or more than one partner is found.
     *       If the owner Id in merchant_access_map is the same as submerchantId.
     *       If the partner is not onboarded on NSS.
     *       If the partner is onboarded on NSS but not an aggregate settlement parent.
     *
     *  In other cases we return a single string of parent/partner Id.
     *
     * @param string $submerchantId
     *
     * @return string
     */
    public function fetchAggregateSettlementForNSSParent(string $submerchantId): string
    {
        $merchantAccessMapList = $this->repo
            ->merchant_access_map
            ->fetchAffiliatedPartnersForSubmerchant($submerchantId);

        if (($merchantAccessMapList->isEmpty() === true) or ($merchantAccessMapList->count() > 1))
        {
            $this->trace->info(TraceCode::ZERO_OR_MORE_THAN_ONE_PARENTS_FOUND,[
                'sub_merchant_id'   => $submerchantId,
                'merchant_list'     => $merchantAccessMapList,
                'count'             => $merchantAccessMapList->count()
            ]);

            return '';
        }
        else
        {
            $parentMerchantID = $merchantAccessMapList->first()->getEntityOwnerId();

            if ($parentMerchantID === $submerchantId)
            {
                return '';
            }

            $nssFeature = $this->repo
                ->feature
                ->findByEntityTypeEntityIdAndName(Constants::MERCHANT, $parentMerchantID, FeatureConstants::NEW_SETTLEMENT_SERVICE);

            $featureResult = ($nssFeature === null) ? false : true;

            if($featureResult === false)
            {
                $this->trace->info(TraceCode::PARTNER_NOT_ONBOARDED_ON_NSS,[
                    'sub_merchant_id'      => $submerchantId,
                    'parent_merchant_id'   => $parentMerchantID,
                    'feature_result'       => $featureResult,
                    'action'               => 'Merchant Migration on NSS'
                ]);

                return '';
            }

            $req = [
                'merchant_id' => $parentMerchantID
            ];

            $response =  app('settlements_api')->merchantConfigGet($req, $this->mode);

            $isAggregateSettlement = $response['config']['preferences']['aggregate_settlement_parent'];

            if($isAggregateSettlement === false)
            {
                $this->trace->info(TraceCode::PARTNER_NOT_ENABLED_FOR_AGGREGATE_SETTLEMENT,[
                    'partner_id'       => $parentMerchantID,
                    'sub_merchant_id'  => $submerchantId
                ]);

                return '';
            }

            $this->trace->info(TraceCode::PARENT_ID_FOR_SUBMERCHANT_NSS_MIGRATION,[
                'sub_merchant_id'               => $submerchantId,
                'parent_merchant_id'            => $parentMerchantID,
                'aggregate_settlement_parent'   => $isAggregateSettlement,
                'nss_feature_parent'            => $featureResult
            ]);

            return $parentMerchantID;
        }
    }

    public function isMerchantWhitelistedForLazypay(Merchant\Entity $merchant) :bool {
        try {
            $properties = [
                "id" => $merchant->getId(),
                "experiment_id" => $this->app['config']->get('app.lazypay_whitelisted_merchants_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchant->getId(),
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variables = $response['response']['variant']['variables'];

            foreach ($variables as $variable) {
                if ($variable['key'] == "result" && $variable['value'] == "on") {
                    return true;
                }
            }

        } catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::LAZYPAY_WHITELISTED_MERCHANTS_SPLITZ_ERROR
            );
        }
        return false;
    }

    public function isSplitzExperimentEnable(array $properties, string $checkVariant, string $traceCode = null): bool
    {
        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? null;

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === $checkVariant)
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $id = $properties['id'] ?? null;

            $traceCode = $traceCode ?? TraceCode::SPLITZ_ERROR;

            $this->trace->traceException($e, Trace::ERROR, $traceCode, ['id' => $id]);
        }

        return false;
    }

    public function addMerchantDetailsOfToken(
        Entity $merchant,
        array &$merchantsList,
        array &$merchantIdMapping
    ) : void
    {
        $merchantId = $merchant->getId();

        if (array_key_exists($merchantId, $merchantIdMapping) === false)
        {
            // We have decided not to expose the actual Merchant IDs due to security issues.
            // We would expose random IDs as Merchant ID and is used over here only for mapping purpose.
            $merchantIdMapping[$merchantId] = Str::random(20);

            $merchantDisplayName = $this->getMerchantDisplayName($merchant);

            $merchantDetails = [
                Entity::NAME            => $merchant->getName(),
                Entity::LOGO_URL        => $merchant->getFullLogoUrlWithSize(),
                Constants::WEBSITE_NAME => $merchantDisplayName
            ];

            $merchantsList[$merchantIdMapping[$merchantId]] = $merchantDetails ;
        }
    }

    protected function getWebsiteDomainName(string $websiteUrl) : string
    {
        $websiteUrl = $this->preProcessWebsiteForDomainName($websiteUrl);

        $host = parse_url($websiteUrl, PHP_URL_HOST);

        $extractedDomains = (new TLDExtract())->extract($host);

        if (count($extractedDomains) >= 2)
        {
            $hostWithoutTld = $extractedDomains[0];

            // divide in subdomain and second level domain
            $hostParts = explode('.', $hostWithoutTld);

            // take second level domain(just below top level domain) as website name
            return $hostParts[count($hostParts)-1];
        }

        return "";
    }

    protected function getMerchantDisplayName(Entity $merchant) : string
    {
        if ($merchant->isShared() === true)
        {
            return Constants::RAZORPAY_DISPLAY_NAME;
        }

        $merchantWebsite = $merchant->getWebsite();

        $isValidWebsite = $this->isValidSchemeAndHostForMerchantWebsite($merchantWebsite);

        if ($isValidWebsite === true)
        {
            $merchantDisplayName = $this->getWebsiteDomainName($merchantWebsite);
        }

        // We are using merchant website domain as the display name
        // Exclude showing website domain as merchant name for those having websites as IPs, Email Ids, UPI Ids
        // and domains which are present in the array DOMAINS_TO_BE_EXCLUDED
        // Show Billing label as the display name for merchants which are being excluded
        // If Billing label is not present, show the Business name as the display name
        // If Business name is not present, show the Merchant name as the display name

        if ((empty($merchantDisplayName) === true) or
            (is_numeric($merchantDisplayName) === true) or
            (in_array($merchantDisplayName, Constants::DOMAINS_TO_BE_EXCLUDED, true)))
        {
            $merchantDetail = $merchant->merchantDetail;

            // Get first non-empty value in the list
            $merchantDisplayName = current(
                array_filter([
                    $merchant->getBillingLabelNotName(),
                    $merchantDetail->getBusinessName(),
                    $merchant->getName(),
                ])
            ) ?: '';
        }

        return $merchantDisplayName;
    }

    protected function preProcessWebsiteForDomainName(string $websiteUrl) : string
    {
        $websiteUrl = trim($websiteUrl);

        return strtolower($websiteUrl);
    }

    /**
     * checks if scheme is valid(http and https) and
     * host is not 'play.google.com' and 'apps.apple.com'
     * also check if url contains @ (in case of email id and upi id) or : (in case of ip)
     * @param $websiteUrl
     * @return bool
     */
    protected function isValidSchemeAndHostForMerchantWebsite($websiteUrl) : bool
    {
        $host = parse_url($websiteUrl, PHP_URL_HOST);

        $scheme = parse_url($websiteUrl, PHP_URL_SCHEME);

        // allow only http, https scheme
        // do not consider play store app link as merchant website
        if (($host === null) or
            ($scheme === null) or
            ((($scheme === 'http') or ($scheme === 'https')) === false) or
            ((($host === 'play.google.com') or ($host === 'apps.apple.com')) === true))
        {
            return false;
        }

        $websiteUrl = preg_replace("(^https?://)", "", $websiteUrl);

        if ((str_contains($websiteUrl, ':') === true) or
            (str_contains($websiteUrl, '@') === true))
        {
            return false;
        }

        return true;
    }

    private function detachAndAttachSubmerchantsOwnersForEmailUpdate(Entity $merchant, $oldOwner, $newOwner)
    {
        if (!$merchant->isPartner())
        {
            return;
        }

        $lastProcessedId = null;
        $submerchantAccessMappings = $this->repo->merchant_access_map->getSubMerchantsFromEntityOwnerId($merchant->getId(), 500, $lastProcessedId);

        while ($submerchantAccessMappings->isEmpty() === false)
        {
            $submerchantIds = $submerchantAccessMappings->pluck(AccessMap\Entity::MERCHANT_ID)->toArray();

            $submerchantsUsersForPrimary = $this->repo->merchant_user->fetchMerchantUsersForUserIdRoleAndProduct($oldOwner->getId(), [ROLE::OWNER], Product::PRIMARY, $submerchantIds);
            $this->detachSubmerchantsOwnersForEmailUpdateForProduct($merchant->getId(), $oldOwner, $submerchantsUsersForPrimary, Product::PRIMARY);
            $this->attachSubmerchantsOwnersForEmailUpdateForProduct($merchant->getId(), $newOwner, $submerchantsUsersForPrimary, Product::PRIMARY);

            $submerchantsUsersForBanking = $this->repo->merchant_user->fetchMerchantUsersForUserIdRoleAndProduct($oldOwner->getId(), [ROLE::OWNER, ROLE::VIEW_ONLY], Product::BANKING, $submerchantIds);
            $this->detachSubmerchantsOwnersForEmailUpdateForProduct($merchant->getId(), $oldOwner, $submerchantsUsersForBanking, Product::BANKING);
            $this->attachSubmerchantsOwnersForEmailUpdateForProduct($merchant->getId(), $newOwner, $submerchantsUsersForBanking, Product::BANKING);

            $lastProcessedId = $submerchantAccessMappings->last()->getId();

            $submerchantAccessMappings = $this->repo->merchant_access_map->getSubMerchantsFromEntityOwnerId($merchant->getId(), 500, $lastProcessedId);
        }
    }

    private function detachSubmerchantsOwnersForEmailUpdateForProduct($partnerId, $oldOwner, $submerchantsUser, $product)
    {
        if($submerchantsUser->isEmpty() === true)
        {
            return;
        }

        $submerchantIdsToDetach = array_pluck($submerchantsUser->toArray(), MerchantUser\Entity::MERCHANT_ID);
        $this->repo->detach($oldOwner, $product . User\Entity::MERCHANTS, $submerchantIdsToDetach);

        $this->trace->info(TraceCode::SUB_MERCHANTS_USER_DETACH_SUCCESSFUL, [
            'partner_id'       => $partnerId,
            'oldOwner'         => $oldOwner->getId(),
            'product'          => $product,
        ]);
    }

    private function attachSubmerchantsOwnersForEmailUpdateForProduct($partnerId, $newOwner, $submerchantsUser, $product)
    {
        if($submerchantsUser->isEmpty() === true)
        {
            return;
        }

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $submerchantsToAttach = [];
        foreach ($submerchantsUser as $submerchantUser)
        {
            $mappingParams = [
                'role'       => $submerchantUser->role,
                'product'    => $submerchantUser->product,
                'created_at' => $currentTimestamp,
                'updated_at' => $currentTimestamp
            ];

            $submerchantsToAttach[$submerchantUser->merchant_id] = $mappingParams;
        }

        $this->repo->attach($newOwner, $product . User\Entity::MERCHANTS, $submerchantsToAttach);

        $this->trace->info(TraceCode::SUB_MERCHANTS_USER_ATTACH_SUCCESSFUL, [
            'partner_id'       => $partnerId,
            'newOwner'         => $newOwner->getId(),
            'product'          => $product,
        ]);
    }

    private function detachAndAttachSubmerchantOwners(Entity $merchant, string $oldOwnerId, string $newOwnerId, string $product)
    {
        if (!$merchant->isPartner())
        {
            return;
        }

        $submerchantIds = $this->repo->merchant_user->fetchMerchantIdForUserIdRoleAndProduct($oldOwnerId, ROLE::OWNER, $product);
        $submerchants = $this->repo->merchant->findMany($submerchantIds);

        foreach ($submerchants as $submerchant)
        {
            $userMerchantMappingInputData = [
                'action'      => 'detach',
                'role'        => Role::OWNER,
                'merchant_id' => $submerchant->getId(),
                'product'     => $product
            ];

            (new User\Service)->updateUserMerchantMapping($oldOwnerId, $userMerchantMappingInputData);

            $userMerchantMappingInputData['action'] = 'attach';

            (new User\Service)->updateUserMerchantMapping($newOwnerId, $userMerchantMappingInputData);
        }
    }

    public function pushSelfServeSuccessEventsToSegment()
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        return [$segmentEventName, $segmentProperties];
    }

    private function pushSelfServeActionForAnalyticsForMerchantConfigUpdate($input, $merchant)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] =
            $this->getSelfServeActionForMerchantConfigUpdate($input);

        if (isset($segmentProperties[SegmentConstants::SELF_SERVE_ACTION]) === true)
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    private function pushSelfServeActionForAnalyticsForEnablingInstantRefund($input, $merchant)
    {
        [$segmentEventName, $segmentProperties] = $this->pushSelfServeSuccessEventsToSegment();

        if ((isset($input[Entity::DEFAULT_REFUND_SPEED]) === true) and
            ($input[Entity::DEFAULT_REFUND_SPEED] === Refund\Constants::OPTIMUM))
        {
            $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Enable Instant Refund';

            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, $segmentEventName
            );
        }
    }

    private function getSelfServeActionForMerchantConfigUpdate($input)
    {
        if (isset($input[Entity::DISPLAY_NAME]) === true)
        {
            return 'Display Name Updated';
        }

        if ($this->isCreditAlertCreated($input) === true)
        {
            return 'Credit Alert Created';
        }

        if (isset($input[Entity::BALANCE_THRESHOLD]) === true)
        {
            return 'Funds Alert Created';
        }

        if (isset($input[Entity::BRAND_COLOR]) === true)
        {
            return 'Theme Color Changed';
        }

        if (isset($input[Entity::LOGO_URL]) === true)
        {
            return 'Brand Logo Uploaded';
        }

        if (isset($input[Entity::DEFAULT_REFUND_SPEED]) === true)
        {
            return 'Refund Speed Updated';
        }

        if (isset($input[Entity::TRANSACTION_REPORT_EMAIL]) === true)
        {
            return 'Email Notification Enabled';
        }
    }

    private function isCreditAlertCreated($input)
    {
        if (((isset($input[Entity::AMOUNT_CREDITS_THRESHOLD]) === true) and
            (isset($input[Entity::FEE_CREDITS_THRESHOLD]) === true) and
            (isset($input[Entity::REFUND_CREDITS_THRESHOLD]) === true)) and
            (($input[Entity::AMOUNT_CREDITS_THRESHOLD] > 0) or
            ($input[Entity::FEE_CREDITS_THRESHOLD] > 0) or
            ($input[Entity::REFUND_CREDITS_THRESHOLD] > 0)))
        {
            return true;
        }

        return false;
    }

    public function createOrEditMerchantIpConfig(array $input)
    {

        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);
        }

        $this->trace->info(TraceCode::MERCHANT_IP_CONFIG_CREATE_EDIT_REQUEST,
            [
                'merchant_id'     => $this->merchant->getId(),
                'whitelisted_ips' => $input['whitelisted_ips'],
            ]);

        $whitelistedIps = $input['whitelisted_ips'];

        $errorIps = [];

        foreach ($whitelistedIps as $ipv)
        {
            if ((filter_var($ipv, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) and
                (filter_var($ipv, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false))
            {
                array_push($errorIps, $ipv);
            }
        }

        if (empty($errorIps) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_IP_FORMAT_INVALID,
                 null,
                 $errorIps
            );
        }

        $accessor = Settings\Accessor::for($this->merchant, Settings\Module::IP_WHITELIST_CONFIG);

        //if merchant has opted out already should be able to opt in from dashboard
        if ($accessor->exists(self::OPT_OUT) === true)
        {
            $this->trace->info(TraceCode::MERCHANT_IP_CONFIG_CREATE_REQUEST_AFTER_OPTED_OUT,
                [
                    'merchant_id'     => $this->merchant->getId(),
                    'whitelisted_ips' => $input['whitelisted_ips'],
                ]);

            $accessor->delete(self::OPT_OUT)->save();
        }

        $this->updateIpConfigForService($input, $accessor);

        if ($this->merchant->isFeatureEnabled(FeatureConstants::ENABLE_IP_WHITELIST) === false)
        {
            $featureParams = [
                Feature\Entity::ENTITY_TYPE => E::MERCHANT,
                Feature\Entity::ENTITY_ID   =>  $this->merchant->getId(),
                Feature\Entity::NAMES       => [FeatureConstants::ENABLE_IP_WHITELIST],
                Feature\Entity::SHOULD_SYNC => true,
            ];

            (new FeatureService())->addFeatures($featureParams);
        }

        return $this->fetchMerchantIpConfig();
    }

    private function updateIpConfigForService(array $input, $accessor)
    {
        $whitelistedIps = $input['whitelisted_ips'];

        $defaultServices = Route::getDefaultServicesEligibleForIpWhitelist();

        if (isset($input['service']) === true)
        {
            if ($this->checkIfIpCountAcrossServiceExhausted($defaultServices, $accessor, $whitelistedIps, $input['service']) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Max No of Ips allowed is' . self::MAX_NO_OF_IPS_ALLOWED);
            }

            $service = $input['service'];

            $this->updateIPConfigRedisKeyAndTable($this->merchant, $service, $whitelistedIps, $accessor);
        }
        else
        {
            foreach ($defaultServices as $service)
            {
                $this->updateIPConfigRedisKeyAndTable($this->merchant, $service, $whitelistedIps, $accessor);
            }
        }
    }

    protected function checkIfIpCountAcrossServiceExhausted(array $defaultServices, $accessor, array $whitelistedIps, string $inputService)
    {
        $totalWhitelisted = [];

        $whitelistRequestedCount = count($whitelistedIps);

        foreach ($defaultServices as $service)
        {
            if ($inputService === $service)
            {
                continue;
            }
            $ipList = json_decode($accessor->get($service), true);

            if (in_array(self::DEFAULT_IP_WHITELIST, $ipList, true) === true)
            {
                $ipList = [];
            }

            $totalWhitelisted = array_unique(array_merge($ipList, $totalWhitelisted));
        }

        if (in_array(self::DEFAULT_IP_WHITELIST, $whitelistedIps, true) === true)
        {
            $whitelistRequestedCount = 0;
        }

        return (count($totalWhitelisted) + $whitelistRequestedCount > self::MAX_NO_OF_IPS_ALLOWED);
    }

    private function updateIPConfigRedisKeyAndTable(Merchant\Entity $merchant, string $service, array $whitelistedIps, $accessor)
    {
        $redisKey = 'ip_config' . '_' . $merchant->getId() . '_' . $service;

        $this->app['redis']->del($redisKey);

        $this->app['redis']->sadd($redisKey, $whitelistedIps);

        $whitelistedIpsUnique = array_unique($whitelistedIps);

        $accessor->upsert($service, json_encode($whitelistedIpsUnique))->save();
    }

    public function editOptStatusForMerchantIPConfig(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_OPT_STATUS_EDIT_REQUEST, $input);

        (new Validator)->validateInput('ipConfigOptStatusEdit', $input);

        $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $optedOut = false;

        $accessor = Settings\Accessor::for($this->merchant, Settings\Module::IP_WHITELIST_CONFIG);

        if ($accessor->exists(self::OPT_OUT) === true)
        {
            $optedOut = true;
        }

        if (boolval($input[self::OPT_OUT]) === $optedOut)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Opting out/in is not allowed since it is already in same state.');
        }

        if (boolval($input[self::OPT_OUT]) === true)
        {
            $input['whitelisted_ips'] = ['*'];

            $this->updateIpConfigForService($input, $accessor);

            // if opt out is not service specific then only mark the overall opt out status for merchant
            if (isset($input['service']) === false)
            {
                $accessor->upsert(self::OPT_OUT, 'true')->save();
            }
        }
        else
        {
            $accessor->delete(self::OPT_OUT)->save();

            $this->updateIpConfigForService($input, $accessor);
        }

        return $this->fetchMerchantIpConfig();
    }

    public function fetchMerchantIpConfig()
    {
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            return $this->fetchMerchantIpConfigForAdmin($this->merchant->getId());
        }

        $response = [];

        $response[self::OPTED_OUT] = false;

        $accessor = Settings\Accessor::for($this->merchant, Settings\Module::IP_WHITELIST_CONFIG);

        $whitelistedIpConfigs = $accessor->all();

        $totalWhitelistedIps = [];

        foreach ($whitelistedIpConfigs as $key => $value)
        {
            $value = json_decode($value, true);

            if (($key === self::OPT_OUT) and
                ($value === true))
            {
                $response[self::OPTED_OUT] = true;

                $totalWhitelistedIps = [];

                break;
            }

            if (in_array(self::DEFAULT_IP_WHITELIST, $value, true) === true)
            {
                continue;
            }

            $totalWhitelistedIps = array_unique(array_merge($totalWhitelistedIps, $value));
        }

        $response['whitelisted_ips'] = $totalWhitelistedIps;

        $response['allowed_ips_count'] = self::MAX_NO_OF_IPS_ALLOWED;

        return $response;
    }

    public function fetchMerchantIpConfigForAdmin(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $response = [];

        $response[self::OPTED_OUT] = false;

        $accessor = Settings\Accessor::for($merchant, Settings\Module::IP_WHITELIST_CONFIG);

        $whitelistedIpConfigs = $accessor->all();

        $whitelistedIps = [];

        foreach ($whitelistedIpConfigs as $key => $value)
        {
            $value = json_decode($value, true);

            if (($key === self::OPT_OUT) and
                ($value === true))
            {
                $response[self::OPTED_OUT] = true;
            }
            else
            {
                $whitelistedIps[$key] = $value;
            }
        }

        $response['whitelisted_ips'] = $whitelistedIps;

        $response['allowed_ips_count'] = self::MAX_NO_OF_IPS_ALLOWED;

        return $response;
    }

    public function blockLinkedAccountCreationIfApplicable(Entity $merchant)
    {
        if (in_array($merchant->getId(), Preferences::BLOCK_LINKED_ACCOUNT_CREATION_MIDS) === true) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_BLOCKED,
                null,
                [
                    'parent_merchant_id' => $merchant->getId(),
                ]
            );
        }
    }

    private function sendAccountMappedToPartnerWebhook($merchant)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $merchant
        ];

        $this->app['events']->dispatch('api.account.mapped_to_partner', $eventPayload);
    }

    /**
     * Triggers async Job for capturing IP and create legal documents for Oauth Authorize.
     * @param string $merchantId
     */
    public function captureConsentsForOauth(string $merchantId, array $data)
    {
        $input = [
            DEConstants::CONSENT            => true,
            DEConstants::IP_ADDRESS         => $data['ip'],
            DEConstants::ENTITY_ID          => $data[Entity::APPLICATION_ID],
            Consent\Entity::ENTITY_TYPE     => Entity::APPLICATION,
            DEConstants::DOCUMENTS_DETAIL   => [
                [
                    DEConstants::TYPE => Constants::TERMS,
                    DEConstants::URL  => Constants::RAZORPAY_PARTNERSHIP_OAUTH_TERMS,
                ]
            ]
        ];

        CapturePartnershipConsents::dispatch($this->mode, $input, $merchantId, Constants::OAUTH);
    }

    /**
     * Fetches the capital applications for a given product for sub-merchants of a partner
     *
     * @param Entity $partner
     * @param array  $input
     *
     * @return JsonResponse|Response
     * @throws BadRequestException
     * @throws Throwable
     */
    public function fetchCapitalApplicationsForSubmerchants(Entity $partner, array $input): JsonResponse|Response
    {
        $appIds = $this->getPartnerApplicationIds($partner);

        // filter out the sub-merchants that the partner actually has access to.
        $merchantIds = $this->repo->merchant_access_map->filterSubmerchantIdsLinkedToAppIdsForProduct(
            $appIds,
            $input[Base\PublicEntity::MERCHANT_ID],
            Product::BANKING,
            [Constants::CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX . $partner->getId()]
        );
        $merchantIds = $merchantIds->pluck(Base\PublicEntity::MERCHANT_ID)->toArray();

        $this->trace->info(
            TraceCode::FETCH_CAPITAL_APPLICATIONS_FOR_SUBMERCHANTS_REQUEST,
            [
                "merchants" => $merchantIds,
                "input"     => $input
            ]
        );

        return $this->capitalSubmerchantUtility()
                    ->fetchApplicationsForSubmerchantsForProduct(
                        $merchantIds,
                        $input[Constants::PRODUCT_ID]
                    );
    }

    //assuming that a record for the merchant will already be existing in the api database
    //since singup will be done at api monolith only
    //so we are only editing the record and not creating it
    public function savePGOSDataToAPI(array $data)
    {
        $splitzResult = (new Detail\Core)->getSplitzResponse($data[Entity::ID], 'pgos_migration_dual_writing_exp_id');

        if ($splitzResult === 'variables')
        {
            $merchant = $this->repo->merchant->find($data[Entity::ID]);

            // dual write only for below merchants
            // merchants for whom pgos is serving onboarding requests
            // merchants who are not completely activated
            if ($merchant->getService() === Merchant\Constants::PGOS and
                $merchant->merchantDetail->getActivationStatus()!=Detail\Status::ACTIVATED)
            {
                unset($data[Entity::ID]);

                $merchant->edit($data);

                $this->repo->saveOrFail($merchant);
            }
        }
    }

    public function fetchAllMerchantEntitiesRelatedInfo(array $merchantList, string $type = "")
    {
        $this->app['rzp.mode'] = 'live';

        $merchantEntitiesInfoErrorCode = [
            'LIST_MAX_SIZE_EXCEEDED' => 'List size exceeded the max limit',
            'INVALID_LIST_TYPE' => 'Invalid merchant list type'
        ];

        $responseArray = [
            'merchant_info' => [],
            'count' => 0,
            'error' => ['code' => '', 'description' => '']
        ];

        $merchantIds = [];

        if (count($merchantList) > self::MAX_ARRAY_LIMIT_FOR_MERCHANT_ENTITIES_INFO)
        {
            $errorCode = 'LIST_MAX_SIZE_EXCEEDED';
            $responseArray['error'] = ['code' => $errorCode, 'description' => $merchantEntitiesInfoErrorCode[$errorCode]];

            return $responseArray;
        }

        switch ($type)
        {
            case "email":
                foreach ($merchantList as $email)
                {
                    $merchant      = $this->repo->merchant->fetchByEmailAndOrgId($email);

                    $merchantIds[] = optional(optional($merchant)->first())->getId();
                }
                break;

            case "id":
                $merchantIds = $merchantList;
                break;

            default:
                $errorCode = 'INVALID_LIST_TYPE';
                $responseArray['error'] = ['code' => $errorCode, 'description' => $merchantEntitiesInfoErrorCode[$errorCode]];

                return $responseArray;
        }

        $count = 0;

        foreach($merchantIds as $merchantId)
        {
            try {
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $merchantWebsite = $this->repo->merchant_website->getAllWebsiteDetailsForMerchantId($merchantId);

                $merchantVerificationDetail = $this->repo->merchant_verification_detail->getDetailsForMerchant($merchantId);

                $bvsValidation = $this->repo->bvs_validation->getAllValidationsForMerchant($merchantId);

                $documents = $this->repo->merchant_document->findAllDocumentsForMerchant($merchantId);

                $merchantInfo = [
                    'merchant'                      => $merchant->getAttributes(),
                    'merchant_detail'               => optional($merchant->merchantDetail)->getAttributes(),
                    'merchant_business_detail'      => optional($merchant->merchantBusinessDetail)->getAttributes(),
                    'merchant_website'              => optional($merchantWebsite->toArray())[0] ?? (new \stdClass()),
                    'merchant_verification_detail'  => $merchantVerificationDetail->toArray(),
                    'bvs_validation'                => $bvsValidation->toArray(),
                    'merchant_document'             => $documents->toArray()
                ];

                $merchantInfo['merchant_business_detail']['website_details'] = optional($merchant->merchantBusinessDetail)->getWebsiteDetails() ?? (new \stdClass());

                $merchantInfo['merchant_business_detail']['app_urls'] = optional($merchant->merchantBusinessDetail)->getAppUrls() ?? (new \stdClass());

                $count += 1;

                $responseArray['merchant_info'][] = $merchantInfo;

            } catch (Throwable $e) {

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ERROR_IN_FETCHING_MERCHANT_ENTITIES_INFO,
                    [
                        Base\PublicEntity::MERCHANT_ID => $merchantId,
                    ]
                );

                continue;
            }
        }

        $responseArray['count'] = $count;

        return $responseArray;
    }

    public function getMerchantAuthorizationForPartner(string $merchantId, string $partnerId) : array
    {
        $subMerchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        $partner->getValidator()->validateIsAggregatorPartner($partner);

        $partnerAccess = $this->isMerchantManagedByPartner($subMerchant->getId(), $partnerId);

        return [
            Constants::PARTNER_ACCESS => $partnerAccess,
            Constants::PARTNER_NAME   => $partner->merchantDetail->getBusinessName(),
            Entity::PARTNER_TYPE      => $partner->getPartnerType()
        ];
    }

    public function saveMerchantAuthorizationToPartner(string $merchantId, array $input) : array
    {
        $subMerchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $partnerId = $input[Merchant\Constants::PARTNER_ID];

        $partner = $this->repo->merchant->findOrFailPublic($partnerId);

        $partner->getValidator()->validateIsAggregatorPartner($partner);

        $partnerAccess = $this->isMerchantManagedByPartner($subMerchant->getId(), $partnerId);

        $accessMap = $this->transaction(function() use ($subMerchant, $partner, $partnerAccess)
        {
            $this->updateMerchantConsentForPartner($subMerchant, $partner);

            if ($partnerAccess === false)
            {
                return $this->createPartnerSubmerchantAccessMap($partner, $subMerchant);
            }
            return [];
        });

        return [
            Constants::PARTNER_ACCESS => ($partnerAccess || (!empty($accessMap)))
        ];
    }

    private function updateMerchantConsentForPartner(Entity $merchant, Entity $partner)
    {
        $partnerConfig = (new PartnerConfig\Core())->fetchPartnersManagedApplicationConfig($partner);

        $merchantDetail = $merchant->merchantDetail;

        $consentDetails = [
            DEConstants::DOCUMENTS_DETAIL => [
                [
                    DEConstants::TYPE    => MerchantConsentConstants::PARTNER_AUTH_TERMS,
                    DEConstants::URL     => Constants::RAZORPAY_PARTNER_AUTH_TERMS,
                    DEConstants::CONTENT => str_replace('{partnerName}', $partnerConfig->getBrandName(), DEConstants::PARTNER_AUTH_CONSENT_TEMPLATE),
                ]
            ],
            DEConstants::IP_ADDRESS       => $this->app['request']->ip(),
            Consent\Entity::ENTITY_ID     => $partner->getId(),
            Consent\Entity::ENTITY_TYPE   => DEConstants::PARTNER,
        ];

        $legalDocumentsInput = [
            DEConstants::IP_ADDRESS      => $this->app['request']->ip(),
            DEConstants::OWNER_NAME      => $merchantDetail->getBusinessName() ?? 'NA',
            DEConstants::SIGNATORY_NAME  => $merchantDetail->getPromoterPanName() ?? 'NA'
        ];

        (new Merchant\Detail\Service())->createMerchantConsent($merchant->getId(), $consentDetails, $legalDocumentsInput, [MerchantConsentConstants::PARTNER_AUTH_TERMS]);
    }

    public function isPaymentsEnabledForNoDocMerchants()
    {
        $properties = [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.enable_payments_for_no_doc_merchants_experiment_id')
        ];

        return $this->isSplitzExperimentEnable($properties, 'enable');
    }

    public function suspendLinkedAccountsOfParentMerchantIfPresent(string $parentMerchantId)
    {
        $iteration = 0;

        do
        {
            $offset = $iteration * 1000;

            $linkedAccountMids = $this->repo->merchant->fetchUnsuspendedLinkedAccountMids($parentMerchantId, $offset);

            $this->trace->info(
                TraceCode::LINKED_ACCOUNTS_FETCHED_FOR_SUSPENSION,
                [
                    'parent_merchant_id'    => $parentMerchantId,
                    'linked_account_ids'    => $linkedAccountMids,
                    'linked_accounts_count' => count($linkedAccountMids),
                    'iteration'             => $iteration,
                ]
            );

            $this->repo->transactionOnLiveAndTest(function () use ($linkedAccountMids) {
                $this->repo->merchant->updateLinkedAccountsAsSuspendedOrUnsuspendedInBulk($linkedAccountMids, true);
            });

            $this->trace->info(
                TraceCode::LINKED_ACCOUNTS_SUSPENSION_SUCCESSFUL,
                [
                    'parent_merchant_id' => $parentMerchantId,
                    'linked_account_ids' => $linkedAccountMids,
                    'iteration'          => $iteration,
                ]
            );

            $iteration += 1;
        }
        while (empty($linkedAccountMids) === false);
    }

    public function unsuspendLinkedAccountsOfParentMerchantIfPresent(string $parentMerchantId)
    {
        $iteration = 0;

        do
        {
            $offset = $iteration * 1000;

            $linkedAccountMids = $this->repo->merchant->fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension($parentMerchantId, $offset);

            $this->trace->info(
                TraceCode::LINKED_ACCOUNTS_FETCHED_FOR_UNSUSPENSION,
                [
                    'parent_merchant_id'    => $parentMerchantId,
                    'linked_account_ids'    => $linkedAccountMids,
                    'linked_accounts_count' => count($linkedAccountMids),
                    'iteration'             => $iteration
                ]
            );

            $this->repo->transactionOnLiveAndTest(function() use ($linkedAccountMids) {
                $this->repo->merchant->updateLinkedAccountsAsSuspendedOrUnsuspendedInBulk($linkedAccountMids, false);
            });

            $this->trace->info(
                TraceCode::LINKED_ACCOUNTS_UNSUSPENSION_SUCCESSFUL,
                [
                    'parent_merchant_id'    => $parentMerchantId,
                    'linked_account_ids'    => $linkedAccountMids,
                    'iteration'             => $iteration,
                ]
            );

            $iteration += 1;
        }
        while (empty($linkedAccountMids) === false);
    }

    public function addFeatureFlagForMerchant(Entity $merchant, string $featureFlag)
    {
        $merchantId = $merchant->getId();

        $context = [
            'merchant_id' => $merchantId,
            'feature_flag' => $featureFlag,
        ];

        if ($merchant->isFeatureEnabled($featureFlag) === true)
        {
            $this->trace->info(
                TraceCode::MERCHANT_FEATURE_FLAG_ALREADY_EXISTS
                , $context);

            return;
        }

        $this->addFeatureFlag($merchantId, $featureFlag);
    }

    protected function addFeatureFlag(string $merchantId, string $featureFlag)
    {
        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchantId,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAMES       => [$featureFlag],
            Feature\Entity::SHOULD_SYNC => true,
        ];

        try
        {
            (new Feature\Service)->addFeatures($featureParams);
        }
        catch (\Throwable $exception)
        {
            $this->trace->info(TraceCode::FEATURE_GET_STATUS_FAILED, [
                'merchant_id'       => $merchantId,
                'feature_flag'      => $featureFlag,
                'error_code'        => $exception->getCode(),
                'error_desc'        => $exception->getMessage(),
            ]);
        }

    }

    private function isIndustryLevelQuery(string $filterName): bool
    {
        return in_array($filterName, AnalyticsConstants::INDUSTRY_LEVEL_QUERIES, true);
    }
}
