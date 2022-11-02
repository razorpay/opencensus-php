<?php

namespace RZP\Models\Partner;

use Razorpay\OAuth;

use Request;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Feature;
use RZP\Trace\Tracer;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Email;
use RZP\Models\Batch\Constants;
use RZP\Constants\Entity as CE;
use RZP\Models\Merchant\Preferences;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Entity as ME;
use RZP\Models\Merchant\Detail\DeDupe;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\WebhookV2\Stork;
use RZP\Models\Merchant\Account\Entity as Account;
use RZP\Models\Batch\Helpers\SubMerchant as Helper;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Jobs\SubMerchantBatchUploadValidationStatusUpdater;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApp;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\FeeBearer;

class SubMerchantBatchUtility extends Base\Core
{
    /**
     * @var MerchantDetailCore
     */
    protected $merchantDetailCore;

    /**
     * @var Merchant\Service
     */
    protected $merchantService;

    /**
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * @var Merchant\Entity
     */
    protected $partner;

    /**
     * @var string
     */
    protected $userId;

    /**
     * Used to check if activation form  needs to be auto-submitted
     *
     * @var bool
     */
    protected $autoSubmit = false;

    /**
     * Used to check if activation form  detail needs to be auto-filled
     *
     * @var bool
     */
    protected $autofillDetails = false;

    /**
     * Used to check if sub-merchants need to be auto-activated
     *
     * @var bool
     */
    protected $autoActivate = false;

    /**
     * Used to check if dedupe need to be performed on Sub Merchant or not.
     *
     * @var bool
     */
    protected $dedupe = false;

    /**
     * Used to check if the sub-merchants need to be instantly activated.
     *
     * @var bool
     */
    protected $instantlyActivate = false;

    /**
     * Used to check if sub-merchant email needs to be treated as dummy
     * when provided in which case the submerchant email is same as the
     * partner email and the dummy is stored in the merchant_emails table
     * for business purposes.
     *
     * @var bool
     */
    protected $useMerchantEmailAsDummy = true;

    /**
     *  This variables contain all the config passed to the batch
     */
    protected $settings = [];

    /**
     *  When mid is not passed in the input header file then batch service sends this static string to API as
     *  request param. Due to this new sub-merchant account creation flow is breaking.
     */
    const STATIC_BATCH_MID  = '##merchant_id##';

    public function processSubMerchantEntry(array & $entry, array $configs)
    {
        /**
         * If dedupe entry value is not send from front end then unset it from being logged in Sumo.
         * Otherwise it will be create unnecessarily confusion because it is not public accessible only some specific
         * User who has dedupe permission can pass its value.
         */
        if (isset($entry[ME::DEDUPE]) === false)
        {
            unset($entry[ME::DEDUPE]);
            unset($configs[ME::DEDUPE]);
        }

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_CREATE_ENTRY,
            [
                'entry'  => $entry,
                'config' => $configs,
            ]
        );

        $subMerchant = $this->repo->transactionOnLiveAndTest(function() use (& $entry, $configs)
        {
            $this->preProcessConfigs($configs);
            $subMerchant = $this->createSubMerchantForEntry($entry);
            $this->unsetExtraOutputKeys($entry);
            return $subMerchant;
        });

        $properties = [
            'id' => $this->partner->getId(),
            'experiment_id' => $this->app['config']->get('app.submerchant_bulk_validation_status_update_exp_id')
        ];

        $isExpEnable = (new Merchant\Core())->isSplitzExperimentEnable($properties,'enable');

