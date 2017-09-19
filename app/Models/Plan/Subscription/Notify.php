<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Invoice;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Constants\Timezone;
use RZP\Models\Plan\Subscription;
use RZP\Models\Payment\Processor;
use RZP\Mail\Subscription as SubscriptionMail;

class Notify extends Processor\Notify
{
    protected $payment;
    protected $merchant;
    protected $mode;
    protected $trace;
    protected $template;
    protected $invoice = null;
    protected $subscription = null;
    protected $slackEnabled = true;
    protected $options = [];

    const TIMESTAMP_FIELDS = [
        Invoice\Entity::BILLING_START,
        Invoice\Entity::BILLING_END,
        Subscription\Entity::CHARGE_AT,
    ];

    /**
     * Creates a new Notify instance
     *
     * @param Payment\Entity $payment The payment associated with the Notify
     */
    function __construct(Subscription\Entity $subscription, array $options = [])
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->subscription = $subscription;

        $this->merchant = $this->subscription->merchant;

        if (isset($options[Event::PAYMENT]) === true)
        {
            $this->payment = $options[Event::PAYMENT];

            if ($this->payment->hasInvoice() === true)
            {
                $this->invoice = $this->payment->invoice;
            }

            unset($options[Event::PAYMENT]);
        }

        $this->options = $options;

