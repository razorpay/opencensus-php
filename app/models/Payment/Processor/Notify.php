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
        // $this doesn't work with closures
        // https://wiki.php.net/rfc/closures/removal-of-this
        $data = $this->template;

        Mail::queue($view, $this->template,
            function($message) use ($data, $subject, $to){
                $message->to($to);
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
            if ($this->isEnabled())
            {
                $this->sendMail($view, $subject, $to);
            }
        }
    }

    /**
     * This is the primary public method for this class
     * @param  string $event Trigger notifications for this event
     * @return null
     */
    public function trigger($event)
    {
        $slackData = $this->getSlackData($event);

        $slackMessages = [
            self::AUTHORIZED    =>  'Payment Authorized',
            self::CAPTURED      =>  'Payment Captured',
            self::REFUNDED      =>  'Payment Refunded'
        ];

        // Send out Slack notifications for the event
        // You can control slack posts via SLACK_ENABLE
        $this->slackPost($slackMessages[$event], $slackData);

        // Mails use the entire template
        $this->notifyViaMail($event);
    }

    protected function getSubject($event, $merchant = true)
    {
        $entity = 'Payment';

        if ($event === self::REFUNDED)
        {
            $entity = 'Refund';
        }

        if(isset($this->template['merchant']['billing_label']))
        {
            $subject = "$entity successful for {$this->template['merchant']['billing_label']}";
        }
        else
        {
            $subject = "$entity successful for {$this->template['payment']['amount']}";
        }

        // All mails to merchants must have the prefix
        if ($merchant === true)
        {
            $subject = "Razorpay | $subject";
        }

        return $subject;
    }

    protected function getSlackData($event)
    {
        switch ($event) {
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

        if (array_key_exists('payment.method.0', $data) and $data['payment.method.0'] === 'Card')
        {
            // We don't want to post the card number on Slack
            unset($data['payment.method.1']);
            unset($data['payment.method.0']);
            $data['payment.method'] = 'Card';
        }

        return $data;
    }

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

        return ( is_numeric($value) and ($value <= PHP_INT_MAX) and ($value >= -PHP_INT_MAX));
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
        foreach ($data as $key => $value) {
            if ($value === null or $value === false)
            {
                unset($data[$key]);
            }

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
    protected function flatten($array, $prefix = '') {

        $result = array();

        foreach ($array as $key=>$value)
        {
            if (is_array($value)) {
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
    protected function isEnabled()
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
