<?php

namespace RZP\Models\Merchant;

use Mail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Merchant\Activation as ActivationMail;


class Activate extends Base\Core
{
    const MAIL_EXCLUDED_METHODS = [
        // Don't include marketplace transfer method (for now)
        Payment\Method::TRANSFER,

        // Don't include bank transfer (VA) method (for now)
        // TODO: Will add once we've figured out how to display max fees correctly
        Payment\Method::BANK_TRANSFER
    ];

    /**
     * This function is used for activating merchant
     * @param Entity $merchant
     * @param bool $activateByStatus which is by default false, it determines
     * if the activation is done by the new activation status `activated`.
     *
     * @return array
     */
    public function activate(Entity $merchant, bool $activateByStatus = false): array
    {
        $merchant->getValidator()->validateBeforeActivate();

        //
        // Ensure that all payment methods enabled for the merchant
        // has an associated pricing assigned
        //
        (new Methods\Core)->checkPricing($merchant);

        // $terminal = (new Terminal\Repository)->getByMerchantId($id);

        // if ($terminal === null)
        // {
        //     throw new Exception\BadRequestException(
        //         ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        // }

        if ($activateByStatus === true)
        {
            $bankAccount = (new Detail\Service)->getBankAccountMap($merchant->merchantDetail);

            (new Service)->addBankAccount($merchant->id, $bankAccount);
        }

        $ba = $this->repo->bank_account->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        $oldMerchant = clone $merchant;

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

        $merchant->enableReceiptEmails();

        $merchant->activate();

        if ($activateByStatus === false)
        {
            // Triggering
            $workflow = $this->app['workflow']
                             ->setEntity($merchant->getEntity())
                             ->handle($oldMerchant, $merchant);
        }

        (new Merchant\Core)->createBalance($merchant, 'live');

        $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $this->repo->saveOrFail($merchant);

            $merchantDetail = $merchant->merchantDetail;

            $merchantDetail->setLocked(true);

            $this->repo->saveOrFail($merchantDetail);
        });

        $this->trace->info(
            TraceCode::MERCHANT_ACCOUNT_ACTIVATED,
            ['merchant_id' => $merchant->getId()]);

        $this->sendMerchantActivatedEvents($merchant);

        return $merchant->toArrayPublic();
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
        $plan = $this->repo->merchant->getPricingPlanOrFailPublic($merchant);

        $org = $merchant->org;

        $subjectName = $merchant->getBillingLabel();

        if ($org === null)
        {
            $org = $this->repo->org->getRazorpayOrg();
        }

        $subject = $org->getBusinessName() . " | Account activated for $subjectName";

        $plan = $plan->toArrayPublic();

        $rules = $this->filterActiveRulesForMerchant($plan['rules'], $merchant);

        $data = [
            'merchant' => [
                'name'          => $merchant->getName(),
                'website'       => $merchant->getWebsite(),
                'billing_label' => $merchant->getBillingLabel(),
                'email'         => $merchant->getEmail(),
                'org'           => [
                    'business_name' => $org->getBusinessName(),
                    'custom_code'   => $org->getCustomCode(),
                ],
            ],
            'rules'    => $this->formatPricingRules($rules),
            'subject'  => $subject,
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
        $arrangedRules = array();

        $orderOfRules = array('card', 'netbanking', 'wallet', 'emi', 'exceptional');

        $exceptionalRules = $emiRules = $cardRules = $netbankingRules = $walletRules = [];

        foreach ($rules as $rule)
        {
            switch ($rule['payment_method'])
            {
                case 'card':
                    if ($rule['payment_network'] === null)
                    {
                        $cardRules[] = $rule;
                    }
                    else
                    {
                        $exceptionalRules[] = $rule;
                    }

                    break;

                case 'netbanking':
                    $netbankingRules[] = $rule;
                    break;

                case 'wallet':
                    $walletRules[] = $rule;
                    break;

                case 'emi':
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
}
