<?php

namespace RZP\Models\Partner;

use Throwable;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Models\Partner\Activation;
use RZP\Models\Merchant\AccessMap;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Jobs\SubmerchantFirstTransactionEvent ;
use RZP\Services\Segment\EventCode as SegmentEvent;

class Service extends Base\Service
{
    private $merchantCore;

    private $activationCore;

    private $merchantValidator;

    private $partnerActivationValidator;

    public function __construct()
    {
        $this->core = new Core();

        $this->merchantCore = new Merchant\Core();

        $this->activationCore = new Activation\Core();

        $this->merchantValidator = new Merchant\Validator();

        $this->partnerActivationValidator = new Activation\Validator();

        parent::__construct();
    }

    /**
     * @param   string  $merchant_id Merchant id for which need to check if exp enabled
     *
     * @return  bool    is experiment enabled
     */
    private function isUserRoleMigrationExpEnabled(string $merchant_id): bool
    {
        if (empty($merchant_id) === true)
        {
            return false;
        }

        return $this->merchantCore->isSplitzExperimentEnable(
            [
                'id'            => $merchant_id,
                'experiment_id' => $this->app['config']->get('app.user_role_migration_for_x_exp_id'),
            ],
            'enable'
        );
    }

     /**
     * Changes the referred application based sub-merchants to managed application sub-merchants
     *
     * @param   array  $input  { "partner_id" => <aggregator_partner> }
     *
     * @return  array  List of affected sub-merchants
     */
    public function migrateReferredSubMToManagedSubM(array $input)
    {
        // currently any private auth can also be accessed via partner auth creds too.
        // incase request is made via partner auth creds, then we need to get merchant_id from different function
        // and if request came via private auth then other function
        $merchantId = $this->auth->isPartnerAuth() ? $this->auth->getPartnerMerchantId() : $this->auth->getMerchantId();
        if ($this->isUserRoleMigrationExpEnabled($merchantId) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ROUTE_DISABLED);
        }

        $this->trace->info(TraceCode::REFERRED_TO_MANAGED_MIGRATION_START, ['merchant_id' => $merchantId]);

        (new Validator())->validateInput('migrateReferredSubMToManagedSubM', $input);

        $partnerId = $input['partner_id'];
        $partner = $this->repo->merchant->findOrFail($partnerId);
        if ($partner->isAggregatorPartner() === false)
        {
            return null;
        }

        $referredAppIds = $this->merchantCore->getPartnerApplicationIds($partner, [MerchantApplications\Entity::REFERRED]);
        $managedAppIds = $this->merchantCore->getPartnerApplicationIds($partner, [MerchantApplications\Entity::MANAGED]);

        $accessMaps = $this->repo->merchant_access_map->getAllMappingsByEntityIdAndEntityOwnerId($referredAppIds[0], $partnerId);
        (new AccessMap\Core())->updateApplications($accessMaps, $managedAppIds[0]);

