<?php

namespace Models\Merchant;

use Constants\Mode;
use Carbon\Carbon;
use Mail;

use Models\Base;
use Models\Merchant;
use Models\Key;
use Models\Payment;
use Models\Pricing;
use Models\Terminal;
use Models\Merchant\Webhook;
use EE\Exception;
use EE\Error\ErrorCode;

use Trace\TraceCode;

class Activate
{
    public function __construct($app)
    {
        $this->app = $app;

        $this->repo = new Merchant\Repository;
    }

    public function activate($merchant)
    {
        if ($merchant->isActivated())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        $pricing = $this->repo->getPricingPlanOrFailPublic($merchant);

        // $terminal = (new Terminal\Repository)->getByMerchantId($id);

        // if ($terminal === null)
        // {
        //     throw new Exception\BadRequestException(
        //         ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        // }

        $ba = (new BankAccount\Repository)->getBankAccount($merchant);

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

        $this->sendActivationEmail($merchant);

        return $merchant->toArrayPublic();
    }

    /**
     * Sends activation email to the merchant, cc's notifications
     * Includes pricing details in the email (properly formatted)
     * @param  Models\Merchant\Entity $merchant merchant entity
     * @return null
     */
    protected function sendActivationEmail($merchant)
    {
        $plan = $merchant->getPricingPlan();

        $subjectName = $merchant->getBillingLabelElseName();

        $subject = "Razorpay | Account activated for $subjectName";

        $data = [
            'merchant'  =>  $merchant->toArray(),
            'plan'      =>  $plan,
            'rules'     =>  $this->formatPricingRules($plan['rules']),
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
            $display = Payment\Method::formatted($rule['payment_method']);

            // This now holds Credit/Debit/All
            $method = $rule['payment_method_type'] ? : 'Visa/MasterCard/Maestro';

            // If we have a payment_network (such as AMEX/DICL)
            if ($rule['payment_network'] !== null)
            {
                // This becomes "American Express Cards"
                $display = $rule['payment_network_name'] . ' Cards';
            }
            elseif (($method !== null) and
                    ($rule['payment_method'] === 'card') and
                    ($rule[Pricing\Entity::INTERNATIONAL] === true))
            {
                // This is Credit/Debit/[ Visa/MasterCard/Maestro ] Cards
                $display = ucfirst($method) . ' International Cards';
            }
            elseif ($method !== null and $rule['payment_method'] === 'card')
            {
                // This is Credit/Debit/[ Visa/MasterCard/Maestro ] Cards
                $display = ucfirst($method) . ' Cards';
            }

            // Passing amount range rules seperately
            // Support currently for only one set of amountRangeRules
            if ($rule[Pricing\Entity::AMOUNT_RANGE_ACTIVE] === true)
            {
                $amountRangeMin = $rule[Pricing\Entity::AMOUNT_RANGE_MIN]/100;
                $amountRangeMax = $rule[Pricing\Entity::AMOUNT_RANGE_MAX]/100;

                if ($amountRangeMin === 0)
                {
                    $amountRangeRules['low'] = $display.' Below INR '.
                                $amountRangeMax.' - '.$rule['pricing_display'];
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
}
