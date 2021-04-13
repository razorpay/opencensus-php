<?php

namespace RZP\Mail\Reward;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class NewReward extends Mailable
{
    protected $data;

    protected $templateName;

    protected $merchantEmail;

    protected $customSubject;

    public function __construct(string $merchantEmail, string $subject, array $data)
    {
        parent::__construct();

        $this->data = $data;

        $this->customSubject = $subject;

        $this->merchantEmail = $merchantEmail;
    }

    protected function addRecipients()
    {
        $this->to($this->merchantEmail);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
            Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view("emails.reward.new_rewards");

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->customSubject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
                    'reward' => $this->data['reward'],
                    'subject' => $this->data['subject'],
                    'content' => $this->data['content'],
                    'merchant_name'=> $this->data['merchant_name']
                ];

        $this->with($data);

        return $this;
    }
}
