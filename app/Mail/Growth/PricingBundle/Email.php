<?php


namespace RZP\Mail\Growth\PricingBundle;


use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Email extends Mailable
{
    protected $data;

    protected $input;

    public function __construct(array $data, array $input)
    {
        parent::__construct();

        $this->data = $data;

        $this->input = $input;
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
        $this->subject($this->input['subject']);

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
            'template_name'      => $this->input['template_name'],
            'params'  => $this->data,
        ];
    }

    protected function addHtmlView()
    {
        $this->view($this->input['template_name']);

        return $this;
    }
}
