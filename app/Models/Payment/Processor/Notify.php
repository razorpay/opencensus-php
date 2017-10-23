<?php

namespace RZP\Models\Payment\Processor;

use App;
use Carbon\Carbon;
use Mail;
use RZP\Constants\MailTags;
use RZP\Constants\Mode;
use RZP\Jobs\Invoice\Job as InvoiceJob;
use RZP\Jobs\DispatchRouter;
use RZP\Mail\Payment as PaymentMail;
use RZP\Models\Invoice;
use RZP\Models\Invoice\ViewDataSerializer;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;

class Notify
{
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
     * @var Payment\Entity
     */
    protected $payment;
    /**
     * @var Payment\Refund\Entity
     */
    protected $refund;
    protected $merchant;
    protected $mode;
    protected $trace;
    protected $template;
    protected $invoice = null;
    protected $slackEnabled = true;

    /**
     * Creates a new Notify instance
     *
     * @param Payment\Entity $payment The payment associated with the Notify
     */
    function __construct(Payment\Entity $payment)
    {
        $this->app = App::getFacadeRoot();

        $this->payment = $payment;

        $this->merchant = $this->payment->merchant;

        if ($this->payment->hasInvoice())
        {
            $this->invoice = $this->payment->invoice;
        }

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->refreshTemplate();
    }

    /**
     * Regenerates the entire template
     */
    protected function refreshTemplate()
    {
        $this->template = $this->templateData();
    }

    /**
     * Allows the notifier to be used for a refund as well
     *
     * @param Payment\Refund\Entity $refund Refund entity
     */
    public function addRefund(Payment\Refund\Entity $refund)
    {
        $this->refund = $refund;
        $this->refreshTemplate();
    }

    /**
     * Sends out mails for a particular event trigger
     *
     * @param string $event
     */
    protected function notifyViaMail(string $event)
    {
        $mailableClass = $this->getMailableClass($event);

        if (Payment\Event::isCustomerEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template);

            if ($this->invoice !== null)
            {
                if (in_array($event, Payment\Event::INVOICE_EVENTS, true) === true)
                {
                    $invoiceData = (new ViewDataSerializer($this->invoice))->get();

                    $mailable->setInvoiceDetails($invoiceData);
                }
            }

            if ($this->isCustomerMailEnabled($mailable) === true)
            {
                Mail::queue($mailable);
            }
        }

        if (Payment\Event::isMerchantEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template, true);

            if ($this->invoice !== null)
            {
                if (in_array($event, Payment\Event::INVOICE_EVENTS, true) === true)
                {
                    $invoiceData = (new ViewDataSerializer($this->invoice))->get();

                    $mailable->setInvoiceDetails($invoiceData);
                }
            }

