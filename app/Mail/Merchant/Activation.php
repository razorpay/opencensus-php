<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Card;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Pricing;
use RZP\Models\Payment;

class Activation extends Mailable
{
    protected $merchant;

    protected $plan;

    public function __construct(Merchant\Entity $merchant, PublicCollection $plan)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->plan = $plan;

    }

    protected function addRecipients()
    {
       $email = $this->merchant->getEmail();

       // For marketplace accounts, send this email to the parent merchant
       if ($this->merchant->isLinkedAccount() === true)
       {
            $email = $this->merchant->parent->getEmail();
       }

       $this->to($email);

       return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.activation');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.merchant.activation_text');

        return $this;
    }

    protected function addCc()
    {
        $this->cc(Constants::MAIL_ADDRESSES[Constants::NOTIFICATIONS]);

        return $this;
    }

    protected function addSubject()
    {
        $subjectName = $this->merchant->getBillingLabelElseName();

        $subject = "Razorpay | Account activated for $subjectName";

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $subjectName = $this->merchant->getBillingLabelElseName();

        $plan = $this->plan->toArrayPublic();

        $rules = $this->filterActiveRulesForMerchant($plan['rules'], $this->merchant);

        $data = [
            'merchant' => $this->merchant->toArray(),
            'plan'     => $plan,
            'name'     => $subjectName,
            'rules'    => $this->formatPricingRules($rules),
        ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_ACTIVATED);
        });

        return $this;
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
     * Current checks for International, Emi and Amex
     *
     * @param array $rules Array of rules
     * @return array Array of rules
     **/
    protected function filterActiveRulesForMerchant($rules)
    {
        $returnRules = [];

        $merchantMethods = (new Methods\Core)->getMethods($this->merchant);

        foreach ($rules as $rule) {
            // Don't add rules other than payment
            if ($rule[Pricing\Entity::FEATURE] !== Pricing\Feature::PAYMENT)
            {
                continue;
            }

            // Don't add international rule if merchant international not active
            if (($this->merchant->isInternational() === false) and
                 ($rule[Pricing\Entity::INTERNATIONAL] === true))
            {
                continue;
            }

            // Don't add emi rule if merchant emi not active
            if (($merchantMethods->isEmiEnabled() === false) and
                 ($rule[Pricing\Entity::PAYMENT_METHOD] === Methods\Entity::EMI))
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
