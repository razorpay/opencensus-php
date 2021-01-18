<?php

namespace RZP\Mail\Admin;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;


class FreshchatReport extends Mailable
{

    // todo: temporarily adding individual emails. will be updated to googlegroup email id shortly
    // https://razorpay.slack.com/archives/C01CS8YBEQZ/p1610976070042000?thread_ts=1610964451.039000&cid=C01CS8YBEQZ
    const RECIPIENTS = [
        'saimadhav.heblikar@razorpay.com',
        'madhan.g@razorpay.com',
        'tejas.v@razorpay.com',
        'karishma.bhowmik@razorpay.com',
    ];
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data = [])
    {
        parent::__construct();

        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function addHtmlView()
    {
        return $this->view('emails.admin.freshchat_report');
    }

    protected function addRecipients()
    {
        $this->to(self::RECIPIENTS);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Freshchat chat dump from ' . $this->data['metadata']['start'] . ' to ' . $this->data['metadata']['end'] . ' (UTC)';

        $this->subject($subject);

        return $this;
    }

    public function addAttachments()
    {
        foreach ($this->data['attachments'] as $attachment)
        {
            $this->attach($attachment['file_path'], [
                'as' => $attachment['name'],
                'mime-type' => 'application/zip',
            ]);
        }

        return $this;
    }

}
