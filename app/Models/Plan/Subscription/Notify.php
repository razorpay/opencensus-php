<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Plan\Subscription;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Processor;

class Notify extends Processor\Notify
{
    protected $payment;
    protected $mode;
    protected $trace;
    protected $template;
    protected $invoice = null;
    protected $subscription = null;
    protected $slackEnabled = true;

    /**
     * Creates a new Notify instance
     *
     * @param Payment\Entity $payment The payment associated with the Notify
     */
    function __construct(Subscription\Entity $subscription, Payment\Entity $payment = null)
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->subscription = $subscription;

        if ($payment !== null)
        {
            $this->payment = $payment;

            if ($payment->hasInvoice() === true)
            {
                $this->invoice = $payment->invoice;
            }
        }

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

        if (Event::isCustomerEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template);

            if ($this->isCustomerMailEnabled($mailable) === true)
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
    protected function getSlackPostColor(string $event)
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

        switch ($event)
        {
            // Both cases are the same
            case Event::ACTIVATED:
            case Event::CHARGED:
                $payment = $this->template['payment'];
                $data['payment_id'] = $this->getPaymentLinkForSlack($payment['id']);
                break;
            case Event::PENDING:
            case Event::HALTED:
                $data['payment'] = $this->template['payment'];
                $data['payment']['id'] = $this->getPaymentLinkForSlack($data['payment']['id']);
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
            'merchant'  => [
                'billing_label' => $this->subscription->merchant->getBillingLabel(),
                'website'       => $this->subscription->merchant->getWebsite(),
                // This is the reporting email address for the merchant
                'email'         => $this->subscription->merchant->getTransactionReportEmail(),
                'id'            => $this->subscription->merchant->getId(),
            ],
        ];

        if ($this->payment !== null)
        {
            $data['customer'] = [
                'email' => $this->payment->getEmail(),
                'phone' => $this->payment->getContact()
            ];

            $data['payment']  = [
                'id'              => $this->payment->getId(),
                'public_id'       => $this->payment->getPublicId(),
                'amount'          => $this->payment->getFormattedAmount(),
                'raw_amount'      => $this->payment['base_amount'],
                'adjusted_amount' => $this->payment->getAdjustedAmountWrtCustFeeBearer(),
                'timestamp'       => $this->payment->getUpdatedAt(),
                'captured_at'     => $this->payment->getAttribute('captured_at'),

                // note that payment method is unavailable to the merchant
                'method'    => $this->payment->getMethodWithDetail(),
                'orderId'   => $this->payment->getOrderId(),
                'risk'      => $this->payment->merchant->getRiskRating()
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

        if ($this->refund !== null)
        {
            $data['refund'] = [
                'id'         => $this->refund->getId(),
                'amount'     => $this->refund->getFormattedAmount(),
                'timestamp'  => $this->refund->getCreatedAt(),
                'payment_id' => $this->refund->payment->getId(),
                'public_id'  => $this->refund->getPublicId(),
            ];
        }

        if ($this->subscription !== null)
        {
            // TODO: Add subscription things
        }

        return $data;
    }

    protected function getMailableClass(string $event)
    {
        return 'RZP\\Mail\\Subscription\\' . studly_case($event);
    }
}
