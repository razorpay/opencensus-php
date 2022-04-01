<?php

namespace RZP\Models\PaymentLink;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Mail\PaymentLink\PaymentRequest;

class Notifier extends Base\Core
{
    const SMS_WITH_AMOUNT_TEMPLATE    = 'sms.payment_page.with_amount';
    const SMS_WITHOUT_AMOUNT_TEMPLATE = 'sms.payment_page.without_amount';

    /**
     * @var \RZP\Services\Raven
     */
    protected $raven;

    public function __construct()
    {
        parent::__construct();

        $this->raven = $this->app['raven'];
    }

    /**
     * Sends email and sms notifications to a customer with a payment link
     *
     * @param Entity $paymentLink
     * @param array  $input
     */
    public function notifyByEmailAndSms(Entity $paymentLink, array $input)
    {
        $emails   = $input[Entity::EMAILS] ?? [];
        $contacts = $input[Entity::CONTACTS] ?? [];

        foreach ($emails as $email)
        {
            $this->notifyByEmail($paymentLink, $email);
        }

        foreach ($contacts as $contact)
        {
            $this->notifyBySms($paymentLink, $contact);
        }
    }

    /**
     * Sends email notification to a customer with a payment link
     *
     * @param Entity $paymentLink
     * @param string $email
     */
    protected function notifyByEmail(Entity $paymentLink, string $email)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_EMAIL_REQUEST,
            [
                Entity::ID    => $paymentLink->getId(),
                Entity::EMAIL => $email,
            ]);

        $this->trace->count(Metric::PAYMENT_PAGE_EMAIL_NOTIFY_TOTAL, $paymentLink->getMetricDimensions());

        $mailPayload = (new ViewSerializer($paymentLink))->serializeForInternal();

        $mailable = new PaymentRequest($mailPayload, $email);

        try
        {
            Mail::send($mailable);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYMENT_LINK_NOTIFY_BY_EMAIL_FAILURE,
                [
                    Entity::ID    => $paymentLink->getId(),
                    Entity::EMAIL => $email,
                ]);
        }
    }

    /**
     * Sends sms notification to a customer with a payment link
     *
     * @param  Entity $paymentLink
     * @param  string $contact
     */
    protected function notifyBySms(Entity $paymentLink, string $contact)
    {
        $this->trace->count(Metric::PAYMENT_PAGE_SMS_NOTIFY_TOTAL, $paymentLink->getMetricDimensions());

        $request = $this->getRavenSendPaymentLinkRequestInput($paymentLink, $contact);

        try
        {
            $this->raven->sendSms($request, false);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                null,
                [
                    Entity::ID      => $paymentLink->getId(),
                    Entity::CONTACT => $contact,
                ]);
        }
    }

    /**
     * Prepares raven request input
     *
     * @param  Entity $paymentLink
     * @param  string $contact
     *
     * @return array
     */
    protected function getRavenSendPaymentLinkRequestInput(Entity $paymentLink, string $contact): array
    {
        $merchant = $paymentLink->merchant;

        $amount = $paymentLink->getAmountToSendSmsOrEmail();

        $template = $this->getTemplateForSMS($amount);

        $payload = [
            'receiver' => $contact,
            'source'   => "api.{$this->mode}.payment_link",
            // The template for invoice & payment_link is same, we are continuing to use the same for now
            'template' => $template,
            'params'   => [
                'merchant_name' => $merchant->getBillingLabel(),
                'amount'        => amount_format_IN($amount),
                'invoice_link'  => $paymentLink->getShortUrl(),
                'currency'      => $paymentLink->getCurrency(),
            ],
        ];

        $orgId = $merchant->getMerchantOrgId();

        // appending orgId in stork context to be used on stork to select org specific sms gateway.
        if (empty($orgId) === false)
        {
            $payload['stork']['context']['org_id'] = $orgId;
        }

        return $payload;
    }

    protected function getTemplateForSMS($amount = null)
    {
        return $amount === null ? self::SMS_WITHOUT_AMOUNT_TEMPLATE : self::SMS_WITH_AMOUNT_TEMPLATE;
    }
}
