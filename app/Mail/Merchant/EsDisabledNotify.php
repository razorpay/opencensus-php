<?php

namespace RZP\Mail\Merchant;

use RZP\Mail\Base;
use RZP\Mail\Base\Constants;

class EsDisabledNotify extends Base\Mailable
{

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addHtmlView()
    {
        return $this;
    }

    protected function addTextView()
    {
        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::CROSS_BORDER];

        $header = "Cross Border";

        $this->from($email, $header);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email'], $this->data['merchant_name']);
        return $this;
    }

    protected function addSubject()
    {
        $subjectLine = "[Important] On-demand Settlements is discontinued for your account";

        $this->subject($subjectLine);

        return $this;
    }

    protected function getParamsForStork(): array
    {
        $data = $this->data;

        $storkParams = [
            'template_namespace' => 'payments_crossborder',
            'template_name' => 'cross_border_es_disable_mail',
            'org_id' => $data['org_id'],
            'params' => [
                'merchant_name' => $data['merchant_name'],
            ],
        ];
        return $storkParams;
    }
}
