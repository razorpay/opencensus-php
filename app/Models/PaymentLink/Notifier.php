<?php

namespace RZP\Models\PaymentLink;

use Mail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Mail\PaymentLink\Notify as NotifyMail;

class Notifier extends Base\Core
{
    protected $mode;
    protected $raven;

    public function __construct()
    {
        parent::__construct();

        $this->mode  = $this->app['rzp.mode'];

        $this->raven = $this->app['raven'];
    }

    /**
     * Sends email and sms notifications to a customer with a payment link
     * @param Entity $paymentLink
     * @param array $input
     */
    public function notifyByEmailAndSms(Entity $paymentLink, array $input)
    {
        $emails = $input['emails'] ?? [];

        $contacts = $input['contacts'] ?? [];

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
     * @param Entity $paymentLink
     * @param string $email
     */
    protected function notifyByEmail(Entity $paymentLink, string $email)
    {
        $this->trace->info(
            TraceCode::PAYMENT_LINK_EMAIL_REQUEST,
            [
                'id'    => $paymentLink->getId(),
                'email' => $email,
            ]);

        $data = $paymentLink->toArrayPublic();

        $notifyMail = new NotifyMail($data, $email);

        try
        {
            Mail::send($notifyMail);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::INFO,
                TraceCode::PAYMENT_LINK_NOTIFY_BY_EMAIL_FAILURE,
                [
                    'id'    => $paymentLink->getId(),
                    'email' => $email,
                ]);
        }
    }

    /**
     * Sends sms notification to a customer with a payment link
     * @param Entity $paymentLink
     * @param string $contact
     *
     * @return bool
     */
    protected function notifyBySms(Entity $paymentLink, string $contact): bool
    {
        $request = $this->getRavenSendPaymentLinkRequestInput($paymentLink, $contact);

        try
        {
            $response = $this->raven->sendSms($request, false);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                null,
                [
                    'contact' => $contact,
                    'id'      => $paymentLink->getId(),
                ]);

            return false;
        }

        return true;
    }

    /**
     * Prepares raven request input
     * @param Entity $paymentLink
     * @param string $contact
     *
     * @return array
     */
    protected function getRavenSendPaymentLinkRequestInput(Entity $paymentLink, string $contact): array
    {
        $merchant = $paymentLink->merchant;

        $request = [
            'receiver' => $contact,
            'source'   => "api.{$this->mode}.payment_link",
            'template' => 'sms.payment_link',
            'params'   => [
                'merchant_name'    => $merchant->getBillingLabel(),
                'payment_link_url' => $paymentLink->getShortUrl(),
                'amount'           => $paymentLink->getAmount() / 100,
            ]
        ];

        $this->trace->info(
            TraceCode::PAYMENT_LINK_RAVEN_REQUEST,
            [
                'id'      => $paymentLink->getId(),
                'request' => $request,
            ]);

        return $request;
    }
}
