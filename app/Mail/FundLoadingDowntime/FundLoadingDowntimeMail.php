<?php

namespace RZP\Mail\FundLoadingDowntime;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants as Constants;
use RZP\Models\FundLoadingDowntime\Constants as Constant;

class FundLoadingDowntimeMail extends Mailable
{
    protected $merchantEmail;
    public $downtimeParams;
    public $flowType;

    const SOURCE    = 'fund_loading_downtime';
    const NAMESPACE = 'razorpayx_payouts_core';

    public function __construct($flowType , array $data)
    {
        parent::__construct();

        $this->merchantEmail = $data['email_id'];

        $this->downtimeParams = $data['params'];

        $this->flowType = $flowType;
    }

    protected function addSubject()
    {
        switch ($this->flowType)
        {
            case Constant::CREATION:

                $this->subject('Downtime communication for loading funds to RazorpayX virtual account');
                break;

            Default:

                $this->subject('Update on Downtime communication for loading funds to RazorpayX virtual account');
                break;
        }

        return $this;
    }

    protected function addSender()
    {
        // for stage testing, use .in instead of .com in the email id
        $senderEmail = Constants::MAIL_ADDRESSES[Constants::X_SUPPORT];
        $senderName  = Constants::HEADERS[Constants::X_SUPPORT];

        $this->from($senderEmail, $senderName);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->merchantEmail);

        return $this;
    }

    protected function shouldSendEmailViaStork(): bool
    {
        return true;
    }

    protected function addHtmlView()
    {
        $templateName = self::SOURCE . '.' . $this->flowType;
        $this->view($templateName);
        return $this;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => self::NAMESPACE,
            'template_name'      => self::SOURCE . '.' . $this->flowType,
            'params'             => $this->downtimeParams,
        ];
    }
}