        $this->refreshTemplate();
    }

    protected function notifyViaSlack($event)
    {
        // We don't send out a notification on capture
        $slackMessages = [
            Event::ACTIVATED => 'SUBSCRIPTION_ACTIVATED',
            Event::CHARGED   => 'SUBSCRIPTION_CHARGED',
            Event::PENDING   => 'SUBSCRIPTION_PENDING',
            Event::HALTED    => 'SUBSCRIPTION_HALTED',
            Event::CANCELLED => 'SUBSCRIPTION_CANCELLED',
        ];

        $settings = [
            'channel' => $this->getSlackChannel($event),
            'color'   => $this->getSlackPostColor($event),
        ];

        // Send out Slack notifications for the event
        // You can control slack posts via SLACK_ENABLE
        if ((array_key_exists($event, $slackMessages)) and
            ($this->isSlackEnabled()))
        {
            $slackData = $this->getSlackData($event);

            $this->app['slack']->queue($slackMessages[$event], $slackData, $settings);
        }
    }

    /**
     * Sends out mails for a particular event trigger
     *
     * @param  string $event
     * @return null
     */
    protected function notifyViaMail($event)
    {
        $mailableClass = $this->getMailableClass($event);

        $this->setOptions($event);

        if (Event::isCustomerEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template);

            if ($this->isCustomerMailEnabledForMerchant($mailable) === true)
            {
                Mail::queue($mailable);
            }
        }

        if (Event::isMerchantEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template, true);

            if ($this->isMerchantMailEnabled($mailable) === true)
            {
                Mail::queue($mailable);
            }
        }
    }

    /**
     * Returns color to use for slack posts
     *
     * @return string
     */
    protected function getSlackPostColor(string $event = null)
    {
        switch ($event)
        {
            case Event::ACTIVATED:
            case Event::CHARGED:
                return 'good';
            case Event::PENDING:
            case Event::CANCELLED:
                return 'warning';
            case Event::HALTED:
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

        return $config->get('slack.channels.subscriptions');
    }

    /**
     * This is the primary public method for this class
     *
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
            // $this->notifyViaSlack($event);

            // Mails use the entire template
            // So there is no need to get separate data for each
            $this->notifyViaMail($event);
        }
        catch (\Exception $e)
        {
            // Shouldn't fail for any reason
            $this->trace->error(
                TraceCode::SUBSCRIPTION_NOTIFY_FAILED,
                [
                    'subscription_id' => $this->subscription->getPublicId(),
                    'message'         => 'Subscription Notify raised an exception'
                ]
            );

            $this->trace->traceException($e);
        }
    }

    /**
     * Returns slack formatted version of a subscription id
     *
     * @param  string $id Subscription Id
     * @return string
     */
    protected function getSubscriptionLinkForSlack($id)
    {
        return "<https://dashboard.razorpay.com/admin#/app/entity/live/subscription/$id|sub_$id>";
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
     *
     * @param  string $event Trigger event
     * @return array Flat array of data to be sent to Slack
     */
    protected function getSlackData($event)
    {
        $data = $this->template['subscription'];
        $data['id'] = $this->getSubscriptionLinkForSlack($data['id']);

        $payment   = null;
        $paymentId = null;

        if ($this->payment !== null)
        {
            $payment = $this->template['payment'];

            $paymentId = $payment['id'];
        }

        switch ($event)
        {
            // Both cases are the same
            case Event::ACTIVATED:
            case Event::CHARGED:
                $data['payment_id'] = $this->getPaymentLinkForSlack($paymentId);
                break;
            case Event::PENDING:
            case Event::HALTED:
                $data['payment'] = $payment;
                $data['payment']['id'] = $this->getPaymentLinkForSlack($paymentId);
                break;
            case Event::CANCELLED:
                break;
            default:
                break;
        }

        // Add merchant data
        $data['merchant'] = $this->getMerchantForSlack();

        $data = $this->flatten($data);

        return $data;
    }

    /**
     * Returns template data to be used for mail and slack templates
     *
     * Also includes refund information if provided via addRefund
     *
     * @return array Template data
     */
    protected function templateData()
    {
        $data = [
            'subscription' => [
                Subscription\Entity::ID         => $this->subscription->getId(),
                Subscription\Entity::STATUS     => $this->subscription->getStatus(),
                Subscription\Entity::PUBLIC_ID  => $this->subscription->getPublicId(),
                Subscription\Entity::TYPE       => $this->subscription->getType(),
                Subscription\Entity::CHARGE_AT  => $this->formatTime($this->subscription->getChargeAt()),
            ],
            'merchant'  => [
                Merchant\Entity::BILLING_LABEL => $this->merchant->getBillingLabel(),
                Merchant\Entity::WEBSITE       => $this->merchant->getWebsite(),
                Merchant\Entity::EMAIL         => $this->merchant->getTransactionReportEmail(),
                Merchant\Entity::ID            => $this->merchant->getId(),
            ],
            'customer' => [
                'email' => $this->subscription->customer->getEmail(),
                'phone' => $this->subscription->customer->getContact()
            ],
        ];

        if ($this->payment !== null)
        {
            $data['payment']  = [
                Payment\Entity::ID              => $this->payment->getId(),
                Payment\Entity::PUBLIC_ID       => $this->payment->getPublicId(),
                Payment\Entity::AMOUNT          => $this->payment->getFormattedAmount(),
                // Payment\Entity::TIMESTAMP       => $this->payment->getUpdatedAt(),
                Payment\Entity::CAPTURED_AT     => $this->formatTime($this->payment->getAttribute('captured_at')),

                // note that payment method is unavailable to the merchant
                Payment\Entity::METHOD    => $this->payment->getMethodWithDetail(),
            ];

            if ($this->payment->hasCard() === true)
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

            if ($this->payment->isFailed() === true)
            {
                $data['payment']['error_description'] = $this->payment->getErrorDescription();
            }
        }

        if ($this->invoice !== null)
        {
            $data['invoice']  = [
                Invoice\Entity::ID            => $this->invoice->getId(),
                Invoice\Entity::PUBLIC_ID     => $this->invoice->getPublicId(),
                Invoice\Entity::BILLING_START => $this->formatTime($this->invoice->getBillingStart()),
                Invoice\Entity::BILLING_END   => $this->formatTime($this->invoice->getBillingEnd()),
            ];
        }

        return $data;
    }

    protected function setOptions(string $event)
    {
        $defaultOptions = Event::DEFAULT_OPTIONS[$event];

        $this->template['options'] = array_merge($defaultOptions, $this->options);
    }

    protected function formatTime($time)
    {
        if ($time === null)
        {
            return;
        }

        return Carbon::createFromTimestamp($time, Timezone::IST)->format('j M Y');
    }

    protected function isTimestamp($key, $value)
    {
        if (in_array($key, self::TIMESTAMP_FIELDS, true) === true)
        {
            return true;
        }

        return parent::isTimestamp($key, $value);
    }

    /**
     * Decides if we send a mail to customer for a payment event
     *
     *
     * @return bool
     */
    protected function isCustomerMailEnabledForMerchant()
    {
        // If the merchant has disabled customer emails
        // And this was a customer receipt email don't send a mail
        if ($this->merchant->isReceiptEmailsEnabled() === false)
        {
            return false;
        }

        return $this->isEnabled();
    }

    protected function getMailableClass(string $event)
    {
        return 'RZP\\Mail\\Subscription\\' . studly_case($event);
    }
}
