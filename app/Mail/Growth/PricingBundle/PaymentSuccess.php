<?php

namespace RZP\Mail\Growth\PricingBundle;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class PaymentSuccess extends Mailable
{
    protected $data;

    const SUBJECT = 'Congratulations, your payment was successful! 🚀';

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY], Constants::HEADERS[Constants::NOREPLY]);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['merchant']['email']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => 'platform_growth',
            'org_id'             => $this->data['merchant']['org_id'],
            'template_name'      => 'growth.pricing_bundle.payment_success',
            'params'  => $this->data,
        ];
    }

    protected function addHtmlView()
    {
        $this->view('growth.pricing_bundle.payment_success');

        return $this;
    }
}
