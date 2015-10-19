<?php

namespace Models\Payment\Processor;

use App;
use Carbon\Carbon;
use Constants\Mode;
use Mail;
use Models\Payment;
use Services\SlackPoster;

class Notify
{
    use SlackPoster;
    const AUTHORIZED = 'authorized';
    const CAPTURED   = 'captured';
    const REFUNDED   = 'refunded';

    // TODO: Shift to constants once we update PHP
    protected $mailViews = [
        self::AUTHORIZED    =>  [
            'customer'  => [
                'html'=> 'emails.payment.customer',
                'text'=> 'emails.payment.customer_text'
            ]
        ],
        self::CAPTURED      =>  [
            'merchant'  => [
                'html'=> 'emails.payment.merchant',
                'text'=> 'emails.payment.merchant_text'
            ]
        ],
        self::REFUNDED      =>  [
            'customer'  => 'emails.refund.common',
            'merchant'  => 'emails.refund.common'
        ]
    ];

    protected $payment;
    protected $refund;
    protected $config;
    protected $mode;

    /**
     * Creates a new Notify instance
     * @param Payment\Entity $payment The payment associated with the Notify
     */
    function __construct(Payment\Entity $payment)
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;
        $this->refreshTemplate();

        $this->config = $this->app->config->get('applications.mailgun');
        $this->mode = $this->app['rzp.mode'];
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
    protected function sendMail($view, $subject, $to)
    {
        Mail::queue($view, $this->template,
            function ($message) use ($subject, $to){

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

                $message->subject($subject);
            }
        );
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
        foreach ($this->mailViews[$event] as $type => $view)
        {
            $isMerchant = ($type === 'merchant');

            $subject = $this->getSubject($event, $isMerchant);
            $to = $this->template[$type]['email'];

            // This finally sends the mail
            if ($this->isMailEnabled())
            {
                $this->sendMail($view, $subject, $to);
            }
        }
    }

    protected function notifyViaSlack($event)
    {
        $slackData = $this->getSlackData($event);

        // We don't send out a notification on capture
        $slackMessages = [
            self::AUTHORIZED    =>  'Payment Authorized',
            self::REFUNDED      =>  'Payment Refunded'
        ];

        // Send out Slack notifications for the event
        // You can control slack posts via SLACK_ENABLE

        if (array_key_exists($event, $slackMessages))
        {
            $this->slackPost($slackMessages[$event], $slackData);
        }
    }

    /**
     * This is the primary public method for this class
     * @param  string $event Trigger notifications for this event
     * @return null
     */
    public function trigger($event)
    {
        // Send out notification for Slack
        $this->notifyViaSlack($event);

        // Mails use the entire template
        // So there is no need to get separate data for each
        $this->notifyViaMail($event);
    }

    protected function getSubject($event, $merchant = true)
    {
        $action = 'Payment';

        if ($event === self::REFUNDED)
        {
            $action = 'Refund';
        }

        if(isset($this->template['merchant']['billing_label']))
        {
            $subject = "$action successful for {$this->template['merchant']['billing_label']}";
        }
        else
        {
            $subject = "$action successful for {$this->template['payment']['amount']}";
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
            case self::AUTHORIZED:
                $data = $this->template;
                break;

            case self::CAPTURED:
                $data = $this->template['payment'];
                break;

            case self::REFUNDED:
                $data = $this->template['refund'];
                break;
        }

        $data = $this->flatten($data);

        /**
         * See the getMethodWithDetail method in Models\Payment\Entity
         * for why its numeric array
         */
        if ((array_key_exists('payment.method.0', $data)) and
            ($data['payment.method.0'] === 'Card'))
        {
            // We don't want to post the card number on Slack
            unset($data['payment.method.1']);
            unset($data['payment.method.0']);

            // We just show the method as Card
            // Without any further details
            $data['payment.method'] = 'Card';
        }

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
                'email'         =>  $this->payment->merchant->getTransactionReportEmail()
            ],
            'payment'   =>  [
                'id'        =>  $this->payment->getId(),
                'amount'    =>  "INR ".number_format($this->payment['amount']/100, 2),
                'timestamp' =>  $this->payment->getUpdatedAt(),
                'captured_at' => $this->payment->getAttribute('captured_at'),
                // note that payment method is unavailable to the merchant
                'method'    =>  $this->payment->getMethodWithDetail(),
                'orderId'   =>  $this->payment->getOrderId()
            ]
        ];

        if ($this->refund)
        {
            $data['refund'] = [
                'id'        =>  $this->refund->getId(),
                'amount'    =>  $this->refund->getAmount(),
                'timestamp' =>  $this->refund->getCreatedAt(),
                'payment_id'=>  $this->refund->payment->getId()
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
                $data[$key] = Carbon::createFromTimeStamp($value, "Asia/Kolkata")->format('j M Y h:i a');
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
     * Whether or not we need to trigger the notifications
     * @return boolean
     */
    protected function isMailEnabled()
    {
        // We only send mails if Mode is not TEST
        // or if the env=dev
        if($this->app->environment('dev'))
            return true;

        if($this->mode === Mode::TEST)
            return false;

        return true;
    }
}