        return $accessMaps->pluck(MerchantEntity::MERCHANT_ID)->toArray();
    }

    /**
     * @throws Throwable
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function savePartnerDetailsForActivation(array $input)
    {
        $this->partnerActivationValidator->validatePartnerFormSaveAndSubmit($this->merchant);

        $this->partnerActivationValidator->validateInput('savePartnerActivation', $input);

        $this->merchant->load('merchantDetail');

        $merchantDetail = $this->merchant->merchantDetail;

        $merchantInput = $input;

        unset($merchantInput[Detail\Entity::SUBMIT]);
        unset($merchantInput[Detail\Entity::KYC_CLARIFICATION_REASONS]);

        if (empty($merchantInput) === false)
        {
            $merchantInput[Activation\Constants::PARTNER_KYC_FLOW] = true;

            (new Detail\Core())->saveMerchantDetails($merchantInput, $this->merchant);
        }

        $response = $this->core->processPartnerActivation($input, $merchantDetail, $this->merchant);

        $response[Detail\Constants::LOCK_COMMON_FIELDS] = (new Detail\Core())->fetchCommonFieldsToBeLocked($merchantDetail);

        return $response;
    }

    public function getPartnerActivationDetails()
    {

        $this->merchantValidator->validateIsPartner($this->merchant);

        $merchantDetails = $this->merchant->merchantDetail;

        $response = $this->core->createPartnerResponse($merchantDetails);

        $response[Detail\Constants::LOCK_COMMON_FIELDS] = (new Detail\Core())->fetchCommonFieldsToBeLocked($merchantDetails);

        return $response;
    }

    public function updatePartnerActivationStatus(string $merchantId, array $input)
    {
        $activationCore = new Activation\Core();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $this->merchantValidator->validateIsPartner($merchant);

        $partnerActivation = $activationCore->createOrFetchPartnerActivationForMerchant($merchant, false);

        $admin = $this->app['basicauth']->getAdmin();

        return $this->activationCore->updatePartnerActivationStatus($merchant, $partnerActivation, $admin, $input);
    }


    public function editPartnerActivationDetails($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $this->core->editPartnerActivation($merchant, $input);

        return $this->core->createPartnerResponse($merchant->merchantDetail);
    }

    public function performAction(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PARTNER_ACTION_DATA,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $this->merchantValidator->validateIsPartner($merchant);

        $partnerActivation = $this->core->getPartnerActivation($merchant);

        return $this->activationCore->performAction($partnerActivation, $input);
    }

    public function bulkAssignReviewer(array $input): array
    {
        (new Detail\Validator())->validateInput('bulk_assign_reviewer', $input);

        $merchants  = $input[Detail\Entity::MERCHANTS];

        $reviewerId = $input[Detail\Entity::REVIEWER_ID];

        return $this->activationCore->bulkAssignReviewer($reviewerId, $merchants);
    }

    public function sendEventsOfPartnersWithPendingCommissionAndIncompleteKYC(): array
    {

        $partnerIdsWithIncompleteKyc = $this->repo->partner_activation->fetchPartnersWithIncompleteKyc();

        $month = Carbon::now(Timezone::IST)->month;

        foreach($partnerIdsWithIncompleteKyc as $partnerId)
        {
            $partner = $this->repo->merchant->fetchMerchantFromId($partnerId);

            $commissionBalance = $partner->commissionBalance;

            if($commissionBalance !== null and ($commissionBalance->getBalance() > 0))
            {
                $properties = [
                    'partner_id'     =>  $partnerId,
                    'product_group'  =>  $partner[Merchant\Entity::PRODUCT],
                    'month'          =>  $month,
                    'commission'     =>  $commissionBalance->getBalance()
                ];

                $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                    $partner, $properties, SegmentEvent::PARTNER_HAVE_COMMISSION);
            }

        }

        return ['success' => 'true'];
    }

    public function sendPartnerWeeklyActivationSummaryEmails(array $input)
    {
        $limit = $input['limit'] ?? null;

        $afterId = $input['afterId'] ?? null;

        $mock = $input['mock'] ?? false;

        return $this->core->dispatchPartnerWeeklyActivationSummaryMails($limit, $afterId, $mock);
    }

    public function sendSubmerchantFirstTransactionSegmentEvents(array $input)
    {
        SubmerchantFirstTransactionEvent::dispatch($this->mode, $input);

        return ['triggered' => 'true', 'input' => $input];
    }
    /**
     * bulk migrates reseller partners to aggregator partners by pushing jobs
     *
     * @param $input {
     *                  "data" => array({ "merchant_id" => <merchantID>, "new_auth_create" => <Boolean> }),
     *                  "batch_size" => <Int>
     *              }
     * @return mixed
     * @throws Throwable
     */
    public function bulkMigrateResellerToAggregatorPartner(array $input)
    {
        if ($this->isPartnerTypeMigrationExpEnabled() === false)
        {
            return ['success' => true, 'errorMessage' => null];
        }
        return $this->core()->bulkMigrateResellerToAggregatorPartner($input);
    }

    /**
     * migrates a single reseller partner to aggregator partner
     *
     * @param $input { "merchant_id" => <merchantID>, "new_auth_create" => Boolean }
     * @return mixed
     * @throws Throwable
     */
    public function migrateResellerToAggregatorPartner($input)
    {
        if ($this->isPartnerTypeMigrationExpEnabled() === false)
        {
            return ['success' => true, 'errorMessage' => "Merchant is not allowed for migration."];
        }

        $traceInfo = ['params' => $input];
        $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_AGGREGATOR_REQUEST, $traceInfo);
        $result = null;

        try
        {
            $result = $this->core()->migrateResellerToAggregatorPartner($input);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_ERROR, $traceInfo);
            throw $e;
        }

        $traceInfo = ['success' => $result, 'errorMessage' => null];
        $this->trace->info(TraceCode::MIGRATE_RESELLER_TO_AGGREGATOR_SUCCESS, $traceInfo);
        return $traceInfo;
    }

    /**
     * Checks whether merchant from partner auth is allowed to run this migration.
     *
     * @return bool
     */
    private function isPartnerTypeMigrationExpEnabled() : bool
    {
        $merchantId = $this->auth->isPartnerAuth() ? $this->auth->getPartnerMerchantId() : $this->auth->getMerchantId();
        $properties = [
            'id'            => $merchantId,
            'experiment_id' => $this->app['config']->get('app.partner_type_migration_exp_id'),
        ];
        return $this->merchantCore->isSplitzExperimentEnable($properties, 'enable');
    }
}
