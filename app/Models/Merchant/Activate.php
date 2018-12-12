<?php

namespace RZP\Models\Merchant;

use Mail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\VirtualAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Models\Merchant\Notify as NotifyTrait;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Mail\Merchant\Activation as ActivationMail;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Models\Admin\Org\Hostname\Entity as HostNameEntity;
use RZP\Mail\Merchant\InstantActivation as InstantActivationMail;

class Activate extends Base\Core
{
    use NotifyTrait;

    const MAIL_EXCLUDED_METHODS = [
        // Don't include marketplace transfer method (for now)
        Payment\Method::TRANSFER,

        // Don't include bank transfer (VA) method (for now)
        // TODO: Will add once we've figured out how to display max fees correctly
        Payment\Method::BANK_TRANSFER
    ];

    /**
     * This function is used for activating merchant
     *
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetail
     *
     * @return Detail\Entity
     */
    public function activate(Entity $merchant, Detail\Entity $merchantDetail): Detail\Entity
    {
        // Merchants who have been activated (instantly activated whitelisted merchants)
        if ($merchant->isActivated() === true)
        {
            return $this->markKycVerified($merchant, $merchantDetail);
        }

        //
        // For merchants who never went through the instant activations flow, and,
        // who went through the instant activations flow and got greylisted
        //
        return $this->activateAndMarkKycVerified($merchant, $merchantDetail);
    }

