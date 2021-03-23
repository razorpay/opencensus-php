<?php

namespace RZP\Mail\Downtime;

use Redis;
use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Gateway\Downtime\Severity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Downtime\Entity;
use RZP\Models\Payment\Processor\Netbanking;

class DowntimeNotification extends Mailable
{
    protected $data;

    protected $status;

    const CREATED   = 'CREATED';

    const RESOLVED  = 'RESOLVED';

    const REFERENCE_ID_TAG = 'References';

    const IN_REPLY_TO = 'In-Reply-To';

    public function __construct(array $downtime, string $status, $email=[], $lastSeverity=null)
    {
        parent::__construct();

        $this->data = $downtime;

        $this->status = $status;

        $this->data['last_severity'] = $lastSeverity;

        $this->data['downtime'] = 'yes';

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

        switch ($this->data[Entity::SEVERITY])
        {
            case Severity::HIGH:
                $this->data['severity_text'] = "high number of";
                break;
            case Severity::MEDIUM:
                $this->data['severity_text'] = "some";
                break;
            case Severity::LOW:
                $this->data['severity_text'] = "a few";
                break;
        }

        if(isset($downtime[Entity::MERCHANT_ID]) === true)
        {
            $this->data['type'] = 'merchant';
        }
        else
        {
            $this->data['type'] = 'platform';
        }

        $this->data['email'] = $email;
    }

    protected function addRecipients()
    {
        $recipientEmail = null;

        if(isset($this->data[Entity::MERCHANT_ID]) === true)
        {
            $recipientEmail = $this->data['email'];
        }
        else
        {
            switch ($this->data['method'])
            {
                case Method::CARD :
                    $recipientEmail = Constants::MAIL_ADDRESSES[Constants::DOWNTIME_NOTIFICATION_CARD];
                    break;

                case Method::WALLET :
                    $recipientEmail = Constants::MAIL_ADDRESSES[Constants::DOWNTIME_NOTIFICATION_WALLET];
                    break;

                case Method::UPI :
                    $recipientEmail = Constants::MAIL_ADDRESSES[Constants::DOWNTIME_NOTIFICATION_UPI];
                    break;

                case Method::NETBANKING :
                    $recipientEmail = Constants::MAIL_ADDRESSES[Constants::DOWNTIME_NOTIFICATION_NETBANKING];
                    break;
            }
        }

        $this->to($recipientEmail);

        return $this;
    }

    protected function addSubject()
    {
        $method = $this->data[Entity::METHOD];

        if($method === Method::CARD)
        {
            $method = 'cards';
        }

        $subject = null;

        if ($this->status === self::RESOLVED)
        {
            if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
            {
                $subject = 'Payments using ' . $method . ' are now back to normal';
            }
            else
            {
                $subject = 'Payments using ' . $this->data['dimension'] . ' ' . $method . ' are now back to normal';
            }
        }
        elseif($this->data['last_severity'] === null)
        {
            if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
            {
                $subject = ucwords($this->data['severity_text']) . ' declines observed in customer payments attempted by '  . $method;
            }
            else
            {
                $subject = ucwords($this->data['severity_text']) . ' declines observed by ' .  $this->data['dimension'] . ' for payments attempted by ' . $method;
            }
        }
        else
        {
            if( $this->data[Entity::SEVERITY] === Severity::HIGH || ($this->data[Entity::SEVERITY] === Severity::MEDIUM && $this->data['last_severity'] === Severity::LOW))
            {
                if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
                {
                    $subject = 'Increased number of declines observed now for payments using ' . $method;
                }
                else
                {
                    $subject = 'Increased number of declines now by ' . $this->data['dimension'] . ' for payments using ' . $method;
                }
            }
            elseif ( $this->data[Entity::SEVERITY] === Severity::MEDIUM && $this->data['last_severity'] === Severity::HIGH)
            {
                if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
                {
                    $subject = 'Lesser number of declines observed now for payments using ' . $method;
                }
                else
                {
                    $subject = 'Lesser number of declines now by ' . $this->data['dimension'] . ' for payments using ' . $method;
                }

            }
            elseif ( $this->data[Entity::SEVERITY] === Severity::LOW)
            {
                if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
                {
                    $subject = 'Very few declines observed now for payments using ' . $method;
                }
                else
                {
                    $subject = 'Very few declines now by ' . $this->data['dimension'] . ' for payments using ' . $method;
                }
            }
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
            if ($this->data['last_severity'] !== null)
            {
                $this->view('emails.downtime.update_downtime');
            }
            else
            {
                $this->view('emails.downtime.create_downtime');
            }
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

            $redisKey = 'downtime_' . $this->data['id'];

            $redis = Redis::connection();

            $count = $redis->scard($redisKey);

            if ($count > 0)
            {
                $messageIds = $redis->smembers($redisKey);

                $references = '';

                foreach ($messageIds as $messageId)
                {
                    $references = $references . " " . $messageId;
                }

                $headers->addTextHeader(self::IN_REPLY_TO, $messageIds[$count-1]);

                $headers->addTextHeader(self::REFERENCE_ID_TAG, $references);
            }
        });

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::DOWNTIME_NOTIFICATION;
    }
}
