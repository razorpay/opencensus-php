<?php

namespace RZP\Models\Merchant;

use Mail;
use RZP\Jobs\PaymentPageProcessor;
use RZP\Models\Feature\Constants;
use Throwable;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Counter;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\VirtualAccount;
use RZP\Models\BankingAccount;
use RZP\Mail\Merchant\AxisActivation;
use RZP\Models\BankingAccountTpv;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\User\Service as UserService;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Admin\Org\Hostname\Entity as HostNameEntity;
use RZP\Mail\Merchant\RazorpayX\AccountActivationConfirmation;
use RZP\Mail\Merchant\InstantActivation as InstantActivationMail;
use RZP\Mail\Merchant\RazorpayX\InstantActivation as RazorpayXInstantActivationMail;
use RZP\Services\Segment\EventCode as SegmentEvent;

class Activate extends Base\Core
{
    use NotifyTrait;

    const BUSINESS_BANKING_ENABLED_TOPIC = 'business-banking-enabled';

    /**
     * This function is used for activating merchant
     *
     * @param Entity $merchant
     *
     * @param bool $triggerWorkflow
     * @param bool $shouldSave
     * @return Detail\Entity
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function activate(Entity $merchant, bool $triggerWorkflow = true, bool &$shouldSave = true): Detail\Entity
    {
        // Merchants who have been activated (instantly activated whitelisted merchants)
        if ($merchant->isActivated() === true)
        {
            $this->trace->info(TraceCode::ALREADY_ACTIVATED, $merchant->toArrayPublic());

            return $this->markKycVerified($merchant, $triggerWorkflow, $shouldSave);
        }
        //
        // For merchants who never went through the instant activations flow, and,
        // who went through the instant activations flow and got greylisted
        //
        $this->trace->info(TraceCode::NOT_ACTIVATED, $merchant->toArrayPublic());

        return $this->activateAndMarkKycVerified($merchant, $triggerWorkflow, $shouldSave);
    }

    /**
     * @param Entity $merchant
     *
     * @param bool $triggerWorkflow
     * @param bool $shouldSave
     * @return Detail\Entity
     *
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function activateAndMarkKycVerified(Entity $merchant, bool $triggerWorkflow = true, bool &$shouldSave = true): Detail\Entity
    {
        $merchantDetail = $merchant->merchantDetail;

        $merchant->getValidator()->validateBeforeActivate();

        $this->validateMethodsAndPricing($merchant);

        if ($triggerWorkflow === true)
        {
            // Triggering workflow for the activation_status change in merchantDetail entity
            $this->app['workflow']
                ->handle();
        }

        $this->enableOneClickCheckoutIfApplicable($merchant);

        if ($this->shouldCreateBankAccount($merchantDetail) === true)
        {
            (new Detail\Core)->setBankAccountForMerchant($merchant);

            $merchant->getValidator()->validateHasBankAccount();
        }

        $merchant->enableReceiptEmails();

        // set methods before activating
        $merchant->setDefaultMethodsBasedOnCategory();

        $merchant->activate();

        $merchant->releaseFunds();

        // making sure that merchant's has_key_access is set to true when website is set.
        if ((empty($merchantDetail->getWebsite()) === false) and
            ($merchant->getHasKeyAccess() === false))
        {
            $merchant->setHasKeyAccess(true);
        }

        $merchantCore = new Merchant\Core;

        $merchantCore->updateInternationalIfApplicable($merchant, $merchantDetail);

        $merchantBalance = $merchantCore->createBalance($merchant, 'live');

        $merchantCore->createBalanceConfig($merchantBalance, 'live');

        PaymentPageProcessor::dispatch(Mode::LIVE, [
            'event'     => PaymentPageProcessor::PAYMENT_HANDLE_CREATION,
            'merchant_id'  => $merchant->getPublicId(),
        ]);

        //to be removed once hold funds issue is resolved
        $this->trace->info(TraceCode::MERCHANT_HOLD_FUNDS_PRE_TRANSCACTION,$merchant->toArrayPublic());

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetail, $merchantCore)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);

            if ($merchant->isActivated() === true)
            {
                $merchantCore->addMerchantEmailToMailingList($merchant);
            }

            $this->activateBusinessBankingIfApplicable($merchant);
        });
        //
        // Activate Promotions/Coupons for Merchant if applicable.
        // Balance need to be created before applying promotion/coupon as credits are associated with it.
        //
        $this->activateMerchantPromotions($merchant);

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_ACTIVATED);

        $this->sendMerchantActivatedEvents($merchant);

        $this->trace->info(TraceCode::MERCHANT_HOLD_FUNDS_POST_TRANSCACTION,$merchant->toArrayPublic());

        return $merchantDetail;
    }

    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     * @param bool          $batchFlow
     * @param bool          $sendActivationMail
     *
     * @return array
     * @throws BadRequestException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function instantlyActivate(Entity $merchant, Detail\Entity $merchantDetails, bool $batchFlow = false): array
    {
        $detailCore = new Detail\Core;

        $merchant->getValidator()->validateBeforeInstantlyActivate();

        $this->validateMethodsAndPricing($merchant);

        $merchant->enableReceiptEmails();

        // set methods before activating
        $merchant->setDefaultMethodsBasedOnCategory();

        $merchant->activate();

        $this->enableOneClickCheckoutIfApplicable($merchant);

        (new Merchant\Core)->updateInternationalIfApplicable($merchant, $merchantDetails);

        $merchant->holdFunds();

        $originProduct = $this->app['basicauth']->getRequestOriginProduct();

        $merchant->setActivationSource($originProduct);

        $merchantBalance = (new Core)->createBalance($merchant, 'live');

        (new Core)->createBalanceConfig($merchantBalance, 'live');

        PaymentPageProcessor::dispatch(Mode::LIVE, [
            'event'     => PaymentPageProcessor::PAYMENT_HANDLE_CREATION,
            'merchant_id'  => $merchant->getPublicId(),
        ]);

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_INSTANTLY_ACTIVATED, [
            'merchant_id'   => $merchant->getId()
        ]);

        //
        // If a merchant does not have website or app, we would need to activate them
        // only with PLs, Invoices and should not get API keys in live mode. Merchant's has_key_access
        // should be set to true only if one submits website details, there by will be able to
        // generate/access keys.
        //
        $detailCore->checkAndMarkHasKeyAccess($merchantDetails, $merchant);

        $activationStatusData = [
            Detail\Entity::ACTIVATION_STATUS => Detail\Status::INSTANTLY_ACTIVATED,
        ];

        $this->repo->saveOrFail($merchant);

        if ($merchant->isActivated() === true)
        {
            (new Merchant\Core)->addMerchantEmailToMailingList($merchant);
        }

        if (($batchFlow === false) or (new Detail\Validator)->validateBatchFlowAndActivationStatusState($merchant,$activationStatusData,$batchFlow))
        {
            $detailCore->updateActivationStatus($merchant, $activationStatusData, $merchant);
        }

        $properties = [
            'previousActivationStatus'    => null,
            'currentActivationStatus'     => $merchantDetails->getActivationStatus()
        ];

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $properties, SegmentEvent::PAYMENTS_ENABLED);

        $this->activateMerchantPromotions($merchant);

        return $merchant->toArrayPublic();
    }

    /**
     * @param Entity $merchant
     *
     * @param bool $triggerWorkflow
     * @param bool $shouldSave
     * @return Detail\Entity
     * @throws BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function markKycVerified(Entity $merchant, bool $triggerWorkflow = true, bool &$shouldSave = true): Detail\Entity
    {
        $merchantDetail = $merchant->merchantDetail;

        // @todo: add a check - should be through an instantly_activated state
        $merchant->getValidator()->validateBeforeKycVerified();

        if ($triggerWorkflow)
        {
            // Triggering workflow for the activation_status change in merchantDetail entity
            $this->app['workflow']
                ->handle();
        }

        if ($this->shouldCreateBankAccount($merchantDetail) === true)
        {
            (new Detail\Core)->setBankAccountForMerchant($merchant);

            $merchant->getValidator()->validateHasBankAccount();
        }

        $merchant->releaseFunds();

        $merchantCore = new Merchant\Core;

        $merchantCore->updateInternationalIfApplicable($merchant, $merchantDetail);

        $this->trace->info(TraceCode::MERCHANT_HOLD_FUNDS_PRE_TRANSCACTION,$merchant->toArrayPublic());

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetail, $merchantCore)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);

            if ($merchant->isActivated() === true)
            {
                $merchantCore->addMerchantEmailToMailingList($merchant);
            }
        });

        $this->activateBusinessBankingIfApplicable($merchant);

        //
        // Live transactions get disabled if the activation_status changes to 'rejected'.
        // If later the status is change to 'activated', enable live transactions explicitly.
        //
        (new Merchant\Core)->enableLive($merchant);

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_KYC_VERIFIED);

        $this->sendMerchantActivatedEvents($merchant);

        $this->trace->info(TraceCode::MERCHANT_HOLD_FUNDS_POST_TRANSCACTION,$merchant->toArrayPublic());

        return $merchantDetail;
    }

    private function enableOneClickCheckoutIfApplicable(Entity $merchant)
    {
        if ($merchant->isBusinessBankingEnabled() === false)
        {
            switch ($merchant->merchantDetail->getBusinessCategory())
            {
                case Merchant\Detail\BusinessCategory::ECOMMERCE:
                case Merchant\Detail\BusinessCategory::FASHION_AND_LIFESTYLE:

                    $featureParams = [
                        Feature\Entity::ENTITY_TYPE => \RZP\Constants\Entity::MERCHANT,
                        Feature\Entity::ENTITY_ID   => $merchant->getId(),
                        Feature\Entity::NAMES       => [Constants::ONE_CC_MERCHANT_DASHBOARD],
                        Feature\Entity::SHOULD_SYNC => true,
                    ];

                    $this->addFeatures($featureParams);
            }
        }
    }

    /**
     * Ensure that all payment methods enabled for the merchant has an associated pricing assigned
     *
     * @param Entity $merchant
     */
    protected function validateMethodsAndPricing(Entity $merchant)
    {
        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        $methodCore = new Methods\Core;

        $methodCore->checkCategorySubcategoryAndEnableEmi($merchant, $methods);

        $methodCore->checkPricing($merchant, $methods, true);
    }