        if($isExpEnable === true)
        {
            SubMerchantBatchUploadValidationStatusUpdater::dispatch($this->mode, $subMerchant->getId());
        }

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_CREATED,
            [
                'subMerchantId' => $subMerchant->getId(),
            ]
        );

        Tracer::inSpan(
            ['name' => 'submerchant_onboarding_batch.process_sub_merchant.invalidate_cache'],
            function () use ($subMerchant)
            {
                $this->invalidateAffectedOwnersCache($subMerchant->getId());
            }
        );

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_CREATE_CACHE_INVALIDATED,
            [
                'subMerchantId' => $subMerchant->getId(),
                'status'         => $entry[Header::STATUS],
                'merchant_id'    => $entry[Header::MERCHANT_ID],
                'merchant_email' => $entry[Header::MERCHANT_EMAIL],
                'partner_id'     => $configs[Header::PARTNER_ID],
            ]
        );

        $subMerchantDetails = (new MerchantDetailCore)->getMerchantDetails($subMerchant);

        $currentActivationStatus = $subMerchantDetails->getActivationStatus();

        if($currentActivationStatus=="activated")
        {
            $this->app['terminals_service']->requestDefaultMerchantInstruments($subMerchant->getId());
        }

        $this->trace->info(
            TraceCode::BATCH_SERVICE_SUBMERCHANT_CREATE_RESPONSE,
            [
                'status'         => $entry[Header::STATUS],
                'merchant_id'    => $entry[Header::MERCHANT_ID],
                'merchant_email' => $entry[Header::MERCHANT_EMAIL],
                'partner_id'     => $configs[Header::PARTNER_ID],
            ]
        );

        return $entry;
    }

    protected function preProcessConfigs(array $configs)
    {
        $this->merchantDetailCore = new MerchantDetailCore;

        $this->merchantService  = new Merchant\Service;

        $this->merchantCore = new Merchant\Core;

        $this->settings = $configs;

        $this->autoSubmit = (empty($this->settings[ME::AUTO_SUBMIT]) === false);

        $this->autofillDetails = (empty($this->settings[ME::AUTOFILL_DETAILS]) === false);

        $this->autoActivate = (empty($this->settings[ME::AUTO_ACTIVATE]) === false);

        $this->instantlyActivate = (empty($this->settings[ME::INSTANTLY_ACTIVATE]) === false);

        $this->dedupe = (empty($this->settings[ME::DEDUPE]) === false);

        //
        // This is true by default and needs to be overridden only when an input
        // is set to False explicitly, it should not be overridden by null. Hence
        // the following explicitly check for isset.
        //
        if (isset($this->settings[ME::USE_EMAIL_AS_DUMMY]) === true)
        {
            $this->useMerchantEmailAsDummy = (bool) $this->settings[ME::USE_EMAIL_AS_DUMMY];
        }

        //
        // set default values for  AUTO_ENABLE_INTERNATIONAL and SKIP_BA_REGISTRATION as false as of now
        // once dashboard changes are done for supporting these two fields we can remove default values of these fields
        //

        $this->settings[ME::AUTO_ENABLE_INTERNATIONAL] = (bool) ($this->settings[ME::AUTO_ENABLE_INTERNATIONAL] ?? false);
        $this->settings[ME::SKIP_BA_REGISTRATION]      = (bool) ($this->settings[ME::SKIP_BA_REGISTRATION] ?? true);

        $this->partner = $this->repo->merchant->findOrFailPublic($this->settings[ME::PARTNER_ID]);

        $this->updateAuthDetails($this->partner);

        $this->userId = $this->partner->primaryOwner()->getId();
    }

    /**
     * @param array $entry
     *
     * @return Merchant\Entity
     * @throws \RZP\Exception\BadRequestException
     * @throws BadRequestValidationFailureException
     */
    protected function createOrFetchSubMerchant(array &$entry)
    {
        $input = Helper::getSubMerchantInput($entry, $this->userId, $this->useMerchantEmailAsDummy);

        if (empty($entry[Header::MERCHANT_ID]) === true or $entry[Header::MERCHANT_ID] === self::STATIC_BATCH_MID)
        {
            Request::instance()->request->add([User\Entity::SKIP_CAPTCHA_VALIDATION => true]);

            $subMerchantArray = $this->merchantService->createSubMerchant($input, $this->partner, PartnerConstants::BULK_ONBOARDING_ADMIN);

            $subMerchant = $this->repo->merchant->findOrFailPublic(
                Account::verifyIdAndStripSign($subMerchantArray[ME::ID]));
        }
        else
        {
            $subMerchant = $this->repo->merchant->findOrFailPublic($entry[Header::MERCHANT_ID]);

            $subMerchantEmail = strtolower($entry[Header::MERCHANT_EMAIL]);

            $isMapped = (new Merchant\AccessMap\Core())->isMerchantMappedToPartnerWithAppType($this->partner, $subMerchant, MerchantApp::MANAGED);

            //
            // Do not update sub-merchant details if any one of the following conditions are true
            // 1. If partner and sub-merchant are not mapped
            // 2. If sub-merchant email is given in the input and is not same as the one in DB
            //
            if ($isMapped === false)
            {
                $entry[Header::STATUS]            = Status::FAILURE;
                $entry[Header::ERROR_DESCRIPTION] = PublicErrorDescription::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND;

                $msg = $entry[Header::ERROR_DESCRIPTION];

                throw new BadRequestValidationFailureException($msg, Entity::FILE, $subMerchant->getEmail());
            }
            else if (($entry[Header::MERCHANT_EMAIL] !== '') and ($subMerchant->getEmail() !== $subMerchantEmail))
            {
                $entry[Header::STATUS]            = Status::FAILURE;
                $entry[Header::ERROR_DESCRIPTION] = PublicErrorDescription::BAD_REQUEST_MERCHANT_EMAIL_AND_INPUT_EMAIL_DIFFERENT;

                $msg = $entry[Header::ERROR_DESCRIPTION];

                throw new BadRequestValidationFailureException($msg, Entity::FILE, $subMerchant->getEmail());
            }
        }

        return $subMerchant;
    }

    /**
     * @param  array $entry
     *
     * @return ME
     * @throws \RZP\Exception\BadRequestException
     * @throws \Throwable
     */
    protected function createSubMerchantForEntry(array & $entry)
    {
        $entry = Helper::sanitizeMerchantDetailInput($entry, Constants::CONFIG_PARAMS);

        $subMerchant = $this->createOrFetchSubMerchant($entry);

        if (empty($subMerchant) === true)
        {
            $entry[Header::STATUS]            = Status::FAILURE;
            $entry[Header::ERROR_DESCRIPTION] = 'Could not create/Find merchant';

            return null;
        }

        $status = Status::SUCCESS;

        if ($this->instantlyActivate === true)
        {
            $subMerchant = Tracer::inSpan(
                ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.instantly_activate'],
                function () use ($entry, $subMerchant)
                {
                    $instantActivationInput = Helper::getInstantActivationInput($entry);

                    $this->merchantDetailCore->saveInstantActivationDetails($instantActivationInput, $subMerchant);

                    //
                    // We are updating merchant object in instant activation flow , so reloading object so that we have updated merchant object
                    //
                    $subMerchant->reload();

                    return $subMerchant;
                }
            );
        }

        if ($this->autofillDetails === true)
        {
            $detailInput = Tracer::inSpan(
                ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.autofill.activation_form'],
                function () use ($entry)
                {
                    // Fill in merchant details (activation form)
                    return Helper::getSubMerchantDetailInput($entry, $this->partner, $this->useMerchantEmailAsDummy);
                }
            );

            if ($subMerchant->isActivated() === true)
            {
                $detailInput = Tracer::inSpan(
                    ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.autofill.activated'],
                    function () use ($detailInput)
                    {
                        //
                        // If merchant is coming from instant activation flow , we don't allow change in business category
                        // and subcategory field so removing these two fields from input
                        //
                        return Helper::sanitizeMerchantDetailInput($detailInput, Constants::CATEGORY_DETAILS);
                    }
                );
            }

            if ($this->merchantDetailCore->shouldSkipBankAccountRegistration() == true)
            {
                $detailInput = Tracer::inSpan(
                    ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.autofill.skip_bank_register'],
                    function () use ($detailInput)
                    {
                        //
                        // SubMerchant batch upload flow allows skipping bank account registration as the partner
                        // is there liable for the risk and the sub-merchants must be activated directly.
                        //  so removing bank account details from input
                        //
                        return Helper::sanitizeMerchantDetailInput($detailInput, Constants::BANK_DETAILS);
                    }
                );
            }

            if (empty($entry[Header::MERCHANT_ID]) === false)
            {
                $this->trace->info(
                    TraceCode::EDIT_SUBMERCHANT_FROM_MERCHANT_ID,
                    [
                        'merchant_id'   => $subMerchant->getId(),
                        'input'         => $entry,
                        'upsert_fields' => $detailInput,
                    ]
                );
            }

            $this->merchantDetailCore->saveMerchantDetails($detailInput, $subMerchant);
        }

        if ($this->autoSubmit === true)
        {
            Tracer::inSpan(
                ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.autosubmit.save_files'],
                function () use ($subMerchant)
                {
                    // Save files
                    $this->merchantDetailCore->saveDummyActivationFiles($subMerchant);
                }
            );

            // for registered merchants aadhar esign status will be validated before form submission
            // if aadhar lined linked is set to 1. Which we do not want in this case.
            // more context here https://razorpay.slack.com/archives/C4MSCSHSL/p1646395661750039
            // Submit activation form
            $submitData = [
                CE::STAKEHOLDER => [
                    Stakeholder\Entity::AADHAAR_LINKED => 0,
                ],
                MerchantDetail::SUBMIT => '1'
            ];
            $response   = $this->merchantDetailCore->saveMerchantDetails($submitData, $subMerchant);

            if ($response[MerchantDetail::SUBMITTED] === false)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_ACTIVATION_FORM_SUBMISSION_FAILURE,
                    [
                        'response'    => $response,
                        'merchant_id' => $subMerchant->getId(),
                    ]);

                $status                           = Status::FAILURE;
                $entry[Header::ERROR_DESCRIPTION] = 'Activation details not submitted successfully';
            }

            if (($response[MerchantDetail::SUBMITTED] === true) and ($this->autoActivate === true))
            {
                $status = Status::SUCCESS;

                Tracer::inSpan(
                    ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.autosubmit.auto_update_category_details'],
                    function () use ($entry, $subMerchant)
                    {
                        $this->merchantCore->autoUpdateCategoryDetails(
                            $subMerchant,
                            $entry[Header::BUSINESS_CATEGORY],
                            $entry[Header::BUSINESS_SUB_CATEGORY]);
                    }
                );

                $websiteUpdateData = [ME::WEBSITE => $entry[Header::WEBSITE_URL]];

                $this->merchantCore->edit($subMerchant, $websiteUpdateData);

                $response = $this->setMerchantActivationStatus($subMerchant,$response, $this->dedupe);

                if ($response[ME::ACTIVATED] === false)
                {
                    $status = Status::FAILURE;

                    $entry[Header::ERROR_DESCRIPTION] = 'Merchant not activated successfully';
                }
            }
        }

        if ($subMerchant->org->isFeatureEnabled(Feature\Constants::SUB_MERCHANT_PRICING_AUTOMATION))
        {
            if (empty($entry[Header::FEE_BEARER]) !== true)
            {
                $this->merchantCore->updateSubMerchantFeeBearer($subMerchant, $entry[Header::FEE_BEARER]);
            }

            $this->merchantCore->updateSubMerhantPricingPlanBasedOnFeeBearerAndSubcategory($subMerchant);
        }

        if (($this->useMerchantEmailAsDummy === true) and (empty($entry[Header::MERCHANT_EMAIL]) === false))
        {
            $emailInput = [
                Email\Entity::EMAIL => $entry[Header::MERCHANT_EMAIL],
                Email\Entity::TYPE  => Email\Type::PARTNER_DUMMY,
            ];

            Tracer::inSpan(
                ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.upsert_email'],
                function () use ($subMerchant, $emailInput)
                {
                    (new Email\Core)->upsert($subMerchant, $emailInput);
                }
            );
        }

        $entry[Header::STATUS]      = $status;
        $entry[Header::MERCHANT_ID] = Account::getSignedId($subMerchant->getId());

        Tracer::inSpan(
            ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.add_mswipe_config'],
            function () use ($subMerchant, $entry)
            {
                $this->addMSwipeConfigurations($subMerchant, $entry);
            }
        );

        return $subMerchant;
    }

    /**
     * This check Dedupe 1st if it's run and return true, don't update merchant status because here
     * Merchant will be already in expected state i.e Under Review.
     * If Dedupe return false that means merchant is not found as impersonated then update status.
     *
     * @param ME $subMerchant
     * @param $response
     * @param bool $force
     * @return MerchantDetail
     * @throws \Throwable
     */
    protected function setMerchantActivationStatus(Merchant\Entity $subMerchant,$response, $force = false)
    {
        [$isImpersonated, $action] = (new DeDupe\Core())->match($subMerchant, $force);

        if($isImpersonated === true)
        {
            $this->trace->info(
                TraceCode::DEDUPE_SUCCESS_SUB_MERCHANT_IS_IMPERSONATE,
                [
                    'orgId'         => $subMerchant->getOrgId(),
                    'merchant_id'   => $subMerchant->getId(),
                    'dedupe'        => $force,
                ]
            );

            return $response;
        }

        $activationStatusData = $this->getApplicableActivationStatusForPartner($subMerchant);

        $response = Tracer::inSpan(['name' => 'submerchant_onboarding_batch.updateActivationStatus'],
            function () use ($subMerchant, $activationStatusData)
            {
                return $this->merchantDetailCore->updateActivationStatus($subMerchant, $activationStatusData, $subMerchant);
            });

        return $response;
    }

    /**
     * This returns merchant status to be updated. If feature flag is enabled on org,
     * Then return activate with mcc pending otherwise return activated, as this is a regular flow.
     *
     * @param ME $subMerchant
     * @return array
     */
    protected function getApplicableActivationStatusForPartner(Merchant\Entity $subMerchant)
    {
        if($subMerchant->org->isFeatureEnabled(Feature\Constants::ORG_SUB_MERCHANT_MCC_PENDING) === true)
        {
            return [MerchantDetail::ACTIVATION_STATUS => Merchant\Detail\Status::ACTIVATED_MCC_PENDING];
        }

        return [MerchantDetail::ACTIVATION_STATUS => Merchant\Detail\Status::ACTIVATED];
    }


    protected function unsetExtraOutputKeys(array & $entry)
    {
        $outputHeaders = Header::HEADER_MAP[Type::SUB_MERCHANT][Header::OUTPUT];

        $entry = array_only($entry, $outputHeaders);
    }

    /**
     * updates merchant information into auth,
     * this is being used to set org id and merchant info
     *
     * @param ME    $merchant
     * @param array $config
     */
    private function updateAuthDetails(ME $merchant)
    {
        $this->app['basicauth']->setMerchant($merchant);

        $batchContext = [
            Entity::TYPE    => Type::SUB_MERCHANT,
            Constants::DATA => $this->settings,
        ];

        $this->app['basicauth']->setBatchContext($batchContext);
    }

    /**
     * MSwipe merchants require some extra configurations to be added. This should ideally be a
     * part of a separate batch or workflow, but adding a new batch will take time, and existing
     * workflows alternatives all buckle under the stress of MSwipe numbers. Eg. Assigning four
     * features to 5000 merchants everyday generally results in a spike in queued messages and
     * Slack webhooks getting throttled. It also results in a massive loss of time, since there
     * are dedicated people in activations who work on nothing but MSwipe activations on some
     * days. So yes, this is a hack, but a very VERY useful one.
     */
    protected function addMSwipeConfigurations($subMerchant, $entry)
    {
        if ($this->isMswipeSubmerchant() === false)
        {
            return;
        }

        Tracer::inSpan(
            ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.add_mswipe_config.assign_settlement_schedule'],
            function () use ($subMerchant)
            {
                (new Merchant\Service)->assignSettlementSchedule($subMerchant->getId(), [
                    'schedule_id' => Preferences::MSWIPE_SETTLEMENT_SCHEDULE_ID,
                ]);
            }
        );

        Tracer::inSpan(
            ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.add_mswipe_config.assign_pricing_plan'],
            function () use ($subMerchant)
            {
                (new Merchant\Service)->assignPricingPlan($subMerchant->getId(), [
                    Merchant\Entity::PRICING_PLAN_ID => Preferences::MSWIPE_PRICING_PLAN_ID,
                ]);
            }
        );

        Tracer::inSpan(
            ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.add_mswipe_config.feature.create'],
            function () use ($subMerchant)
            {
                foreach (Preferences::MSWIPE_FEATURE_LIST as $featureName)
                {
                    (new Feature\Core)->create([
                        Feature\Entity::ENTITY_TYPE => CE::MERCHANT,
                        Feature\Entity::ENTITY_ID   => $subMerchant->getId(),
                        Feature\Entity::NAME        => $featureName,
                    ], true);
                }
            }
        );

        Tracer::inSpan(
            ['name' => 'submerchant_onboarding_batch.process_sub_merchant.create.add_mswipe_config.update_payment_method'],
            function () use ($subMerchant)
            {
                (new Merchant\Service)->updatePaymentMethods($subMerchant->getId(), Preferences::MSWIPE_METHOD_LIST);
            }
        );
    }

    protected function isMswipeSubmerchant()
    {
        if ($this->partner->getId() === Preferences::MSWIPE_PARTNER_MID)
        {
            return true;
        }

        return false;
    }

    private function invalidateAffectedOwnersCache(string $merchantId)
    {
        (new Stork('live'))->invalidateAffectedOwnersCache($merchantId);
        (new Stork('test'))->invalidateAffectedOwnersCache($merchantId);
    }
}
