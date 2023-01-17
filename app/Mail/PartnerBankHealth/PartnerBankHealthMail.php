<?php

namespace RZP\Mail\PartnerBankHealth;

use RZP\Mail\Base\Mailable;
use RZP\Exception\LogicException;
use RZP\Models\PartnerBankHealth\Status;
use RZP\Mail\Base\Constants as Constants;
use RZP\Models\Payout\Notifications\SmsConstants;
use RZP\Models\Merchant\MerchantNotificationConfig\NotificationType;

class PartnerBankHealthMail extends Mailable
{
    public $params;

    const DOWNTIME_SUBJECT = "We are facing an issue with processing %s payouts through %s";
    const UPTIME_SUBJECT   = "%s Payouts through %s is up and running!";

    const SMILING_FACE_WITH_TEAR = "\xF0\x9F\xA5\xB2"; //slack equivalent : 🥲

    const SOURCE = NotificationType::PARTNER_BANK_HEALTH;

    public function __construct($input)
    {
        parent::__construct();
        $this->params = $input;
    }

    public function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    public function addSubject()
    {
        switch ($this->params['status'])
        {
            case Status::DOWN:
                $subject = self::DOWNTIME_SUBJECT . ' ' . self::SMILING_FACE_WITH_TEAR;
                break;
            case Status::UP:
                $subject = self::UPTIME_SUBJECT;
                break;
            default:
                throw new LogicException("Not a valid status : " . $this->params['status']);
        }

        $subject = sprintf($subject, $this->params['mode'], $this->params['source']);

        return $this->subject($subject);
    }

    public function to($address, $name = null)
    {
        $this->to[] = ['address' => $address, 'name' => $name];
    }

    protected function addSender()
    {
        // for stage testing, use .in instead of .com in the email id
        $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $senderName  = Constants::HEADERS[Constants::NOREPLY];

        $this->from($senderEmail, $senderName);

        return $this;
    }

    public function addHtmlView()
    {
       return $this->view('emails.' . self::SOURCE . '.' . $this->params['status']);
    }

    public function getParamsForStork(): array
    {
        $emailParams = [
            'merchant_display_name' => $this->params['merchant_display_name'],
            'source'                => $this->params['source'],
            'mode'                  => $this->params['mode'],
            'support_email'         => Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
        ];

        $status = $this->params['status'];

        if ($status === Status::DOWN)
        {
            $emailParams['start_time'] = $this->params['start_time'];
        }
        elseif ($status === Status::UP)
        {
            $emailParams['end_time'] = $this->params['end_time'];
        }

        return [
            'template_namespace' => SmsConstants::PAYOUTS_CORE_TEMPLATE_NAMESPACE,
            'template_name'      => 'emails.' . NotificationType::PARTNER_BANK_HEALTH . '.' . $status,
            'params'             => $emailParams,
            'owner_id'           => $this->params['merchant_id'],
        ];
    }
}
