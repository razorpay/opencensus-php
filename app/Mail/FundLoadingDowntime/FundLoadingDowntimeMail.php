<?php

namespace RZP\Mail\FundLoadingDowntime;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants as Constants;
use RZP\Models\FundLoadingDowntime\Entity;
use RZP\Models\FundLoadingDowntime\Constants as Constant;

class FundLoadingDowntimeMail extends Mailable
{
    protected $orgId;

    public $merchantId;
    public $merchantEmail;
    public $emailParams;
    public $flowType;
    public $templateName;

    const NAMESPACE = 'razorpayx_payouts_core';

    // emojis used in email subject
    const whiteCheckMark = "\xE2\x9C\x85";     // slack equivalent : ✅ (:white_check_mark:)
    const rotatingLight  = "\xF0\x9F\x9A\xA8"; // slack equivalent : 🚨 (:rotating_light:)

    public function __construct($flowType , array $emailParams)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->orgId = $app['basicauth']->getAdmin()->getOrgId();

        $this->emailParams = $emailParams;

        $this->flowType = $flowType;

        $this->templateName = array_pull($this->emailParams, 'templateName');
    }

    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    public function setMerchantEmailId($emailId)
    {
        $this->merchantEmail = $emailId;
    }

    protected function addSubject()
    {
        $subject = null;
        $channel = $this->emailParams[Entity::CHANNEL];

        switch ($this->flowType)
        {
            case Constant::CREATION:
                $subject = self::rotatingLight . "[Downtime Alert] : RazorpayX Lite via {$channel} | " . $this->getDurationsInSubject();
                break;

            Case Constant::UPDATION:
                $subject = self::rotatingLight . "[Downtime Updated] : RazorpayX Lite via {$channel} | " . $this->getDurationsInSubject();
                break;

            case Constant::RESOLUTION:
                $subject = self::whiteCheckMark . "[Downtime Resolved] : RazorpayX Lite via {$channel}";
                break;

            case Constant::CANCELLATION:
                $subject = self::whiteCheckMark . "[Downtime Cancelled] : RazorpayX Lite via {$channel}";
                break;
        }

        return $this->subject($subject);
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
        $this->view($this->templateName);
        return $this;
    }

    protected function getParamsForStork(): array
    {
        return [
            'template_namespace' => self::NAMESPACE,
            'template_name'      => $this->templateName,
            'params'             => $this->emailParams,
            'org_id'             => $this->orgId,
            'owner_id'           => $this->merchantId,
        ];
    }

    /** Construct the subject based on the number of durations
     *  see https://razorpay.slack.com/archives/C01HA1ZDT4L/p1643713677010339
     */
    protected function getDurationsInSubject()
    {
        if(empty($this->emailParams[Constant::DURATIONS_AND_MODES]) === true)
        {
            // this means only one duration to be mentioned in the subject
            $duration = "{$this->emailParams[Entity::START_TIME]} {$this->emailParams[Entity::END_TIME]}";
        }
        else
        {
            // this means multiple durations to be mentioned, separated by '&'
            $durations = [];
            foreach ($this->emailParams[Constant::DURATIONS_AND_MODES] as $durationAndMode)
            {
                $durations[] = $durationAndMode[Entity::START_TIME] . ' ' . $durationAndMode[Entity::END_TIME];
            }
            $duration = implode(' & ', $durations);
        }

        return $duration;
    }
}
