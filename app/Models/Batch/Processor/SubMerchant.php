<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Merchant\Email;
use RZP\Constants\Entity as CE;
use RZP\Models\Batch\Constants;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Merchant\Entity as ME;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Account\Entity as Account;
use RZP\Models\Batch\Helpers\SubMerchant as Helper;
use RZP\Models\Partner\Constants as PartnerConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApp;

class SubMerchant extends Base
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

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->merchantDetailCore = new MerchantDetailCore;

        $this->merchantService  = new Merchant\Service;

        $this->merchantCore = new Merchant\Core;
    }

    protected function processEntry(array & $entry)
    {
        $subMerchant = $this->repo->transactionOnLiveAndTest(function() use (& $entry)
        {
            $subMerchant = $this->createSubMerchantForEntry($entry);
            $this->unsetExtraOutputKeys($entry);
            return $subMerchant;
        });

        $subMerchantDetails = (new MerchantDetailCore)->getMerchantDetails($subMerchant);

        $currentActivationStatus = $subMerchantDetails->getActivationStatus();

        if($currentActivationStatus=="activated")
        {
            try
            { // check whether merchant is in db
                $merchant = $this->repo->merchant->findOrFail($subMerchant->getId());

                $this->app['terminals_service']->requestDefaultMerchantInstruments($merchant->getId());
            }
            catch(\Exception $e)
            {
                $data = [
                    Entity::MERCHANT_ID => $subMerchant->getId(),
                    'error'             => $e->getMessage()
                ];

                $this->trace->info(TraceCode::MERCHANT_DOES_NOT_EXIST, $data);

            }
        }
    }

    protected function performPreProcessingActions()
    {
        $config = $this->settingsAccessor->all()->toArray();

        $this->settings = array_merge($this->params, $config);

        $this->autoSubmit = (empty($this->settings[ME::AUTO_SUBMIT]) === false);

        $this->autofillDetails = (empty($this->settings[ME::AUTOFILL_DETAILS]) === false);

        $this->autoActivate = (empty($this->settings[ME::AUTO_ACTIVATE]) === false);

        $this->instantlyActivate = (empty($this->settings[ME::INSTANTLY_ACTIVATE]) === false);

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

        return parent::performPreProcessingActions();
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

        $this->app['basicauth']->setBatchContext($this->getBatchContext($this->settings));
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
            $instantActivationInput = Helper::getInstantActivationInput($entry);

            $this->merchantDetailCore->saveInstantActivationDetails($instantActivationInput, $subMerchant);

            //
            //
            // We are updating merchant object in instant activation flow , so reloading object so that we have updated merchant object
            //
            $subMerchant->reload();
        }

        if ($this->autofillDetails === true)
        {
            // Fill in merchant details (activation form)
            $detailInput = Helper::getSubMerchantDetailInput($entry, $this->partner, $this->useMerchantEmailAsDummy);

            if ($subMerchant->isActivated() === true)
            {
                //
                // If merchant is coming from instant activation flow , we don't allow change in business category
                // and subcategory field so removing these two fields from input
                //
                $detailInput = Helper::sanitizeMerchantDetailInput($detailInput, Constants::CATEGORY_DETAILS);
            }

            if ($this->merchantDetailCore->shouldSkipBankAccountRegistration() == true)
            {
                //
                // SubMerchant batch upload flow allows skipping bank account registration as the partner
                // is there liable for the risk and the sub-merchants must be activated directly.
                //  so removing bank account details from input
                //
                $detailInput = Helper::sanitizeMerchantDetailInput($detailInput, Constants::BANK_DETAILS);
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
            // Save files
            $this->merchantDetailCore->saveDummyActivationFiles($subMerchant);

            // Submit activation form
            $submitData = [MerchantDetail::SUBMIT => '1'];
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
                $this->merchantCore->autoUpdateCategoryDetails(
                        $subMerchant,
                        $entry[Header::BUSINESS_CATEGORY],
                        $entry[Header::BUSINESS_SUB_CATEGORY]);

                $websiteUpdateData = [ME::WEBSITE => $entry[Header::WEBSITE_URL]];

                $this->merchantCore->edit($subMerchant, $websiteUpdateData);

                $activationStatusData = [
                    MerchantDetail::ACTIVATION_STATUS => Merchant\Detail\Status::ACTIVATED
                ];

                $response = $this->merchantDetailCore->updateActivationStatus($subMerchant, $activationStatusData, $subMerchant);

                if ($response[ME::ACTIVATED] === false)
                {
                    $status = Status::FAILURE;

                    $entry[Header::ERROR_DESCRIPTION] = 'Merchant not activated successfully';
                }
            }
        }

        if (($this->useMerchantEmailAsDummy === true) and (empty($entry[Header::MERCHANT_EMAIL]) === false))
        {
            $emailInput = [
                Email\Entity::EMAIL => $entry[Header::MERCHANT_EMAIL],
                Email\Entity::TYPE  => Email\Type::PARTNER_DUMMY,
            ];

            (new Email\Core)->upsert($subMerchant, $emailInput);
        }

        $entry[Header::STATUS]      = $status;
        $entry[Header::MERCHANT_ID] = Account::getSignedId($subMerchant->getId());

        $this->addMSwipeConfigurations($subMerchant, $entry);

        return $subMerchant;
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

        (new Merchant\Service)->assignSettlementSchedule($subMerchant->getId(), [
            'schedule_id' => Preferences::MSWIPE_SETTLEMENT_SCHEDULE_ID,
        ]);

        (new Merchant\Service)->assignPricingPlan($subMerchant->getId(), [
            Merchant\Entity::PRICING_PLAN_ID => Preferences::MSWIPE_PRICING_PLAN_ID,
        ]);

        foreach (Preferences::MSWIPE_FEATURE_LIST as $featureName)
        {
            (new Feature\Core)->create([
                Feature\Entity::ENTITY_TYPE => CE::MERCHANT,
                Feature\Entity::ENTITY_ID   => $subMerchant->getId(),
                Feature\Entity::NAME        => $featureName,
            ], true);
        }

        (new Merchant\Service)->updatePaymentMethods($subMerchant->getId(), Preferences::MSWIPE_METHOD_LIST);
    }

    protected function isMswipeSubmerchant()
    {
        if ($this->partner->getId() === Preferences::MSWIPE_PARTNER_MID)
        {
            return true;
        }

        return false;
    }

    protected function unsetExtraOutputKeys(array & $entry)
    {
        $outputHeaders = Header::HEADER_MAP[Type::SUB_MERCHANT][Header::OUTPUT];

        $entry = array_only($entry, $outputHeaders);
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }

    protected function resetErrorOnSuccess(): bool
    {
        return false;
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

        if (empty($entry[Header::MERCHANT_ID]) === true)
        {
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
}
