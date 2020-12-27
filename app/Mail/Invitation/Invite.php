<?php

namespace RZP\Mail\Invitation;

use RZP\Mail\Base\Common;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Invite extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $email = $this->data['email'];

        $this->to($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Invitation to join a team | Razorpay';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $emailParams = [
            'sender_name'   => $this->data['sender_name'],
            'merchant_name' => $this->data['name'],
            'token'         => $this->data['token'],
            'product'       => $this->data['product'],
        ];

        if (isset($this->data['user_id']))
        {
            $emailParams['data'] = $this->getUserOrgData($this->data['user_id']);
        }

        $this->with(array_merge($emailParams, $this->data));

        return $this;
    }

    protected function addHtmlView()
    {
        $view = $this->data['user_id'] ? 'emails.invitation.existing' : 'emails.invitation.new';

        $this->view($view);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_INVITATION_MAIL);
        });

        return $this;
    }
}