    /**
     * @param Entity $merchant
     */
    protected function activateMerchantPromotions(Entity $merchant)
    {
        $merchantPromotions = $this->repo->merchant_promotion->getByMerchantId($merchant->getId());

        $merchantPromotionCore = (new Merchant\Promotion\Core);

        foreach ($merchantPromotions as $merchantPromotion)
        {
            try
            {
                $merchantPromotionCore->activate($merchantPromotion);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::PROMOTION_ACTIVATION_FAILED,
                    ['merchant_promotion_id' => $merchantPromotion->getId()]);
            }
        }
    }

    /**
     * Send merchant activated events to drip & eventManager
     * Also, send the email to merchant.
     *
     * @param Entity $merchant
     */
    protected function sendMerchantActivatedEvents(Entity $merchant)
    {
        $this->app['drip']->sendDripMerchantInfo($merchant, Merchant\Action::ACTIVATED);

        $attributes = $merchant->toArrayEvent();

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::ACTIVATED, $attributes);

        $this->sendActivationEmail($merchant);

        $zapierData = (new Detail\Service)->getActivationZapierData($merchant);

        (new Detail\Core)->postFormSubmissionToZapier($zapierData, 'activations', $merchant);

        if (empty($merchant->getEmail() === false))
        {
            $this->app->hubspot->trackHubspotEvent($merchant->getEmail(), [
                'settlement_enabled' => true
            ]);
        }
    }

    /**
     * Handles the logic for auto-activation of accounts
     *
     * @param Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function autoActivate(Entity $merchant)
    {
        $merchant->getValidator()->validateBeforeActivate($merchant);

        // set methods before activating
        $merchant->setDefaultMethodsBasedOnCategory();

        $merchant->activate();

        // Create the live mode balance entity for the merchant
        $merchantBalance = (new Merchant\Core)->createBalance($merchant, Mode::LIVE);

        (new Merchant\Core)->createBalanceConfig($merchantBalance, Mode::LIVE);

        $this->repo->saveOrFail($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_LINKED_ACCOUNT_ACTIVATED,
            [
                'type'        => 'auto_activate',
                'merchant_id' => $merchant->getId()
            ]);
    }

    /**
     * Sends activation email to the merchant, cc's notifications
     * Includes pricing details in the email (properly formatted)
     *
     * @param  Entity $merchant
     * @return null
     */
    public function sendActivationEmail($merchant)
    {
        if($merchant->getOrgId() === OrgEntity::AXIS_ORG_ID)
        {
            $this->sendActivationEmailForAxisOrg($merchant);
            return;
        }

        //
        // In order to distinguish between RX merchant and PG Merchant, we cannot use getRequestOriginProduct, because
        // this activation happens from Admin Dashboard, in which case the OriginProduct will always be Primary.
        // Hence we will check if the Merchant has business_banking enabled, we will send the RX email, else the default PG email
        //

        $isBusinessBankingEnabled = $merchant->isBusinessBankingEnabled();

        $this->trace->info(TraceCode::ACTIVATION_CONFIRMATION_EMAIL,
                            [
                                'merchant_id'                 => $merchant->getId(),
                                'is_business_banking_enabled' => $isBusinessBankingEnabled
                            ]);

        if ($isBusinessBankingEnabled === true)
        {
            if ($merchant->hasBankingAccounts() === false)
            {
                $this->trace->error(TraceCode::NO_ASSOCIATED_BANKING_ACCOUNT,
                                    [
                                        'merchant_id' => $merchant->getId()
                                    ]);
            }
            else
            {
                Mail::queue(new AccountActivationConfirmation($merchant->getId()));
            }
        }
    }

    private function sendActivationEmailForAxisOrg($merchant)
    {
        $isAxisWrapperEnabled = (new Merchant\Core())->isRazorxExperimentEnable($merchant->getId(),
            RazorxTreatment::AXIS_WRAPPER_ENABLED);

        if($isAxisWrapperEnabled === false)
        {
            return;
        }

        $org = $merchant->org;
        $dashboardUrl = $this->app['config']->get('applications.dashboard.url');

        $data = [
            DEConstants::MERCHANT             => [
                Merchant\Entity::NAME          => $merchant->getName(),
                Merchant\Entity::BILLING_LABEL => $merchant->getBillingLabel(),
                Merchant\Entity::EMAIL         => $merchant->getEmail(),
                DEConstants::ORG               => [
                    DEConstants::HOSTNAME => $org->getPrimaryHostName(),
                    Merchant\Detail\Entity::BUSINESS_NAME => $org->getBusinessName()
                ],
                'dashboard_url'                => $dashboardUrl
            ],
        ];

        $mail = new AxisActivation($data, $org->toArray());

        Mail::queue($mail);

        /*
         * sending out password reset email to the merchants, since password was created by the system
         */
        $input = [
            "email" => $merchant->getEmail()
        ];
        (new UserService)->postResetPassword($input);
    }

    /**
     * Sends banking activation sms to the merchant If
     * Business Banking is Enabled AND Merchant status is activated
     *
     * @param Entity $merchant
     */
    public function sendBankingVaActivationSmsIfApplicable(Entity $merchant)
    {
        if ((optional($merchant->merchantDetail)->getActivationStatus() !== Merchant\Detail\Status::ACTIVATED) ||
            ($merchant->isBusinessBankingEnabled() === false))
        {
            return;
        }

        $this->trace->info(TraceCode::BANKING_ACTIVATION_CONFIRMATION_SMS_VA_REQUEST,
            [
                'merchant_id' => $merchant->getId(),
            ]);

        try
        {
            $users = $merchant->ownersAndAdmins(Product::BANKING);

            if ($users === null)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_USER_NOT_PRESENT);
            }

            foreach ($users as $user)
            {
                $payload = [
                    'receiver' => $user->getContactMobile(),
                    'source'   => "api",
                    'template' => 'sms.account.activate_banking_va',
                    'params'   => [
                    ],
                ];

                $this->app->raven->sendSms($payload);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BANKING_ACTIVATION_CONFIRMATION_SMS_VA_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    public function notifyMerchantForInstantActivation(Entity $merchant)
    {
        $instantActivationMail = null;

        $activationSource = $merchant->getActivationSource();

        $this->trace->info(TraceCode::INSTANT_ACTIVATION_NOTIFICATION,
                           [
                               'merchant_id'          => $merchant->getPublicId(),
                               'activation_source'    => $activationSource,
                               'has_banking_accounts' => $merchant->hasBankingAccounts()
                           ]
        );

        if (($activationSource === Product::BANKING) and
            ($merchant->hasBankingAccounts() === true))
        {
            $instantActivationMail = new RazorpayXInstantActivationMail($merchant->getId());
        }
        else
        {
            $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

            $data = [
                'merchant' => [
                    Entity::NAME              => $merchant->getName(),
                    Entity::BILLING_LABEL     => $merchant->getBillingLabel(),
                    Entity::EMAIL             => $merchant->getEmail(),
                    Entity::ACTIVATION_SOURCE => $merchant->getActivationSource(),
                    Entity::BUSINESS_BANKING  => $merchant->isBusinessBankingEnabled(),
                    'org'                     => [
                        OrgEntity::BUSINESS_NAME => $org->getBusinessName(),
                        OrgEntity::CUSTOM_CODE   => $org->getCustomCode(),
                    ],
                ],
            ];

            $data['merchant']['org'][HostNameEntity::HOSTNAME] = $org->getPrimaryHostName();

            $instantActivationMail = new InstantActivationMail($data, $org->toArray());
        }

        Mail::queue($instantActivationMail);
    }

    /**
     * Activates Business Banking for a merchant.
     *
     * This flow kicks in following flows:
     * - instant activations
     * - activations
     * - kyc verification
     * - merchant switch product
     *
     * @param Entity $merchant
     *
     * @param bool   $sendActivationSms
     *
     * @return Entity
     * @throws Throwable
     */
    public function activateBusinessBankingIfApplicable(Entity $merchant): Entity
    {
        // VA should not be activated for unregistered business in case of PG KYC approval
        if (($merchant->isBusinessBankingEnabled() === false) or
             ((new Service())->isAllowedForBusinessBanking($merchant->getId()) === false))
        {
            return $merchant;
        }

        //
        // Reset the connection to the requests original mode
        //
        $originalMode = $this->app['basicauth']->getMode();

        // Creating the live and test mode entities in a db txn
        // Either both get created or none
        $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            try
            {
                $this->createBankingEntitiesForMode($merchant, Mode::LIVE);

                $this->createBankingEntitiesForMode($merchant, Mode::TEST);
            }
            catch (\Throwable $e)
            {
                $this->trace->count(Merchant\Metric::MERCHANT_RAZORPAYX_ACTIVATION_FAILED_TOTAL);

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::MERCHANT_RAZORPAYX_ACTIVATION_FAILED);

                throw $e;
            }
        });

        $this->setDbAndModelConnectionWithMode($originalMode, $merchant);

        // publish message on the metro topic business-banking-enabled
        $data = array(
            Entity::MERCHANT_ID => $merchant->getPublicId()
        );


        $this->trace->info(TraceCode::BANKING_ENABLED_MESSAGE, [
            'data' => $data
        ]);

        $encodedData = [
            'data' => json_encode($data)
        ];

        try
        {
            $response = $this->app['metro']->publish(self::BUSINESS_BANKING_ENABLED_TOPIC, $encodedData);

            $this->trace->info(TraceCode::BUSINESS_BANKING_ENABLED_MESSAGE_PUBLISHED, [
                'response' => $response
            ]);

        } catch (Throwable $exception)
        {
            $this->trace->count(Metric::BUSINESS_BANKING_ENABLED_TRIGGER_FAILURE);

            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::PUBLISH_BANKING_ENABLED_MESSAGE_FAILURE);

            // activation part will not fail if the message publish to metro fails
        }

        // Refreshing merchant here so that relations for original mode are fetched again
        return $merchant->refresh();
    }

    public function createBankingEntitiesForMode(Entity $merchant, string $mode)
    {
        $this->setDbAndModelConnectionWithMode($mode, $merchant);

        // Refreshing merchant here so that relations for respective modes are fetched again
        $merchant->refresh();

        $onboardMerchant = false;

        if ($mode === Mode::LIVE)
        {
            $onboardMerchant = $this->onBoardMerchantOnRazorpayxInLiveMode($merchant);
        }
        else if ($mode === Mode::TEST)
        {
            $onboardMerchant = $this->onBoardMerchantOnRazorpayxInTestMode($merchant);
        }

        $this->trace->info(
            TraceCode::MERCHANT_RAZORPAYX_ACTIVATION_REQUEST,
            [
                'merchant_id'       => $merchant->getId(),
                'should_onboard'    => $onboardMerchant,
                'mode'              => $mode,
            ]);

        $seriesPrefix = trim(Terminal\Core::getBankAccountSeriesPrefixForX($merchant, $mode));

        if ($onboardMerchant === true)
        {
            // Create Banking Balance
            [$balance, $created] = (new Balance\Core)->createOrFetchSharedBankingBalance($merchant, $mode);

            // Create Virtual Account
            $virtualAccount = (new VirtualAccount\Core)->createOrFetchBankingVirtualAccount($merchant,
                                                                                            $balance,
                                                                                            $seriesPrefix);

            // Create Banking Account
            [$bankingAccount, $baCreatedNow] = (new BankingAccount\Core)->createOrFetchSharedBankingAccountFromVA($virtualAccount);

            // Call Ledger Entity method which will take care of creating the account for this balance in Ledger.
            // Flow will come here only if balance is created successfully in API DB.
            $ledgerExperimentActive = $this->onBoardMerchantOnLedger($merchant, $mode);

            // check if experiment is active and balance is created in this call
            if (($ledgerExperimentActive === true) and ($created === true))
            {
                if ($merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false)
                {
                    (new Feature\Core)->create(
                        [
                            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                            Feature\Entity::ENTITY_ID   => $merchant->getId(),
                            Feature\Entity::NAME        => Feature\Constants::LEDGER_JOURNAL_WRITES,
                        ]);

                    $this->trace->info(
                        TraceCode::LEDGER_JOURNAL_WRITES_FEATURE_ASSIGNED,
                        [
                            'merchant_id'       => $merchant->getId(),
                            'mode'              => $mode,
                        ]);
                }

                (new Merchant\Balance\Ledger\Core)->createXLedgerAccount($merchant, $bankingAccount, $mode);
            }

            (new Counter\Core)->fetchOrCreate($balance);

            $this->trace->info(
                TraceCode::MERCHANT_BUSINESS_BANKING_ACCOUNT,
                [
                    'virtual_account_id' => $virtualAccount->getId(),
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $virtualAccount->getMerchantId(),
                    'mode'               => $mode,
                    'series_prefix'      => $seriesPrefix,
                ]);

            $this->addPayoutFeatureIfApplicable($merchant, $mode);

            $this->addSkipHoldFundsOnPayout($merchant);

            //create activated TPV
            (new BankingAccountTpv\Core())->createAutoApprovedTpvForActivatedMerchants($merchant, $mode);

            if (($mode === Mode::LIVE) and
                ($baCreatedNow === true))
            {
                $this->sendBankingVaActivationSmsIfApplicable($merchant);
            }
        }
    }

    protected function blockRxActivationIfApplicable($merchant)
    {
        $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::BLOCK_X_REGISTRATION]) ?? false;

        if (boolval($config) === true)
        {
            $this->trace->info(TraceCode::BLOCKING_RX_ACTIVATIONS_TEMPORARILY, [
                'onboard_merchant'  => true,
                'merchant_id'       => $merchant->getId(),
                'config'            => $config,
            ]);
        }

        return boolval($config);
    }

    public function addPayoutFeatureIfApplicable(Entity $merchant, string $mode, bool $isCurrentAccountActivated = false)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::PAYOUT) === true)
        {
            return;
        }

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        //
        // Assign payout feature if:
        // In live mode: check if merchant has been activated
        // In test mode: always
        //
        if (($mode === Mode::TEST) or
            ($merchantDetails->getActivationStatus() === Detail\Status::ACTIVATED) or $isCurrentAccountActivated)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID   => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                Feature\Entity::NAMES       => [Feature\Constants::PAYOUT],
                // Sync isn't required as this gets called separately for live and test mode
                // If it were to be sync, greylsited merchants would get payout feature without being activated
                Feature\Entity::SHOULD_SYNC => false,
            ];

            $this->addFeatureWhileHandlingStaleRead($featureParams);
        }
    }

    /**
     * Adding this wrapper to handle stale read from redis cache for payout and skip_hold_funds_on_payout feature
     * And to also make the code testable
     * If payout/skip_hold_funds_on_payout feature was present,
     * then this should not get called ideally but at times we have
     * seen cache lag issues because of which this is getting called even if feature is already present.
     * Slack link: https://razorpay.slack.com/archives/C012KKG1STS/p1628227117052900?thread_ts=1628181255.051600&cid=C012KKG1STS
     */
    public function addFeatureWhileHandlingStaleRead(array $featureParams)
    {
        try
        {
            $this->addFeatures($featureParams);
        }
        catch (BadRequestException $exception)
        {
            if ($exception->getCode() === ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED)
            {
                $this->trace->info(TraceCode::FEATURE_STALE_READ_SUCCESS, $featureParams);
            }
            else
            {
                throw $exception;
            }
        }
    }

    public function addFeatures(array $featureParams)
    {
        (new Feature\Service)->addFeatures($featureParams);
    }

    /**
     * @param $merchantDetail
     *
     * @return bool
     */
    protected function shouldCreateBankAccount($merchantDetail): bool
    {
        return ((Detail\Core::shouldSkipBankAccountRegistration() === false) and
                ($merchantDetail->hasBankAccountDetails() === true));
    }

    protected function setDbAndModelConnectionWithMode(string $mode, Merchant\Entity $merchant)
    {
        $this->app['basicauth']->setModeAndDbConnection($mode);

        $merchant->setConnection($mode);
    }

    protected function onBoardMerchantOnRazorpayxInLiveMode(Entity $merchant)
    {
        return (($merchant->isActivated() === true) and
            (empty($merchant->getEmail()) === false));
        // This was done for YesBank moratorium. Not required now.
        // and ($this->blockRxActivationIfApplicable($merchant) === false));
    }

    protected function onBoardMerchantOnRazorpayxInTestMode(Entity $merchant)
    {
        // We have ramped up this experiment to a 100% of users and do not need this anymore
        // $experimentActive = $this->onBoardMerchantOnRazorpayx($merchant, Mode::TEST);
        return true;
    }

    protected function onBoardMerchantOnRazorpayx(Entity $merchant, string $mode): bool
    {
        $variant = $this->app->razorx->getTreatment($merchant->getId(),
            Merchant\RazorxTreatment::RAZORPAY_X_TEST_MODE_ONBOARDING,
            $mode
        );

        $result = (strtolower($variant) !== 'off');

        return $result;
    }

    protected function addSkipHoldFundsOnPayout(Entity $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature\Constants::SKIP_HOLD_FUNDS_ON_PAYOUT) === true)
        {
            return;
        }

        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchant->getId(),
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAMES       => [Feature\Constants::SKIP_HOLD_FUNDS_ON_PAYOUT],
        ];

        $this->addFeatureWhileHandlingStaleRead($featureParams);
    }

    // Returns true if experiment and env variable to onboard merchant on ledger is running.
    protected function onBoardMerchantOnLedger(Entity $merchant, string $mode): bool
    {
        $variant = $this->app->razorx->getTreatment($merchant->getId(),
            Merchant\RazorxTreatment::LEDGER_ONBOARDING,
            $mode
        );

        return (strtolower($variant) === 'on');
    }
}
