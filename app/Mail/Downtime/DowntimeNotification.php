<?php

namespace RZP\Mail\Downtime;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Downtime\Entity;
use RZP\Models\Payment\Processor\Netbanking;

class DowntimeNotification extends Mailable
{
    protected $data;

    protected $status;

    const CREATED   = 'CREATED';

    const RESOLVED  = 'RESOLVED';

    public function __construct(array $downtime, string $status)
    {
        parent::__construct();

        $this->data = $downtime;

        $this->status = $status;

        if (isset($this->data[Entity::ISSUER]) && $this->data['method'] != Method::WALLET)
        {
            $bank = Netbanking::getName($this->data[Entity::ISSUER]);

            if (isset($bank))
            {
                $this->data[Entity::ISSUER] = $bank;
            }
        }

        $dimension = null;

        switch($this->data['method'])
        {
            case Method::CARD :
                if (isset($this->data[Entity::NETWORK]))
                {
                    $dimension = $this->data[Entity::NETWORK];
                }
                elseif (isset($this->data[Entity::ISSUER]))
                {
                    $dimension = $this->data[Entity::ISSUER];
                }
                break;

            case Method::WALLET:
            case Method::NETBANKING :
                if (isset($this->data[Entity::ISSUER]))
                {
                    $dimension = $this->data[Entity::ISSUER];
                }
                break;

            case Method::UPI :
                if (isset($this->data[Entity::PSP]))
                {
                    $dimension = $this->data[Entity::PSP];

                }
                else if (isset($this->data[Entity::VPA_HANDLE]) && $this->data[Entity::VPA_HANDLE] != Entity::ALL)
                {
                    $dimension = $this->data[Entity::VPA_HANDLE];
                    $this->data[Entity::PSP] = 'GooglePay';
                }
                else
                {
                    $dimension = 'All UPI instruments';
                    $this->data[Entity::PSP] = 'NPCI';
                }
                break;
        }

        $this->data['dimension'] = $dimension;
    }

    protected function addRecipients()
    {
        $recipientEmail = null;

        switch ($this->data['method'])
        {
            case Method::CARD :
                $recipientEmail = Constants::MAIL_ADDRESSES[Constants::PG_NOTIFICATION_CARD];
                break;

            case Method::WALLET :
                $recipientEmail = Constants::MAIL_ADDRESSES[Constants::PG_NOTIFICATION_WALLET];
                break;

            case Method::UPI :
                $recipientEmail = Constants::MAIL_ADDRESSES[Constants::PG_NOTIFICATION_UPI];
                break;

            case Method::NETBANKING :
                $recipientEmail = Constants::MAIL_ADDRESSES[Constants::PG_NOTIFICATION_NETBANKING];
                break;
        }

        $this->to($recipientEmail);

        return $this;
    }

    protected function addSubject()
    {
        $method = $this->data[Entity::METHOD];

        $scheduled = $this->data[Entity::SCHEDULED];

        $subject = null;

        if ($this->status === self::RESOLVED)
        {
            $subject = '[Resolved] RE: ';
        }

        if ($scheduled === false)
        {
            if (isset($this->data['dimension']) && $method === Method::CARD)
            {
                $subject = $subject . "[IMP] We have noticed a disruption in " . $this->data['dimension'] . " Debit & Credit Card services" ;
            }
            elseif (isset($this->data['dimension']) && $method === Method::UPI)
            {
                $subject = $subject . "[IMP] We have noticed a disruption in " . $this->data['dimension'] . " UPI services" ;
            }
            elseif (isset($this->data['dimension']) && $method === Method::NETBANKING)
            {
                $subject = $subject . "[IMP] We have noticed a disruption in " . $this->data['dimension'] . " Net Banking services" ;
            }
        }
        else
        {
            $subject = $subject . ' ' . $this->data['dimension'] . ' has announced scheduled downtime';
        }

        $this->data['subject'] = $subject;

        $this->subject($subject);

        return $this;
    }

    protected function addSender()
    {
        $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $senderName = Constants::HEADERS[Constants::NOREPLY];

        $this->from($senderEmail, $senderName);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $this->replyTo($email);

        return $this;
    }

    protected function addMailData()
    {
        if($this->data[Entity::SCHEDULED] === true && isset($this->data[Entity::BEGIN]) && isset($this->data[Entity::END]))
        {
            $this->data[Entity::BEGIN] = Carbon::createFromTimestamp($this->data[Entity::BEGIN], Timezone::IST)
                ->format('d-M-Y h:i:sa');
            $this->data[Entity::END] = Carbon::createFromTimestamp($this->data[Entity::END], Timezone::IST)
                ->format('d-M-Y h:i:sa');
        }

        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        if ($this->status === self::CREATED)
        {
            $this->view('emails.downtime.create_downtime');
        }
        else if ($this->status === self::RESOLVED)
        {
            $this->view('emails.downtime.resolve_downtime');
        }

        return $this;
    }

    protected function addHeaders()
    {
        $mailTag = $this->getMailTag();

        $this->withSwiftMessage(function ($message) use ($mailTag)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $mailTag);
        });

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::DOWNTIME_NOTIFICATION;
    }
}
