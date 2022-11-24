<?php

namespace RZP\Mail\Common;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Notifications\Dashboard\Events;
use RZP\Notifications\Dashboard\Constants as DashboardConstants;

class GenericEmail extends Mailable
{
    protected $data;

    protected $org;

    protected $template;

    protected $recipient;

    protected $sender;

    public function __construct( $data, array $recipient, array $sender, array $org, string $template, string $subject)
    {
        parent::__construct();

        $this->data = $data;
        $this->org = $org;
        $this->subject = $subject;
        $this->template = $template;
        $this->recipient = $recipient;
        $this->sender = $sender;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    protected function addRecipients()
    {
        $this->to($this->recipient['email'],$this->recipient['name']);
        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->template);

        return $this;
    }

    protected function addSender()
    {
        if(isset($sender))
        {
            $this->from($this->sender['email'],$this->sender['name']);
        }
        else
        {
            $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
            $senderName = Constants::HEADERS[Constants::NOREPLY];

            $this->from($senderEmail, $senderName);
        }

        return $this;
    }

    protected function addBcc()
    {
//        if($this->org[Org\Entity::ID] === Org\Entity::RAZORPAY_ORG_ID)
//        {
//            $this->cc(Constants::MAIL_ADDRESSES[Constants::RAZORPAY_HELP_DESK]);
//        }

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();
        });

        return $this;
    }
}