    /**
     * @param Entity $merchant
     *
     * @return Detail\Entity
     */
    public function activateAndMarkKycVerified(Entity $merchant, Detail\Entity $merchantDetail): Detail\Entity
    {
        $merchant->getValidator()->validateBeforeActivate();

        $this->validateMethodsAndPricing($merchant);

        (new Detail\Core)->setBankAccountForMerchant($merchantDetail);

        $merchant->getValidator()->validateHasBankAccount();

        $this->activateMerchantPromotions($merchant);

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

        (new Merchant\Core)->createBalance($merchant, 'live');

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetail)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);

            $this->activateBusinessBankingIfApplicable($merchant);
        });

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_ACTIVATED);

        $this->sendMerchantActivatedEvents($merchant);

        return $merchantDetail;
    }

    /**
     * Instantly activates a merchant with funds on hold
     *
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @return array
     */
    public function instantlyActivate(Entity $merchant, Detail\Entity $merchantDetails): array
    {
        $merchant->getValidator()->validateBeforeInstantlyActivate();

        $this->validateMethodsAndPricing($merchant);

        // @todo: Enable sometime later after instant activations is launched
        // $this->activateMerchantPromotions($merchant);

        $merchant->enableReceiptEmails();

        $merchant->activate();

        $merchant->holdFunds();

        (new Core)->createBalance($merchant, 'live');

        $this->repo->transactionOnLiveAndTest(function () use ($merchant)
        {
            $this->repo->saveOrFail($merchant);
        });

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_INSTANTLY_ACTIVATED);

        $detailCore = new Detail\Core;

        //
        // If a merchant does not have website or app, we would need to activate them
        // only with PLs, Invoices and should not get API keys in live mode. Merchant's has_key_access
        // should be set to true only if one submits website details, there by will be able to
        // generate/access keys.
        //
        $detailCore->checkAndMarkHasKeyAccess($merchantDetails);

        $activationStatusData = [
            Detail\Entity::ACTIVATION_STATUS => Detail\Status::INSTANTLY_ACTIVATED,
        ];

        $detailCore->updateActivationStatus($merchantDetails, $activationStatusData, $merchant);

        $this->activateBusinessBankingIfApplicable($merchant);

        // @todo: Add support for multiple channels here - Drip, Zapier, Slack, Emails (merchant and admins)
        // $this->fireInstantActivationTrigger($merchantDetails, $merchant);

        $this->notifyMerchantForInstantActivation($merchant);

        return $merchant->toArrayPublic();
    }

    /**
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetail
     *
     * @return Detail\Entity
     */
    public function markKycVerified(Entity $merchant, Detail\Entity $merchantDetail): Detail\Entity
    {
        // @todo: add a check - should be through an instantly_activated state
        $merchant->getValidator()->validateBeforeKycVerified();

        (new Detail\Core)->setBankAccountForMerchant($merchantDetail);

        $merchant->getValidator()->validateHasBankAccount();

        $merchant->releaseFunds();

        // Triggering workflow for the activation_status change in merchantDetail entity
        $this->app['workflow']
             ->handle();

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $merchantDetail)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);
        });

        $this->activateBusinessBankingIfApplicable($merchant);

        //
        // Live transactions get disabled if the activation_status changes to 'rejected'.
        // If later the status is change to 'activated', enable live transactions explicitly.
        //
        (new Merchant\Core)->enableLive($merchant);

        $this->trace->info(TraceCode::MERCHANT_ACCOUNT_KYC_VERIFIED);

        $this->sendMerchantActivatedEvents($merchant);

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

        $methodCore->checkMccAndEnableEmi($merchant, $methods);

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

        (new Detail\Core)->postFormSubmissionToZapier($zapierData, 'activations');

        $this->logActionToSlack($merchant, SlackActions::ACTIVATE);
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

    public function notifyMerchantForInstantActivation($merchant)
    {
        $org = $merchant->org ?: $this->repo->org->getRazorpayOrg();

        $data = [
            'merchant' => [
                Entity::NAME          => $merchant->getName(),
                Entity::BILLING_LABEL => $merchant->getBillingLabel(),
                Entity::EMAIL         => $merchant->getEmail(),
                'org'                 => [
                    OrgEntity::BUSINESS_NAME => $org->getBusinessName(),
                    OrgEntity::CUSTOM_CODE   => $org->getCustomCode(),
                ],
            ],
        ];

        $data['merchant']['org'][HostNameEntity::HOSTNAME] = $org->getPrimaryHostName();

        $instantActivationMail = new InstantActivationMail($data, $org->toArray());

        Mail::queue($instantActivationMail);
    }

    /**
     * Returns formatted pricing rules with proper display text
     * as an array with the display text as the key
     * [
     *   "2%" => ["Credit Cards", "Wallets"],
     *   "1.8%" => ["Wallets"],
     *   "2.1%" => ["Net Banking"]
     * ]
     * @param  array $rules Array of rules
     * @return array Formatted rules with flipped keys
     */
    protected function formatPricingRules($rules)
    {
        $newRules = $amountRangeRules = [];

        $rules = $this->rearrangeRules($rules);

        foreach ($rules as $rule)
        {
            $rule['pricing_display'] = Pricing\Plan::formattedPricing($rule);

            // This just holds Wallet/Card/Net Banking as of now
            $display = Payment\Method::formatted($rule[Pricing\Entity::PAYMENT_METHOD]);

            // This now holds Credit/Debit/All
            $methodType = $rule[Pricing\Entity::PAYMENT_METHOD_TYPE] ? : 'Visa/MasterCard/Maestro';

            if ($rule[Pricing\Entity::PAYMENT_METHOD] === Payment\Method::CARD)
            {
                // If we have a payment_network (such as AMEX/DICL)
                if ($rule[Pricing\Entity::PAYMENT_NETWORK] !== null)
                {
                    // This becomes "American Express Cards"
                    $display = $rule[Pricing\Entity::PAYMENT_NETWORK_NAME] . ' Cards';
                }
                else if ($methodType !== null)
                {
                    $type = ' ';

                    if ($rule[Pricing\Entity::INTERNATIONAL] === true)
                    {
                        $type .= 'International ';
                    }

                    // This is Credit/Debit/[ Visa/MasterCard/Maestro ] Cards
                    $display = ucfirst($methodType) . $type . 'Cards';
                }
            }
            else if ($rule[Pricing\Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING)
            {
                if ($rule[Pricing\Entity::PAYMENT_NETWORK] !== null)
                {
                    $display = $rule[Pricing\Entity::PAYMENT_NETWORK_NAME] . ' Net Banking';
                }
            }

            // Passing amount range rules separately
            // Support currently for only one set of amountRangeRules
            if ($rule[Pricing\Entity::AMOUNT_RANGE_ACTIVE] === true)
            {
                $amountRangeMin = $rule[Pricing\Entity::AMOUNT_RANGE_MIN] / 100;

                $amountRangeMax = $rule[Pricing\Entity::AMOUNT_RANGE_MAX] / 100;

                if ($amountRangeMin === 0)
                {
                    $amountRangeRules['low'] = $display . ' Below INR ' .
                                $amountRangeMax . ' - '.$rule['pricing_display'];
                }
                else
                {
                    $amountRangeRules['high'] = $display.' Over INR '.
                                $amountRangeMin.' - '.$rule['pricing_display'];
                }

                continue;
            }

            // We flip this around to store the rules as an array with the
            // pricing display as the key. Since the pricing display is
            // deterministic (see Pricing\Plan::formattedPricing)
            // The same pricing gives the same display
            //
            // Now we can iterate over the newRules array and display
            // the list of pricing options at the same pricing in the same
            // line easily
            $newRules[$rule['pricing_display']][] = $display;
        }

        return [ 'amountRangeRules' => $amountRangeRules,
                     'otherRules'   => $newRules];
    }

    /**
     * Rearrange $rules to display in emails in appropriate order
     * Rules are arranged on basis of usage:
     *  Basic Card Rules,
     *  Basic Netbanking Rules,
     *  Basic Wallet Rules,
     *  Any Other Exceptional Cases,
     * @param array $rules
     * @return array
     */
    protected function rearrangeRules(array $rules)
    {
        $arrangedRules = [];

        $orderOfRules = [
            Payment\Method::CARD,
            Payment\Method::NETBANKING,
            Payment\Method::WALLET,
            Payment\Method::EMI,
            'exceptional'
        ];

        $exceptionalRules = $emiRules = $cardRules = $netbankingRules = $walletRules = [];

        foreach ($rules as $rule)
        {
            switch ($rule['payment_method'])
            {
                case Payment\Method::CARD:
                    if ($rule['payment_network'] === null)
                    {
                        $cardRules[] = $rule;
                    }
                    else
                    {
                        $exceptionalRules[] = $rule;
                    }

                    break;

                case Payment\Method::NETBANKING:
                    $netbankingRules[] = $rule;
                    break;

                case Payment\Method::WALLET:
                    $walletRules[] = $rule;
                    break;

                case Payment\Method::EMI:
                    $emiRules[] = $rule;
                    break;

                default:
                    $exceptionalRules[] = $rule;
                    break;
            }
        }

        foreach ($orderOfRules as $ruleType)
        {
            $arrangedRules = array_merge($arrangedRules, ${$ruleType.'Rules'});
        }

        return $arrangedRules;
    }

    /**
     * Remove rules in the merchant's pricing plan
     * for methods not enabled for the merchant
     *
     * @param array $rules Array of rules
     * @param Entity $merchant Merchant entity being activated
     * @return array Array of rules
     **/
    protected function filterActiveRulesForMerchant($rules, $merchant)
    {
        $returnRules = [];

        $merchantMethods = (new Methods\Core)->getMethods($merchant);

        foreach ($rules as $rule) {
            // Don't add rules other than payment
            if ($rule[Pricing\Entity::FEATURE] !== Pricing\Feature::PAYMENT)
            {
                continue;
            }

            // Not mentioning some methods in the activation mails
            if (in_array($rule[Pricing\Entity::PAYMENT_METHOD], self::MAIL_EXCLUDED_METHODS, true) === true)
            {
                continue;
            }

            // Don't add international rule if merchant international not active
            if (($merchant->isInternational() === false) and
                 ($rule[Pricing\Entity::INTERNATIONAL] === true))
            {
                continue;
            }

            $methodCheck = 'is' . studly_case($rule[Pricing\Entity::PAYMENT_METHOD]) . 'Enabled';

            if ($merchantMethods->$methodCheck() === false)
            {
                continue;
            }

            // Don't add amex rule if merchant amex not active
            if (($merchantMethods->isAmexEnabled() === false) and
                 ($rule[Pricing\Entity::PAYMENT_NETWORK] === Card\Network::AMEX))
            {
                continue;
            }

            // If none of the above rule exceptions are valid, add the rule to
            // be returned.
            $returnRules[] = $rule;
        }

        return $returnRules;
    }

    /**
     * Activates Business Banking for a merchant.
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function activateBusinessBankingIfApplicable(Entity $merchant): Entity
    {
        if ($merchant->isBusinessBankingEnabled() === true)
        {
            $merchantDetails = (new Detail\Core())->getMerchantDetails($merchant);

            // If merchant is instantly activated or Activated this flow will kick in.
            if ($merchant->isActivated() === true)
            {
                // Business Banking logic is coupled only with the live mode.
                $liveMode = $this->app['basicauth']->getLiveConnection();

                $this->app['basicauth']->setModeAndDbConnection($liveMode);

                // Create Banking Balance.
                $balance = (new Balance\Service())->createOrFetchBalance($merchant, Product::BANKING, $liveMode);

                // Virtual Account.
                $virtualAccount = (new VirtualAccount\Core())->createOrFetchBankingVirtualAccount($merchant, $balance);
            }

            // This means that L2 form is also verified.
            if ($merchantDetails->getActivationStatus() === Detail\Status::ACTIVATED)
            {
                // Enable Payouts.
            }
        }

        return $merchant;
    }

}
