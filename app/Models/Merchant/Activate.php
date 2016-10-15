<?php

namespace RZP\Models\Merchant;

use RZP\Constants\Mode;
use Carbon\Carbon;
use Mail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Card;
use RZP\Models\Key;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Webhook;
use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Trace\TraceCode;

class Activate extends Base\Core
{
    public function activate($merchant)
    {
        if ($merchant->isActivated())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        $plan = $this->repo->merchant->getPricingPlanOrFailPublic($merchant);

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

        $ba = $this->repo->bank_account->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        (new Merchant\Validator)->validateBeforeActivate($merchant);

        (new Merchant\Core)->createBalance($merchant, 'live');

        $merchant->enableReceiptEmails();

        $merchant->activate();

        $this->repo->saveOrFail($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_ACCOUNT_ACTIVATED,
            ['merchant_id' => $merchant->getId()]);

        $this->sendActivationEmail($merchant, $plan);

        return $merchant->toArrayPublic();
    }

    /**
     * Sends activation email to the merchant, cc's notifications
     * Includes pricing details in the email (properly formatted)
     * @param  RZP\Models\Merchant\Entity $merchant merchant entity
     * @return null
     */
    protected function sendActivationEmail($merchant, $plan)
    {
        $subjectName = $merchant->getBillingLabelElseName();

        $subject = "Razorpay | Account activated for $subjectName";

        $plan = $plan->toArrayPublic();

        $rules = $this->filterActiveRulesForMerchant($plan['rules'], $merchant);

        $data = [
            'merchant'  =>  $merchant->toArray(),
            'plan'      =>  $plan,
            'rules'     =>  $this->formatPricingRules($rules),
            'subject'   =>  $subject,
        ];

        $config = $this->app->config->get('applications.mailgun');

        // Send the activation email
        $this->app['mailer']->queue(
            [
                'html' => 'emails.merchant.activation',
                'text' => 'emails.merchant.activation_text'
            ],
            $data,
            function ($message) use ($data, $config)
            {
                $message->to($data['merchant']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->cc('notifications@razorpay.com');
                $message->subject($data['subject']);
            }
        );
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
                $amountRangeMin = $rule[Pricing\Entity::AMOUNT_RANGE_MIN]/100;
                $amountRangeMax = $rule[Pricing\Entity::AMOUNT_RANGE_MAX]/100;

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
     * @param entity $merchant Merchant entity
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

            // Don't add international rule if merchant international not active
            if (($merchant->isInternational() === false) and
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
