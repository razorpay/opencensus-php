<?php

namespace RZP\Mail\Admin\Account;

use RZP\Constants\MailTags;

class Create extends Base
{
    protected $url;

    public function __construct($admin, array $input, string $url)
    {
        parent::__construct($admin, $input);

        $this->header = MailTags::ADMIN_CREATE;

        $this->url = $url;
    }

    public function canSend()
    {
        return ($this->org->getAuthType() !== 'password');
    }

    protected function addSubject()
    {
        $subject = 'Your admin account details for ' . $this->org->getDisplayName() . ' dashboard';

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.user');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.admin.user_text');

        return $this;
    }

    protected function addMailData()
    {
            $data = [
            'user' => $this->admin->getEmail(),
            // todo: Hack for now. Remove it
            'password' => $this->input['password'],
            'org' => $this->org->getDisplayName(),
            'url' => $this->url,
            ];

        $this->with($data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::ADMIN_CREATE);
        });

        return $this;
    }
}
