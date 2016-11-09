<?php

namespace RZP\Models\Payment\Processor;

use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
use Mail;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Notify
{
    const AUTHORIZED = 'authorized';
    const CARD_SAVED = 'card_saved';
    const CAPTURED   = 'captured';
    const REFUNDED   = 'refunded';
    const FAILED_TO_AUTHORIZED = 'failed_to_authorized';

    /**
     * The minimum amount for a transaction to be considered risky
     * This is used to decide low and high value transactions and pick
     * the correct slack channel. Currently set to INR 3000
     */
    const MIN_RISK_AMOUNT = 300000;

    /**
     * This is the minimum risk rating for a merchant that prompts a
     * post on the RISKY channel. The scale goes from 1-5. This is
     * decided by our risk team.
     */
    const MIN_HIGH_RISK_RATING = 3;
    const HIGH_RISK_RATING     = 4;
    const MAX_HIGH_RISK_RATING = 5;

    /**
     * When are receipt emails sent to the customer
     * @var array
     */
    protected static $receiptEmails = [
        self::AUTHORIZED,
        self::REFUNDED,
        self::FAILED_TO_AUTHORIZED
    ];

    // TODO: Shift to constants once we update PHP
    protected $mailViews = [
        self::AUTHORIZED    =>  [
            'customer'  => [
                'from' => 'care',
                'view' => [
                    'html'=> 'emails.payment.customer',
                    'text'=> 'emails.payment.customer_text'
                ]
            ]
        ],
        self::CAPTURED      =>  [
            'merchant'  => [
                'view' => [
                    'html'=> 'emails.payment.merchant',
                    'text'=> 'emails.payment.merchant_text'
                ]
            ]
        ],
        self::REFUNDED      =>  [
            'customer'  => [
                'from'  =>  'care',
                'view'  =>  'emails.refund.common',
            ],
            'merchant'  => [
                'view'  =>  'emails.refund.common',
            ]
        ],
        self::FAILED_TO_AUTHORIZED => [
            'customer'  => [
                'from'  =>  'care',
                'view' => [
                    'html'  =>  'emails.payment.customer',
                    'text'  =>  'emails.payment.customer_text'
                ]
            ],
            'merchant'  =>  [
                'view' => [
                    'html'  =>  'emails.payment.failed_to_authorized',
                    'text'  =>  'emails.payment.failed_to_authorized_text',
                ],
            ],
        ],
        self::CARD_SAVED    => [
            'customer'  => [
                'from' => 'care',
                'view' => 'emails.payment.cardsaving',
            ]
        ]
    ];

    protected $payment;
    protected $refund;
    protected $mode;
    protected $trace;

    /**
     * Creates a new Notify instance
     * @param Payment\Entity $payment The payment associated with the Notify
     */
    function __construct(Payment\Entity $payment)
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;
        $this->refreshTemplate();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->domain = $this->app['config']->get('applications.mailgun.url');
    }

    /**
     * Regenerates the entire template
     * @return null
     */
    protected function refreshTemplate()
    {
        $this->template = $this->templateData();
    }

    /**
     * Allows the notifier to be used for a refund as well
     * @param Payment\Refund\Entity $refund Refund entity
     */
    public function addRefund(Payment\Refund\Entity $refund)
    {
        $this->refund = $refund;
        $this->refreshTemplate();
    }

    /**
     * Sends out a mail given the view, subject and email address
     * Uses Mail::queue to queue emails
     * @param  string $view    array or string of mail views to use
     * @param  string $subject Subject of email
     * @param  string $to      Email address to send to
     * @return null
     */
    protected function sendMail($view, $subject, $to, $from = 'reports')
    {
        $from       = $this->getCompleteEmail($from);
        $replyTo    = $this->getCompleteEmail('support');
        $domain     = $this->domain;
        $fromHeader = 'Team Razorpay';

        Mail::queue(
            $view,
            $this->template,
            function ($message) use ($subject, $to, $from, $fromHeader, $replyTo, $domain)
            {
                // Bug fix because some from addresses were
                // not generated properly and are in the queue
                // Will drop this later
                if ($from === "@$domain")
                {
                    $from = "reports@$domain";
                }

                // to might be an array
                if (is_array($to))
                {
                    // For merchant emails
                    foreach ($to as $email)
                    {
                        $message->to($email);
                    }
                }
                else
                {
                    // This is for customer emails
                    $message->to($to);
                }

                $message->from($from, $fromHeader);
                $message->subject($subject);
                $message->replyTo($replyTo);
            }
        );
    }


    /**
     * Returns a complete email address
     * @param  string $user (reports)
     * @return string (reports@razorpay.com)
     */
    protected function getCompleteEmail($user)
    {
        return "$user@{$this->domain}";
    }

    /**
     * Sends out mails for a particular event trigger
     * @param  string $event
     * @return null
     */
    protected function notifyViaMail($event)
    {
        // This sends out mail for all views defined above
        // type = merchant|customer
        foreach ($this->mailViews[$event] as $type => $struct)
        {
            $isMerchant = ($type === 'merchant');

            $subject = $this->getSubject($event, $isMerchant);
            $to = $this->template[$type]['email'];

            $view = $struct['view'];

            $from = (isset($struct['from'])) ? $struct['from'] : null;

            // This finally sends the mail
            if ($this->isMailEnabled($event, $isMerchant))
            {
                if ($from !== null)
                {
                    $this->sendMail($view, $subject, $to, $from);
                }
                else
                {
                    $this->sendMail($view, $subject, $to);
                }

            }
        }
    }

    protected function notifyViaSlack($event)
    {
        $slackData = $this->getSlackData($event);

        // We don't send out a notification on capture
        $slackMessages = [
            self::FAILED_TO_AUTHORIZED => 'Failed Payment Authorized',
            self::AUTHORIZED    =>  'Payment Authorized',
            self::REFUNDED      =>  'Payment Refunded'
        ];

        // Send out Slack notifications for the event
        // You can control slack posts via SLACK_ENABLE

        if ((array_key_exists($event, $slackMessages)) and
            ($this->isSlackEnabled()))
        {
            $settings = [
                'channel'   => $this->getSlackChannel(),
                'color'     => $this->getSlackPostColor(),
            ];

            $this->app['slack']->queue($slackMessages[$event], $slackData, $settings);
        }
    }

    /**
     * Returns color to use for slack posts
     * @return string
     */
    protected function getSlackPostColor()
    {
        switch ($this->template['payment']['risk'])
        {
            case 1:
            case 2:
            case 3:
                return 'good';
                break;
            case 4:
                return 'warning';
            case 5:
                return 'danger';
            // Peter River color from flatuicolors.com
            default:
                return '#4AA3DF';
        }
    }

    /**
     * Returns the slack channel to be used for posting
     */
    protected function getSlackChannel()
    {
        $config = $this->app['config'];

        $channel = $config->get('slack.channels.low');

        $riskRating = $this->template['payment']['risk'];

        $amount = $this->template['payment']['raw_amount'];

        // The priority order is important here
        if ($riskRating == self::MAX_HIGH_RISK_RATING)
        {
            $channel = $config->get('slack.channels.highrisk');
        }
        else if ($riskRating === self::HIGH_RISK_RATING)
        {
            $channel = $config->get('slack.channels.high_4');
        }
        else if (($riskRating === self::MIN_HIGH_RISK_RATING) and
                ($amount <= 1000))
        {
            $channel = $config->get('slack.channels.lt_10');
        }
        else if ($riskRating >= self::MIN_HIGH_RISK_RATING)
        {
            $channel = $config->get('slack.channels.risky');
        }
        else if ($this->payment->amount >= self::MIN_RISK_AMOUNT)
        {
            $channel = $config->get('slack.channels.high');
        }

        return $channel;
    }

    /**
     * This is the primary public method for this class
     * @param  string $event Trigger notifications for this event
     * @return null
     */
    public function trigger($event)
    {
        /**
         * This is wrapped in a try-catch block as this is not
         * critical path for the payment operation
         * We should continue running even if this raises critical error.
         */
        try
        {
            // Send out notification for Slack
            $this->notifyViaSlack($event);

            // Mails use the entire template
            // So there is no need to get separate data for each
            $this->notifyViaMail($event);
        }
        catch (\Exception $e)
        {
            // Shouldn't fail for any reason
            $this->trace->error(
                TraceCode::PAYMENT_NOTIFY_FAILED,
                [
                    'payment_id' => $this->payment->getPublicId(),
                    'message'    => 'Payment Notify raised an exception'
                ]
            );

            $this->trace->traceException($e);
        }
    }

    protected function getSubject($event, $merchant = true)
    {
        $action = 'Payment';

        if ($event === self::REFUNDED)
        {
            $action = 'Refund';
        }

        /**
         * The reason we have a fallback to the amount here is because
         * not every merchant necessarily has a proper billing label (most do)
         * Since the dba field was moved from the dashboard to the API after a
         * while. All new merchants have this field for sure, though. We
         * can do a survey later and remove this check from here and other
         * places
         */
        if (isset($this->template['merchant']['billing_label']))
        {
            $subject = "$action successful for {$this->template['merchant']['billing_label']}";
        }
        else
        {
            $subject = "$action successful for {$this->template['payment']['amount']}";
        }

        if ($event === self::CARD_SAVED)
        {
            $subject = "Card successfully saved with Razorpay";
        }

        // All mails that we send out to the merchant follow the same pattern:
        // Razorpay | X action taken for Y
        // Y is usually the merchant name/billing label
        // But if that is unavailable, we might use amount
        //
        // Direct emails to customers are without the prefix
        if ($merchant === true)
        {
            $subject = "Razorpay | $subject";
        }

        return $subject;
    }

    protected function getMerchantForSlack()
    {
        $website = $this->template['merchant']['website'];
        $text    = $this->template['merchant']['billing_label'];

        $dashboardLink = $this->payment->merchant->getDashboardEntityLink();
        $merchantId = $this->template['merchant']['id'];

        // If we don't have billing label or website, just send to dashboard
        if (empty($text) or empty($website))
        {
            return "<$dashboardLink|$merchantId>";
        }

        return "<$website|$text> [<$dashboardLink|$merchantId>]";
    }

    /**
     * Returns slack formatted version of a payment id
     * @param  string $id Payment Id
     * @return string
     */
    protected function getPaymentLinkForSlack($id)
    {
        return "<https://dashboard.razorpay.com/admin#/app/payments/live/$id|pay_$id>";
    }

    /**
     * Returns slack formatted version of a refund id
     * @param  string $id Refund id
     * @return string     Formatted URL to Refund
     */
    protected function getRefundLinkForSlack($id)
    {
        return "<https://dashboard.razorpay.com/admin#/app/entity/live/refund/$id|rfnd_$id>";
    }

    /**
     * Returns a flat array that is to be sent to Slack for a trigger event
     * We don't need to send out the original payment details for a refund
     * The array keys are flattened (concatenated using dots)
     * Because slack doesn't support nested arrays
     *
     * So payment.amount = INR 500
     *  & payment.currency = INR
     *
     * Would be some common examples
     * @param  string $event Trigger event
     * @return array Flat array of data to be sent to Slack
     */
    protected function getSlackData($event)
    {
        switch ($event)
        {
            // Both cases are the same
            case self::FAILED_TO_AUTHORIZED:
            case self::AUTHORIZED:
                $data = $this->template['payment'];
                $data['id'] = $this->getPaymentLinkForSlack($data['id']);
                unset($data['method'], $data['public_id']);
                break;

            // Capture is unused right now
            case self::CAPTURED:
                $data = $this->template['payment'];
                break;

            case self::REFUNDED:
                $data = $this->template['refund'];
                $data['id'] = $this->getRefundLinkForSlack($data['id']);
                $data['payment_id'] = $this->getPaymentLinkForSlack($data['payment_id']);
                unset($data['public_id']);
                break;
        }

        // Add merchant data
        $data['merchant'] = $this->getMerchantForSlack();

        if (isset($data['orderId']))
        {
            $orderId = $data['orderId'];
            unset($data['orderId']);
            $data['orderId'] = $orderId;
        }

        // This is for both pyaments and refund
        if (isset($data['timestamp']))
        {
            unset($data['timestamp']);
        }

        if ((isset($data['risk']) === true) and
            ($data['risk'] === self::MAX_HIGH_RISK_RATING))
        {
            unset ($data['risk']);
            $data['email'] = $this->template['customer']['email'];
            $data['phone'] = $this->template['customer']['phone'];
        }

        $data = $this->flatten($data);

        return $data;
    }

    /**
     * Returns template data to be used for mail and slack templates
     *
     * Also includes refund information if provided via addRefund
     * @return array Template data
     */
    protected function templateData()
    {
        $data  = [
            'customer'  =>  [
                'email' =>  $this->payment->getEmail(),
                'phone' =>  $this->payment->getContact()
            ],
            'merchant'  =>  [
                'billing_label' =>  $this->payment->merchant->getBillingLabel(),
                'website'       =>  $this->payment->merchant->getWebsite(),
                // This is the reporting email address for the merchant
                'email'         =>  $this->payment->merchant->getTransactionReportEmail(),
                'id'            =>  $this->payment->merchant->getId(),
            ],
            'payment'   =>  [
                'id'        =>  $this->payment->getId(),
                'public_id' =>  $this->payment->getPublicId(),
                'amount'    =>  "INR ".number_format($this->payment['amount']/100, 2),
                'raw_amount' =>  $this->payment['amount'],
                'timestamp' =>  $this->payment->getUpdatedAt(),
                'captured_at' => $this->payment->getAttribute('captured_at'),

                // note that payment method is unavailable to the merchant
                'method'    =>  $this->payment->getMethodWithDetail(),
                'orderId'   =>  $this->payment->getOrderId(),
                'risk'      =>  $this->payment->merchant->getRiskRating()
            ]
        ];

        if ($this->payment->card !== null)
        {
            $card = $this->payment->card;

            $expiryMonth = str_pad($card->getExpiryMonth(), 2, "0", STR_PAD_LEFT);

            $data['card'] = [
                'number'    => '**** **** **** ' . $card->getLast4(),
                'expiry'    => $expiryMonth . '/' . $card->getExpiryYear(),
                'network'   => $card->getNetworkCode(),
                'color'     => $card->getNetworkColorCode(),
            ];
        }

        if ($this->refund)
        {
            $data['refund'] = [
                'id'        =>  $this->refund->getId(),
                'amount'    =>  "INR ".number_format($this->refund->getAmount()/100, 2),
                'timestamp' =>  $this->refund->getCreatedAt(),
                'payment_id'=>  $this->refund->payment->getId(),
                'public_id' =>  $this->refund->getPublicId(),
            ];
        }

        return $data;
    }

    /**
     * Returns whether a key value pair is a timestamp
     * Called after flattening the array
     * @param  string  $key   key name
     * @param  mixed  $value    value
     * @return boolean
     */
    protected function isTimestamp($key, $value)
    {
        if (substr($key, -9) !== 'timestamp')
        {
            return false;
        }

        return ((is_numeric($value)) and
            ($value <= PHP_INT_MAX) and
            ($value >= -PHP_INT_MAX));
    }


    /**
     * Removes all null and false values from the array
     * Expects a flattened array (no nested arrays)
     * Also converts timestamps to proper datetime
     * @param  array  $data data
     * @return array data with all null values removed
     */
    protected function cleanData(array $data)
    {
        foreach ($data as $key => $value)
        {
            // We remove empty values from the array
            // So slack isn't filled with null/false
            if (($value === null) or
                ($value === false))
            {
                unset($data[$key]);
            }

            // Convert timestamps to readable versions
            if ($this->isTimestamp($key, $value))
            {
                $data[$key] = Carbon::createFromTimestamp($value, "Asia/Kolkata")->format('j M Y h:i a');
            }
        }

        return $data;
    }

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     * @param  array $array  input array
     * @param  string $prefix prefix used to concat keys
     * @return array flat version of input array
     */
    protected function flatten(array $array, $prefix = '')
    {
        $result = array();

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result = $result + $this->flatten($value, $prefix . $key . '.');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }

        return $this->cleanData($result);
    }

    /**
     * Whether a given email is meant to be a customer receipt email
     * A receipt email is defined as a mail sent to the customer
     * on a succesful payment. This is currently just the following:
     *   - AUTHORIZED
     *   - FAILED_TO_AUTHORIZED
     * @param  string  $event      Event for which the mail is intended
     * @param  boolean $isMerchant Whether this mail is for the merchant.
     * @return boolean
     */
    protected function isCustomerReceiptEmail($event, $isMerchant)
    {
        // If the mail is for a merchant, it can't be a customer receipt email
        if ($isMerchant)
        {
            return false;
        }

        return in_array($event, self::$receiptEmails);
    }

    /**
     * Whether or not we need to trigger the notifications
     * The order of conditions in this is imporant
     * @param string $event Event triggered
     * @return boolean
     */
    protected function isMailEnabled($event, $isMerchant = false)
    {
        // If the merchant has disabled customer emails
        // And this was a customer receipt email
        if (($this->payment->merchant->isReceiptEmailsEnabled() === false) and
            ($this->isCustomerReceiptEmail($event, $isMerchant)))
        {
            return false;
        }

        return $this->isEnabled();

    }

    /**
     * Whether to send notifications or not
     * @return boolean
     */
    protected function isEnabled()
    {
        // We only send notifications if Mode is not TEST
        // or if the env=dev or env=testing
        // so env=dev or env=testing overrides TEST mode
        if ($this->app->environment('dev', 'testing'))
        {
            return true;
        }

        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        return true;
    }

    /**
     * Whether to send slack notifications
     * @return boolean
     */
    protected function isSlackEnabled()
    {
        return $this->isEnabled();
    }
}
