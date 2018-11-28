<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Models\Invoice;
use RZP\Constants\Timezone;
use RZP\Models\Plan\Subscription;
use RZP\Models\Payment\Processor;

class Notify extends Processor\Notify
{
    protected $app;
    /**
     * @var Payment\Entity
     */
    protected $payment;
    /**
     * @var Merchant\Entity
     */
    protected $merchant;
    /**
     * @var string
     */
    protected $mode;
    protected $trace;
    protected $template;
    /**
     * @var null|Invoice\Entity
     */
    protected $invoice = null;
    /**
     * @var null|Entity
     */
    protected $subscription = null;
    /**
     * @var array
     */
    protected $options = [];

    public function __construct(Subscription\Entity $subscription, array $options = [])
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
     * Sends out mails for a particular event trigger
     *
     * @param string $event
     */
    protected function notifyViaMail(string $event)
    {
        $mailableClass = $this->getMailableClass($event);

        $this->setOptions($event);

        if (Event::isCustomerEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template);

            if (($this->isCustomerMailEnabledForMerchant() === true) and
                ($this->isCustomerMailAvailable() === true))
            {
                Mail::queue($mailable);
            }
        }

        if (Event::isMerchantEvent($event) === true)
        {
            $mailable = new $mailableClass($this->template, true);

            if ($this->isMerchantMailEnabledForMerchant() === true)
            {
                Mail::queue($mailable);
            }
        }
    }

    protected function isCustomerMailAvailable()
    {
        //
        // No point triggering a notification if we don't even have an email
        //
        if ($this->template['customer']['email'] === null)
        {
            return false;
        }

        return true;
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
                Subscription\Entity::ID            => $this->subscription->getPublicId(),
                Subscription\Entity::STATUS        => $this->subscription->getStatus(),
                Subscription\Entity::TYPE          => $this->subscription->getType(),
                Subscription\Entity::CHARGE_AT     => $this->formatTime($this->subscription->getChargeAt()),
                Subscription\Entity::CANCEL_AT     => $this->formatTime($this->subscription->getCancelAt()),
                Subscription\Entity::AUTH_ATTEMPTS => Charge::MAX_AUTH_ATTEMPTS - $this->subscription->getAuthAttempts(),
                Subscription\Entity::HOSTED_URL    => $this->getRetryUrl(),
            ],
            'plan_item' => [
                Item\Entity::NAME              => $this->subscription->plan->item->getName(),
                Item\Entity::DESCRIPTION       => $this->subscription->plan->item->getDescription(),
                Item\Entity::AMOUNT            => $this->subscription->plan->item->getFormattedAmount(),
            ],
            'merchant' => [
                Merchant\Entity::BILLING_LABEL => $this->merchant->getBillingLabel(),
                Merchant\Entity::WEBSITE       => $this->merchant->getWebsite(),
                Merchant\Entity::EMAIL         => $this->merchant->getTransactionReportEmail(),
                Merchant\Entity::ID            => $this->merchant->getId(),
            ],
            'customer' => [
                'email' => $this->subscription->customer->getEmail(),
                'phone' => $this->subscription->customer->getContact()
            ],
            'mode' => $this->mode,
        ];

        if (($this->subscription->token !== null) and
            ($this->subscription->token->card !== null))
        {
            $this->setCardData($data);
        }

        if ($this->payment !== null)
        {
            $this->setPaymentData($data);
        }

        if ($this->invoice !== null)
        {
            $this->setInvoiceData($data);
        }

        return $data;
    }

    protected function setCardData(array & $data)
    {
        $card = $this->subscription->token->card;

        $expiryMonth = str_pad($card->getExpiryMonth(), 2, '0', STR_PAD_LEFT);

        $data['card'] = [
            'number'    => '**** **** **** ' . $card->getLast4(),
            'expiry'    => $expiryMonth . '/' . $card->getExpiryYear(),
            'network'   => $card->getNetworkCode(),
            'color'     => $card->getNetworkColorCode(),
        ];
    }

    protected function setInvoiceData(array & $data)
    {
        $data['invoice']  = [
            Invoice\Entity::ID            => $this->invoice->getId(),
            Invoice\Entity::PUBLIC_ID     => $this->invoice->getPublicId(),
            Invoice\Entity::BILLING_START => $this->formatTime($this->invoice->getBillingStart()),
            Invoice\Entity::BILLING_END   => $this->formatTime($this->invoice->getBillingEnd()),
        ];
    }

    protected function setPaymentData(array & $data)
    {
        $capturedAt = $this->formatTime($this->payment->getAttribute('captured_at'), 'j M Y H:i:s');

        $data['payment']  = [
            Payment\Entity::ID              => $this->payment->getId(),
            Payment\Entity::PUBLIC_ID       => $this->payment->getPublicId(),
            Payment\Entity::AMOUNT          => $this->payment->getFormattedAmount(),
            Payment\Entity::CAPTURED_AT     => $capturedAt,
        ];

        if ($this->payment->isFailed() === true)
        {
            $data['payment']['error_description'] = $this->payment->getErrorDescription();
        }
    }

    protected function getRetryUrl()
    {
        $baseUrl = $this->app['config']->get('app.url') . '/v1';

        $mode = 't';

        if ($this->mode === Mode::LIVE)
        {
            $mode = 'l';
        }

        $url = $baseUrl . '/' . $mode . '/subscriptions/' . $this->subscription->getPublicId();

        return $url;
    }

    protected function setOptions(string $event)
    {
        $defaultOptions = Event::DEFAULT_OPTIONS[$event];

        $this->template['options'] = array_merge($defaultOptions, $this->options);
    }

    protected function formatTime($time, $format = 'j M Y')
    {
        if ($time === null)
        {
            return null;
        }

        return Carbon::createFromTimestamp($time, Timezone::IST)->format($format);
    }

    protected function isTimestamp($key, $value)
    {
        if (substr($key, -3) !== '_at')
        {
            return false;
        }

        return ((is_numeric($value)) and
            ($value <= PHP_INT_MAX) and
            ($value >= -PHP_INT_MAX));
    }

    /**
     * Decides if we send a mail to customer for a payment event
     *
     * @return bool
     */
    protected function isCustomerMailEnabledForMerchant()
    {
        //
        // If the merchant has disabled customer emails and
        // this was a customer receipt email don't send a mail
        //
        if ($this->merchant->isReceiptEmailsEnabled() === false)
        {
            return false;
        }

        if ($this->subscription->getCustomerNotify() === false)
        {
            return false;
        }

        if ($this->subscription->isGlobal() === false)
        {
            return false;
        }

        return true;
    }

    protected function isMerchantMailEnabledForMerchant()
    {
        $merchantTransactionReportEmail = $this->merchant->getTransactionReportEmail();

        return ((empty($merchantTransactionReportEmail) === false) and
                ($this->merchant->isLinkedAccount() === false));
    }

    protected function getMailableClass(string $event)
    {
        return 'RZP\\Mail\\Subscription\\' . studly_case($event);
    }
}
