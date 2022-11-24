<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Models\Admin\Org;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Notifications\Dashboard\Events;
use RZP\Notifications\Dashboard\Constants as DashboardConstants;

class MerchantDashboardEmail extends Mailable
{
    protected $org;

    protected $recipientEmails;

    protected $merchant;

    protected $event;

    protected $merchantDetail;

    public function __construct(array $data, array $merchant, array $merchantDetail, array $org, array $recipientEmails, string $event)
    {
        parent::__construct();

        $this->data = $data;

        $this->org = $org;

        $this->event = $event;

        $this->merchant = $merchant;

        $this->merchantDetail = $merchantDetail;

        $this->recipientEmails = $recipientEmails;
    }

    protected function addRecipients()
    {
        $this->to($this->recipientEmails);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(Events::EMAIL_TEMPLATES[$this->event]);

        return $this;
    }

    protected function addSender()
    {
        [$senderEmail, $senderName] = $this->getSenderEmailAndName();

        $this->from($senderEmail, $senderName);

        $this->to($this->recipientEmails, $this->merchant['name']);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $mailData = array_merge($this->merchant, $this->merchantDetail, $this->org, $this->data);

        $this->with($mailData);

        return $this;
    }

    protected function addSubject()
    {
        $subject = sprintf(Events::EMAIL_SUBJECTS[$this->event], $this->merchantDetail['business_name'], $this->merchant['id'] );

        if(empty($this->data[DashboardConstants::MESSAGE_SUBJECT]) === false)
        {
            $subject = $this->data[DashboardConstants::MESSAGE_SUBJECT];
        }

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, Events::EMAIL_TAGS[$this->event]);
        });

        return $this;
    }

    protected function getSenderEmailAndName()
    {
        if($this->org[Org\Entity::ID] !== Org\Entity::RAZORPAY_ORG_ID)
        {
            $senderEmail = $this->org[Org\Entity::FROM_EMAIL];

            $senderName = $this->org[Org\Entity::DISPLAY_NAME];
        }
        else
        {
            $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

            $senderName = Constants::HEADERS[Constants::NOREPLY];
        }

        return [$senderEmail, $senderName];
    }
}
