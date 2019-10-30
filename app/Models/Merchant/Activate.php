<?php

namespace RZP\Models\Merchant;

use Mail;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\VirtualAccount;
use RZP\Models\BankingAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Mail\Merchant\Activation as ActivationMail;
use RZP\Models\Admin\Org\Hostname\Entity as HostNameEntity;
use RZP\Mail\Merchant\RazorpayX\AccountActivationConfirmation;
use RZP\Mail\Merchant\InstantActivation as InstantActivationMail;
use RZP\Mail\Merchant\RazorpayX\InstantActivation as RazorpayXInstantActivationMail;

class Activate extends Base\Core
{
    use NotifyTrait;

    /**
     * This function is used for activating merchant
     *
     * @param Entity $merchant
     *
     * @return Detail\Entity
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function activate(Entity $merchant): Detail\Entity
    {
        // Merchants who have been activated (instantly activated whitelisted merchants)
        if ($merchant->isActivated() === true)
        {
            return $this->markKycVerified($merchant);
        }

        //
        // For merchants who never went through the instant activations flow, and,
        // who went through the instant activations flow and got greylisted
        //
        return $this->activateAndMarkKycVerified($merchant);
    }

    /**
     * @param Entity        $merchant
     *
     * @return Detail\Entity
     *
     * @throws Exception\BadRequestException
     * @throws Throwable
     */
    public function activateAndMarkKycVerified(Entity $merchant): Detail\Entity
    {
        $merchantDetail = $merchant->merchantDetail;

        $merchant->getValidator()->validateBeforeActivate();

        $this->validateMethodsAndPricing($merchant);

        if ($this->shouldCreateBankAccount($merchantDetail) === true)
        {
            (new Detail\Core)->setBankAccountForMerchant($merchant);

            $merchant->getValidator()->validateHasBankAccount();
        }

        $merchant->enableReceiptEmails();

        $merchant->activate();

        // making sure that merchant's has_key_access is set to true when website is set.
        if ((empty($merchantDetail->getWebsite()) === false) and
            ($merchant->getHasKeyAccess() === false))
        {
            $merchant->setHasKeyAccess(true);
        }

        // Triggering workflow for the activation_status change in merchantDetail entity
        $this->app['workflow']
             ->handle();

        $merchantCore = new Merchant\Core;

        $merchantCore->activateInternationalIfApplicable($merchant, $merchantDetail);

        $merchantCore->createBalance($merchant, 'live');

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetail, $merchantCore)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);

            if($merchant->isActivated() === true)
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

        $this->sendMerchantActivationNotification($merchant);

        return $merchantDetail;
    }

    /**
     * @param Entity $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @return array
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function instantlyActivate(Entity $merchant, Detail\Entity $merchantDetails): array
    {
        $detailCore = new Detail\Core;

        $merchant->getValidator()->validateBeforeInstantlyActivate();

        $this->validateMethodsAndPricing($merchant);

        $merchant->enableReceiptEmails();

        $merchant->activate();

        (new Merchant\Core)->activateInternationalIfApplicable($merchant, $merchantDetails);

        $merchant->holdFunds();

        $originProduct = $this->app['basicauth']->getRequestOriginProduct();

        $merchant->setActivationSource($originProduct);

        (new Core)->createBalance($merchant, 'live');

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_INSTANTLY_ACTIVATED);

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

        if($merchant->isActivated() === true)
        {
            (new Merchant\Core)->addMerchantEmailToMailingList($merchant);
        }

        $detailCore->updateActivationStatus($merchant, $activationStatusData, $merchant);

        $this->activateBusinessBankingIfApplicable($merchant);

        $this->activateMerchantPromotions($merchant);

        $this->notifyMerchantForInstantActivation($merchant);

        return $merchant->toArrayPublic();
    }

    /**
     * @param Entity        $merchant
     *
     * @return Detail\Entity
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     * @throws Throwable
     */
    public function markKycVerified(Entity $merchant): Detail\Entity
    {
        $merchantDetail = $merchant->merchantDetail;

        // @todo: add a check - should be through an instantly_activated state
        $merchant->getValidator()->validateBeforeKycVerified();

        if ($this->shouldCreateBankAccount($merchantDetail) === true)
        {
            (new Detail\Core)->setBankAccountForMerchant($merchant);

            $merchant->getValidator()->validateHasBankAccount();
        }

        $merchant->releaseFunds();

        // Triggering workflow for the activation_status change in merchantDetail entity
        $this->app['workflow']
             ->handle();

        $merchantCore = new Merchant\Core;

        $merchantCore->activateInternationalIfApplicable($merchant, $merchantDetail);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetail, $merchantCore)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);

            if($merchant->isActivated() === true)
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

        $this->sendMerchantActivationNotification($merchant);

        return $merchantDetail;
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

        $merchant->activate();

        // Create the live mode balance entity for the merchant
        (new Merchant\Core)->createBalance($merchant, Mode::LIVE);

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
        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $is_whitelist_activation = $merchant->merchantDetail->getActivationFlow() === ActivationFlow::WHITELIST;

        $data = [
            'merchant' => [
                'name'                               => $merchant->getName(),
                'website'                            => $merchant->getWebsite(),
                'billing_label'                      => $merchant->getBillingLabel(),
                'email'                              => $merchant->getEmail(),
                'activation_source'                  => $merchant->getActivationSource(),
                Constants::IS_WHITELISTED_ACTIVATION => $is_whitelist_activation,
                'org'                                => [
                    'business_name' => $org->getBusinessName(),
                    'custom_code'   => $org->getCustomCode(),
                ],
            ],
        ];

        $data['merchant']['org']['hostname'] = $org->getPrimaryHostName();

        // For marketplace accounts, send this email to the parent merchant
        if ($merchant->isLinkedAccount() === true)
        {
            $data['merchant']['email'] = $merchant->parent->getEmail();
        }

        $activationMail = new ActivationMail($data, $org->toArray());

        Mail::queue($activationMail);
    }

    public function notifyMerchantForInstantActivation(Entity $merchant)
    {
        $instantActivationMail = null;

        $activationSource = $merchant->getActivationSource();

        if (($activationSource === Product::BANKING) and
            ($merchant->hasBankingAccounts() === true))
        {
            $instantActivationMail = new RazorpayXInstantActivationMail($merchant->getId());
        }
        else if ($activationSource === Product::PRIMARY)
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
     * @param Entity $merchant
     *
     * @return Entity
     * @throws Exception\LogicException
     */
    public function activateBusinessBankingIfApplicable(Entity $merchant): Entity
    {
        if ($merchant->isBusinessBankingEnabled() === false)
        {
            return $merchant;
        }

        $merchantDetails = (new Detail\Core)->getMerchantDetails($merchant);

        // If merchant is instantly activated or Activated this flow will kick in.
        if ($merchant->isActivated() === true)
        {
            // Business Banking logic is coupled only with the live mode.
            $liveMode = $this->app['basicauth']->getLiveConnection();

            $this->app['basicauth']->setModeAndDbConnection($liveMode);

            //
            // This endpoint could be hit from test mode as well, depending which this merchant has been read from
            // corresponding connection. Because this entity is synced between both connection, setting connection
            // to live mode is same as fetching merchant of same id from live connection. We need to do this
            // because in subsequent steps we do things like $merchant->bankingBalance which we expect in this flow
            // to query in live connection.
            //
            $merchant->setConnection($liveMode);

            // Create Banking Balance
            $balance = (new Balance\Core)->createOrFetchSharedBankingBalance($merchant,  $liveMode);

            // Virtual Account
            $virtualAccount = (new VirtualAccount\Core)->createOrFetchBankingVirtualAccount($merchant, $balance);

            $bankingAccount = (new BankingAccount\Core)->createOrFetchSharedBankingAccountFromVA($virtualAccount);

            $this->trace->info(
                TraceCode::MERCHANT_BUSINESS_BANKING_ACCOUNT,
                [
                    'virtual_account_id' => $virtualAccount->getId(),
                    'banking_account_id' => $bankingAccount->getId(),
                    'merchant_id'        => $virtualAccount->getMerchantId(),
                ]);
        }

        $merchantDetails->reload();

        // This means that L2 form is also verified.
        if ($merchantDetails->getActivationStatus() === Detail\Status::ACTIVATED)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE  => 'merchant',
                Feature\Entity::NAMES        => [Feature\Constants::PAYOUT],
                Feature\Entity::SHOULD_SYNC  => true,
            ];

            (new Feature\Service)->addFeatures($featureParams);
        }

        return $merchant;
    }

    protected function sendMerchantActivationNotification(Entity $merchant)
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
            Mail::queue(
                new AccountActivationConfirmation($merchant->getId())
            );
        }
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
}