            if ($this->isMerchantMailEnabled($mailable) === true)
            {
                Mail::queue($mailable);
            }
        }
    }

    protected function notifyViaSlack($event)
    {
        // We don't send out a notification on capture
        $slackMessages = [
            Payment\Event::FAILED_TO_AUTHORIZED       => 'Failed Payment Authorized',
            Payment\Event::AUTHORIZED                 => 'Payment Authorized',
            Payment\Event::INVOICE_PAYMENT_AUTHORIZED => 'Payment Authorized',
            Payment\Event::REFUNDED                   => 'Payment Refunded'
        ];

        $settings = [
            'channel' => $this->getSlackChannel(),
            'color'   => $this->getSlackPostColor(),
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
     * Returns color to use for slack posts
     *
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

        $riskRating = $this->template['payment']['risk'];

        $amount = $this->template['payment']['raw_amount'];

        // The priority order is important here
        if ($riskRating === self::MAX_HIGH_RISK_RATING)
        {
            return $config->get('slack.channels.highrisk');
        }
        else if ($riskRating === self::HIGH_RISK_RATING)
        {
            return $config->get('slack.channels.high_4');
        }
        else if (($riskRating === self::MIN_HIGH_RISK_RATING) and
                ($amount <= 1000))
        {
            return $config->get('slack.channels.lt_10');
        }
        else if ($riskRating >= self::MIN_HIGH_RISK_RATING)
        {
            return $config->get('slack.channels.risky');
        }
        else if ($this->payment->amount >= self::MIN_RISK_AMOUNT)
        {
            return $config->get('slack.channels.high');
        }

        // We did not find an appropriate channel, so mark
        // slack as disabled
        $this->slackEnabled = false;
    }

    /**
     * This is the primary public method for this class
     *
     * @param  string $event Trigger notifications for this event
     */
    public function trigger(string $event)
    {
        /**
         * This is wrapped in a try-catch block as this is not
         * critical path for the payment operation
         * We should continue running even if this raises critical error.
         */
        try
        {
            // If it's invoice payment authorization:
            // - dispatch a queue job which updates the invoice pdf,
            // - if invoice's email_notify is set to '0', just return.

            if ($event === Payment\Event::INVOICE_PAYMENT_AUTHORIZED)
            {
                $job = new InvoiceJob(
                            $this->mode,
                            InvoiceJob::AUTHORIZED,
                            $this->invoice->getId());

                (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);
            }

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

    protected function getMerchantForSlack()
    {
        $website = $this->template['merchant']['website'];
        $text    = $this->template['merchant']['billing_label'];

        $dashboardLink = $this->merchant->getDashboardEntityLink();
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
     *
     * @param  string $id Payment Id
     * @return string
     */
    protected function getPaymentLinkForSlack($id)
    {
        return "<https://dashboard.razorpay.com/admin#/app/payments/live/$id|pay_$id>";
    }

    /**
     * Returns slack formatted version of a refund id
     *
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
     *
     * @param  string $event Trigger event
     * @return array Flat array of data to be sent to Slack
     */
    protected function getSlackData($event)
    {
        switch ($event)
        {
            // Both cases are the same
            case Payment\Event::FAILED_TO_AUTHORIZED:
            case Payment\Event::AUTHORIZED:
            case Payment\Event::INVOICE_PAYMENT_AUTHORIZED:
                $data = $this->template['payment'];
                $data['id'] = $this->getPaymentLinkForSlack($data['id']);
                unset($data['method'], $data['public_id']);
                break;

            // Capture is unused right now
            case Payment\Event::CAPTURED:
            case Payment\Event::INVOICE_PAYMENT_CAPTURED:
                $data = $this->template['payment'];
                break;

            case Payment\Event::REFUNDED:
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

        // This is for both payments and refund
        if (isset($data['timestamp']))
        {
            unset($data['timestamp']);
        }

        if ((isset($data['risk']) === true) and
            ($data['risk'] === self::MAX_HIGH_RISK_RATING))
        {
            unset($data['risk']);
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
     *
     * @return array Template data
     */
    protected function templateData()
    {
        $data  = [
            'customer'  => [
                'email' => $this->payment->getEmail(),
                'phone' => $this->payment->getContact()
            ],
            'merchant'  => [
                'billing_label' => $this->merchant->getBillingLabel(),
                'website'       => $this->merchant->getWebsite(),
                // This is the reporting email address for the merchant
                'email'         => $this->merchant->getTransactionReportEmail(),
                'id'            => $this->merchant->getId(),
            ],
            'payment'   => [
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
                'risk'      => $this->merchant->getRiskRating()
            ],
        ];

        if ($this->payment->hasCard())
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
                'id'         => $this->refund->getId(),
                'amount'     => $this->refund->getFormattedAmount(),
                'timestamp'  => $this->refund->getCreatedAt(),
                'payment_id' => $this->refund->payment->getId(),
                'public_id'  => $this->refund->getPublicId(),
            ];
        }

        if ($this->payment->isFailed() === true)
        {
            $data['payment']['error_description'] = $this->payment->getErrorDescription();
        }

        return $data;
    }

    /**
     * Returns whether a key value pair is a timestamp
     * Called after flattening the array
     *
     * @param  string $key   key name
     * @param  mixed  $value value
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
     *
     * @param  array $data data
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
                $data[$key] = Carbon::createFromTimestamp($value, Timezone::IST)->format('j M Y h:i a');
            }
        }

        return $data;
    }

    /**
     * Flattens an array recursively
     * Concatenating keys using periods
     *
     * @param  array  $array  input array
     * @param  string $prefix prefix used to concat keys
     * @return array flat version of input array
     */
    protected function flatten(array $array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result += $this->flatten($value, $prefix . $key . '.');
            }
            else
            {
                $result[$prefix . $key] = $value;
            }
        }

        return $this->cleanData($result);
    }

    /**
     * Decides if we send a mail to customer for a payment event
     *
     * @param PaymentMail\Base $mailable Mailable object being sent
     *
     * @return bool
     */
    protected function isCustomerMailEnabled(PaymentMail\Base $mailable)
    {
        // If it is a customer mail and the customer's email address
        // is null or void@razorpay.com don't send email
        if ($this->payment->isCustomerMailAbsent() === true)
        {
            return false;
        }

        // If the merchant has disabled customer emails
        // And this was a customer receipt email don't send a mail
        if (($this->merchant->isReceiptEmailsEnabled() === false) and
            ($mailable->isCustomerReceiptEmail() === true))
        {
            return false;
        }

        return $this->isEnabled();
    }

    protected function isMerchantMailEnabled(PaymentMail\Base $mailable)
    {
        $merchantTransactionReportEmail = $this->merchant->getTransactionReportEmail();

        return (($this->isEnabled() === true) and
                (empty($merchantTransactionReportEmail) === false) and
                ($this->merchant->isLinkedAccount() === false));
    }

    /**
     * Whether to send notifications or not
     * depending on the environment and the mode
     *
     * @return boolean
     */
    protected function isEnabled()
    {
        //
        // We only send notifications if Mode is not TEST
        // or if the env=dev or env=testing
        // so env=dev or env=testing overrides TEST mode
        //
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
     *
     * @return boolean
     */
    protected function isSlackEnabled()
    {
        return (($this->isEnabled() === true) and
                ($this->slackEnabled === true));
    }

    protected function getMailableClass(string $event)
    {
        // Invoice payment mails are in \RZP\Mail\Invoice\Payment namespace
        // hence we return that namespace
        if (Payment\Event::isInvoiceEvent($event) === true)
        {
            $event = Payment\Event::getInvoiceEventName($event);

            return 'RZP\\Mail\\Invoice\\Payment\\' . $event;
        }
        return 'RZP\\Mail\\Payment\\' . studly_case($event);
    }
}
