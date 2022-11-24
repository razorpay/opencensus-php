<?php

namespace RZP\Mail\Downtime;

use Redis;
use Carbon\Carbon;
use Symfony\Component\Mime\Email;

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

    const DOWNTIME_SEVERITY_TEXT_MAP = [
        Severity::HIGH      => "high number of",
        Severity::MEDIUM    => "some",
        Severity::LOW       => "a few",
    ];

    const GRANULAR_DOWNTIME_SEVERITY_TEXT_MAP = [
        Severity::HIGH      => "High Severity Downtime",
        Severity::MEDIUM    => "Medium Severity Downtime",
        Severity::LOW       => "Low Severity Downtime",
    ];

    public function __construct(array $downtime, string $status, bool $merchantDowntimesEnabled, $email=[], $cc=null, $lastSeverity=null)
    {
        parent::__construct();

        $this->data = $downtime;

        $this->status = $status;

        $this->data['last_severity'] = $lastSeverity;

        // With this feature, merchant downtime communication has been enabled.
        // Granular fields such as card type and upi flow can now be sent for merchant downtimes.
        $this->data['merchant_downtimes_enabled'] = $merchantDowntimesEnabled;

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

        if(($this->data['merchant_downtimes_enabled'] === true)
            && (isset($downtime[Entity::TYPE]) === true))
        {
            $this->data['instrument'] = ucwords($downtime[Entity::TYPE] . " " . $downtime[Entity::METHOD]);
        }
        else
        {
            $this->data['instrument'] = ucwords($downtime[Entity::METHOD]);
        }

        $this->data['severity_text'] = $this->getSeverityText($this->data[Entity::SEVERITY], $merchantDowntimesEnabled);

        if(isset($downtime[Entity::MERCHANT_ID]) === true)
        {
            $this->data['type'] = 'merchant';
        }
        else
        {
            $this->data['type'] = 'platform';
        }

        $this->data['email'] = $email;

        $this->data['cc'] = $cc;
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

    protected function addCc()
    {
        if(isset($this->data['cc']) === true)
        {
            $this->cc($this->data['cc']);
        }

        return $this;
    }

    protected function addSubject()
    {
        $method = $this->data[Entity::METHOD];

        $merchantDowntimesEnabled = $this->data['merchant_downtimes_enabled'];

        $instrument = $this->data['instrument'];

        $subject = null;
        $internalSubject = null;
        $firstSeverity = null;

        $redisKey = 'downtime_' . $this->data['id'];

        $redis = Redis::connection();

        // For the first email saving severity in redis
        // for subsequent emails fetching the initial severity from redis

        if ($this->status === self::CREATED && $this->data['last_severity'] === null)
        {
            $redis->set($redisKey, $this->data[Entity::SEVERITY]);
        }
        else
        {
            $firstSeverity = $redis->get($redisKey);

            // Delete redis key after resolve
            if($this->status === self::RESOLVED)
            {
                $redis->del($redisKey);
            }
        }

        if ($this->status === self::RESOLVED)
        {
            if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
            {
                $internalSubject = 'Payments using ' . $instrument . ' are now back to normal';
            }
            else
            {
                $internalSubject = 'Payments using ' . $this->data['dimension'] . ' ' . $instrument . ' are now back to normal';
            }
        }
        elseif($this->data['last_severity'] === null)
        {
            $internalSubject = $this->getFirstSubject($instrument, $this->data['dimension'], $this->getSeverityText($this->data[Entity::SEVERITY], false), false);
        }
        else
        {
            if( $this->data[Entity::SEVERITY] === Severity::HIGH || ($this->data[Entity::SEVERITY] === Severity::MEDIUM && $this->data['last_severity'] === Severity::LOW))
            {
                if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
                {
                    $internalSubject = 'Increased number of declines observed now for payments using ' . $instrument;
                }
                else
                {
                    $internalSubject = 'Increased number of declines now by ' . $this->data['dimension'] . ' for payments using ' . $instrument;
                }
            }
            elseif ( $this->data[Entity::SEVERITY] === Severity::MEDIUM && $this->data['last_severity'] === Severity::HIGH)
            {
                if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
                {
                    $internalSubject = 'Lesser number of declines observed now for payments using ' . $instrument;
                }
                else
                {
                    $internalSubject = 'Lesser number of declines now by ' . $this->data['dimension'] . ' for payments using ' . $instrument;
                }

            }
            elseif ( $this->data[Entity::SEVERITY] === Severity::LOW)
            {
                if ( $this->data['dimension'] == null || $this->data['dimension'] == "All UPI instruments")
                {
                    $internalSubject = 'Very few declines observed now for payments using ' . $instrument;
                }
                else
                {
                    $internalSubject = 'Very few declines now by ' . $this->data['dimension'] . ' for payments using ' . $instrument;
                }
            }
        }

        $this->data['subject'] = $internalSubject;

        if ($firstSeverity === null)
        {
            $subject = $this->getFirstSubject($instrument, $this->data['dimension'], $this->data['severity_text'], $merchantDowntimesEnabled);
        }
        else
        {
            if ($this->status === self::RESOLVED)
            {
                $subject = '[Resolved] RE: ';

            }
            elseif ($this->data['last_severity'] !== null)
            {
                $subject = '[Updated] RE: ';
            }

            $severityText = $this->getSeverityText($firstSeverity, $merchantDowntimesEnabled);

            $subject .= $this->getFirstSubject($instrument, $this->data['dimension'], $severityText, $merchantDowntimesEnabled);
        }

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

        $this->withSymfonyMessage(function (Email $message) use ($mailTag)
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

    protected function getSeverityText(string $severity, bool $merchantDowntimesEnabled)
    {
        if($merchantDowntimesEnabled === true)
        {
            return self::GRANULAR_DOWNTIME_SEVERITY_TEXT_MAP[$severity];
        }

        return self::DOWNTIME_SEVERITY_TEXT_MAP[$severity];
    }

    protected function getFirstSubject($method, $dimension, $severityText, $newFormat)
    {
        $subject = ucwords($severityText);

        $dimensionString = "";

        if (!($dimension == null || $dimension == "All UPI instruments"))
        {
            $dimensionString = ucwords($dimension);
        }

        if($newFormat === true)
        {
            $subject .= ' | ';

            if(empty($dimensionString) === false)
            {
                $subject .= $dimensionString . ' | ';
            }
        }
        else
        {
            $subject .= ' declines observed for ' .  $dimensionString . ' payments attempted by ';
        }

        $subject .= ucwords($method);

        return $subject;
    }
}
