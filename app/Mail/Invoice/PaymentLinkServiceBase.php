<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Merchant;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Preferences;
use RZP\Trace\TraceCode;

class PaymentLinkServiceBase extends Mailable
{

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = $this->getSenderEmail();

        $fromHeader = $this->data[E::MERCHANT][Merchant\Entity::NAME];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function getSenderEmail(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);
    }

    protected function getSenderHeader(): string
    {
        $orgCode = $this->data['org']['custom_code'] ?? '';

        return Constants::getSenderNameForOrg($orgCode, Constants::NOREPLY);
    }

    protected function addRecipients()
    {
        $customerEmail = $this->data['to'];

        $this->to($customerEmail);

        return $this;
    }

    protected function addSubject()
    {
        $subject = $this->data['subject'];

        $this->subject($subject);

        return $this;
    }

    protected function addReplyTo()
    {

        $email = $this->getSenderEmail();

        $header = $this->getSenderHeader();

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->data['view']);
        $merchantId = $this->data['merchant']['id'];
        switch ($merchantId) {
            case Preferences::MID_BAGIC_2:
            case Preferences::MID_BAGIC:
                $this->view('emails.invoice.customer.custom.bagic_email');
                break;
        }

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        $app = \App::getFacadeRoot();

        $merchantId = $this->data['merchant']['id'];
        switch ($merchantId) {
            case Preferences::MID_BAGIC_2:
            case Preferences::MID_BAGIC:
                return false;
        }

        $isStorkEmailVIAEnabled = $this->isSendingPaymentLinkMailsSupported($merchantId, $this->view);

        $traceData = [
            'merchant_id' => $merchantId,
            'should_create_via_stork' => $isStorkEmailVIAEnabled,
            'template_view' => $this->view,
        ];

        $app['trace']->info(TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_VIA_SPLITZ , $traceData);

        return ($isStorkEmailVIAEnabled);
    }

    protected function getParamsForStork(): array
    {
        if($this->view == "emails.invoice.customer.notification")
        {
            return [
                'template_name' => $this->view,
                'template_namespace' => 'payments_payment_links',
                'org_id' => $this->data['org']['id'],
                'params' => $this->data
            ];
        }
        else if($this->view == "emails.invoice.customer.notification_pl_v2")
        {
            if($this->data['view_extend_address'] == 'emails.invoice.notification')
            {
                $this->view('emails.invoice.notification');
            }
            else if($this->data['view_extend_address'] == 'emails.invoice.customer.notification_qr_pl_v2')
            {
                $this->view('emails.invoice.customer.notification_qr_pl_v2');
            }
        }
        return [
            'template_name' => $this->view,
            'template_namespace' => 'payments_payment_links',
            'org_id' => $this->data['org']['id'],
            'params' => $this->data
        ];
    }

    public function isSendingPaymentLinkMailsSupported($merchantId,$view) : bool {
        $traceCode = TraceCode::PAYMENT_LINK_EMAIL_ATTEMPT_STORK;

        $experimentId = 'app.send_payment_link_emails_via_stork';

        try {
            $app = \App::getFacadeRoot();
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $app['config']->get($experimentId),
                'request_data'  => json_encode(['merchant_id' => $merchantId , 'template_name' => $view])
            ];
            $response = $app['splitzService']->evaluateRequest($properties);
            $variant = $response['response']['variant']['name'] ?? '';

            $app['trace']->info($traceCode, [
                'splitzUserResult' => $response,
            ]);

            return  $variant == "enable";

        } catch (\Exception $e) {
            $app['trace']->traceException($e, null, $traceCode);
        }
        return false;
    }
}
